<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Utils;

use WP_Post;
use WP_Term;

class Props {
	/**
	 * Returns entity props for the entity with the given id and type
	 * @param int $entity_id
	 * @param string $entity_type  TODO: should be swapped to an enum
	 * @return array{
	 * 		name:string,
	 * 		image:array{
	 * 			src:string,
	 * 			alt:string,
	 * 			id:int|null
	 * 		},
	 * 		excerpt:string|null,
	 * 		description:string|null,
	 * 		slug:string,
	 * 		url:string,
	 *		entityType:string,
	 * 		entitySlug:string,
	 * 		entityId:int
	 * 		}|null
	 */
	public static function getDefaultEntityProps($entity_id, $entity_type) {
		switch ($entity_type) {
			case "post":
				return self::getDefaultPostEntityProps($entity_id);
			case "taxonomy":
				return self::getDefaultTaxonomyEntityProps($entity_id);
			case "author":
				return self::getDefaultAuthorEntityProps($entity_id);
			default:
				return null;
		}
	}

	/**
	 * @param int $entity_id
	 * @return array{
	 * 		name:string,
	 * 		image:array{
	 * 			src:string,
	 * 			alt:string,
	 * 			id:int|null
	 * 		},
	 * 		excerpt:string|null,
	 * 		description:string|null,
	 * 		slug:string,
	 * 		url:string,
	 *		entityType:string,
	 * 		entitySlug:string,
	 * 		entityId:int
	 * 		}|null
	 */
	public static function getDefaultAuthorEntityProps($entity_id) {
		/**
		 * @var array<string,mixed>
		 */
		$user_meta = get_user_meta($entity_id);
		if (!$user_meta) {
			return null;
		}

		$first_name = $user_meta["first_name"];
		$last_name = $user_meta["last_name"];

		$name =
			(is_string($first_name) ? $first_name : "") .
			" " .
			(is_string($last_name) ? $last_name : "");
		$image = General::getEntityImage($entity_id, "author", "full");

		$excerpt_meta = get_user_meta($entity_id, "excerpt", true);
		$excerpt = is_string($excerpt_meta) ? $excerpt_meta : null;

		$description_meta = $user_meta["description"];
		$description = is_string($description_meta) ? $description_meta : null;
		$url = General::getEntityUrl($entity_id, "author");
		$entity_type = "author";

		return [
			"name" => $name,
			"image" => $image,
			"description" => $description,
			"excerpt" => $excerpt,
			"slug" => "",
			"url" => $url,
			"entityType" => $entity_type,
			"entitySlug" => "",
			"entityId" => $entity_id,
		];
	}

	/**
	 * @param int $entity_id
	 * @return array{
	 * 		name:string,
	 * 		image:array{
	 * 			src:string,
	 * 			alt:string,
	 * 			id:int|null
	 * 		},
	 * 		excerpt:string|null,
	 * 		description:string|null,
	 * 		slug:string,
	 * 		url:string,
	 * 		entityType:string,
	 * 		entitySlug:string,
	 * 		entityId:int
	 * 		}|null
	 */
	public static function getDefaultTaxonomyEntityProps($entity_id) {
		$term = get_term($entity_id);
		if (!$term instanceof WP_Term) {
			return null;
		}

		$entity_slug = $term->taxonomy;
		$name = $term->name;
		$image = General::getEntityImage($entity_id, "taxonomy", "full");
		$slug = $term->slug;
		$url = General::getEntityUrl($entity_id, "taxonomy");

		$excerpt_meta = get_term_meta($entity_id, "excerpt", true);
		$excerpt = is_string($excerpt_meta) ? $excerpt_meta : null;
		$description = $term->description ? $term->description : null;
		$entity_type = "taxonomy";

		return [
			"name" => $name,
			"image" => $image,
			"excerpt" => $excerpt,
			"description" => $description,
			"slug" => $slug,
			"url" => $url,
			"entityType" => $entity_type,
			"entitySlug" => $entity_slug,
			"entityId" => $entity_id,
		];
	}

	/**
	 * @param int $entity_id
	 * @return array{
	 * 		name:string,
	 * 		image:array{
	 * 			src:string,
	 * 			alt:string,
	 * 			id:int|null
	 * 		},
	 *      slug: string,
	 * 		excerpt:string|null,
	 * 		description:string|null,
	 * 		url:string,
	 * 		entityType:string,
	 * 		entitySlug:string,
	 * 		entityId:int
	 * 		}|null
	 */
	public static function getDefaultPostEntityProps($entity_id) {
		$post = get_post($entity_id);

		if (!$post instanceof WP_Post) {
			return null;
		}

		$entity_slug = $post->post_type;

		$name = $post->post_title;
		$image = General::getEntityImage($entity_id, "post", "full");

		$excerpt_meta = $post->post_excerpt;
		$excerpt = $excerpt_meta ? $excerpt_meta : null;

		$description_meta = get_post_meta($entity_id, "description", true);
		$description = is_string($description_meta) ? $description_meta : null;

		$slug = $post->post_name;

		$url = General::getEntityUrl($entity_id);

		$entity_type = "post";

		return [
			"name" => $name,
			"image" => $image,
			"slug" => $slug,
			"excerpt" => $excerpt,
			"description" => $description,
			"url" => $url,
			"entityType" => $entity_type,
			"entitySlug" => $entity_slug,
			"entityId" => $entity_id,
		];
	}
}
