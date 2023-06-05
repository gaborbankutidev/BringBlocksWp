<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

class Config {
	public static function get_jwt_secret_key() {
		return defined("BRING_JWT_SECRET_KEY")	
		? BRING_JWT_SECRET_KEY // TODO: find a way to mute this type of error
			: "jwt-secret-key-sample"; // TODO: probably throw error here
	}

	// return bring blocks supported post types
	public static function get_supported_post_types(
		$without_bring_post_types = false,
	) {
		$bring_post_types = $without_bring_post_types
			? []
			: ["bring_header", "bring_footer", "bring_layout"]; // can not be removed

		$bring_custom_post_types = apply_filters("bring_blocks_post_types", [
			"post",
			"page",
		]);

		return array_merge($bring_post_types, $bring_custom_post_types);
	}

	public static function get_layout_post_types() {
		return apply_filters("bring_blocks_layout_post_types", ["post"]);
	}

	public static function get_layout_taxonomies() {
		return apply_filters("bring_blocks_layout_taxonomies", [
			"category",
			"post_tag",
		]);
	}

	public static function get_entity_props() {
		$default_entity_props = [
			"entityId",
			"entityType",
			"entitySlug",
			"name",
			"image",
			"excerpt",
			"description",
			"slug",
			"url",
		];
		return apply_filters("bring_blocks_entity_props", $default_entity_props);
	}

	public static function get_form_fields($form) { // FIXME: unused variable
		return apply_filters("bring_supported_forms", []);
	}
}
