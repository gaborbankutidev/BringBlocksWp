<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

use Bring\BlocksWP\Config;

class Next {
	/**
	 * @return void
	 */
	public static function init() {
		$data_token = Config::getEnv()["DATA_TOKEN"];
		$next_url = Config::getEnv()["NEXT_URL"];

		// Clear cache on save posts
		add_action(
			"save_post",
			function () use ($next_url, $data_token) {
				if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
					return;
				}
				wp_remote_get("{$next_url}/api/clear-cache?token={$data_token}");
			},
			999,
		);

		// Clear cache on save terms
		add_action(
			"edited_term",
			function () use ($next_url, $data_token) {
				wp_remote_get("{$next_url}/api/clear-cache?token={$data_token}");
			},
			999,
		);
	}
}
