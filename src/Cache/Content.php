<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

use Bring\BlocksWP\Config;
use Bring\BlocksWP\Utils;

class Content {
	/**
	 * @return void
	 */
	public static function init() {
		// Add bringContentHtml to the the window
		add_action("admin_enqueue_scripts", self::localize(...));
		add_action("enqueue_block_editor_assets", self::localize(...));

		// Reset cache on save posts
		add_action(
			"save_post",
			function ($post_id, $post) {
				// return if autosave
				if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
					return;
				}

				// return if not public
				$post_type_object = get_post_type_object($post->post_type);
				if (!$post_type_object || !$post_type_object->public) {
					return;
				}

				update_post_meta($post_id, "bring_content_html", "");
			},
			999,
			2,
		);

		// Reset cache on save terms
		add_action(
			"edited_term",
			function ($term_id, $tt_id, $taxonomy) {
				// return if not public
				$taxonomy_object = get_taxonomy($taxonomy);
				if (!$taxonomy_object || !$taxonomy_object->public) {
					return;
				}

				update_term_meta($term_id, "bring_content_html", "");
			},
			999,
			3,
		);
	}

	/**
	 * Returns the content html for an entity
	 *
	 * @param int $entity_id
	 * @param string $entity_type
	 * @return string|null
	 */
	public static function getContentHtml($entity_id, $entity_type = "post") {
		// get from meta
		$content_html = "";

		if ($entity_type === "post") {
			$content_html = get_post_meta($entity_id, "bring_content_html", true);
		} elseif ($entity_type === "taxonomy") {
			$content_html = get_term_meta($entity_id, "bring_content_html", true);
		} elseif ($entity_type === "author") {
			$content_html = get_user_meta($entity_id, "bring_content_html", true);
		} else {
			return $content_html;
		}

		if (is_string($content_html) && !empty($content_html)) {
			return $content_html;
		}

		// query if meta is empty
		$url =
			Config::getEnv()["NEXT_BASE_URL"] .
			Utils\General::getEntityUrl($entity_id, $entity_type);

		$response = wp_remote_get($url);
		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return null;
		}

		// Parse response body
		$response_body = wp_remote_retrieve_body($response);
		$dom = new \DOMDocument();
		@$dom->loadHTML($response_body, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		$xpath = new \DOMXPath($dom);

		$main = $xpath->query("//main");
		if (!$main || !$main->length) {
			return null;
		}

		$content_html = $dom->saveHTML($main->item(0));
		if (!$content_html) {
			return null;
		}

		// set meta
		if ($entity_type === "post") {
			update_post_meta($entity_id, "bring_content_html", $content_html);
		}
		if ($entity_type === "taxonomy") {
			update_term_meta($entity_id, "bring_content_html", $content_html);
		}
		if ($entity_type === "author") {
			update_user_meta($entity_id, "bring_content_html", $content_html);
		}

		return $content_html;
	}

	/**
	 * Localize script
	 *
	 * @return void
	 */
	private static function localize() {
		$post_id = isset($_GET["post"]) ? sanitize_text_field($_GET["post"]) : "";
		$terms_id = isset($_GET["post"]) ? sanitize_text_field($_GET["tag_ID"]) : "";

		$entity_id = 0;
		$entity_slug = "";
		$entity_type = "";
		if (is_numeric($post_id)) {
			$entity_id = intval($post_id);
			$entity_type = "post";

			// get post type
			$entity_slug = get_post_type($entity_id);
			if (!$entity_slug) {
				return;
			}

			// return if not public
			$post_type_object = get_post_type_object($entity_slug);
			if (!$post_type_object || !$post_type_object->public) {
				return;
			}
		} elseif (is_numeric($terms_id)) {
			$entity_id = intval($terms_id);
			$entity_type = "taxonomy";

			// get taxonomy
			$term = get_term($entity_id);
			if (!$term || is_wp_error($term)) {
				return;
			}
			$entity_slug = $term->taxonomy;

			// return if not public
			$taxonomy_object = get_taxonomy($entity_slug);
			if (!$taxonomy_object || !$taxonomy_object->public) {
				return;
			}
		} else {
			return;
		}

		if (!is_numeric($entity_id)) {
			return;
		}

		wp_localize_script("jquery", "bringContentHtml", [
			"value" => self::getContentHtml($entity_id, $entity_type),
		]);
	}
}
