<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

class Filter {
	/**
	 * WP filter triplet for rendering dynamic props
	 * @param array<string, mixed> $item
	 * @param string $type TODO: should be swapped to an enum (props | list_item | props_params)
	 * @param array{id: int|null, slug: string, type: string} $entity TODO: type key should be swapped to an enum  (post | taxonomy | author)
	 * @param array<string, mixed> $custom_data
	 * @return array<string, mixed>
	 */
	public static function props($item, $type, $entity, $custom_data = []) {
		$entity_id = $entity["id"];
		$entity_slug = $entity["slug"];
		$entity_type = $entity["type"];

		$item = apply_filters(
			"bring_dynamic_entity_{$type}",
			$item,
			$entity_id,
			$entity_type,
			$entity_slug,
			$custom_data,
		);

		$item = apply_filters(
			"bring_dynamic_{$entity_type}_{$type}",
			$item,
			$entity_id,
			$entity_slug,
			$custom_data,
		);

		$item = apply_filters(
			"bring_dynamic_{$entity_type}_{$type}_{$entity_slug}",
			$item,
			$entity_id,
			$custom_data,
		);

		return $item;
	}

	/**
	 * WP filter triplet for rendering dynamic props and list
	 * @param array<mixed> $items
	 * @param string $type TODO: should be swapped to an enum (list_query_args | list | list_params)
	 * @param array{slug: string, type: string} $entity TODO: type key should be swapped to an enum  (post | taxonomy | author)
	 * @param array<string, mixed> $custom_data
	 * @return array<string, mixed>
	 */
	public static function list($items, $type, $entity, $custom_data = []) {
		$entity_slug = $entity["slug"];
		$entity_type = $entity["type"];

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

		return $items;
	}
}
