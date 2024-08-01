<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Editor;

use Bring\BlocksWP\Config;

class Editor {
	/**
	 * @return void
	 */
	public static function init() {
		Enqueue::init();
		Api::init();
		Blocks::init();

		// Add favicon
		add_action("admin_head", function () {
			$fav_url =
				get_template_directory_uri() . "/vendor/bring/blocks-wp/assets/bring-icon.png";
			echo "<link rel='shortcut icon' href='$fav_url' />\n";
		});

		// Add thumbnail support
		add_theme_support("post-thumbnails");

		// Turn off editor for non-editor posts
		add_action("init", self::nonEditorPosts(...));
	}

	/**
	 * @return void
	 */
	private static function nonEditorPosts() {
		if (!isset($_GET["post"])) {
			return;
		}

		$entity_id = sanitize_text_field($_GET["post"]);
		if (!is_numeric($entity_id)) {
			return;
		}
		$entity_id = intval($entity_id);

		// check if main page
		$main_page_id = get_option("page_on_front");
		$main_page_id = is_numeric($main_page_id) ? intval($main_page_id) : 0;

		if (Config::getNonEditorFront() && $main_page_id && $entity_id === $main_page_id) {
			remove_post_type_support("page", "editor");
			return;
		}

		// check if post is in non-editor list
		$post_type = get_post_type($entity_id);
		if (!$post_type) {
			return;
		}

		$slug = get_post_field("post_name", $entity_id);
		$non_editor_posts = Config::getNonEditorPosts();

		$non_editor_posts = array_key_exists($post_type, $non_editor_posts)
			? $non_editor_posts[$post_type]
			: [];

		if (in_array($slug, $non_editor_posts)) {
			remove_post_type_support($post_type, "editor");
			return;
		}
	}
}
