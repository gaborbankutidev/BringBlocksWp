<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use WP_REST_Request;

class Lists {
	public static function post(WP_REST_Request $request) {
		// check entity type
		$entity_type = Utils::get_entity_type($request);
		if (!$entity_type) {
			return [
				"data" => null,
			];
		}

		// get entity slug
		$entity_slug = Utils::get_entity_slug($request);
		if (!$entity_slug) {
			return [
				"data" => null,
			];
		}

		// limit
		$limit = Utils::get_limit($request);

		// custom data
		$custom_data = Utils::get_custom_data($request);

		return [
			"data" => self::get($entity_type, $entity_slug, $limit, $custom_data),
		];
	}

	public static function get(
		$entity_type,
		$entity_slug,
		$limit = -1,
		$custom_data = [],
	) {
		// author
		if ($entity_type == "author") {
			// TODO author support
		}

		// taxonomy
		if ($entity_type == "taxonomy") {
			// check if taxonomy exists
			if (!taxonomy_exists($entity_slug)) {
				return null;
			}
			$taxonomy = $entity_slug;

			// query terms
			$args = [
				"taxonomy" => $taxonomy,
				"numberposts" => $limit,
				"fields" => "ids",
			];

			$args = apply_filters(
				"bring_dynamic_entity_list_query_args",
				$args,
				"taxonomy", // entity_type
				$taxonomy, // entity_slug
				$custom_data,
			);

			$args = apply_filters(
				"bring_dynamic_taxonomy_list_query_args",
				$args,
				$taxonomy, // entity_slug
				$custom_data,
			);

			$args = apply_filters(
				"bring_dynamic_taxonomy_list_query_args_$taxonomy",
				$args,
				$custom_data,
			);

			$term_ids = get_terms($args);

			// return if empty
			if (!$term_ids) {
				return [];
			}

			// props
			$term_list = [];
			foreach ($term_ids as $term_id) {
				$term = [
					"id" => intval($term_id),
				];

				$custom_dynamic_list_item = apply_filters(
					"bring_dynamic_entity_list_item",
					[],
					$term_id, // entity_id
					"taxonomy", // entity_type
					$taxonomy, // entity_slug
					$custom_data,
				);

				$custom_dynamic_list_item = apply_filters(
					"bring_dynamic_taxonomy_list_item",
					$custom_dynamic_list_item,
					$term_id, // entity_id
					$taxonomy, // entity_slug
					$custom_data,
				);

				$custom_dynamic_list_item = apply_filters(
					"bring_dynamic_taxonomy_list_item_$taxonomy",
					$custom_dynamic_list_item,
					$term_id, // entity_id
					$custom_data,
				);

				$term_list[] = array_merge($term, $custom_dynamic_list_item);
			}

			$term_list = apply_filters(
				"bring_dynamic_entity_list",
				$term_list,
				"taxonomy", // entity_type
				$taxonomy, // entity_slug
				$custom_data,
			);

			$term_list = apply_filters(
				"bring_dynamic_taxonomy_list",
				$term_list,
				$taxonomy, // entity_slug
				$custom_data,
			);

			$term_list = apply_filters(
				"bring_dynamic_taxonomy_list_$taxonomy",
				$term_list,
				$custom_data,
			);

			return $term_list;
		}

		// post
		if ($entity_type == "post") {
			// check if post types exists
			if (!post_type_exists($entity_slug)) {
				return null;
			}
			$post_type = $entity_slug;

			// query posts
			$args = [
				"post_type" => $post_type,
				"numberposts" => $limit,
				"fields" => "ids",
			];

			$args = apply_filters(
				"bring_dynamic_entity_list_query_args",
				$args,
				"post", // entity_type
				$post_type, // entity_slug
				$custom_data,
			);

			$args = apply_filters(
				"bring_dynamic_post_list_query_args",
				$args,
				$post_type, // entity_slug
				$custom_data,
			);

			$args = apply_filters(
				"bring_dynamic_post_list_query_args_$post_type",
				$args,
				$custom_data,
			);

			$post_ids = get_posts($args);

			// return if empty
			if (!$post_ids) {
				return [];
			}

			// build content
			$post_list = [];
			foreach ($post_ids as $post_id) {
				$post = [
					"id" => intval($post_id),
				];

				$custom_dynamic_list_item = apply_filters(
					"bring_dynamic_entity_list_item",
					[],
					$post_id, // entity_id
					"post", // entity_type
					$post_type, // entity_slug
					$custom_data,
				);

				$custom_dynamic_list_item = apply_filters(
					"bring_dynamic_post_list_item",
					$custom_dynamic_list_item,
					$post_id, // entity_id
					$post_type, // entity_slug
					$custom_data,
				);

				$custom_dynamic_list_item = apply_filters(
					"bring_dynamic_post_list_item_$post_type",
					$custom_dynamic_list_item,
					$post_id, // entity_id
					$custom_data,
				);

				$post_list[] = array_merge($post, $custom_dynamic_list_item);
			}

			$custom_dynamic_list = apply_filters(
				"bring_dynamic_entity_list",
				$post_list,
				"post", // entity_type
				$post_type, // entity_slug
				$custom_data,
			);

			$custom_dynamic_list = apply_filters(
				"bring_dynamic_post_list",
				$custom_dynamic_list,
				$post_type, // entity_slug
				$custom_data,
			);

			$custom_dynamic_list = apply_filters(
				"bring_dynamic_post_list_$post_type",
				$custom_dynamic_list,
				$custom_data,
			);

			return $custom_dynamic_list;
		}

		return null;
	}
}
