<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Modules;

use Bring\BlocksWP\Cache\Content;
use Bring\BlocksWP\Config;

class RankMath {
	/**
	 * @return void
	 */
	public static function init() {
		// Enqueue RankMath js
		add_action("admin_enqueue_scripts", self::enqueue(...));
		add_action("enqueue_block_editor_assets", self::enqueue(...));

		// Add content to RankMath recalculate score
		add_filter(
			"rank_math/recalculate_score/data",
			function ($values, $entity_id) {
				$values["content"] = Content::getContentHtml($entity_id);

				return $values;
			},
			99,
			2,
		);

		// Override base URL for RankMath rest api
		add_action("rest_api_init", self::overrideBaseUrl(...));
	}

	/**
	 * @return void
	 */
	private static function enqueue() {
		// Check if the rankmath script is enqueued
		if (!wp_script_is("rank-math-editor", "enqueued")) {
			return;
		}

		wp_enqueue_script(
			"bring-rankmath-scripts",
			plugin_dir_url(dirname(__FILE__)) . "../assets/rankmath.js",
			[],
			Config::getEnv()["BRING_APP_VERSION"],
		);
	}

	/**
	 * Override base URL for RankMath rest api
	 *
	 * @return void
	 */
	private static function overrideBaseUrl() {
		/**
		 * @var string $current_route
		 */
		$current_route = $_SERVER["REQUEST_URI"];
		if (strpos($current_route, "wp-json/rankmath/v1/getHead") === false) {
			return;
		}

		add_filter(
			"home_url",
			function ($url, $path) {
				// Define your custom home URL
				$custom_home_url = Config::getEnv()["NEXT_BASE_URL"];

				// Append the path if it exists
				if (!empty($path)) {
					$custom_home_url = rtrim($custom_home_url, "/") . "/" . ltrim($path, "/");
				}

				return $custom_home_url;
			},
			10,
			2,
		);

		add_filter("user_trailingslashit", function ($url) {
			return rtrim($url, "/");
		});
	}
}
