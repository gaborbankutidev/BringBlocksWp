<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use WP_Term;

class Render {
	/**
	 * @return array<string,mixed>
	 */
	public static function renderEntity() {
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
