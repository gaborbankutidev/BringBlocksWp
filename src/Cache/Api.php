<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Cache;

use WP_REST_Request;
use Bring\BlocksWP\Utils;

class Api {
	/**
	 * @return void
	 */
	public static function init() {
		// register routes
		add_action("rest_api_init", self::routes(...));
	}

	/**
	 * Registers bring rest api routes
	 * @return void
	 */
	private static function routes() {
		// get cached content html
		register_rest_route("bring", "/cache/content", [
			"methods" => "GET",
			"permission_callback" => Utils\Api::createPermissionCallback(),
			"callback" => self::content(...),
		]);
	}

	/**
	 * Handles post request for editor options
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return array{data: string|null}
	 */
	private static function content(WP_REST_Request $request) {
		// check entity type
		$entity_type = Utils\Api::getEntityType($request);
		if (!$entity_type) {
			return [
				"data" => null,
			];
		}

		// get entity slug
		$entity_id = Utils\Api::getEntityId($request);
		if (!$entity_id) {
			return [
				"data" => null,
			];
		}

		return [
			"data" => Content::getContentHtml($entity_id, $entity_type),
		];
	}
}
