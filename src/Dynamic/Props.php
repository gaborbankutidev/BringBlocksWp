<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use Bring\BlocksWP\Utils as GlobalUtils;
use WP_REST_Request;

class Props {
	public static function post(WP_REST_Request $request) {
		// check entity type
		$entity_type = Utils::get_entity_type($request);
		if (!$entity_type) {
			return [
				"data" => null,
			];
		}

		// check entity id
		$entity_id = Utils::get_entity_id($request);
		if (!$entity_id) {
			return [
				"data" => null,
			];
		}

		// custom data
		$custom_data = Utils::get_custom_data($request);

		return [
			"data" => self::get($entity_type, $entity_id, $custom_data),
		];
	}

	public static function get($entity_type, $entity_id, $custom_data = []) {
		// author
		if ($entity_type == "author") {
			// TODO author support
		}

		// taxonomy
		if ($entity_type == "taxonomy") {
			$term = get_term($entity_id);
			if (!$term) {
				return null;
			}

			// props
			$term_props = [
				"name" => $term->name,
				"image" => GlobalUtils::get_entity_image(
					$term->term_id,
					"taxonomy",
					"large",
				),
				"excerpt" => get_term_meta($term->term_id, "excerpt", true) ?? "",
				"description" => $term->description ?? "",
				"slug" => $term->slug,
				"url" => get_term_link($term->term_id) ?? "#",
			];

			$term_props = apply_filters(
				"bring_dynamic_entity_props",
				$term_props,
				$term->term_id, // entity_id
				"taxonomy", // entity_type
				$term->taxonomy, // entity_slug
				$custom_data,
			);

			$term_props = apply_filters(
				"bring_dynamic_taxonomy_props",
				$term_props,
				$term->term_id, // entity_id
				$term->taxonomy, // entity_slug
				$custom_data,
			);

			$term_props = apply_filters(
				"bring_dynamic_taxonomy_props_$term->taxonomy",
				$term_props,
				$term->term_id, // entity_id
				$custom_data,
			);

			return $term_props;
		}

		// post
		if ($entity_type == "post") {
			$post = get_post($entity_id);
			if (!$post) {
				return null;
			}

			// props
			$post_props = [
				"name" => $post->post_title,
				"image" => GlobalUtils::get_entity_image(
					$post->ID,
					"post",
					"large",
				),
				"excerpt" => $post->post_excerpt ?? "",
				"description" => get_post_meta($post->ID, "description", true) ?? "",
				"slug" => $post->post_name,
				"url" => get_permalink($post->ID) ?? "#",
			];

			if ($post->post_excerpt) {
				$post_props["excerpt"] = $post->post_excerpt;
			}

			$image = GlobalUtils::get_entity_image(
				$post->ID,
				"post",
				"large",
			);
			if ($image["src"]) {
				$post_props["image"] = $image;
			}

			$post_props = apply_filters(
				"bring_dynamic_entity_props",
				$post_props,
				$post->ID, // entity_id
				"post", // entity_type
				$post->post_type, // entity_slug
				$custom_data,
			);

			$post_props = apply_filters(
				"bring_dynamic_post_props",
				$post_props,
				$post->ID, // entity_id
				$post->post_type, // entity_slug
				$custom_data,
			);

			$post_props = apply_filters(
				"bring_dynamic_post_props_$post->post_type",
				$post_props,
				$post->ID, // entity_id
				$custom_data,
			);

			return $post_props;
		}

		return null;
	}
}
