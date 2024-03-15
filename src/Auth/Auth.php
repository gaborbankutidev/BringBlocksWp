<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Auth;

use Exception;
use WP_REST_Request;
use WP_REST_Response;

use Bring\BlocksWP\Utils;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

/**
 * Authentication module
 */
class Auth {
	/**
	 * Initializes auth module
	 *
	 * @return void
	 */
	public static function init() {
		add_action("rest_api_init", function () {
			register_rest_route("bring", "/auth/login", [
				"methods" => "POST",
				"callback" => self::login(...),
				"permission_callback" => "__return_true",
			]);
		});
	}

	/**
	 * Callback for wp_login action
	 * Generates jwt token and sets it as cookie
	 * Logs error if token generation fails
	 *
	 * @param WP_REST_REQUEST<array<mixed,mixed>> $request Request
	 *
	 *
	 * @return WP_REST_RESPONSE
	 */
	private static function login($request) {
		$request_body = $request->get_json_params();

		if (
			!isset($request_body["username"]) ||
			!is_string($request_body["username"]) ||
			!isset($request_body["password"]) ||
			!is_string($request_body["password"])
		) {
			return new WP_REST_Response(
				[
					"status" => 400,
					"error" => "Invalid request body",
				],
				400,
			);
		}

		$user = wp_authenticate($request_body["username"], $request_body["password"]);

		if (is_wp_error($user)) {
			return new WP_REST_Response(
				[
					"status" => 400,
					"error" => "Login credentials invalid",
				],
				400,
			);
		}

		try {
			$token = Utils\Api::generateToken($user->ID);
			return new WP_REST_Response(
				[
					"status" => 200,
					"data" => [
						"token" => $token,
					],
				],
				200,
			);
		} catch (Exception $e) {
			return new WP_REST_Response(
				[
					"status" => 500,
					"data" => "Internal server error in token generation!",
				],
				500,
			);
		}
	}
}
