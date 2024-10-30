<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use WP_REST_Response;
use WP_REST_Request;

use Bring\BlocksWP\Redirects\Redirect;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

class Api {
	/**
	 * Initialize api
	 *
	 * @return void
	 */
	public static function init() {
		self::registerRoutes();
	}

	/**
	 * Register search routes
	 *
	 * @return void
	 */
	private static function registerRoutes() {
		add_action("rest_api_init", function () {
			register_rest_route("bring", "/entity(?:/(?P<permalink>.+))?", [
				"methods" => "GET",
				"callback" => self::entity(...),
				"permission_callback" => "__return_true",
			]);
		});
	}

	/**
	 * Handle search request
	 *
	 * @param WP_REST_Request<array<mixed,mixed>> $request
	 * @return mixed
	 */
	private static function entity(WP_REST_Request $request) {
		global $wp;

		/**
		 * @var string $permalink
		 */
		$permalink = $request->get_param("permalink") ?? "";
		$permalink = sanitize_text_field($permalink);

		$front_page_permalink = self::getFrontPagePermalink();

		// Redirect from front page permalink to base url
		if ($front_page_permalink && $permalink === $front_page_permalink) {
			return new WP_REST_Response(
				[
					"responseCode" => "308",
					"redirectTo" => "/", // Redirect to base url
				],
				200,
			);
		}

		// Set permalink to front page permalink if empty
		if ($permalink === "") {
			if (!$front_page_permalink) {
				return new WP_REST_Response(
					[
						"responseCode" => "404",
					],
					200,
				);
			}

			$permalink = $front_page_permalink;
		}

		$redirect = Redirect::getRedirectByFromPermalink($permalink);
		if ($redirect) {
			$redirect->incrementHits();
			return new WP_REST_Response(
				[
					"responseCode" => $redirect->getStatusCode(),
					"redirectTo" => $redirect->getTo(),
				],
				200,
			);
		}

		// Parse permalink & set wp globals -> The rest api request will be handled as a normal request so all WP Query functions will work
		$parsed = Parse::parsePermalink($permalink);

		if ($parsed) {
			$wp->query_posts();
			$wp->handle_404();
			$wp->register_globals();
		}

		// Handle 404
		if (is_404()) {
			return new WP_REST_Response(
				[
					"responseCode" => "404",
				],
				200,
			);
		}

		// Return 404 if not a single post, taxonomy, tag, category or author -> archives are not supported
		if (!is_singular() && !is_tax() && !is_tag() && !is_category() && !is_author()) {
			return new WP_REST_Response(
				[
					"responseCode" => "404",
				],
				200,
			);
		}

		// Render entity
		return new WP_REST_Response(
			[
				"responseCode" => "200",
				"entity" => Render::renderEntity(),
			],
			200,
		);
	}

	/**
	 * Return front page permalink or null if font page is not set or does not exist
	 *
	 * @return string|false
	 */
	private static function getFrontPagePermalink() {
		/**
		 * @var int|null $front_page_id
		 */
		$front_page_id = get_option("page_on_front");

		if (!is_numeric($front_page_id) || !get_post($front_page_id)) {
			return false;
		}

		return get_page_uri($front_page_id);
	}
}
