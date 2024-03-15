<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use WP_Term;

class Render {
	/**
	 * @return void
	 */
	public static function init() {
		add_action("wp", self::render(...));

		// TODO handle redirects
		/* if (is_post_type_archive()) {
			wp_redirect("/");
			exit();
		} */
	}

	/**
	 * @return void
	 */
	public static function render() {
		if (is_admin()) {
			return;
		}

		if (isset($_GET["bypass"]) && $_GET["bypass"] === "1") {
			return;
		}

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
				],
			],
			200,
		);
	}
}
