<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

use Bring\BlocksWP\Config;

class Admin {
	/**
	 * @return void
	 */
	public static function init() {
		add_action("admin_menu", self::addPage(...));
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
		$urls = Urls::getCachedUrls();

		$list = "";
		foreach ($urls as $url => $date) {
			$cached_value = Head::getHead($url);

			$url = $url ? $url : "/";

			$value = $cached_value["response_code"];
			$value .= isset($cached_value["redirect_to"]) ? " -> " . $cached_value["redirect_to"] : "";

			$update_url =
				home_url() . "/" . $url . "?updateCache=1&data_token=" . Config::getEnv()["DATA_TOKEN"];

			$list .= "
				<tr>
					<td>{$url}</td>
					<td>{$value}</td>
					<td>{$date}</td>
					<td><a href='$update_url' target='_bank'>Update</a></td>
				</tr>
			";
		}

		echo "
			<div class='wrap'>
				<h1>Bring Cached URLs</h1>
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
