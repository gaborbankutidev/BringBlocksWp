<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use WP_REST_Request;

class Options {
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
		// TODO refactor that entity slug is optional

		// author
		if ($entity_type == "author") {
			// TODO author support
			return [
				"data" => null,
			];
		}

		// taxonomy
		if ($entity_type == "taxonomy") {
			// check if taxonomy exists
			if (!taxonomy_exists($entity_slug)) {
				return [
					"data" => null,
				];
			}
			$taxonomy = $entity_slug;

			// query terms
			$args = [
				"taxonomy" => $taxonomy,
				"numberposts" => -1,
			];
			$terms = get_terms($args);

			// return if empty
			if (!$terms) {
				return [
					"data" => [],
				];
			}

			// build content
			$term_options = [];
			foreach ($terms as $term) {
				$term_options[] = [intval($term->term_id), $term->name];
			}

			return [
				"data" => $term_options,
			];
		}

		// post
		if ($entity_type == "post") {
			// check if post types exists
			if (!post_type_exists($entity_slug)) {
				return [
					"data" => null,
				];
			}
			$post_type = $entity_slug;

			// query posts
			$args = [
				"post_type" => $post_type,
				"numberposts" => -1,
				"fields" => "ids",
			];
			$post_ids = get_posts($args);

			// return if empty
			if (!$post_ids) {
				return [
					"data" => [],
				];
			}

			// build content
			$post_options = [];
			foreach ($post_ids as $post_id) {
				$post_options[] = [intval($post_id), get_the_title($post_id)];
			}

			return [
				"data" => $post_options,
			];
		}

		return [
			"data" => null,
		];
	}
}
