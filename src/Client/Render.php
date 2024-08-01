<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use Bring\BlocksWP\Config;
use Bring\BlocksWP\Cache\Head;
use WP_Term;

class Render {
	/**
	 * @return void
	 */
	public static function init() {
		add_action("wp", self::render(...));
	}

	/**
	 * @return void
	 */
	public static function render() {
		global $wp;

		// Bypass for admin
		if (is_admin()) {
			return;
		}

		// Bypass for Robots.txt
		if ($wp->request === "robots.txt") {
			return;
		}

		// Bypass and return as normal if param is set
		$bypass = isset($_GET["bypass"]) ? strval($_GET["bypass"]) : null;
		if ($bypass && $bypass === "1") {
			return;
		}

		// Bypass for paths listed in config
		$ignore_paths = Config::getIgnorePaths();
		if (in_array($wp->request, $ignore_paths)) {
			return;
		}

		// Redirect to next site if data token is not set
		$data_token = isset($_GET["data_token"]) ? strval($_GET["data_token"]) : null;
		if (!$data_token) {
			wp_redirect(Config::getEnv()["NEXT_BASE_URL"] . "/" . $wp->request);
			exit();
		}

		// Check if data token is correct
		if ($data_token !== Config::getEnv()["DATA_TOKEN"]) {
			wp_send_json(
				[
					"responseCode" => 400,
					"error" => "wrong_data_token",
					"entity" => null,
				],
				400,
			);
		}

		// Update cache if param is set
		$request_url = $wp->request ? $wp->request . "/" : "";
		$update = isset($_GET["updateCache"]) ? strval($_GET["updateCache"]) : null;
		if ($update && $update === "1") {
			$new_head = Head::updateHead($request_url);
			wp_send_json(
				["Message" => "Url cache updated for " . $request_url, "Value" => $new_head],
				200,
			);
		}

		$head = Head::getHead($request_url);
		$response_code = $head["response_code"];

		// Handle not found
		if ($response_code === 404) {
			wp_send_json(
				[
					"responseCode" => $response_code,
					"error" => "not_found",
					"entity" => null,
				],
				$response_code,
			);
		}

		// Handle redirect
		if (
			$response_code === 301 ||
			$response_code === 302 ||
			$response_code === 307 ||
			$response_code === 308
		) {
			$redirect_location = isset($head["redirect_to"]) ? $head["redirect_to"] : "";
			wp_send_json(
				[
					"responseCode" => $response_code,
					"redirectTo" => str_replace(
						"?bypass=1",
						"",
						str_replace(home_url(), "", $redirect_location),
					),
					"entity" => null,
				],
				$response_code,
			);
		}

		// Render json response for next rendering
		wp_send_json(
			[
				"responseCode" => 200,
				"entity" => self::renderEntity(),
			],
			200,
		);
	}

	/**
	 * @return array<string,mixed>
	 */
	private static function renderEntity() {
		$entityId = null;
		$entitySlug = null;
		$entityType = null;

		$main = null;

		if (is_singular()) {
			$entityId = get_queried_object_id();
			$entitySlug = get_post_type($entityId);
			$entityType = "post";

			$main = Content::getMain($entityId);
		}

		if (is_tax() || is_tag() || is_category()) {
			$entityId = get_queried_object_id();

			$term = get_term($entityId);
			if ($term instanceof WP_Term) {
				$entitySlug = $term->taxonomy;

				$entityType = "taxonomy";
			}
		}

		if (is_author()) {
			$entityId = get_queried_object_id();
			$entityType = "author";
		}

		return [
			"id" => $entityId,
			"slug" => $entitySlug,
			"type" => $entityType,

			"props" =>
				$entityId && $entityType ? Props::getEntityProps($entityId, $entityType) : null,
			"content" => [
				"header" => Content::getHeader(),
				"footer" => Content::getFooter(),
				"layout" =>
					is_string($entitySlug) && $entityType
						? Content::getLayout($entitySlug, $entityType)
						: null,
				"main" => $main,
			],
		];
	}
}
