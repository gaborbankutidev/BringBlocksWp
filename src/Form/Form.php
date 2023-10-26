<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Form;

class Form {
	public static function init() {
		Model::init();
		Api::init();
		Admin::init();
	}
}
