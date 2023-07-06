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
}
