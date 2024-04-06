<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

class Urls {
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
