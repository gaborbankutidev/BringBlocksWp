<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

class Settings {
	public static function init() {
		//BringSettings::init();
		LayoutSettings::init();
	}
}

/**
 * Settings for bring api key and caching options
 */
class BringSettings {
	public static function init() {
		add_action("admin_init", self::setup_settings_page_fields(...));
		add_action("admin_menu", self::create_settings_page(...));
	}

	/**
	 * Create settings page for bring layout
	 */
	private static function create_settings_page() {
		add_options_page(
			"Bring Theme settings",
			"Bring theme",
			"manage_options",
			"bring-theme",
			self::settings_page_content(...),
		);
	}

	/**
	 * Render settings page content
	 */
	private static function settings_page_content() {
		ob_start();
		settings_fields("bring_theme_options");
		do_settings_sections("bring_theme");
		submit_button();
		$output_content = ob_get_clean();

		$output_html = "
			<div class='wrap'>
				<h2>Bring Theme settings</h2>
				<form method='post' action='options.php'>
					$output_content
				</form>
			</div>
		";

		echo $output_html;
	}

	/**
	 * Register sections and fields
	 */
	private static function setup_settings_page_fields() {
		add_settings_section("bring_site_defaults", "Site defaults", null, "bring_theme");

		register_setting("bring_theme_options", "bring_cache_apikey");
		add_settings_field(
			"bring_cache_apikey",
			"Api key",
			self::render_apikey_field(...),
			"bring_theme",
			"bring_site_defaults",
		);
	}

	private static function render_apikey_field($arguments) {
		var_dump($arguments);
	}
}

class LayoutSettings {
	private const LAYOUT_GENERALS = ["author", "date", "search", "not_found"];

	public static function init() {
		add_action("admin_init", self::setup_settings_page_fields(...));
		add_action("admin_menu", self::create_settings_page(...));
	}

	/**
	 * Create settings page for bring layout
	 */
	private static function create_settings_page() {
		add_options_page(
			"Bring layout settings",
			"Bring layout",
			"manage_options",
			"bring-layout",
			self::settings_page_content(...),
		);
	}

	/**
	 * Render settings page content
	 */
	private static function settings_page_content() {
		ob_start();
		settings_fields("bring_layout_options");
		do_settings_sections("bring_layout");
		submit_button();
		$output_content = ob_get_clean();

		$output_html = "
			<div class='wrap'>
				<h2>Bring Layout settings</h2>
				<form method='post' action='options.php'>
					$output_content
				</form>
			</div>
		";

		echo $output_html;
	}

	/**
	 * Register sections and fields
	 */
	private static function setup_settings_page_fields() {
		// General section
		add_settings_section(
			"bring_general_layout_defaults",
			"General layout defaults",
			null,
			"bring_layout",
		);

		// header
		register_setting("bring_layout_options", "bring_default_header_id");
		add_settings_field(
			"bring_default_header_id",
			"Header",
			self::default_header_id_select(...),
			"bring_layout",
			"bring_general_layout_defaults",
		);

		// footer
		register_setting("bring_layout_options", "bring_default_footer_id");
		add_settings_field(
			"bring_default_footer_id",
			"Footer",
			self::default_footer_id_select(...),
			"bring_layout",
			"bring_general_layout_defaults",
		);

		// selects for general layouts
		foreach (self::LAYOUT_GENERALS as $general) {
			register_setting("bring_layout_options", "bring_default_" . "$general" . "_layout_id");
			add_settings_field(
				"bring_default_" . $general . "_layout_id",
				ucfirst("$general layout"),
				fn($a) => self::default_post_layout_id_select($a, $general, true),
				"bring_layout",
				"bring_general_layout_defaults",
			);
		}

		// Post type section
		$layout_post_types = Config::get_layout_post_types();
		if ($layout_post_types) {
			add_settings_section(
				"bring_post_type_layout_defaults",
				"Post type layout defaults",
				null,
				"bring_layout",
			);

			foreach ($layout_post_types as $post_type) {
				register_setting("bring_layout_options", "bring_default_pt_" . "$post_type" . "_layout_id");
				add_settings_field(
					"bring_default_pt_" . $post_type . "_layout_id",
					ucfirst("$post_type layout"),
					fn($a) => self::default_post_layout_id_select($a, "pt_$post_type"),
					"bring_layout",
					"bring_post_type_layout_defaults",
				);
			}
		}

		// Taxonomy section
		$layout_taxonomies = Config::get_layout_taxonomies();
		if ($layout_taxonomies) {
			add_settings_section(
				"bring_taxonomy_layout_defaults",
				"Taxonomy layout defaults",
				null,
				"bring_layout",
			);

			foreach ($layout_taxonomies as $taxonomy) {
				register_setting("bring_layout_options", "bring_default_tax_" . "$taxonomy" . "_layout_id");
				add_settings_field(
					"bring_default_tax_" . $taxonomy . "_layout_id",
					ucfirst("$taxonomy layout"),
					fn($a) => self::default_post_layout_id_select(
						$a,
						"tax_$taxonomy",
						in_array($taxonomy, ["category", "post_tag"]),
					),
					"bring_layout",
					"bring_taxonomy_layout_defaults",
				);
			}
		}
	}

