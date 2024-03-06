<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Utils;

use Bring\BlocksWP\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use WP_REST_Request;
use Exception;

class Api {
	/**
	 * Returns the entity type from the request
	 */
	public static function getEntityType(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		$entity_type = isset($request_body["entityType"])
			? sanitize_text_field($request_body["entityType"])
			: "post";

		return in_array($entity_type, ["post", "taxonomy", "author"]) ? $entity_type : null;
	}

	/**
	 * Returns the entity slug from the request
	 */
	public static function getEntitySlug(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["entitySlug"])
			? sanitize_text_field($request_body["entitySlug"])
			: null;

		// TODO refactor that entity slug is optional and checks that entity slug exists
	}

	/**
	 * Returns the entity id from the request
	 */
	public static function getEntityId(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["entityId"]) ? sanitize_text_field($request_body["entityId"]) : null;
	}

	/**
	 * Return the jwt Secret Key
	 */
	private static function getJwtSecretKey() {
		$jwt_secret_key = Config::getEnv()["JWT_SECRET_KEY"];

		$jwt_secret_key && throw new Exception("JWT_SECRET_KEY is not defined.");

		return $jwt_secret_key;
	}

	/**
	 * Returns the limit from the request
	 */
	public static function getLimit(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["limit"]) &&
			is_int($request_body["limit"]) &&
			$request_body["limit"] > 0
			? $request_body["limit"]
			: -1;
	}

	/**
	 * Returns the custom data from the request
	 */
	public static function getCustomData(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["customData"]) && is_array($request_body["customData"])
			? $request_body["customData"]
			: [];
	}

	/**
	 * Generates jwt for the current user
	 */
	public static function generateToken() {
		// Generating token for user
		$payload = [
			"user_id" => get_current_user_id(),
		];
		return JWT::encode($payload, self::getJwtSecretKey(), "HS256");
	}

	/**
	 * Permission callback to check jwt if the user has permission to edit posts
	 */
	public static function permissionCallback(WP_REST_Request $request) {
		$token = $request->get_header("Authorization");

		$token_payload = [];
		try {
			$decoded = JWT::decode($token, new Key(self::getJwtSecretKey(), "HS256"));
			$token_payload = (array) $decoded;
		} catch (\Throwable $e) {
			return false;
		}

		$user_id = $token_payload["user_id"];

		if (!get_userdata($user_id)) {
			return false;
		}

		wp_set_current_user($user_id);

		if (!current_user_can("edit_posts")) {
			return false;
		}

		return true;
	}
}
