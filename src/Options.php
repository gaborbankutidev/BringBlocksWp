<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

// TODO figure out better way of accessing properties instead of making them public
class Options {
	public static function c_() {
		return new Options();
	}

	public function __construct(
		public bool $useForms = false,
		public string $apikey = "",
		public bool $developmentMode = false,
	) {
	}

	public function setUseForms(bool $value) {
		$this->useForms = $value;
		return $this;
	}

	public function setApikey(string $value) {
		$this->apikey = $value;
		return $this;
	}

	public function setDevelopmentMode(bool $value) {
		$this->developmentMode = $value;
		return $this;
	}
}
