<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

class Cache {
	/**
	 * @return void
	 */
	public static function init() {
		Admin::init();
	}
}
