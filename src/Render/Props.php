<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Render;

use Bring\BlocksWP\Utils;
use Bring\BlocksWP\Config;

class Props {
	/**
	 * Returns entity props for queried entity
	 */
	public static function get_entity_props() {
		// general layout
		$general_layout = Utils::is_general_layout();
		if ($general_layout && $general_layout != "author") {
			return apply_filters("bring_general_" . $general_layout . "_props", []);
		}

		// author
		if ($general_layout == "author") {
			// TODO author support
			// return get_author_entity_props();
		}

		// taxonomy
		if (is_tax() || is_tag() || is_category()) {
			return self::get_taxonomy_entity_props();
		}

		// post
		if (is_singular()) {
			return self::get_post_entity_props();
		}

		return self::filter_entity_props([]);
	}

	public static function get_author_entity_props($entity_id = 0) {
		if (!$entity_id) {
			$entity_id = get_queried_object_id();
		}

		$entity_props = [];
		$user_meta = get_user_meta($entity_id);
		$first_name = $user_meta->first_name;
		$last_name = $user_meta->last_name;

		$entity_props["name"] = "$first_name $last_name";
		$entity_props["image"] = Utils::get_entity_image(
			$entity_id,
			"author",
			"full",
		);

		$excerpt = get_user_meta($entity_id, "excerpt", true);
		if ($excerpt) {
			$entity_props["excerpt"] = $excerpt;
		}

		$entity_props["description"] = $user_meta->description;
		$entity_props["url"] = get_author_posts_url($entity_id);

		$entity_props = apply_filters(
			"bring_entity_props",
			$entity_props,
			$entity_id,
			"author",
			$entity_slug, // FIXME
		);

		$entity_props = apply_filters(
			"bring_author_props",
			$entity_props,
			$entity_id,
		);

		$entity_props["entityType"] = "author";
		$entity_props["entityId"] = $entity_id;

		return self::filter_entity_props($entity_props);
	}

	public static function get_taxonomy_entity_props($entity_id = 0) {
		if (!$entity_id) {
			$entity_id = get_queried_object_id();
		}

		$entity_props = [];
		$term = get_term($entity_id);
		$entity_slug = $term->taxonomy;

		$entity_props["name"] = $term->name;
		$entity_props["image"] = Utils::get_entity_image(
			$entity_id,
			"taxonomy",
			"full",
		);

		$excerpt = get_term_meta($entity_id, "excerpt", true);
		if ($excerpt) {
			$entity_props["excerpt"] = $excerpt;
		}

		if ($term->description) {
			$entity_props["description"] = $term->description;
		}

		$entity_props["slug"] = $term->slug;
		$entity_props["url"] = get_term_link($term);

		$entity_props = apply_filters(
			"bring_entity_props",
			$entity_props,
			$entity_id,
			"taxonomy",
			$entity_slug,
		);

		$entity_props = apply_filters(
			"bring_taxonomy_props",
			$entity_props,
			$entity_id,
			$entity_slug,
		);

		$entity_props = apply_filters(
			"bring_taxonomy_props_$entity_slug",
			$entity_props,
			$entity_id,
		);

		$entity_props["entityType"] = "taxonomy";
		$entity_props["entitySlug"] = $entity_slug;
		$entity_props["entityId"] = $entity_id;

		return self::filter_entity_props($entity_props);
	}

	public static function get_post_entity_props($entity_id = 0) {
		if (!$entity_id) {
			$entity_id = get_queried_object_id();
		}

		$entity_props = [];
		$post = get_post($entity_id);
		$entity_slug = $post->post_type;

		$entity_props["name"] = $post->post_title;
		$entity_props["image"] = Utils::get_entity_image(
			$entity_id,
			"post",
			"full",
		);

		if ($post->post_excerpt) {
			$entity_props["excerpt"] = $post->post_excerpt;
		}

		$description = get_post_meta($entity_id, "description", true);
		if ($description) {
			$entity_props["description"] = $description;
		}

		$entity_props["slug"] = $post->post_name;
		$entity_props["url"] = get_permalink($post);

		$entity_props = apply_filters(
			"bring_entity_props",
			$entity_props,
			$entity_id,
			"post",
			$entity_slug,
		);

		$entity_props = apply_filters(
			"bring_post_props",
			$entity_props,
			$entity_id,
			$entity_slug,
		);

		$entity_props = apply_filters(
			"bring_post_props_$entity_slug",
			$entity_props,
			$entity_id,
		);

		$entity_props["entityType"] = "post";
		$entity_props["entitySlug"] = $entity_slug;
		$entity_props["entityId"] = $entity_id;

		return self::filter_entity_props($entity_props);
	}

	// remove unsupported entity props and set empty values to null
	private static function filter_entity_props($entity_props) {
		$supported_entity_props = Config::get_entity_props();
		$filtered_entity_props = [];
		foreach ($supported_entity_props as $prop) {
			if (!isset($entity_props[$prop])) {
				$filtered_entity_props[$prop] = null;
			} else {
				$filtered_entity_props[$prop] = $entity_props[$prop];
			}
		}

		return $filtered_entity_props;
	}
}
