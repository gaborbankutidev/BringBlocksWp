<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Layout;

class Layout {
	public static function init() {
		Model::init();
		Settings::init();
	}
}
