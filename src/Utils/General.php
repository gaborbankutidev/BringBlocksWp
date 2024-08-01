<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Utils;

use WP_Error;
use WP_Term;

class General {
	/**
	 * Var dump variable into a string
	 * @return string|false
	 */
	public static function varDumpToString(mixed $var) {
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}

	/**
	 * Returns the relative url of the entity
	 *
	 * @param int $entity_id
	 * @param "post"|"taxonomy"|"author" $entity_type
	 * @return string
	 */
	public static function getEntityUrl($entity_id, $entity_type = "post") {
		$url = null;

		$entity_type === "post" && ($url = get_permalink($entity_id));
		$entity_type === "taxonomy" && ($url = get_term_link($entity_id));
		$entity_type === "author" && ($url = get_author_posts_url($entity_id));

		if (empty($url) || is_wp_error($url)) {
			return "";
		}

		return str_replace(home_url(), "", $url);
	}

	/**
	 * Returns image by attachment id in ImageType format
	 *
	 * @param int $image_id
	 * @param string $size TODO: should be swapped to an enum ( thumbnail | medium | medium_large | large | full )
	 *
	 * @return array{src:string,alt:string,id:int|null}
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

		/**
		 * @var mixed
		 */
		$image_src = $img[0];
		$image_alt = get_post_meta($image_id, "_wp_attachment_image_alt", true);

		return [
			"src" => is_string($image_src) ? $image_src : "",
			"alt" => is_string($image_alt) ? $image_alt : "",
			"id" => $image_id,
		];
	}

	/**
	 * Returns entity image by entity id & type in ImageType format
	 *
	 * @param int $entity_id
	 * @param string $entity_type TODO: should be swapped to an enum ( post | taxonomy | author )
	 * @param string $size TODO: should be swapped to an enum ( thumbnail | medium | medium_large | large | full )
	 *
	 * @return array{src:string,alt:string,id:int|null}
	 */
	public static function getEntityImage(
		int $entity_id,
		$entity_type = "post",
		$size = "thumbnail",
	) {
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
	 * @param string $taxonomy
	 * @param callable|null $collect_data
	 * @param int $parent
	 * @param array<mixed> $args
	 * @return array<mixed>
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
		/* @phpstan-ignore-next-line */ // TODO: this will take serious work
		$term_ids = get_terms($a);

		$terms = [];

		if ($term_ids instanceof WP_Error) {
			return $terms;
		}

		/** @var int[] $term_ids */
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
	 * Returns the list of wp menu location with the menu id
	 *
	 * @return array<array{key: string, menuId: int}>
	 */
	public static function getMenuLocations() {
		/**
		 * @var array<string,int> $menu_locations
		 */
		$menu_locations = get_nav_menu_locations();

		$locations = [];
		foreach ($menu_locations as $location => $menu_id) {
			$locations[] = [
				"key" => $location,
				"menuId" => $menu_id,
			];
		}

		return $locations;
	}

	/**
	 * Returns the list of wp menus with the menu objects
	 * @param bool $with_items
	 * @return array<mixed>
	 */
	public static function getMenus($with_items = true) {
		/* @phpstan-ignore-next-line */ // This is invalid based on the type files
		$menu_terms = get_terms("nav_menu");

		if (!is_array($menu_terms)) {
			return [];
		}

		$menus = [];
		foreach ($menu_terms as $menu_term) {
			if (!$menu_term instanceof WP_Term) {
				continue;
			}
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
	 * @param int $menu_id
	 * @param int $parent_item_id
	 * @return array<mixed>
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
				"url" => str_replace(home_url(), "", $menu_item->url),
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
