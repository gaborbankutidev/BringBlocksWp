<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Dynamic;

use Bring\BlocksWP\Utils;

class Props {
	public static function getDynamicProps($entity_type, $entity_id, $custom_data = []) {
		// author
		if ($entity_type == "author") {
			// TODO author support
		}

		// taxonomy
		if ($entity_type == "taxonomy") {
			$term_props = Utils\Props::getDefaultTaxonomyEntityProps($entity_id);
			$entity_slug = get_term($entity_id)->taxonomy;

			return Filter::apply(
				$term_props,
				"props",
				[
					"id" => $entity_id,
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$custom_data,
			);
		}

		// post
		if ($entity_type == "post") {
			$post_props = Utils\Props::getDefaultPostEntityProps($entity_id);
			$entity_slug = get_post($entity_id)->post_type;

			return Filter::apply(
				$post_props,
				"props",
				[
					"id" => $entity_id,
					"slug" => $entity_slug,
					"type" => $entity_type,
				],
				$custom_data,
			);
		}

		return null;
	}
}
