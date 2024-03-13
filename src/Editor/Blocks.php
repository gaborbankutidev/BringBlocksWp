<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Editor;

use Bring\BlocksWP\Config;

class Blocks {
	/**
	 * @return void
	 */
	public static function init() {
		add_filter("allowed_block_types_all", self::blocks(...));
	}

	/**
	 * @return array<string>
	 */
	private static function blocks() {
		$blocks = Config::getBlocks();

		$list = ["core/paragraph"];
		foreach ($blocks as $block) {
			$list[] = "bring/$block";
		}

		return $list;
	}
}
