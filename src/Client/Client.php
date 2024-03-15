<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

class Client {
	/**
	 * @return void
	 */
	public static function init() {
		Render::init();

		// Add menu
		add_action("init", function () {
			register_nav_menus();
		});
	}
}
