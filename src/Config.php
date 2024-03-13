<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Exception;

/**
 * @phpstan-type Env array{DATA_TOKEN:string,JWT_SECRET_KEY:string,NEXT_URL:string}
 * @phpstan-type Layout array{header:bool,footer:bool,layout:bool,library:bool}
 */

class Config {
	/**
	 * Check if Config was initialized
	 * @var bool
	 */
	private static $is_initialized = false;

	/**
	 * Static properties
	 * @var Layout
	 */
	private static $layout = [
		"header" => false,
		"footer" => false,
		"layout" => false,
		"library" => false,
	];

	/**
	 * @var array<string>
	 */
	private static $editor_post_types = ["post", "page"];
	/**
	 * @var array<string>
	 */
	private static $layout_post_types = ["post"];
	/**
	 * @var array<string>
	 */
	private static $layout_taxonomies = ["category", "post_tag"];

	/**
	 * @var array<string>
	 */
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

	/**
	 * @var array<string>
	 */
	private static $forms = [];

	/**
	 * @var array<string>
	 */
	private static $blocks = ["postcontent"];

	/**
	 * @var Env|null
	 */
	private static $env = null;

	// Private constructor & setter methods
	private function __construct() {
		self::$is_initialized = true;
	}

	/**
	 * @param bool $v
	 * @return Config
	 */
	public function useHeader($v = true) {
		self::$layout["header"] = $v;
		return $this;
	}

	/**
	 * @param bool $v
	 * @return Config
	 */
	public function useFooter($v = true) {
		self::$layout["footer"] = $v;
		return $this;
	}

	/**
	 * @param bool $v
	 * @return Config
	 */
	public function useLayout($v = true) {
		self::$layout["layout"] = $v;
		return $this;
	}

	/**
	 * @param bool $v
	 * @return Config
	 */
	public function useLibrary($v = true) {
		self::$layout["library"] = $v;
		return $this;
	}

	/**
	 * @param array<string> $post_types
	 * @return Config
	 */
	public function editorPostTypes($post_types = []) {
		self::$editor_post_types = array_merge($post_types, self::$editor_post_types);
		return $this;
	}

	/**
	 * @param array<string> $post_types
	 * @return Config
	 */
	public function layoutPostTypes($post_types = []) {
		self::$layout_post_types = array_merge($post_types, self::$layout_post_types);
		return $this;
	}

	/**
	 * @param array<string> $taxonomies
	 * @return Config
	 */
	public function layoutTaxonomies($taxonomies = []) {
		self::$layout_taxonomies = array_merge($taxonomies, self::$layout_taxonomies);
		return $this;
	}

	/**
	 * @param array<string> $props
	 * @return Config
	 */
	public function entityProps($props) {
		self::$entity_props = array_merge($props, self::$entity_props);
		return $this;
	}

	/**
	 * @param array<string> $blocks
	 * @return Config
	 */
	public function blocks($blocks) {
		self::$blocks = array_merge($blocks, self::$blocks);
		return $this;
	}

	/**
	 * @param array<string> $forms
	 * @return Config
	 */
	public function forms($forms) {
		self::$forms = $forms;
		return $this;
	}

	/**
	 * @param Env $env
	 * @return Config
	 */
	public function env($env) {
		self::$env = $env;
		return $this;
	}

	// Init & Public static getter methods
	/**
	 * @return Config
	 */
	public static function init() {
		if (self::$is_initialized) {
			throw new Exception("Already initialized"); // TODO
		}

		return new self();
	}

	/**
	 * @return Layout
	 */
	public static function getLayout() {
		return self::$layout;
	}

	/**
	 * @param bool $without_layout_post_types
	 * @return mixed
	 */
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

	/**
	 * @return mixed
	 */
	public static function getLayoutPostTypes() {
		return apply_filters("bring_blocks_layout_post_types", self::$layout_post_types);
	}

	/**
	 * @return mixed
	 */
	public static function getLayoutTaxonomies() {
		return apply_filters("bring_blocks_layout_taxonomies", self::$layout_taxonomies);
	}

	/**
	 * @return mixed
	 */
	public static function getEntityProps() {
		return apply_filters("bring_blocks_entity_props", self::$entity_props);
	}

	/**
	 * @return array<string>
	 */
	public static function getBlocks() {
		return self::$blocks;
	}

	/**
	 * @return mixed
	 */
	public static function getForms() {
		return apply_filters("bring_blocks_forms", self::$forms);
	}

	/**
	 * @return Env
	 */
	public static function getEnv() {
		if (self::$env === null) {
			throw new Exception("No env"); // TODO
		}
		return self::$env;
	}
}
