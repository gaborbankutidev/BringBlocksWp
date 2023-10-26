<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Editor;

class Editor {
	public static function init() {
		Enqueue::init();
		Api::init();
		Blocks::init();

		// Add favicon
		add_action("admin_head", function () {
			$fav_url = get_template_directory_uri() . "/vendor/bring/blocks-wp/assets/bring-icon.png";
			echo "<link rel='shortcut icon' href='$fav_url' />\n";
		});
	}
}
