<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use Bring\BlocksWP\Utils;
use WP_Error;

class Props {
	/**
	 * @param string $entity_type TODO: should be swapped to an enum
	 * @param int $entity_id
	 * @param array{custom_data: array<string,mixed>} $options
	 * @return array<string, mixed>|null
	 */
	public static function getDynamicProps($entity_type, $entity_id, $options) {
		// author
		if ($entity_type == "author") {
			// TODO author support
		}

		// taxonomy
		if ($entity_type == "taxonomy") {
			$term_props = Utils\Props::getDefaultTaxonomyEntityProps($entity_id);
			$term = get_term($entity_id);
			if ($term instanceof WP_Error || $term === null) {
				return null;
			}
			$entity_slug = $term->taxonomy;

			return Filter::props(
				$term_props ? $term_props : [],
				"props",
				[
					"id" => $entity_id,
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);
		}

		// post
		if ($entity_type == "post") {
			$post_props = Utils\Props::getDefaultPostEntityProps($entity_id);
			$post = get_post($entity_id);
			if ($post === null) {
				return null;
			}
			$entity_slug = $post->post_type;

			return Filter::props(
				$post_props ? $post_props : [],
				"props",
				[
					"id" => $entity_id,
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$options["custom_data"],
			);
		}

		return null;
	}
}
