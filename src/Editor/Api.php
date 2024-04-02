<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Editor;

use WP_REST_Request;
use WP_Error;
use Bring\BlocksWP\Config;
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
		// options for controls in editor
		register_rest_route("bring", "/editor/options", [
			"methods" => "POST",
			"permission_callback" => "__return_true",
			"callback" => self::options(...),
		]);

		// update post content object
		register_rest_route("bring", "/editor/save", [
			"methods" => "POST",
			"permission_callback" => Utils\Api::createPermissionCallback(),
			"callback" => self::save(...),
		]);
	}

	/**
	 * Handles post request for editor options
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return array{data: array<mixed>|null}
	 */
	private static function options(WP_REST_Request $request) {
		// check entity type
		$entity_type = Utils\Api::getEntityType($request);
		if (!$entity_type) {
			return [
				"data" => null,
			];
		}

		// get entity slug
		$entity_slug = Utils\Api::getEntitySlug($request);
		if (!$entity_slug) {
			return [
				"data" => null,
			];
		}

		// author
		if ($entity_type == "author") {
			// TODO author support
			return [
				"data" => null,
			];
		}

		// taxonomy
		if ($entity_type == "taxonomy") {
			// check if taxonomy exists
			if (!taxonomy_exists($entity_slug)) {
				return [
					"data" => null,
				];
			}
			$taxonomy = $entity_slug;

			// query terms
			$args = [
				"taxonomy" => $taxonomy,
				"numberposts" => -1,
			];
			$terms = get_terms($args);

			// return if empty
			if (!$terms) {
				return [
					"data" => [],
				];
			}

			if (!is_array($terms)) {
				return [
					"data" => [],
				];
			}

			// build content
			$term_options = [];
			foreach ($terms as $term) {
				$term_options[] = [intval($term->term_id), $term->name];
			}

			return [
				"data" => $term_options,
			];
		}

		// post
		if ($entity_type == "post") {
			// check if post types exists
			if (!post_type_exists($entity_slug)) {
				return [
					"data" => null,
				];
			}
			$post_type = $entity_slug;

			// query posts
			$args = [
				"post_type" => $post_type,
				"numberposts" => -1,
				"fields" => "ids",
			];
			$post_ids = get_posts($args);

			// return if empty
			if (!$post_ids) {
				return [
					"data" => [],
				];
			}

			// build content
			$post_options = [];
			foreach ($post_ids as $post_id) {
				$post_options[] = [intval($post_id), get_the_title($post_id)];
			}

			return [
				"data" => $post_options,
			];
		}

		return [
			"data" => null,
		];
	}

	/**
	 * Updates object content for the post
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return array{success: bool}|WP_Error
	 */
	private static function save(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		// missing params
		if (!isset($request_body["entityId"]) || !isset($request_body["contentObject"])) {
			return new WP_Error("missing_params", "entityId or contentObject is missing", [
				"status" => 400,
			]);
		}

		$entity_id = sanitize_text_field($request_body["entityId"]);
		if (!is_numeric($entity_id)) {
			return new WP_Error("wrong_params", "entityId is not numeric", [
				"status" => 400,
			]);
		}
		$entity_id = intval($entity_id);

		// return of post doesn't exist
		if (!get_post_status($entity_id)) {
			return new WP_Error("no_page", "Page not found.", [
				"status" => 404,
			]);
		}

		// check if post supports bring blocks
		$post_type = get_post_type($entity_id);
		if (!in_array($post_type, Config::getEditorPostTypes())) {
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
			"success" => is_int($object_update) ? true : $object_update,
		];
	}
}