	/**
	 * Renders headers select
	 */
	private static function default_header_id_select($arguments) {
		// FIXME: unused variable
		$bring_default_header_id = get_option("bring_default_header_id");

		$args = [
			"post_type" => "bring_header",
			"post_status" => "publish",
			"numberposts" => -1,
			"fields" => "ids",
		];
		$bring_header_ids = get_posts($args);

		$output_html = self::build_select_for_layout(
			$bring_header_ids,
			$bring_default_header_id,
			"bring_default_header_id",
			true,
		);

		echo $output_html;
	}

	/**
	 * Renders footer select
	 */
	private static function default_footer_id_select($arguments) {
		// FIXME: unused variable
		$bring_default_footer_id = get_option("bring_default_footer_id");

		$args = [
			"post_type" => "bring_footer",
			"post_status" => "publish",
			"numberposts" => -1,
			"fields" => "ids",
		];
		$bring_footer_ids = get_posts($args);

		$output_html = self::build_select_for_layout(
			$bring_footer_ids,
			$bring_default_footer_id,
			"bring_default_footer_id",
			true,
		);

		echo $output_html;
	}

	/**
	 * Renders post layout select
	 */
	private static function default_post_layout_id_select(
		$arguments, // FIXME: unused variable
		$selector,
		$with_none = false,
	) {
		$bring_default_layout_id = get_option("bring_default_" . $selector . "_layout_id");

		$args = [
			"post_type" => "bring_layout",
			"post_status" => "publish",
			"numberposts" => -1,
			"fields" => "ids",
		];
		$bring_layout_ids = get_posts($args);

		$output_html = self::build_select_for_layout(
			$bring_layout_ids,
			$bring_default_layout_id,
			"bring_default_" . $selector . "_layout_id",
			$with_none,
		);

		echo $output_html;
	}

	/**
	 * Generates the select field for the given in layout id-s
	 */
	private static function build_select_for_layout(
		$bring_layout_ids,
		$bring_default_layout_id,
		$option_id,
		$with_none = false,
	) {
		if ($with_none && !$bring_layout_ids) {
			return "<a href='/wp-admin/edit.php?post_type=bring_layout'>Create a layout!</a>";
		}

		$options = "";
		foreach ($bring_layout_ids as $key => $bring_layout_id) {
			// FIXME: unused variable
			$selected = $bring_default_layout_id == $bring_layout_id ? "selected" : "";
			$title = get_the_title($bring_layout_id);
			$options .= "<option value='$bring_layout_id' $selected>$title</option>";
		}

		$none_option = "";
		if ($with_none) {
			$selected = $bring_default_layout_id ? "selected" : "";
			$none_option = "<option value='0' $selected>None</option>";
		}

		$output_html = "
			<select name='$option_id' id='$option_id'>
				$none_option
				$options
			</select>
    	";

		return $output_html;
	}
}
