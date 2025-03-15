<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Layout;

use Bring\BlocksWP\Config;

/**
 * Register layout related post types: header, footer, layout, library
 */
class Model {
	/**
	 * @return void
	 */
	public static function init() {
		add_action("init", self::register(...));
	}

	/**
	 * @return void
	 */
	private static function register() {
		$def_args = [
			"description" => "",
			"public" => false,
			"publicly_queryable" => false,
			"show_ui" => true,
			"show_in_rest" => true,
			"rest_base" => "",
			"rest_controller_class" => "WP_REST_Posts_Controller",
			"rest_namespace" => "wp/v2",
			"has_archive" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => false,
			"menu_position" => 51,
			"delete_with_user" => false,
			"exclude_from_search" => false,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"can_export" => false,
			"rewrite" => false,
			"query_var" => true,
			"menu_icon" => "",
			"supports" => ["title", "editor", "revisions"], // TODO add optional editor support
			"show_in_graphql" => false,
			"show_in_admin_bar" => true,
		];

		$layout_pts = [
			"header" => [
				"labels" => [
					"name" => "Headers",
					"singular_name" => "Header",
				],
				"menu_icon" => "dashicons-welcome-widgets-menus",
			],
			"footer" => [
				"labels" => [
					"name" => "Footers",
					"singular_name" => "Footer",
				],
				"menu_icon" => "dashicons-welcome-widgets-menus",
			],
			"layout" => [
				"labels" => [
					"name" => "Layouts",
					"singular_name" => "Layout",
				],
				"menu_icon" => "dashicons-layout",
			],
			"library" => [
				"labels" => [
					"name" => "Library",
					"singular_name" => "Library",
				],
				"menu_icon" => "dashicons-book",
			],
		];

		// register layout post types
		$layout = Config::getLayout();

		foreach ($layout_pts as $pt => $pt_args) {
			$layout[$pt] && register_post_type("bring_$pt", array_merge($def_args, $pt_args));
		}
	}
}
