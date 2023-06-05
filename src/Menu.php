<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

class Menu {
	public static function init() {
		add_action("init", self::register(...));
	}

	/**
	 * Register nav menu locations
	 */
	private static function register() {
		register_nav_menus([
			"primary" => "Primary menu",
			"mobile" => "Mobile menu",
			"footer" => "Footer menu",
		]);
	}
}
