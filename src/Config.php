<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Exception;

class Config {
	/**
	 * Check if Config was initialized
	 * @var bool
	 */
	private static $is_initialized = false;

	/**
	 * Static properties
	 * @var array{header:bool,footer:bool,layout:bool,library:bool}
	 */
	private static $layout = [
		"header" => false,
		"footer" => false,
		"layout" => false,
		"library" => false,
	];

	/**
	 * @var bool
	 */
	private static $rank_math = false;

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
	 * @var array{DATA_TOKEN:string,JWT_SECRET_KEY:string,NEXT_URL:string}|null
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
	 * @param bool $v
	 * @return Config
	 */
	public function useRankMath($v = true) {
		self::$rank_math = $v;
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
	 * @param array{DATA_TOKEN:string,JWT_SECRET_KEY:string,NEXT_URL:string} $env
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
	 * @return array{header:bool,footer:bool,layout:bool,library:bool}
	 */
	public static function getLayout() {
		return self::$layout;
	}

	/**
	 * @param bool $without_layout_post_types
	 * @return array<string>
	 */
	public static function getEditorPostTypes($without_layout_post_types = false) {
		$layout_post_types = [];
		foreach (self::$layout as $pt => $v) {
			$v && ($layout_post_types[] = "bring_$pt");
		}

		$editor_post_types = $without_layout_post_types
			? self::$editor_post_types
			: array_merge(self::$editor_post_types, $layout_post_types);

		$filtered_result = apply_filters("bring_blocks_editor_post_types", $editor_post_types);
		/**
		 * @var array<string> TODO: This should be fixed without typecasting
		 */
		$filtered = is_array($filtered_result) ? $filtered_result : [];
		return $filtered;
	}

	/**
	 * @return array<string>
	 */
	public static function getLayoutPostTypes() {
		$filtered_result = apply_filters("bring_blocks_layout_post_types", self::$layout_post_types);
		/**
		 * @var array<string> TODO: This should be fixed without typecasting
		 */
		$filtered = is_array($filtered_result) ? $filtered_result : [];
		return $filtered;
	}

	/**
	 * @return array<string>
	 */
	public static function getLayoutTaxonomies() {
		$filtered_result = apply_filters("bring_blocks_layout_taxonomies", self::$layout_taxonomies);
		/**
		 * @var array<string> TODO: This should be fixed without typecasting
		 */
		$filtered = is_array($filtered_result) ? $filtered_result : [];
		return $filtered;
	}

	/**
	 * @return array<string>
	 */
	public static function getEntityProps() {
		$filtered_result = apply_filters("bring_blocks_entity_props", self::$entity_props);
		/**
		 * @var array<string> TODO: This should be fixed without typecasting
		 */
		$filtered = is_array($filtered_result) ? $filtered_result : [];
		return $filtered;
	}

	/**
	 * @return array<string>
	 */
	public static function getBlocks() {
		return self::$blocks;
	}

	/**
	 * @return array<string>
	 */
	public static function getForms() {
		$filtered_result = apply_filters("bring_blocks_forms", self::$forms);
		/**
		 * @var array<string> TODO: This should be fixed without typecasting
		 */
		$filtered = is_array($filtered_result) ? $filtered_result : [];
		return $filtered;
	}

	/**
	 * @return array{DATA_TOKEN:string,JWT_SECRET_KEY:string,NEXT_URL:string}
	 */
	public static function getEnv() {
		if (self::$env === null) {
			throw new Exception(
				"Environment variables are not initialized! Did you forget to forget to set them with Config::init()...->env(...)?",
			);
		}
		return self::$env;
	}

	/**
	 * @return bool
	 */
	public static function getRankMath() {
		return self::$rank_math;
	}
}
