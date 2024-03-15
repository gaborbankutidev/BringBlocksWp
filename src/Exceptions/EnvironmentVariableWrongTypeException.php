<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Exceptions;

use Exception;
use Throwable;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

class EnvironmentVariableWrongTypeException extends Exception {
	public function __construct(
		string $env_name,
		string $expected_type,
		int $code = 0,
		Throwable $previous = null,
	) {
		parent::__construct("$env_name has the wrong type (expected $expected_type)!", $code, $previous);
	}
}
