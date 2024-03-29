<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use Bring\BlocksWP\Config;

class Content {
	/**
	 * Returns content object for a post
	 * @param int $post_id
	 * @return mixed
	 */
	public static function getContentObject($post_id) {
		$post_type = get_post_type($post_id);
		$editor_post_types = Config::getEditorPostTypes();

		// return if post type is not supported
		if (!in_array($post_type, $editor_post_types)) {
			return [];
		}

		return get_post_meta($post_id, "bring_content_object", true) ?? [];
	}

	/**
	 * @param int $entity_id
	 * @return mixed
	 */
	public static function getMain($entity_id) {
		$post_type = get_post_type($entity_id);
		$editor_post_types = Config::getEditorPostTypes(true);

		// return if post type is not supported
		if (!in_array($post_type, $editor_post_types)) {
			return [];
		}

		return self::getContentObject($entity_id);
	}

	/**
	 * @param string $entity_slug
	 * @param string $entity_type TODO: should be swapped to an enum
	 * @return mixed
	 */
	public static function getLayout($entity_slug, $entity_type) {
		$layout_slug = "";

		if ($entity_type == "post") {
			$layout_post_types = Config::getLayoutPostTypes();
			if (!in_array($entity_slug, $layout_post_types)) {
				return null;
			}

			$layout_slug = "pt_$entity_slug";
		}

		if ($entity_type == "taxonomy") {
			$layout_taxonomies = Config::getLayoutTaxonomies();
			if (!in_array($entity_slug, $layout_taxonomies)) {
				return null;
			}

			$layout_slug = "tax_$entity_slug";
		}

		$entity_type == "author" && ($layout_slug = "author");
		$entity_type == "general" && ($layout_slug = $entity_slug);

		$default_layout_id = get_option("bring_default_{$layout_slug}_layout_id");
		if (!$default_layout_id || !is_int($default_layout_id)) {
			return null;
		}

		return self::getContentObject($default_layout_id);
	}

	/**
	 * @return mixed
	 */
	public static function getHeader() {
		$default_header_id = get_option("bring_default_header_id");
		// header_id not found
		if (!$default_header_id || !is_int($default_header_id)) {
			return null;
		}

		return self::getContentObject($default_header_id);
	}

	/**
	 * @return mixed
	 */
	public static function getFooter() {
		$default_footer_id = get_option("bring_default_footer_id");
		// footer_id not found
		if (!$default_footer_id || !is_int($default_footer_id)) {
			return null;
		}

		return self::getContentObject($default_footer_id);
	}

	/**
	 * @param int|null $entity_id
	 * @return string|null
	 */
	public static function getHead($entity_id) {
		if (!$entity_id) {
			return null;
		}

		$base_head = Config::getRankMath() ? self::getRankMathHead() : "";
		$head = apply_filters("bring_head", $base_head);
		return strval($head);
	}

	/**
	 * @return string
	 */
	public static function getRankMathHead() {
		global $wp;
		$rank_math_endpoint = home_url() . "/wp-json/rankmath/v1/getHead";
		$current_url = home_url() . "/" . $wp->request;
		$response = wp_remote_get($rank_math_endpoint . "?url=" . $current_url);
		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code !== 200 && $response_code !== "200") {
			return "";
		}

		$response_body = wp_remote_retrieve_body($response);

		/**
		 * @var array{success:bool,head:string}
		 */
		$parsed_body = json_decode($response_body);

		if (!$parsed_body["success"]) {
			return "";
		}

		return $parsed_body["head"];
	}
}
