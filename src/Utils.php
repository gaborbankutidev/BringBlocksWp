<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use WP_REST_Request;

class Utils {
	/**
	 * Var dump variable into a string
	 */
	public static function var_dump_to_string(mixed $var) {
		ob_start();
		var_dump($var);
		$result = ob_get_clean();
		return $result;
	}

	/**
	 * Returns image by attachment id in ImageType format
	 */
	public static function get_image($image_id, $size = "thumbnail") {
		$img = wp_get_attachment_image_src($image_id, $size);

		if (!$img || !$img[0]) {
			return [
				"src" => "",
				"alt" => "",
				"id" => null,
			];
		}

		$image_src = $img[0];
		$image_alt = get_post_meta($image_id, "_wp_attachment_image_alt", true);

		return [
			"src" => $image_src,
			"alt" => $image_alt ? $image_alt : "",
			"id" => $image_id,
		];
	}

	/**
	 * Returns entity image by entity id & type in ImageType format
	 */
	public static function get_entity_image($entity_id, $entity_type = "post", $size = "thumbnail") {
		$image_id = 0;

		// get meta data
		if ($entity_type == "author") {
			$image_id = get_user_meta($entity_id, "image", true);
		}
		if ($entity_type == "taxonomy") {
			$image_id = get_term_meta($entity_id, "image", true);
		}
		if ($entity_type == "post") {
			$image_id = get_post_thumbnail_id($entity_id);
		}

		return self::get_image($image_id, $size);
	}

	/**
	 * Recursively get taxonomy and its children
	 */
	public static function get_taxonomy_hierarchy($taxonomy, $collect_data = null, $parent = 0) {
		$term_ids = get_terms($taxonomy, [
			"parent" => $parent,
			"hide_empty" => false,
			"fields" => "ids",
		]);

		$terms = [];

		if (!count($term_ids)) {
			return $terms;
		}

		foreach ($term_ids as $key => $term_id) {
			// FIXME: unused variable
			$term = [
				"id" => $term_id,
				"children" => self::get_taxonomy_hierarchy($taxonomy, $collect_data, $term_id),
			];

			if (is_callable($collect_data)) {
				$term = array_merge($term, $collect_data($term_id));
			}

			$terms[] = $term;
		}

		return $terms;
	}

	/**
	 * Return the slug of queried general layout or false
	 */
	public static function is_general_layout() {
		if (is_author()) {
			return "author";
		}
		if (is_date()) {
			return "date";
		}
		if (is_search()) {
			return "search";
		}
		if (is_404()) {
			return "not_found";
		}
		return false;
	}

	/**
	 * Generates jwt for the current user
	 */
	public static function generate_token() {
		// Generating token for user
		$payload = [
			"user_id" => get_current_user_id(),
		];
		return JWT::encode($payload, Config::get_jwt_secret_key(), "HS256");
	}

	/**
	 * Permission callback to check jwt
	 */
	public static function permission_callback(WP_REST_Request $request) {
		$token = $request->get_header("Authorization");

		$token_payload = [];
		try {
			$decoded = JWT::decode($token, new Key(Config::get_jwt_secret_key(), "HS256"));
			$token_payload = (array) $decoded;
		} catch (\Throwable $e) {
			// FIXME: unused variable ?????
			return;
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
