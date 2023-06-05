<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

/**
 * Register layout related post types: header, footer, layout, library
 */
class Layout {
	public static function init() {
		add_action("init", self::register(...));
	}

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
			"supports" => ["title", "editor"],
			"show_in_graphql" => false,
			"show_in_admin_bar" => true,
		];

		$layout_pts = [
			"bring_header" => [
				"labels" => [
					"name" => "Headers",
					"singular_name" => "Header",
				],
				"menu_icon" => "dashicons-welcome-widgets-menus",
			],
			"bring_footer" => [
				"labels" => [
					"name" => "Footers",
					"singular_name" => "Footer",
				],
				"menu_icon" => "dashicons-welcome-widgets-menus",
			],
			"bring_layout" => [
				"labels" => [
					"name" => "Layouts",
					"singular_name" => "Layout",
				],
				"menu_icon" => "dashicons-layout",
			],
			"bring_library" => [
				"labels" => [
					"name" => "Library",
					"singular_name" => "Library",
				],
				"menu_icon" => "dashicons-book",
			],
		];

		// register layuot post types
		foreach ($layout_pts as $pt => $pt_args) {
			register_post_type($pt, array_merge($def_args, $pt_args));
		}
	}
}
