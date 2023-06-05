<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Bring\BlocksWP\Dynamic\Cache;
use Bring\BlocksWP\Dynamic\Content;
use Bring\BlocksWP\Dynamic\Lists;
use Bring\BlocksWP\Dynamic\Options;
use Bring\BlocksWP\Dynamic\Props;

class Dynamic {
	public static function init() {
		Cache::init();
		add_action("rest_api_init", self::register_api_routes(...));
	}

	private static function register_api_routes() {
		// options for controls in editor
		register_rest_route("bring", "/dynamic/options", [
			"methods" => "POST",
			"callback" => Options::post(...),
			"permission_callback" => "__return_true",
		]);

		// for listing posts
		register_rest_route("bring", "/dynamic/list", [
			"methods" => "POST",
			"callback" => Lists::post(...),
			"permission_callback" => "__return_true",
		]);

		// value for a post
		register_rest_route("bring", "/dynamic/props", [
			"methods" => "POST",
			"callback" => Props::post(...),
			"permission_callback" => "__return_true",
		]);

		//query content
		register_rest_route("bring", "/dynamic/content", [
			"methods" => "POST",
			"callback" => Content::post(...),
			"permission_callback" => "__return_true",
		]);
	}
}
