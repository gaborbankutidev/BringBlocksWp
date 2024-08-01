<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

use Bring\BlocksWP\Config;

class Admin {
	/**
	 * @return void
	 */
	public static function init() {
		$data_token = Config::getEnv()["DATA_TOKEN"];
		$next_base_url = Config::getEnv()["NEXT_BASE_URL"];

		// Cached URLs filter
		add_filter("query_vars", self::addQueryVar(...));

		// Add Cached URLs page to admin
		add_action("admin_menu", self::addPage(...));

		// Add Clear next cache button to admin bar
		add_action(
			"admin_bar_menu",
			function ($wp_admin_bar) use ($next_base_url, $data_token) {
				$args = [
					"id" => "bring_clear_cache",
					"title" => "Clear cache",
					"href" => "{$next_base_url}/api/clear-cache?token={$data_token}",
					"meta" => [
						"class" => "custom-button-class",
						"target" => "_blank",
					],
				];
				$wp_admin_bar->add_node($args);
			},
			999,
		);
	}

	/**
	 * Adds query var to admin
	 *
	 * @param array<string> $vars
	 * @return array<string>
	 */
	private static function addQueryVar($vars) {
		if (is_admin()) {
			$vars[] = "response_code";
		}
		return $vars;
	}

	/**
	 * @return void
	 */
	private static function addPage() {
		add_submenu_page(
			"tools.php", // The slug name for the parent menu (or the file name of a standard WordPress admin page).
			"Cached URLs", // The text to be displayed in the title tags of the page when the menu is selected.
			"Cached URLs", // The text to be used for the menu.
			"manage_options", // The capability required for this menu to be displayed to the user.
			"bring-cached-urls", // The unique slug name to refer to this submenu.
			self::renderPage(...), // The function to display the page content.
		);
	}

	/**
	 * @return void
	 */
	private static function renderPage() {
		$cached_urls = Head::getCachedUrls();

		$urls = [
			"all" => [],
			"success" => [],
			"redirect" => [],
			"not_found" => [],
			"other" => [],
		];

		foreach ($cached_urls as $url => $date) {
			$cached_value = Head::getHead($url);
			$url = $url ? $url : "/";
			$response_code = $cached_value["response_code"];
			$redirect_to = isset($cached_value["redirect_to"])
				? " -> " . $cached_value["redirect_to"]
				: "";

			$value = [
				"url" => $url,
				"value" => $response_code . $redirect_to,
				"date" => $date,
			];

			$urls["all"][] = $value;

			switch ($response_code) {
				case 200:
					$urls["success"][] = $value;
					break;
				case 301:
				case 302:
				case 307:
				case 308:
					$urls["redirect"][] = $value;
					break;
				case 404:
					$urls["not_found"][] = $value;
					break;
				default:
					$urls["other"][] = $value;
					break;
			}
		}

		$filter_value =
			isset($_GET["type"]) &&
			in_array($_GET["type"], ["all", "success", "redirect", "not_found", "other"])
				? $_GET["type"]
				: "all";

		$filter = [];
		foreach ($urls as $type => $items) {
			$count = count($items);
			if ($count === 0) {
				continue;
			}
			$filter[] = [
				"label" => ucfirst(str_replace("_", " ", $type)),
				"count" => $count,
				"url" => "?page=bring-cached-urls&type=$type",
				"class" => $type === $filter_value ? "class='current'" : "",
			];
		}
		$filter = count($filter) > 2 ? $filter : [];

		$filter_html = "";
		foreach ($filter as $index => $value_type) {
			$separator = $index + 1 === count($filter) ? "" : " |";

			$filter_html .= "
				<li>
					<a href='{$value_type["url"]}' {$value_type["class"]}>
						{$value_type["label"]} <span class='count'>({$value_type["count"]})</span>
					</a>$separator
				</li>
			";
		}

		$list = "";
		foreach ($urls[$filter_value] as $item) {
			$update_url =
				home_url() .
				"/" .
				$item["url"] .
				"?updateCache=1&data_token=" .
				Config::getEnv()["DATA_TOKEN"];

			$list .= "
				<tr>
					<td>{$item["url"]}</td>
					<td>{$item["value"]}</td>
					<td>{$item["date"]}</td>
					<td><a href='$update_url' target='_bank'>Update</a></td>
				</tr>
			";
		}

		$count = count($urls[$filter_value]);

		echo "
			<div class='wrap'>
				<h1>Bring Cached URLs</h1>
				<div class='tablenav top' style='margin-bottom: 0;'>
					<ul class='subsubsub' style='margin-top: 0;'>
						$filter_html
					</ul>
					<div class='tablenav-pages one-page'><span class='displaying-num'>$count items</span>
				</div>
				</div>
				<table class='wp-list-table widefat fixed striped'>
					<thead>
						<tr>
							<th scope='col' class='manage-column column-id'>Urls</th>
							<th scope='col' class='manage-column column-name'>Value</th>
							<th scope='col' class='manage-column column-name'>Date</th>
							<th scope='col' class='manage-column column-name'>Update</th>
						</tr>
					</thead>
					$list
					<tfoot>
						<tr>
							<th scope='col' class='manage-column column-id'>Urls</th>
							<th scope='col' class='manage-column column-name'>Value</th>
							<th scope='col' class='manage-column column-name'>Date</th>
							<th scope='col' class='manage-column column-name'>Update</th>
						</tr>
					</tfoot>
				</table>
			</div>
		";
	}
}
