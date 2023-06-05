<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

class Cache {
	public static function init() {
		add_action("init", self::register_dynamic_blocks(...));
	}

	public static function register_dynamic_blocks() {
		$dynamic_blocks = apply_filters("bring_dynamic_blocks", []);

		if (!$dynamic_blocks) {
			return;
		}

		foreach ($dynamic_blocks as $name => $callback) {
			register_block_type("bring/$name", [
				"api_version" => 2,
				"render_callback" => function ($attributes, $content) use ($callback) { // FIXME: unused variable
					$cache = $callback($attributes);
					if (!$cache) {
						return;
					}

					add_filter("bring_dynamic_cache", function ($a) use ($cache) {
						return array_merge($a, $cache);
					});
				},
			]);
		}
	}
}
