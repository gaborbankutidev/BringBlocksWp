<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Form;

/**
 * Register form related post types
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
		/*
		 * Post type: Bring form submissions
		 */
		register_post_type("bring_form_subm", [
			"labels" => [
				"name" => "Form submissions",
				"singular_name" => "Form submission",
			],
			"description" => "",
			"public" => false,
			"publicly_queryable" => false,
			"show_ui" => true,
			"show_in_rest" => false,
			"rest_base" => "",
			"rest_controller_class" => "WP_REST_Posts_Controller",
			"rest_namespace" => "wp/v2",
			"has_archive" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => false,
			"delete_with_user" => false,
			"exclude_from_search" => true,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"can_export" => false,
			"query_var" => true,
			"menu_icon" => "dashicons-feedback",
			"supports" => ["title"],
			"show_in_graphql" => false,
			"show_in_admin_bar" => true,
		]);

		register_post_meta("bring_form_subm", "form_data", [
			"type" => "string",
			"description" => "Raw data from submitted form",
			"single" => true,
			"show_in_rest" => false, // Show in the WP REST API response. Default: false.
		]);

		register_post_meta("bring_form_subm", "form_name", [
			"type" => "string",
			"description" => "Form name",
			"single" => true,
			"show_in_rest" => false,
		]);
	}
}
