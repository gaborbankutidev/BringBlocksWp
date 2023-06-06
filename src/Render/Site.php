<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Render;

class Site {
	/**
	 * Returns site props
	 */
	public static function get_site_props() {
		$default_site_props = [
			// "logo" => "todo",
			"url" => get_home_url(),
			"menus" => self::get_menus(),
		];

		return apply_filters("bring_site_props", $default_site_props);
	}

	/**
	 * Returns the list of wp menus with the menu objects
	 */
	private static function get_menus() {
		$menu_terms = get_terms("nav_menu");

		$menus = [];
		foreach ($menu_terms as $key => $menu_term) {
			// FIXME: unused variable
			$menus[] = [
				"id" => $menu_term->term_id,
				"name" => strtolower($menu_term->name),
				"items" => self::get_menu_items($menu_term->term_id),
			];
		}

		return $menus;
	}

	/**
	 * Returns the menu item object for the wp menu
	 */
	private static function get_menu_items($menu_id, $parent_item_id = "0") {
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
				"target" => $menu_item->target,
			];

			$children = self::get_menu_items($menu_id, $menu_item->ID);
			if ($children) {
				$item["children"] = $children;
			}

			$menu_items[] = $item;
		}

		return $menu_items;
	}
}
