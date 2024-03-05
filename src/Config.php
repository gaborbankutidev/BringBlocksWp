<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

class Config {
	// Check if Config was initialized
	private static $is_initialized = false;

	// Static properties
	private static $layout = [
		"header" => false,
		"footer" => false,
		"layout" => false,
		"library" => false,
	];

	private static $thumbnail = false;

	private static $editor_post_types = ["post", "page"];
	private static $layout_post_types = ["post"];
	private static $layout_taxonomies = ["category", "post_tag"];

	private static $entity_props = [
		"entityId",
		"entityType",
		"entitySlug",
		"slug",
		"url",

		"name",
		"image",
		"excerpt",
		"description",
	];

	private static $forms = [];

	private static $blocks = ["postcontent"];

	// Private constructor & setter methods
	private function __construct() {
		self::$is_initialized = true;
	}

	public function useHeader($v = true) {
		self::$layout["header"] = $v;
		return $this;
	}
	public function useFooter($v = true) {
		self::$layout["footer"] = $v;
		return $this;
	}
	public function useLayout($v = true) {
		self::$layout["layout"] = $v;
		return $this;
	}
	public function useLibrary($v = true) {
		self::$layout["library"] = $v;
		return $this;
	}

	public function useThumbnail($v = true) {
		self::$thumbnail = $v;
		return $this;
	}

	public function editorPostTypes($post_types = []) {
		self::$editor_post_types = array_merge($post_types, self::$editor_post_types);
		return $this;
	}
	public function layoutPostTypes($post_types = []) {
		self::$layout_post_types = array_merge($post_types, self::$layout_post_types);
		return $this;
	}
	public function layoutTaxonomies($taxonomies = []) {
		self::$layout_taxonomies = array_merge($taxonomies, self::$layout_taxonomies);
		return $this;
	}

	public function entityProps($props) {
		self::$entity_props = array_merge($props, self::$entity_props);
		return $this;
	}

	public function blocks($blocks) {
		self::$blocks = array_merge($blocks, self::$blocks);
		return $this;
	}

	public function forms($forms) {
		self::$forms = $forms;
		return $this;
	}

	// Init & Public static getter methods
	public static function init() {
		if (self::$is_initialized) {
			return;
		}

		return new self();
	}

	public static function getLayout() {
		return self::$layout;
	}

	public static function getEditorPostTypes($without_layout_post_types = false) {
		$layout_post_types = [];
		foreach (self::$layout as $pt => $v) {
			$v && ($layout_post_types[] = "bring_$pt");
		}

		$editor_post_types = $without_layout_post_types
			? self::$editor_post_types
			: array_merge(self::$editor_post_types, $layout_post_types);

		return apply_filters("bring_blocks_editor_post_types", $editor_post_types);
	}
	public static function getLayoutPostTypes() {
		return apply_filters("bring_blocks_layout_post_types", self::$layout_post_types);
	}
	public static function getLayoutTaxonomies() {
		return apply_filters("bring_blocks_layout_taxonomies", self::$layout_taxonomies);
	}

	public static function getEntityProps() {
		return apply_filters("bring_blocks_entity_props", self::$entity_props);
	}

	public static function getBlocks() {
		return self::$blocks;
	}

	public static function getForms() {
		return apply_filters("bring_blocks_forms", self::$forms);
	}

	public static function getThumbnailSupport() {
		return self::$thumbnail;
	}
}
