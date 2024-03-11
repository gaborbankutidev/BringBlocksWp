<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use Bring\BlocksWP\Config;

class Render {
	public static function init() {
		add_action("wp", self::render(...));

		// TODO handle redirects
		/* if (is_post_type_archive()) {
			wp_redirect("/");
			exit();
		} */
	}

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
			$entitySlug = $term->taxonomy;

			$entityType = "taxonomy";
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
				"id" => $entityId,
				"slug" => $entitySlug,
				"type" => $entityType,

				"props" => Props::getEntityProps($entityId, $entityType),
				"content" => [
					"main" => $main,
					"layout" => Content::getLayout($entitySlug, $entityType),
					"header" => Content::getHeader(),
					"footer" => Content::getFooter(),
				],
			],
			200,
		);
	}

	public static function render() {
		global $wp;

		if (is_admin()) {
			return;
		}

		$bypass = isset($_GET["bypass"]) ? strval($_GET["bypass"]) : null;
		$data_token = isset($_GET["data_token"]) ? strval($_GET["data_token"]) : null;

		if ($data_token && $data_token === Config::getEnv()["DATA_TOKEN"]) {
			// Render json response for next rendering
			self::renderJson();
			return;
		}

		if ($bypass && $bypass === "1") {
			// Return as normal
			return;
		}

		// Redirect to next site
		wp_redirect(Config::getEnv()["NEXT_URL"] . $wp->request);
		exit();
	}
}
