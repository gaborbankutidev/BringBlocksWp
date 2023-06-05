<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use Bring\BlocksWP\Config;
use Bring\BlocksWP\Render\Content as RenderContent;
use Bring\BlocksWP\Render\Props;
use WP_REST_Request;

class Content {
	public static function post(WP_REST_Request $request) {
		// check entity type
		$entity_type = Utils::get_entity_type($request);
		if (!$entity_type) {
			return [
				"data" => null,
			];
		}

		// check entity id
		$entity_id = Utils::get_entity_id(); // FIXME
		if (!$entity_id) {
			return [
				"data" => null,
			];
		}

		// author
		if ($entity_type == "author") {
			// TODO author support
		}

		// taxonomy
		if ($entity_type === "taxonomy") {
			$term = get_term($entity_id);

			// return on failure
			if (!$term) {
				return [
					"data" => null,
				];
			}

			$taxonomy = $term->taxonomy;
			$layout_taxonomies = Config::get_layout_taxonomies();

			// return if not layout taxonomy
			if (!in_array($taxonomy, $layout_taxonomies)) {
				return [
					"data" => null,
				];
			}

			$content = [];
			$content = RenderContent::get_default_taxonomy_layout($content, $entity_id);
			$props = Props::get_taxonomy_entity_props($entity_id);

			return [
				"entityContent" => $content,
				"entityProps" => $props,
			];
		}

		// post
		if ($entity_type == "post") {
			$post_type = get_post_type($entity_id);

			// return on failure
			if (!$post_type) {
				return [
					"data" => null,
				];
			}

			$supported_post_types = Config::get_supported_post_types(true);
			$layout_post_types = Config::get_layout_post_types();

			// return if not block supported or layout post type
			if (
				!in_array(
					$post_type,
					array_merge($supported_post_types, $layout_post_types),
				)
			) {
				return [
					"data" => null,
				];
			}

			$content = [];
			$content = RenderContent::get_main($content, $entity_id);
			$content = RenderContent::get_default_post_layout($content, $entity_id);

			$props = Props::get_post_entity_props($entity_id);

			return [
				"entityContent" => $content,
				"entityProps" => $props,
			];
		}
	}
}
