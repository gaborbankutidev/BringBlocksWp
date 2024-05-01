<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Editor;

use Bring\BlocksWP\Utils;

// TODO refactor
class Enqueue {
	/**
	 * @return void
	 */
	public static function init() {
		add_action("enqueue_block_editor_assets", self::editor(...));
	}

	/**
	 * @return void
	 */
	private static function editor() {
		$theme_version = wp_get_theme()->get("Version");

		// editor styles
		wp_enqueue_style(
			"bring-editor-styles",
			get_template_directory_uri() . "/vendor/bring/blocks-wp/assets/editor.css",
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
			"token" => Utils\Api::generateToken(),
		]);
	}
}
