<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

class Lists {
	public static function getDynamicList($entity_type, $entity_slug, $limit = -1, $custom_data = []) {
		$entity_ids = [];

		// author
		if ($entity_type == "author") {
			// TODO author support
		}

		// taxonomy
		if ($entity_type == "taxonomy") {
			// query terms
			$args = [
				"taxonomy" => $entity_slug,
				"numberposts" => $limit,
				"fields" => "ids",
			];

			$args = Filter::apply(
				$args,
				"list_query_args",
				[
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$custom_data,
			);

			$entity_ids = get_terms($args);
		}

		// post
		if ($entity_type == "post") {
			// query posts
			$args = [
				"post_type" => $entity_slug,
				"numberposts" => $limit,
				"fields" => "ids",
			];

			$args = Filter::apply(
				$args,
				"list_query_args",
				[
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$custom_data,
			);

			$entity_ids = get_posts($args);
		}

		// return if empty
		if (!$entity_ids) {
			return [];
		}

		// props
		$entity_list = [];
		foreach ($entity_ids as $entity_id) {
			$item = [
				"id" => intval($entity_id),
			];

			$custom_item = Filter::apply(
				[],
				"list_item",
				[
					"id" => $entity_id,
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$custom_data,
			);

			$entity_list[] = array_merge($item, $custom_item);
		}

		return Filter::apply(
			$entity_list,
			"list",
			[
				"slug" => $entity_slug,
				"type" => $entity_type,
			],
			$custom_data,
		);

		return null;
	}
}
