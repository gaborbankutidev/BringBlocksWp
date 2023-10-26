<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Utils;

class Props {
	/**
	 * Returns entity props for the entity with the given id and type
	 */
	public static function getDefaultEntityProps($entityId, $entityType) {
		switch ($entityType) {
			case "post":
				return self::getDefaultPostEntityProps($entityId);
			case "taxonomy":
				return self::getDefaultTaxonomyEntityProps($entityId);
			case "author":
				return self::getDefaultAuthorEntityProps($entityId);
			default:
				return [];
		}
	}

	public static function getDefaultAuthorEntityProps($entity_id) {
		$entity_props = [];
		$user_meta = get_user_meta($entity_id);
		$first_name = $user_meta->first_name;
		$last_name = $user_meta->last_name;

		$entity_props["name"] = "$first_name $last_name";
		$entity_props["image"] = General::getEntityImage($entity_id, "author", "full");

		$excerpt = get_user_meta($entity_id, "excerpt", true);
		if ($excerpt) {
			$entity_props["excerpt"] = $excerpt;
		}

		$entity_props["description"] = $user_meta->description;
		$entity_props["url"] = get_author_posts_url($entity_id);

		$entity_props["entityType"] = "author";
		$entity_props["entityId"] = $entity_id;

		return $entity_props;
	}

	public static function getDefaultTaxonomyEntityProps($entity_id) {
		$entity_props = [];
		$term = get_term($entity_id);
		$entity_slug = $term->taxonomy;

		$entity_props["name"] = $term->name;
		$entity_props["image"] = General::getEntityImage($entity_id, "taxonomy", "full");

		$excerpt = get_term_meta($entity_id, "excerpt", true);
		if ($excerpt) {
			$entity_props["excerpt"] = $excerpt;
		}

		if ($term->description) {
			$entity_props["description"] = $term->description;
		}

		$entity_props["slug"] = $term->slug;
		$entity_props["url"] = get_term_link($term);

		$entity_props["entityType"] = "taxonomy";
		$entity_props["entitySlug"] = $entity_slug;
		$entity_props["entityId"] = $entity_id;

		return $entity_props;
	}

	public static function getDefaultPostEntityProps($entity_id) {
		$entity_props = [];
		$post = get_post($entity_id);
		$entity_slug = $post->post_type;

		$entity_props["name"] = $post->post_title;
		$entity_props["image"] = General::getEntityImage($entity_id, "post", "full");

		if ($post->post_excerpt) {
			$entity_props["excerpt"] = $post->post_excerpt;
		}

		$description = get_post_meta($entity_id, "description", true);
		if ($description) {
			$entity_props["description"] = $description;
		}

		$entity_props["slug"] = $post->post_name;
		$entity_props["url"] = get_permalink($post);

		$entity_props["entityType"] = "post";
		$entity_props["entitySlug"] = $entity_slug;
		$entity_props["entityId"] = $entity_id;

		return $entity_props;
	}
}
