<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Utils;

use Exception;
use WP_REST_Request;
use WP_Error;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use Bring\BlocksWP\Config;
use Bring\BlocksWP\Exceptions\UserNotFoundException;

class Api {
	/**
	 * Returns the entity type from the request
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return string|null TODO: should be swapped to an enum
	 */
	public static function getEntityType(WP_REST_Request $request) {
		/**
		 * @var string|null
		 */
		$entity_type = $request->get_param("entityType");

		$entity_type = !is_null($entity_type) ? sanitize_text_field($entity_type) : "post";

		return in_array($entity_type, ["post", "taxonomy", "author"]) ? $entity_type : null;
	}

	/**
	 * Returns the entity slug from the request
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return string|null
	 */
	public static function getEntitySlug(WP_REST_Request $request) {
		/**
		 * @var string|null
		 */
		$entity_slug = $request->get_param("entitySlug");

		return !is_null($entity_slug) ? sanitize_text_field($entity_slug) : null;
		// TODO refactor that entity slug is optional and checks that entity slug exists
	}

	/**
	 * Returns the entity id from the request
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return int|null
	 */
	public static function getEntityId(WP_REST_Request $request) {
		/**
		 * @var string|null
		 */
		$entity_id = $request->get_param("entityId");

		$entity_id = !is_null($entity_id) ? sanitize_text_field($entity_id) : null;

		return is_numeric($entity_id) ? intval($entity_id) : null;
	}

	/**
	 * Returns the limit from the request
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return int
	 */
	public static function getLimit(WP_REST_Request $request) {
		/**
		 * @var string|null
		 */
		$limit = $request->get_param("limit");

		$limit = !is_null($limit) ? sanitize_text_field($limit) : null;
		$limit = is_numeric($limit) ? intval($limit) : 0;

		return $limit > 0 ? $limit : -1;
	}

	/**
	 * Returns the offset from the request
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return int
	 */
	public static function getOffset(WP_REST_Request $request) {
		/**
		 * @var string|null
		 */
		$offset = $request->get_param("offset");

		$offset = !is_null($offset) ? sanitize_text_field($offset) : null;
		$offset = is_numeric($offset) ? intval($offset) : 0;

		return $offset >= 0 ? $offset : 0;
	}

	/**
	 * Returns the offset from the request
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return int
	 */
	public static function getPage(WP_REST_Request $request) {
		/**
		 * @var string|null
		 */
		$page = $request->get_param("page");

		$page = !is_null($page) ? sanitize_text_field($page) : null;
		$page = is_numeric($page) ? intval($page) : 1;

		return $page >= 1 ? $page : 1;
	}

	/**
	 * Returns the custom data from the request
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return array<string,mixed>
	 */
	public static function getCustomData(WP_REST_Request $request) {
		/**
		 * @var string|null
		 */
		$custom_data_json = $request->get_param("customData");

		/**
		 * @var array<string,mixed>|null
		 */
		$custom_data = !is_null($custom_data_json) ? json_decode($custom_data_json, true) : null;

		return is_array($custom_data) ? $custom_data : [];
	}

	/**
	 * Generates jwt for the current user
	 *
	 * @param int $user_id
	 *
	 * @return string Generated token
	 */
	public static function generateToken($user_id = 0) {
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		// Generating token for user
		$payload = [
			"iss" => get_site_url(),
			"aud" => Config::getEnv()["NEXT_URL"],
			"exp" => time() + 60 * 60 * 24 * 14, // Expiration Time, 2 weeks
			"iat" => time(), // Issued At: current time
			"userId" => $user_id,
		];
		return JWT::encode($payload, Config::getEnv()["JWT_SECRET_KEY"], "HS256");
	}

	/**
	 * Validates jwt token
	 *
	 * @param string $token Token to validate
	 *
	 * @throws UserNotFoundException
	 *
	 * @return int User id
	 */
	public static function validateToken(string $token) {
		$key = Config::getEnv()["JWT_SECRET_KEY"];

		$token = str_replace(["Bearer ", "\""], "", $token);
		$decoded = JWT::decode($token, new Key($key, "HS256"));

		if (!isset($decoded->userId)) {
			throw new Exception("User Id not found in token");
		}

		$auth_user = get_user_by("id", $decoded->userId);
		if (!$auth_user) {
			throw new UserNotFoundException("User not found");
		}

		return $auth_user->ID;
	}

	/**
	 * Creates permission callback to allow users with the selected capabilities
	 * Empty capabilities array -> no permission check
	 *
	 * @param array<string> $capabilities
	 * @param bool $set_current_user
	 *
	 * @return callable|string Permission callback function
	 */
	public static function createPermissionCallback(
		$capabilities = ["edit_posts"],
		$set_current_user = true,
	) {
		return function (WP_REST_Request $request) use ($capabilities, $set_current_user) {
			$token = $request->get_header("Authorization");

			// Unauthorized if token not found
			if (!$token) {
				return new WP_Error("jwt_not_found", "Jwt not found", [
					"status" => 401, // 401 => Unauthorized
				]);
			}

			// Get user from the token
			try {
				$user_id = self::validateToken($token);

				if (!$user_id) {
					return new WP_Error("user_not_found", "User not found", [
						"status" => 401, // 401 => Unauthorized
					]);
				}
			} catch (Exception $e) {
				return new WP_Error("invalid_token", $e->getMessage(), [
					"status" => 401, // 401 => Unauthorized
				]);
			}

			// Set current user
			$set_current_user && wp_set_current_user($user_id);

			// Check permission
			$user_has_permission = $capabilities === [];
			foreach ($capabilities as $capability) {
				if (user_can($user_id, $capability)) {
					$user_has_permission = true;
					break;
				}
			}

			if (!$user_has_permission) {
				return new WP_Error("no_permission", "No permission", [
					"status" => 403, // 403 => Forbidden
				]);
			}

			// return true
			return true;
		};
	}
}
