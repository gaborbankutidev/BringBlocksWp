<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use Bring\BlocksWP\Config;
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
	private static function renderJson() {
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

		if (is_404()) {
			$entitySlug = "not_found";
			$entityType = "general";
		}

		// todo return content & props
		wp_send_json(
			[
				"responseCode" => 200,
				"id" => $entityId,
				"slug" => $entitySlug,
				"type" => $entityType,

				"props" => $entityId && $entityType ? Props::getEntityProps($entityId, $entityType) : null,
				"content" => [
					"main" => $main,
					"layout" =>
						is_string($entitySlug) && $entityType ? Content::getLayout($entitySlug, $entityType) : null,
					"header" => Content::getHeader(),
					"footer" => Content::getFooter(),
					"head" => Content::getHead($entityId),
				],
			],
			200,
		);
	}

	/**
	 * @return void
	 */
	public static function render() {
		global $wp;

		if (is_admin()) {
			return;
		}

		$bypass = isset($_GET["bypass"]) ? strval($_GET["bypass"]) : null;
		$data_token = isset($_GET["data_token"]) ? strval($_GET["data_token"]) : null;

		// Data token request
		if ($data_token && $data_token === Config::getEnv()["DATA_TOKEN"]) {
			$response = wp_remote_head(home_url() . "/" . $wp->request . "?bypass=1");
			$response_code = wp_remote_retrieve_response_code($response);

			// Handle not found
			if ($response_code === "404" || $response_code === 404) {
				wp_send_json(
					[
						"responseCode" => intval($response_code),
						"slug" => "not_found",
						"type" => "general",
					],
					intval($response_code),
				);
			}

			// Handle redirect
			if (
				$response_code === "301" ||
				$response_code === 301 ||
				$response_code === "302" ||
				$response_code === 302 ||
				$response_code === "307" ||
				$response_code === 307
			) {
				$redirect_location = wp_remote_retrieve_header($response, "Location");
				wp_send_json(
					[
						"responseCode" => intval($response_code),
						"redirectTo" => str_replace("?bypass=1", "", str_replace(home_url(), "", $redirect_location)),
					],
					intval($response_code),
				);
			}

			// Render json response for next rendering
			self::renderJson();
		}

		// Return as normal
		if ($bypass && $bypass === "1") {
			return;
		}

		// Redirect to next site
		wp_redirect(Config::getEnv()["NEXT_URL"] . $wp->request);
		exit();
	}
}
