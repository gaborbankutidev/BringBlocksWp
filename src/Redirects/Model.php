<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Redirects;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

/**
 * Redirects model
 *
 * @package Bring\BlocksWP\Redirects
 * @since 2.0.1
 */
class Model {
	/**
	 * Register the post type and meta fields
	 * @return void
	 */
	public static function register() {
		add_action("init", self::post_type(...));
		add_action("init", self::post_meta(...));
	}

	/**
	 * Register the post type
	 * @return void
	 */
	private static function post_type() {
		register_post_type("redirect", [
			"labels" => [
				"name" => __("Redirects", "blocks-wp"),
				"singular_name" => __("Redirect", "blocks-wp"),
				"menu_name" => __("Redirects", "blocks-wp"),
				"name_admin_bar" => __("Redirect", "blocks-wp"),
				"all_items" => __("Redirects", "blocks-wp"),
				"add_new" => __("Add New Redirect", "blocks-wp"),
				"add_new_item" => __("Add New Redirect", "blocks-wp"),
				"edit_item" => __("Edit Redirect", "blocks-wp"),
				"new_item" => __("New Redirect", "blocks-wp"),
				"view_item" => __("View Redirect", "blocks-wp"),
				"search_items" => __("Search Redirects", "blocks-wp"),
				"not_found" => __("No Redirects found", "blocks-wp"),
				"not_found_in_trash" => __("No Redirects found in Trash", "blocks-wp"),
				"parent_item_colon" => __("Parent Redirect:", "blocks-wp"),
				"attributes" => __("Redirect Attributes", "blocks-wp"),
				"insert_into_item" => __("Insert into Redirect", "blocks-wp"),
				"uploaded_to_this_item" => __("Uploaded to this Redirect", "blocks-wp"),
				"update_item" => __("Update Redirect", "blocks-wp"),
				"view_items" => __("View Redirects", "blocks-wp"),
				"filter_items_list" => __("Filter Redirects list", "blocks-wp"),
				"items_list_navigation" => __("Redirects list navigation", "blocks-wp"),
				"items_list" => __("Redirects list", "blocks-wp"),
			],
			"description" => "Set up redirects",
			"public" => false,
			"publicly_queryable" => false,
			"show_ui" => true,
			"show_in_rest" => false,
			"has_archive" => false,
			"show_in_menu" => "tools.php",
			"show_in_nav_menus" => false,
			"delete_with_user" => false,
			"exclude_from_search" => true,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"can_export" => false,
			"query_var" => true,
			"supports" => ["title"],
			"show_in_graphql" => false,
			"show_in_admin_bar" => false,
		]);
	}

	/**
	 * Register the post meta fields
	 * @return void
	 */
	private static function post_meta() {
		register_post_meta("redirect", "from", [
			"type" => "string",
			"description" => "From URL",
			"single" => true,
			"show_in_rest" => true,
		]);

		register_post_meta("redirect", "to", [
			"type" => "string",
			"description" => "To URL",
			"single" => true,
			"show_in_rest" => true,
		]);

		register_post_meta("redirect", "status_code", [
			"type" => "string",
			"description" => "Status code",
			"single" => true,
			"show_in_rest" => true,
		]);

		register_post_meta("redirect", "hits", [
			"type" => "string",
			"description" => "Hits count",
			"single" => true,
			"show_in_rest" => true,
		]);
	}
}
