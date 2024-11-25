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
}
