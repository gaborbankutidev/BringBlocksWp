<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Exceptions;

use Exception;
use Throwable;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

class UserNotFoundException extends Exception {
	public function __construct(string $message = "", int $code = 0, Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
	}
}
