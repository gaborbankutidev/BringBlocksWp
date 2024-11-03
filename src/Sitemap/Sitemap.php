<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Sitemap;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

class Sitemap {
	/**
	 * Initialize sitemap
	 *
	 * @return void
	 */
	public static function init() {
		Api::init();
	}
}
