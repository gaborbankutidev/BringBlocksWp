<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

// TODO refactor
class Enqueue {
	public static function init() {
		add_action("admin_enqueue_scripts", self::admin(...));
		add_action("enqueue_block_editor_assets", self::editor(...));
		add_action("wp_enqueue_scripts", self::client(...));
	}

	private static function admin($screen) {
		// FIXME: unused variable
		$theme_version = wp_get_theme()->get("Version");

		// cache
		wp_register_script(
			"bring-cache-scripts",
			get_template_directory_uri() . "/assets/cache.js",
			["jquery"],
			$theme_version,
			true,
		);
		wp_enqueue_script("bring-cache-scripts");
	}

	private static function editor() {
		$theme_version = wp_get_theme()->get("Version");

		// editor styles
		wp_enqueue_style(
			"editor-styles",
			get_template_directory_uri() . "/assets/editor.css",
			[],
			$theme_version,
			"all",
		);

		// check if build exists
		$assets_path = get_stylesheet_directory() . "/build/blocks.asset.php";
		if (!file_exists($assets_path)) {
			return;
		}
		$assets = require get_stylesheet_directory() . "/build/blocks.asset.php";

		// block styles
		wp_enqueue_style(
			"bring-blocks-styles",
			get_stylesheet_directory_uri() . "/build/tailwind.css",
			[],
			$theme_version,
			"all",
		);

		// block scripts
		wp_enqueue_script(
			"bring-blocks-scripts",
			get_stylesheet_directory_uri() . "/build/blocks.js",
			isset($assets["dependencies"]) ? $assets["dependencies"] : [],
			$theme_version,
		);

		// generate jwt
		wp_localize_script("bring-blocks-scripts", "jwt", [
			"token" => Utils::generate_token(),
		]);

		// set bring cache
		wp_localize_script("bring-blocks-scripts", "bringCache", Render::get_bring_cache());
	}

	private static function client() {
		$theme_version = wp_get_theme()->get("Version");

		// bring block styles
		wp_enqueue_style(
			"bring-styles",
			get_stylesheet_directory_uri() . "/build/tailwind.css",
			[],
			$theme_version,
			"all",
		);

		// component scripts
		wp_enqueue_script(
			"bring-components",
			get_stylesheet_directory_uri() . "/build/components.js",
			["react"],
			$theme_version,
			true,
		);

		wp_localize_script("bring-components", "bringCache", Render::get_bring_cache());
	}
}
