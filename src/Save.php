<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use WP_REST_Request;
use WP_Error;

class Save {
	public static function init() {
		// register routes
		add_action("rest_api_init", self::register_api_routes(...));
	}

	/**
	 * Registers bring rest api routes
	 */
	private static function register_api_routes() {
		// update post content object
		register_rest_route("bring", "/update/content-object", [
			"methods" => "POST",
			"permission_callback" => Utils::permission_callback(...),
			"callback" => self::update_content_object(...),
		]);
	}

	/**
	 * Updates object content for the post
	 */
	private static function update_content_object(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		// missing params
		if (!isset($request_body["entityId"]) || !isset($request_body["contentObject"])) {
			return new WP_Error("missing_params", "entityId or contentObject is missing", [
				"status" => 400,
			]);
		}

		$entity_id = sanitize_text_field($request_body["entityId"]);

		// return of post doesn't exist
		if (!get_post_status($entity_id)) {
			return new WP_Error("no_page", "Page not found.", [
				"status" => 404,
			]);
		}

		// check if post supports bring blocks
		$post_type = get_post_type($entity_id);
		if (!in_array($post_type, Config::get_supported_post_types())) {
			return new WP_Error("not_supported", "Post type doesn't support BringBlocks", [
				"status" => 403,
			]);
		}

		$object_update = update_post_meta(
			$entity_id,
			"bring_content_object",
			$request_body["contentObject"],
		);

		return [
			"success" => $object_update,
		];
	}
}
