<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

class Head {
	/**
	 * Returns the head for an URL
	 *
	 * @param string $url
	 * @return array{response_code: int, redirect_to?: string}
	 */
	public static function getHead($url) {
		/**
		 * @var array{response_code: int, redirect_to?: string} | false | null
		 */
		$cached_head = get_transient("bring_cache_$url");
		if ($cached_head) {
			return $cached_head;
		}

		// Fetch head
		$fetched_head = self::fetchHead($url);

		// Update transient & cached urls
		$updated = set_transient("bring_cache_$url", $fetched_head, 7 * DAY_IN_SECONDS);
		$updated && self::updateCachedUrl($url);

		return $fetched_head;
	}

	/**
	 * Returns the head for an URL
	 *
	 * @param string $url
	 * @return array{response_code: int, redirect_to?: string}
	 */
	public static function updateHead($url) {
		delete_transient("bring_cache_$url");
		return self::getHead($url);
	}

	/**
	 * Fetches the head of an URL
	 *
	 * @param string $url
	 * @return array{response_code: int, redirect_to?: string}
	 */
	private static function fetchHead($url) {
		// check head in normal render
		$bypass_url = home_url() . "/" . $url . "?bypass=1";
		$response = wp_remote_head($bypass_url);
		$response_code = intval(wp_remote_retrieve_response_code($response));

		// Handle redirect
		if (
			$response_code === 301 ||
			$response_code === 302 ||
			$response_code === 307 ||
			$response_code === 308
		) {
			$header_redirect_location = wp_remote_retrieve_header($response, "Location");
			/**
			 * @var string $redirect_location
			 */
			$redirect_location = str_replace(
				"?bypass=1",
				"",
				str_replace(home_url(), "", $header_redirect_location),
			);

			return [
				"response_code" => $response_code,
				"redirect_to" => $redirect_location,
			];
		}

		return [
			"response_code" => $response_code,
		];
	}

	/**
	 * Adds a URL to the cached urls option with the current date and time
	 *
	 * @param string $url
	 * @return void
	 */
	public static function updateCachedUrl($url) {
		/**
		 * @var array<string, string> $cached_urls
		 */
		$cached_urls = get_option("bring_cache_urls", []);

		$cached_urls[$url] = date("Y-m-d H:i:s");
		update_option("bring_cache_urls", $cached_urls);
	}

	/**
	 * Adds a URL to the cached urls option with the current date and time
	 *
	 * @return array<string, string>
	 */
	public static function getCachedUrls() {
		/**
		 * @var array<string, string> $cached_urls
		 */
		$cached_urls = get_option("bring_cache_urls", []);

		return $cached_urls;
	}
}
