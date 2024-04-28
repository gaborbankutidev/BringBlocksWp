<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use WP_Error;

class Lists {
	/**
	 * @param string $entity_type TODO: should be swapped to an enum
	 * @param string $entity_slug
	 * @param array{limit: int, offset: int, page: int, custom_data: array<string,mixed>} $options
	 * @return array{entityList: array<string, mixed>, params: array<string, mixed>}
	 */
	public static function getDynamicList($entity_type, $entity_slug, $options) {
		$entity_ids = [];
		$all_entity_ids = [];
		$params = ["count" => 0];

		$limit = $options["limit"];
		$offset = $options["offset"] + ($options["page"] - 1) * $options["limit"];

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
				"offset" => $offset,
				"fields" => "ids",
			];

			$args = Filter::list(
				$args,
				"list_query_args",
				[
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);

			/**
			 * @var int[]|WP_Error
			 */
			/* @phpstan-ignore-next-line */ // TODO: this will take serious work
			$entity_ids = get_terms($args);

			// get terms for count for params
			$args = [
				"taxonomy" => $entity_slug,
				"fields" => "ids",
			];

			$args = Filter::list(
				$args,
				"list_query_args",
				[
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);

			/**
			 * @var int[]|WP_Error
			 */
			/* @phpstan-ignore-next-line */ // TODO: this will take serious work
			$all_entity_ids = get_terms($args);
		}

		// post
		if ($entity_type == "post") {
			// query posts
			$args = [
				"post_type" => $entity_slug,
				"numberposts" => $limit,
				"offset" => $offset,
				"fields" => "ids",
			];

			$args = Filter::list(
				$args,
				"list_query_args",
				[
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);

			/**
			 * @var int[]
			 */
			/* @phpstan-ignore-next-line */ // TODO: this will take serious work
			$entity_ids = get_posts($args);

			// get posts for count for params
			$args = [
				"post_type" => $entity_slug,
				"fields" => "ids",
			];

			$args = Filter::list(
				$args,
				"list_query_args",
				[
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);

			/**
			 * @var int[]
			 */
			/* @phpstan-ignore-next-line */ // TODO: this will take serious work
			$all_entity_ids = get_posts($args);
		}

		// set params count
		$params["count"] = is_array($all_entity_ids) ? count($all_entity_ids) : 0;

		// return if empty
		if (!is_array($entity_ids)) {
			return ["entityList" => [], "params" => $params];
		}

		// props
		$entity_list = [];
		foreach ($entity_ids as $entity_id) {
			$item = Props::getDynamicProps($entity_type, intval($entity_id), $options);

			// skip if item is null
			if (!$item) {
				continue;
			}

			$item = $item["entityProps"];

			$entity_list[] = Filter::props(
				$item,
				"list_item",
				[
					"id" => $entity_id,
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);
		}

		$entity_list = Filter::list(
			$entity_list,
			"list",
			[
				"slug" => $entity_slug,
				"type" => $entity_type,
			],
			$options["custom_data"],
		);

		// params
		$params = Filter::list(
			$params,
			"list_params",
			[
				"slug" => $entity_slug,
				"type" => $entity_type,
			],
			$options["custom_data"],
		);

		return ["entityList" => $entity_list, "params" => $params];
	}
}
