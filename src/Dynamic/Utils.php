<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use WP_REST_Request;

class Utils {
	public static function get_entity_type(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		$entity_type = isset($request_body["entityType"])
			? sanitize_text_field($request_body["entityType"])
			: "post";

		return in_array($entity_type, ["post", "taxonomy", "author"]) ? $entity_type : false;
	}

	public static function get_entity_slug(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["entitySlug"])
			? sanitize_text_field($request_body["entitySlug"])
			: false;

		// TODO refactor that entity slug is optional and checks that entity slug exists
	}

	public static function get_entity_id(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["entityId"]) ? sanitize_text_field($request_body["entityId"]) : false;
	}

	public static function get_limit(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["limit"]) &&
			is_int($request_body["limit"]) &&
			$request_body["limit"] > 0
			? $request_body["limit"]
			: -1;
	}

	public static function get_custom_data(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		return isset($request_body["customData"]) && is_array($request_body["customData"])
			? $request_body["customData"]
			: [];
	}

	public static function get_props_cache_key($entity_type, $entity_id, $custom_data_key = "") {
		return $custom_data_key
			? "prop_{$entity_type}_{$entity_id}_{$custom_data_key}"
			: "prop_{$entity_type}_{$entity_id}";
	}

	public static function get_list_cache_key(
		$entity_type,
		$entity_slug,
		$limit,
		$custom_data_key = "",
	) {
		return $custom_data_key
			? "list_{$entity_type}_{$entity_slug}_{$limit}_{$custom_data_key}"
			: "list_{$entity_type}_{$entity_slug}_{$limit}";
	}
}
