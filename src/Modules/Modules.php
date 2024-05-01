<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Modules;

use Bring\BlocksWP\Config;

class Modules {
	/**
	 * @return void
	 */
	public static function init() {
		Config::getRankMath() && RankMath::init();
	}
}
