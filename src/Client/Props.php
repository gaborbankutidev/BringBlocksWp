<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Client;

use Bring\BlocksWP\Utils;
use Bring\BlocksWP\Config;

class Props {
	/**
	 * Returns entity props for the entity with the given id and type
	 */
	public static function getEntityProps($entityId, $entityType) {
		switch ($entityType) {
			case "post":
				return self::getPostEntityProps($entityId);
			case "taxonomy":
				return self::getTaxonomyEntityProps($entityId);
			case "author":
				return self::getAuthorEntityProps($entityId);
			default:
				return [];
		}
	}

	public static function getAuthorEntityProps($entity_id) {
		$entity_props = Utils\Props::getDefaultAuthorEntityProps($entity_id);

		$entity_props = apply_filters("bring_entity_props", $entity_props, $entity_id, "author", "");

		$entity_props = apply_filters("bring_author_props", $entity_props, $entity_id);

		$entity_props["entityType"] = "author";
		$entity_props["entityId"] = $entity_id;

		return self::filterEntityProps($entity_props);
	}

	public static function getTaxonomyEntityProps($entity_id) {
		$entity_props = Utils\Props::getDefaultTaxonomyEntityProps($entity_id);
		$entity_slug = get_term($entity_id)->taxonomy;

		$entity_props = apply_filters(
			"bring_entity_props",
			$entity_props,
			$entity_id,
			"taxonomy",
			$entity_slug,
		);

		$entity_props = apply_filters("bring_taxonomy_props", $entity_props, $entity_id, $entity_slug);

		$entity_props = apply_filters("bring_taxonomy_props_$entity_slug", $entity_props, $entity_id);

		$entity_props["entityType"] = "taxonomy";
		$entity_props["entitySlug"] = $entity_slug;
		$entity_props["entityId"] = $entity_id;

		return self::filterEntityProps($entity_props);
	}

	public static function getPostEntityProps($entity_id) {
		$entity_props = Utils\Props::getDefaultPostEntityProps($entity_id);
		$entity_slug = get_post($entity_id)->post_type;

		$entity_props = apply_filters(
			"bring_entity_props",
			$entity_props,
			$entity_id,
			"post",
			$entity_slug,
		);

		$entity_props = apply_filters("bring_post_props", $entity_props, $entity_id, $entity_slug);

		$entity_props = apply_filters("bring_post_props_$entity_slug", $entity_props, $entity_id);

		$entity_props["entityType"] = "post";
		$entity_props["entitySlug"] = $entity_slug;
		$entity_props["entityId"] = $entity_id;

		return self::filterEntityProps($entity_props);
	}

	// remove unsupported entity props and set empty values to null
	private static function filterEntityProps($entity_props) {
		$supported_entity_props = Config::getEntityProps();
		$filtered_entity_props = [];
		foreach ($supported_entity_props as $prop) {
			if (!isset($entity_props[$prop])) {
				$filtered_entity_props[$prop] = null;
			} else {
				$filtered_entity_props[$prop] = $entity_props[$prop];
			}
		}

		return $filtered_entity_props;
	}
}
