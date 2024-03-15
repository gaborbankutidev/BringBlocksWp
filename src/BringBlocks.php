<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Bring\BlocksWP\Auth\Auth;
use Bring\BlocksWP\Layout\Layout;
use Bring\BlocksWP\Editor\Editor;
use Bring\BlocksWP\Client\Client;
use Bring\BlocksWP\Dynamic\Dynamic;
use Bring\BlocksWP\Form\Form;
use Bring\BlocksWP\Exceptions\EnvironmentVariableMissingException;
// use Bring\BlocksWP\Exceptions\EnvironmentVariableWrongTypeException;

class BringBlocks {
	/**
	 * @return void
	 */
	public static function init() {
		self::checkEnv();

		Auth::init();
		Layout::init();
		Editor::init();
		Client::init();
		Dynamic::init();

		Config::getForms() && Form::init();
	}

	/**
	 * @return void
	 */
	private static function checkEnv() {
		$env = Config::getEnv();

		foreach (["DATA_TOKEN", "NEXT_URL", "JWT_SECRET_KEY"] as $key) {
			if (!isset($env[$key]) || $env[$key] === "") {
				throw new EnvironmentVariableMissingException($key);
			}
			/* if (!is_string($env[$key])) {
				throw new EnvironmentVariableWrongTypeException($key, "string");
			} */
		}
	}
}
