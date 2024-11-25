<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Bring\BlocksWP\Auth\Auth;
use Bring\BlocksWP\Layout\Layout;
use Bring\BlocksWP\Editor\Editor;
use Bring\BlocksWP\Cache\Cache;
use Bring\BlocksWP\Client\Client;
use Bring\BlocksWP\Dynamic\Dynamic;
use Bring\BlocksWP\Exceptions\ConfigNotInitializedException;
use Bring\BlocksWP\Form\Form;
use Bring\BlocksWP\Modules\Modules;
use Bring\BlocksWP\Redirects\Redirects;
use Bring\BlocksWP\Sitemap\Sitemap;

class BringBlocks {
	/**
	 * @return void
	 */
	public static function init() {
		self::checkConfig();

		Auth::init();
		Layout::init();
		Editor::init();
		Client::init();
		Dynamic::init();
		Cache::init();
		Redirects::init();

		Config::getSitemap() && Sitemap::init();
		Config::getForms() && Form::init();
		Modules::init();
	}

	/**
	 * @return void
	 */
	private static function checkConfig() {
		if (!Config::getIsInitialized()) {
			throw new ConfigNotInitializedException();
		}
	}
}
