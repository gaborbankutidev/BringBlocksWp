<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Editor;

use Bring\BlocksWP\Utils;
use Bring\BlocksWP\Config;

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
		// editor styles
		wp_enqueue_style(
			"bring-editor-styles",
			plugin_dir_url(dirname(__FILE__)) . "../assets/editor.css",
			[],
			Config::getEnv()["BRING_APP_VERSION"],
			"all",
		);
		// check if build exists
		$assets_path = Config::getEnv()["BRING_APP_PLUGIN_PATH"] . "build/blocks.asset.php";
		if (!file_exists($assets_path)) {
			return;
		}
		$assets = require Config::getEnv()["BRING_APP_PLUGIN_PATH"] . "build/blocks.asset.php";

		// block styles
		wp_enqueue_style(
			"bring-blocks-styles",
			Config::getEnv()["BRING_APP_PLUGIN_URL"] . "build/tailwind.css",
			[],
			Config::getEnv()["BRING_APP_VERSION"],
			"all",
		);

		// block scripts
		wp_enqueue_script(
			"bring-blocks-scripts",
			Config::getEnv()["BRING_APP_PLUGIN_URL"] . "build/blocks.js",
			isset($assets["dependencies"]) ? $assets["dependencies"] : [],
			Config::getEnv()["BRING_APP_VERSION"],
		);

		// generate jwt
		wp_localize_script("bring-blocks-scripts", "jwt", [
			"token" => Utils\Api::generateToken(),
		]);
	}
}
