<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Editor;

use Bring\BlocksWP\Config;

class Revalidate {
	/**
	 * @return void
	 */
	public static function init() {
		$data_token = Config::getEnv()["DATA_TOKEN"];
		$next_url = Config::getEnv()["NEXT_URL"];

		add_action(
			"admin_bar_menu",
			function ($wp_admin_bar) use ($next_url, $data_token) {
				$args = [
					"id" => "bring_clear_cache",
					"title" => "Clear cache",
					"href" => "{$next_url}/api/clear-cache?token={$data_token}",
					"meta" => [
						"class" => "custom-button-class",
						"target" => "_blank",
					],
				];
				$wp_admin_bar->add_node($args);
			},
			999,
		);

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
