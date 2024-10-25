<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

use Bring\BlocksWP\Config;

class Cache {
	/**
	 * @return void
	 */
	public static function init() {
		Admin::init();
		Config::getCacheContentHtml() && Content::init();
		Config::getCacheContentHtml() && Api::init();
	}
}
