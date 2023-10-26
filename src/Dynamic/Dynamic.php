<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use Bring\BlocksWP\Utils;
use WP_REST_Request;

class Dynamic {
	public static function init() {
		add_action("rest_api_init", self::routes(...));
	}

	private static function routes() {
		// value for a post
		register_rest_route("bring", "/dynamic/props", [
			"methods" => "GET",
			"permission_callback" => "__return_true",
			"callback" => self::props(...),
		]);

		// for listing posts
		register_rest_route("bring", "/dynamic/list", [
			"methods" => "GET",
			"permission_callback" => "__return_true",
			"callback" => self::lists(...),
		]);

		//query content
		register_rest_route("bring", "/dynamic/site", [
			"methods" => "GET",
			"permission_callback" => "__return_true",
			"callback" => self::site(...),
		]);
	}

	private static function props(WP_REST_Request $request) {
		// Check entity type is set
		$entity_type = Utils\Api::getEntityType($request);
		if (!$entity_type) {
			return [
				"data" => null,
			];
		}

		// Check entity id is set
		$entity_id = Utils\Api::getEntityId($request);
		if (!$entity_id) {
			return [
				"data" => null,
			];
		}

		// Check if entity exists
		if ($entity_type == "author") {
			// TODO author support
			return [
				"data" => null,
			];
		}
		if ($entity_type == "taxonomy" && !get_term($entity_id)) {
			return [
				"data" => null,
			];
		}
		if ($entity_type == "post" && !get_post($entity_id)) {
			return [
				"data" => null,
			];
		}

		// custom data
		$custom_data = Utils\Api::getCustomData($request);

		return [
			"data" => Props::getDynamicProps($entity_type, $entity_id, $custom_data),
		];
	}

	private static function lists(WP_REST_Request $request) {
		// check entity type is set
		$entity_type = Utils\Api::getEntityType($request);
		if (!$entity_type) {
			return [
				"data" => null,
			];
		}

		// get entity slug is set
		$entity_slug = Utils\Api::getEntitySlug($request);
		if (!$entity_slug) {
			return [
				"data" => null,
			];
		}

		// Check if slug exists
		if ($entity_type == "taxonomy" && !taxonomy_exists($entity_slug)) {
			return [
				"data" => null,
			];
		}
		if ($entity_type == "post" && !post_type_exists($entity_slug)) {
			return [
				"data" => null,
			];
		}

		// limit
		$limit = Utils\Api::getLimit($request);

		// custom data
		$custom_data = Utils\Api::getCustomData($request);

		return [
			"data" => Lists::getDynamicList($entity_type, $entity_slug, $limit, $custom_data),
		];
	}

	private static function site() {
		$site_props = [
			"menus" => Utils\General::getMenus(),
		];

		return [
			"data" => apply_filters("bring_site_props", $site_props),
		];
	}
}
