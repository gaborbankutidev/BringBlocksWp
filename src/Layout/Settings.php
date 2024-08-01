<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Layout;

use Bring\BlocksWP\Config;

// TODO: refactor this class
class Settings {
	private const LAYOUT_GENERALS = ["author", "date", "search", "not_found"];
	/**
	 * @var array{header:bool,footer:bool,layout:bool,library:bool}
	 */
	private static $layout_config;

	/**
	 * @return void
	 */
	public static function init() {
		self::$layout_config = Config::getLayout();

		add_action("admin_init", self::setupSettingsPageFields(...));
		add_action("admin_menu", self::createSettingsPage(...));
	}

	/**
	 * Create settings page for bring layout
	 * @return void
	 */
	private static function createSettingsPage() {
		if (
			!self::$layout_config["header"] &&
			!self::$layout_config["footer"] &&
			!self::$layout_config["layout"]
		) {
			return;
		}

		add_options_page(
			"Bring layout settings",
			"Bring layout",
			"manage_options",
			"bring-layout",
			self::settingsPageContent(...),
		);
	}

	/**
	 * Render settings page content
	 * @return void
	 */
	private static function settingsPageContent() {
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
	 * @return void
	 */
	private static function setupSettingsPageFields() {
		// General section
		add_settings_section(
			"bring_general_layout_defaults",
			"General layout defaults",
			fn() => null,
			"bring_layout",
		);

		// Register header setting and field if header is enabled
		if (self::$layout_config["header"]) {
			register_setting("bring_layout_options", "bring_default_header_id");
			add_settings_field(
				"bring_default_header_id",
				"Header",
				self::defaultHeaderIdSelect(...),
				"bring_layout",
				"bring_general_layout_defaults",
			);
		}

		// Register footer setting and field if header is enabled
		if (self::$layout_config["footer"]) {
			register_setting("bring_layout_options", "bring_default_footer_id");
			add_settings_field(
				"bring_default_footer_id",
				"Footer",
				self::defaultFooterIdSelect(...),
				"bring_layout",
				"bring_general_layout_defaults",
			);
		}

		// selects for general layouts
		if (self::$layout_config["layout"]) {
			foreach (self::LAYOUT_GENERALS as $general) {
				register_setting(
					"bring_layout_options",
					"bring_default_" . "$general" . "_layout_id",
				);
				add_settings_field(
					"bring_default_" . $general . "_layout_id",
					ucfirst("$general layout"),
					fn() => self::defaultPostLayoutIdSelect($general, true),
					"bring_layout",
					"bring_general_layout_defaults",
				);
			}
		}

		// Post type section
		$layout_post_types = Config::getLayoutPostTypes();
		if (self::$layout_config["layout"] && $layout_post_types) {
			add_settings_section(
				"bring_post_type_layout_defaults",
				"Post type layout defaults",
				fn() => null,
				"bring_layout",
			);

			foreach ($layout_post_types as $post_type) {
				register_setting(
					"bring_layout_options",
					"bring_default_pt_" . "$post_type" . "_layout_id",
				);
				add_settings_field(
					"bring_default_pt_" . $post_type . "_layout_id",
					ucfirst("$post_type layout"),
					fn() => self::defaultPostLayoutIdSelect("pt_$post_type"),
					"bring_layout",
					"bring_post_type_layout_defaults",
				);
			}
		}

		// Taxonomy section
		$layout_taxonomies = Config::getLayoutTaxonomies();
		if (self::$layout_config["layout"] && $layout_taxonomies) {
			add_settings_section(
				"bring_taxonomy_layout_defaults",
				"Taxonomy layout defaults",
				fn() => null,
				"bring_layout",
			);

			foreach ($layout_taxonomies as $taxonomy) {
				register_setting(
					"bring_layout_options",
					"bring_default_tax_" . "$taxonomy" . "_layout_id",
				);
				add_settings_field(
					"bring_default_tax_" . $taxonomy . "_layout_id",
					ucfirst("$taxonomy layout"),
					fn() => self::defaultPostLayoutIdSelect(
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
	 * @return void
	 */
	private static function defaultHeaderIdSelect() {
		$bring_default_header_id = get_option("bring_default_header_id");

		$args = [
			"post_type" => "bring_header",
			"post_status" => "publish",
			"numberposts" => -1,
			"fields" => "ids",
		];
		$bring_header_ids = get_posts($args);

		$output_html = self::buildSelectForLayout(
			$bring_header_ids,
			$bring_default_header_id,
			"bring_default_header_id",
			true,
		);

		echo $output_html;
	}

	/**
	 * Renders footer select
	 * @return void
	 */
	private static function defaultFooterIdSelect() {
		$bring_default_footer_id = get_option("bring_default_footer_id");

		$args = [
			"post_type" => "bring_footer",
			"post_status" => "publish",
			"numberposts" => -1,
			"fields" => "ids",
		];
		$bring_footer_ids = get_posts($args);

		$output_html = self::buildSelectForLayout(
			$bring_footer_ids,
			$bring_default_footer_id,
			"bring_default_footer_id",
			true,
		);

		echo $output_html;
	}

	/**
	 * Renders post layout select
	 * @param string $selector
	 * @param bool $with_none
	 * @return void
	 */
	private static function defaultPostLayoutIdSelect($selector, $with_none = false) {
		$bring_default_layout_id = get_option("bring_default_" . $selector . "_layout_id");

		$args = [
			"post_type" => "bring_layout",
			"post_status" => "publish",
			"numberposts" => -1,
			"fields" => "ids",
		];
		$bring_layout_ids = get_posts($args);

		$output_html = self::buildSelectForLayout(
			$bring_layout_ids,
			$bring_default_layout_id,
			"bring_default_" . $selector . "_layout_id",
			$with_none,
		);

		echo $output_html;
	}

	/**
	 * Generates the select field for the given in layout id-s
	 * @param array<int> $bring_layout_ids
	 * @param mixed $bring_default_layout_id
	 * @param mixed $option_id
	 * @param bool $with_none
	 * @return string
	 */
	private static function buildSelectForLayout(
		$bring_layout_ids,
		$bring_default_layout_id,
		$option_id,
		$with_none = false,
	) {
		if ($with_none && !$bring_layout_ids) {
			return "<a href='/wp-admin/edit.php?post_type=bring_layout'>Create a layout!</a>";
		}

		$options = "";
		foreach ($bring_layout_ids as $bring_layout_id) {
			$selected = $bring_default_layout_id == $bring_layout_id ? "selected" : "";
			$title = get_the_title($bring_layout_id);
			$options .= "<option value='$bring_layout_id' $selected>$title</option>";
		}

		$none_option = "";
		if ($with_none) {
			$selected = $bring_default_layout_id ? "selected" : "";
			$none_option = "<option value='0' $selected>None</option>";
		}

		// return nothing if $option_id is not a string
		if (!(is_string($option_id) || is_int($option_id))) {
			return "";
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
