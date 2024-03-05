<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Bring\BlocksWP\Layout\Layout;
use Bring\BlocksWP\Editor\Editor;
use Bring\BlocksWP\Client\Client;
use Bring\BlocksWP\Dynamic\Dynamic;
use Bring\BlocksWP\Form\Form;

class BringBlocks {
	public static function init() {
		Layout::init();
		Editor::init();
		Client::init();
		Dynamic::init();

		Config::getForms() && Form::init();
		Config::getThumbnailSupport() && add_theme_support("post-thumbnails");
	}
}
