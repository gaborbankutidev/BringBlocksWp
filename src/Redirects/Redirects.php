<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Redirects;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

/**
 * Redirects
 *
 * @package Bring\BlocksWP\Redirects
 * @since 2.0.1
 */
class Redirects {
	/**
	 *
	 * @return void
	 */
	public static function init() {
		Model::register();
		Admin::init();
	}
}
