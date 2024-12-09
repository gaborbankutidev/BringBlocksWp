<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Sitemap;

use WP_REST_Response;
use WP_REST_Request;
use WP_Error;
use WP_Term;

use Bring\BlocksWP\Utils;
use Bring\BlocksWP\Config;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

class Api {
	/**
	 * Initialize sitemap api
	 *
	 * @return void
	 */
	public static function init() {
		self::registerRoutes();
	}

	/**
	 * Register sitemap routes
	 *
	 * @return void
	 */
	private static function registerRoutes() {
		add_action("rest_api_init", function () {
			register_rest_route("bring", "/sitemap", [
				"methods" => "GET",
				"callback" => self::sitemap(...),
				"permission_callback" => "__return_true",
			]);
		});
	}

	/**
	 * Handle sitemap request
	 *
	 * @return WP_REST_Response
	 */
	private static function sitemap() {
		$sitemapConfig = Config::getSitemap();
		$sitemap = [];

		if (!$sitemapConfig) {
			return new WP_REST_Response($sitemap, 200);
		}

		/**
		 * @var array<string> $post_types
		 */
		$post_types = get_post_types(["public" => true]);
		$post_types = array_intersect($post_types, $sitemapConfig["posts"]);
		foreach ($post_types as $post_type) {
			$posts = get_posts([
				"numberposts" => -1,
				"post_status" => "publish",
				"post_type" => $post_type,
			]);

			foreach ($posts as $post) {
				$url = Config::getEnv()["NEXT_BASE_URL"] . Utils\General::getEntityUrl($post->ID);
				$last_modified = strtotime($post->post_modified);

				$sitemap[] = [
					"url" => $url,
					"lastModified" => $last_modified ? date("c", $last_modified) : date("c"),
				];
			}
		}

		/**
		 * @var array<string> $taxonomies
		 */
		$taxonomies = get_taxonomies(["public" => true]);
		$taxonomies = array_intersect($taxonomies, $sitemapConfig["taxonomies"]);
		foreach ($taxonomies as $taxonomy) {
			$terms = get_terms([
				"taxonomy" => $taxonomy,
				"hide_empty" => false,
			]);

			if ($terms instanceof WP_Error) {
				continue;
			}

			/**
			 * @var array<WP_Term> $terms
			 */
			foreach ($terms as $term) {
				$url =
					Config::getEnv()["NEXT_BASE_URL"] .
					Utils\General::getEntityUrl($term->term_id, "taxonomy");

				$sitemap[] = [
					"url" => $url,
					"lastModified" => date("c"),
				];
			}
		}

		// Authors
		$authors = $sitemapConfig["authors"]
			? get_users([
				"who" => "authors",
				"has_published_posts" => true,
			])
			: [];
		foreach ($authors as $author) {
			$url = Config::getEnv()["NEXT_BASE_URL"] . Utils\General::getEntityUrl($author->ID, "author");

			$sitemap[] = [
				"post_title" => $author->display_name,
				"url" => $url,
				"lastModified" => $author->user_registered,
			];
		}

		return new WP_REST_Response($sitemap, 200);
	}
}
