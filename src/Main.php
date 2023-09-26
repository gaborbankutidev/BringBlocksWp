<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

class Main {
	public static function init(Options|null $options = null) {
		self::redirects();

		$options = $options ? $options : Options::c_();

		// init bring forms
		$options->useForms && Form::init();

		// init cache
		$options->apikey && Cache::init($options->apikey, $options->developmentMode);

		// init bring layout model
		Layout::init();

		// init bring layout settings page
		Settings::init();

		// init bring save
		Save::init();

		// init bring render
		Render::init();

		// init bring menu
		Menu::init();

		// init bring dynamic querying
		Dynamic::init();

		// enqueue bring scripts & styles
		Enqueue::init();

		// use content_html for excerpt if empty
		add_filter("get_the_excerpt", self::updateExcerpt(...), 10, 2);
	}

	private static function redirects() {
		// no redirect in admin
		if (is_admin()) {
			return;
		}

		// post_type archive UNSUPPORTED
		if (is_post_type_archive()) {
			wp_redirect("/");
			exit();
		}

		// TODO refactor other redirects here
	}

	private static function updateExcerpt($original_excerpt, $post) {
		$excerpt = get_post_field("post_excerpt", $post->ID);

		// If excerpt is set, return it.
		if (!empty($excerpt)) {
			return $excerpt;
		}

		// get cached content and return original excerpt if not found
		$encoded_content = get_post_meta($post->ID, "bring_content_html", true);

		if (empty($encoded_content)) {
			return $original_excerpt;
		}

		// decode and process content
		$decoded_content = base64_decode($encoded_content);
		if ($decoded_content !== false) {
			$preprocessed_content = str_replace("><", "> <", $decoded_content);
			$striped_content = wp_strip_all_tags($preprocessed_content);
			return wp_trim_words($striped_content, 45, "...");
		}

		return $original_excerpt;
	}
}
