<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

class Filter {
	/**
	 * TODO: REFACTOR THIS TO PROPER INPUTS WITH DISCRIMINATIVE UNIONS
	 * WP filter triplet for rendering dynamic props and list
	 * @param array<mixed> $items
	 * @param string $type TODO: should be swapped to an enum (props | list_query_args | list_item | list)
	 * @param array{id: int|null, slug: string, type: string} $entity TODO: type key should be swapped to an enum  (post | taxonomy | author)
	 * @param array<mixed> $custom_data
	 * @return mixed
	 */
	public static function apply($items, $type, $entity, $custom_data = []) {
		$with_id = in_array($type, ["props", "list_item"]);
		$entity_id = $with_id ? $entity["id"] : null;
		$entity_slug = $entity["slug"];
		$entity_type = $entity["type"];

		if ($with_id) {
			$items = apply_filters(
				"bring_dynamic_entity_{$type}",
				$items,
				$entity_id,
				$entity_type,
				$entity_slug,
				$custom_data,
			);

			$items = apply_filters(
				"bring_dynamic_{$entity_type}_{$type}",
				$items,
				$entity_id,
				$entity_slug,
				$custom_data,
			);

			$items = apply_filters(
				"bring_dynamic_{$entity_type}_{$type}_{$entity_slug}",
				$items,
				$entity_id,
				$custom_data,
			);
		} else {
			$items = apply_filters(
				"bring_dynamic_entity_{$type}",
				$items,
				$entity_type,
				$entity_slug,
				$custom_data,
			);

			$items = apply_filters(
				"bring_dynamic_{$entity_type}_{$type}",
				$items,
				$entity_slug,
				$custom_data,
			);

			$items = apply_filters(
				"bring_dynamic_{$entity_type}_{$type}_{$entity_slug}",
				$items,
				$custom_data,
			);
		}

		return $items;
	}
}
