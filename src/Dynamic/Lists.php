<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use WP_Error;

class Lists {
	/**
	 * @param string $entity_type TODO: should be swapped to an enum
	 * @param string $entity_slug
	 * @param array{limit: int, offset: int, custom_data: array<string,mixed>} $options
	 * @return mixed
	 */
	public static function getDynamicList($entity_type, $entity_slug, $options) {
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
				"numberposts" => $options["limit"],
				"offset" => $options["offset"],
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
				$options["custom_data"],
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
				"numberposts" => $options["limit"],
				"offset" => $options["offset"],
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
				$options["custom_data"],
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
				"entityId" => intval($entity_id),
				"entitySlug" => $entity_slug,
				"entityType" => $entity_type,
			];

			$custom_item = Filter::apply(
				[],
				"list_item",
				[
					"id" => $entity_id,
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);

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
			$options["custom_data"],
		);
	}
}
