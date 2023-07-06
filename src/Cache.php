<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use WP_REST_Request;

class Cache {
	private static $apikey = "";
	private static $is_bring_cache = false;
	private static $header_or_footer_id = 0;
	private static $bring_cache_url = "https://bring-cache.herokuapp.com";

	public static function init($apikey, bool $development_mode = false) {
		// set apikey
		self::$apikey = $apikey;

		if ($development_mode) {
			self::$bring_cache_url = "http://0.0.0.0:3000/";
		}

		// check query & validate apikey
		add_action("wp", self::check_and_validate(...));

		// register routes
		add_action("rest_api_init", self::register_api_routes(...));

		// admin columns
		is_admin() && current_user_can("edit_posts") && self::add_columns();
	}

	/**
	 * checks query var and get values and sets isBringCache true
	 * or redirects if the request is wrong
	 */
	private static function check_and_validate() {
		// return if bringCache is not set
		if (!isset($_GET["bringCache"]) || sanitize_text_field($_GET["bringCache"]) != "1") {
			return;
		}

		// redirect if api key is missing or wrong
		if (!isset($_GET["apikey"]) || sanitize_text_field($_GET["apikey"]) != self::$apikey) {
			wp_redirect("/");
			exit();
		}

		// set isBringCache
		self::$is_bring_cache = true;

		$header_or_footer_id = sanitize_text_field($_GET["headerOrFooterId"]);
		if (!isset($_GET["headerOrFooterId"]) || $header_or_footer_id == "0") {
			return;
		}

		if (!in_array(get_post_type($header_or_footer_id), ["bring_header", "bring_footer"])) {
			return;
		}

		self::$header_or_footer_id = $header_or_footer_id;
	}

	public static function is_bring_cache() {
		return self::$is_bring_cache;
	}

	public static function header_or_footer_id() {
		return self::$header_or_footer_id;
	}

	private static function register_api_routes() {
		// options for controls in editor
		register_rest_route("bring", "/cache/trigger", [
			"methods" => "POST",
			"callback" => self::trigger_cache(...),
			"permission_callback" => Utils::permission_callback(...),
		]);
	}

	private static function trigger_cache(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		if (!isset($request_body["entityType"]) || !isset($request_body["entityId"])) {
			return [
				"success" => false,
				"message" => "Entity type or id is wrong",
			];
		}

		$entity_type = sanitize_text_field($request_body["entityType"]);
		$entity_id = sanitize_text_field($request_body["entityId"]);

		if ($entity_type == "taxonomy") {
			return self::cache_taxonomy($entity_id);
		}
		if ($entity_type == "post") {
			return self::cache_post($entity_id);
		}
	}

	private static function cache_taxonomy($entity_id) {
		$term = get_term($entity_id);
		$taxonomy = $term->taxonomy;
		if (!in_array($taxonomy, Config::get_layout_taxonomies())) {
			return [
				"success" => false,
				"message" => "Not a layout supported taxonomy",
			];
		}

		$args = [
			"method" => "POST",
			"headers" => [
				"content-type" => "application/json",
			],
			"body" => wp_json_encode([
				"url" => get_term_link(intval($entity_id)),
				"apikey" => self::$apikey,
				"headerOrFooterId" => 0,
			]),
		];

		$response = wp_remote_request(self::$bring_cache_url, $args);

		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code != 200) {
			return [
				"success" => false,
				"response_code" => $response_code,
			];
		}

		$response_body = wp_remote_retrieve_body($response);

		$updated = update_term_meta($entity_id, "bring_content_html", base64_encode($response_body));
		if ($updated) {
			update_term_meta($entity_id, "bring_last_cached", Date("Y.m.d. H:i:s"));
		}

		return ["success" => $updated, "response_body" => $response_body];
	}

	private static function cache_post($entity_id) {
		$post_type = get_post_type($entity_id);
		if (
			!in_array($post_type, Config::get_supported_post_types()) &&
			!in_array($post_type, Config::get_layout_post_types())
		) {
			return [
				"success" => false,
				"message" => "Not a layout or builder supported post type",
			];
		}
		if ($post_type == "bring_layout") {
			return false;
		}
		if (!get_post_status($entity_id)) {
			return false;
		}

		$headerOrFooter = $post_type == "bring_header" || $post_type == "bring_footer";

		$args = [
			"method" => "POST",
			"headers" => [
				"content-type" => "application/json",
			],
			"body" => wp_json_encode([
				"url" => $headerOrFooter ? get_home_url() : get_permalink($entity_id),
				"apikey" => self::$apikey,
				"headerOrFooterId" => $headerOrFooter ? intval($entity_id) : 0,
			]),
		];

		$response = wp_remote_request(self::$bring_cache_url, $args);

		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code != 200) {
			return ["success" => false, "response_code" => $response_code];
		}

		$response_body = wp_remote_retrieve_body($response);

		$updated = update_post_meta($entity_id, "bring_content_html", base64_encode($response_body));
		if ($updated) {
			update_post_meta($entity_id, "bring_last_cached", Date("Y.m.d. H:i:s"));
		}

		return ["success" => $updated, "response_body" => $response_body];
	}

	private static function add_columns() {
		$token = Utils::generate_token();

		$post_types = array_unique(
			array_merge(Config::get_supported_post_types(), Config::get_layout_post_types()),
			SORT_REGULAR,
		);
		foreach ($post_types as $post_type) {
			if ($post_type == "bring_layout") {
				continue;
			}

			add_filter("manage_{$post_type}_posts_columns", function ($c) {
				$c["bring-cache"] = "Cache";
				return $c;
			});

			add_action(
				"manage_{$post_type}_posts_custom_column",
				function ($c, $post_id) use (&$token) {
					if ($c != "bring-cache") {
						return;
					}

					$last_cached = get_post_meta($post_id, "bring_last_cached", true);

					echo "
						<div style='margin-bottom: 4px;'>Last cached: $last_cached</div>
						<a href='#' class='button bringUpdateCache' data-entity-type='post' data-entity-id='$post_id' data-token='$token' style='display: flex; align-items: center; width: fit-content; gap: 4px;'>
							Update cache
							<span></span>
						</a>
					";
				},
				10,
				2,
			);
		}

		$taxonomies = array_unique(Config::get_layout_taxonomies(), SORT_REGULAR);
		foreach ($taxonomies as $taxonomy) {
			add_filter("manage_edit-{$taxonomy}_columns", function ($c) {
				$c["bring-cache"] = "Cache";
				return $c;
			});

			add_action(
				"manage_{$taxonomy}_custom_column",
				function ($string, $c, $term_id) use (&$token) {
					if ($c != "bring-cache") {
						return;
					}

					$last_cached = get_term_meta($term_id, "bring_last_cached", true);

					echo "
						<div style='margin-bottom: 4px;'>Last cached: $last_cached</div>
						<a href='#' class='button bringUpdateCache' data-entity-type='taxonomy' data-entity-id='$term_id' data-token='$token' style='display: flex; align-items: center; width: fit-content; gap: 4px;'>
							Update cache
							<span></span>
						</a>
					";
				},
				10,
				3,
			);
		}
	}
}
