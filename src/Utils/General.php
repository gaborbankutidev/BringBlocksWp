<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Utils;

class General {
	/**
	 * Var dump variable into a string
	 */
	public static function varDumpToString(mixed $var) {
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}

	/**
	 * Returns image by attachment id in ImageType format
	 *
	 * @param int $image_id
	 * @param string $size thumbnail | medium | medium_large | large | full
	 *
	 * @return array [string src, string alt, int|null id]
	 */
	public static function getImage(int $image_id, $size = "thumbnail") {
		$img = wp_get_attachment_image_src($image_id, $size);

		if (!$img || !$img[0]) {
			return [
				"src" => "",
				"alt" => "",
				"id" => null,
			];
		}

		$image_src = $img[0];
		$image_alt = get_post_meta($image_id, "_wp_attachment_image_alt", true);

		return [
			"src" => $image_src,
			"alt" => $image_alt ? $image_alt : "",
			"id" => $image_id,
		];
	}

	/**
	 * Returns entity image by entity id & type in ImageType format
	 *
	 * @param int $entity_id
	 * @param string $entity_type post | taxonomy | author
	 * @param string $size thumbnail | medium | medium_large | large | full
	 *
	 * @return array [string src, string alt, int|null id]
	 */
	public static function getEntityImage(int $entity_id, $entity_type = "post", $size = "thumbnail") {
		$image_id = 0;

		// get meta data
		if ($entity_type == "author") {
			$image_id = get_user_meta($entity_id, "image", true);
		}
		if ($entity_type == "taxonomy") {
			$image_id = get_term_meta($entity_id, "image", true);
		}
		if ($entity_type == "post") {
			$image_id = get_post_thumbnail_id($entity_id);
		}

		if (!$image_id || !is_numeric($image_id)) {
			return [
				"src" => "",
				"alt" => "",
				"id" => null,
			];
		}

		return self::getImage(intval($image_id), $size);
	}

	/**
	 * Recursively get taxonomy and its children
	 */
	public static function getTaxonomyHierarchy(
		$taxonomy,
		callable|null $collect_data = null,
		int $parent = 0,
		array $args = [],
	) {
		$a = array_merge(
			[
				"taxonomy" => $taxonomy,
				"parent" => $parent,
				"hide_empty" => false,
				"fields" => "ids",
			],
			$args,
		);
		$term_ids = get_terms($a);

		$terms = [];

		if (!is_wp_error($term_ids) || !count($term_ids)) {
			return $terms;
		}

		/** @var int $term_id */
		foreach ($term_ids as $term_id) {
			$term = [
				"id" => $term_id,
				"children" => self::getTaxonomyHierarchy($taxonomy, $collect_data, $term_id, $args),
			];

			if (is_callable($collect_data)) {
				$term = array_merge($term, $collect_data($term_id));
			}

			$terms[] = $term;
		}

		return $terms;
	}

	/**
	 * Returns the list of wp menus with the menu objects
	 */
	public static function getMenus($with_items = true) {
		$menu_terms = get_terms("nav_menu");

		$menus = [];
		foreach ($menu_terms as $menu_term) {
			$menu = [
				"id" => $menu_term->term_id,
				"name" => strtolower($menu_term->name),
			];

			$menu = apply_filters("bring_menu", $menu, $menu_term->term_id);

			if ($with_items) {
				$menu["items"] = self::getMenuItems($menu_term->term_id);
			}

			$menus[] = $menu;
		}

		return $menus;
	}

	/**
	 * Returns the menu item object for the wp menu
	 */
	public static function getMenuItems($menu_id, $parent_item_id = 0) {
		$queried_menu_items = wp_get_nav_menu_items($menu_id);
		if (!$queried_menu_items) {
			return [];
		}

		$menu_items = [];
		foreach ($queried_menu_items as $menu_item) {
			if ($menu_item->menu_item_parent != $parent_item_id) {
				continue;
			}

			$item = [
				"id" => $menu_item->ID,
				"order" => $menu_item->menu_order,
				"url" => $menu_item->url,
				"name" => $menu_item->title,
				"description" => $menu_item->description,
				"target" => $menu_item->target,
				"classes" => implode(" ", $menu_item->classes),
			];

			$item = apply_filters("bring_menu_item", $item, $menu_item->ID);

			$children = self::getMenuItems($menu_id, $menu_item->ID);
			if ($children) {
				$item["children"] = $children;
			}

			$menu_items[] = $item;
		}

		return $menu_items;
	}
}
