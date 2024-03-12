<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use WP_Error;
use WP_Term;

class Lists {
	/**
	 * @param string $entity_type TODO: should be swapped to an enum
	 * @param string $entity_slug
	 * @param int $limit
	 * @param array<mixed> $custom_data
	 * @return mixed
	 */
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
					"id" => null,
				],
				$custom_data,
			);

			/**
			 * @var int[]|WP_Error
			 */
			/* @phpstan-ignore-next-line */ // TODO: this will take serious work
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
					"id" => null,
				],
				$custom_data,
			);

			/**
			 * @var int[]
			 */
			/* @phpstan-ignore-next-line */ // TODO: this will take serious work
			$entity_ids = get_posts($args);
		}

		// return if empty
		if (!is_array($entity_ids)) {
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

			/* @phpstan-ignore-next-line */ // TODO: this will take serious work
			$entity_list[] = array_merge($item, $custom_item);
		}

		return Filter::apply(
			$entity_list,
			"list",
			[
				"slug" => $entity_slug,
				"type" => $entity_type,
				"id" => null,
			],
			$custom_data,
		);
	}
}
