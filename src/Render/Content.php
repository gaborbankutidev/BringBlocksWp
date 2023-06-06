<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Render;

use Bring\BlocksWP\Config;
use Bring\BlocksWP\Utils;

class Content {
	/**
	 * Adds main content object to content on bring blocks supported singular page
	 */
	public static function get_main(array $content, $post_id = 0) {
		if (!$post_id) {
			if (!is_singular()) {
				return $content;
			}
			$post_id = get_queried_object_id();
		}

		$post_type = get_post_type($post_id);
		$supported_post_types = Config::get_supported_post_types();

		// return if post type is not supported
		if (!in_array($post_type, $supported_post_types)) {
			return $content;
		}

		$main = get_post_meta($post_id, "bring_content_object", true);
		if ($main) {
			$content["main"] = $main;
		}

		return $content;
	}

	/**
	 * Adds default header content object to content
	 */
	public static function get_default_header(array $content) {
		$default_header_id = get_option("bring_default_header_id");
		// header_id not found
		if (!$default_header_id) {
			return $content;
		}

		$default_header = get_post_meta($default_header_id, "bring_content_object", true);
		// header not found
		if (!$default_header) {
			return $content;
		}

		$content["header"] = $default_header;

		return $content;
	}

	/**
	 * Adds default footer content object to content
	 */
	public static function get_default_footer(array $content) {
		$default_footer_id = get_option("bring_default_footer_id");
		// footer_id not found
		if (!$default_footer_id) {
			return $content;
		}

		$default_footer = get_post_meta($default_footer_id, "bring_content_object", true);
		// footer not found
		if (!$default_footer) {
			return $content;
		}

		$content["footer"] = $default_footer;

		return $content;
	}

	/**
	 * Adds default layout content object to content
	 * or redirects if onset for queried entity
	 */
	public static function get_default_layout(array $content) {
		// author, date, search, not_found
		$general_layout = Utils::is_general_layout();
		if ($general_layout) {
			//var_dump(get_post_types());
			//exit();
			return self::get_default_general_layout($content, $general_layout);
		}

		// taxonomy
		if (is_tax() || is_tag() || is_category()) {
			return self::get_default_taxonomy_layout($content);
		}

		// post
		if (is_singular()) {
			return self::get_default_post_layout($content);
		}
	}

	private static function get_default_general_layout(array $content, string $general_layout) {
		$default_layout_id = get_option("bring_default_" . $general_layout . "_layout_id");
		// layout_id not found
		if (!$default_layout_id) {
			wp_redirect("/");
			exit();
		}

		$default_layout = get_post_meta($default_layout_id, "bring_content_object", true);
		// layout not found
		if (!$default_layout) {
			wp_redirect("/");
			exit();
		}

		$content["layout"] = $default_layout;

		return $content;
	}

	public static function get_default_taxonomy_layout(array $content, $term_id = 0) {
		if (!$term_id) {
			$term_id = get_queried_object_id();
		}

		$term = get_term($term_id);
		$taxonomy = $term->taxonomy;

		$layout_taxonomies = Config::get_layout_taxonomies();

		// redirect if taxonomy is not supported
		if (!in_array($taxonomy, $layout_taxonomies)) {
			wp_redirect("/");
			exit();
		}

		$default_layout_id = get_option("bring_default_tax_" . $taxonomy . "_layout_id");
		// redirect if layout_id is unset for the taxonomy
		if (!$default_layout_id) {
			wp_redirect("/");
			exit();
		}

		$default_layout = get_post_meta($default_layout_id, "bring_content_object", true);
		// redirect if layout is unset for the taxonomy
		if (!$default_layout) {
			wp_redirect("/");
			exit();
		}

		$content["layout"] = $default_layout;

		return $content;
	}

	public static function get_default_post_layout(array $content, $post_id = 0) {
		if (!$post_id) {
			$post_id = get_queried_object_id();
		}
		$post_type = get_post_type($post_id);

		// get layout
		$layout_post_types = Config::get_layout_post_types();

		if (!in_array($post_type, $layout_post_types)) {
			return $content;
		}

		$default_layout_id = get_option("bring_default_pt_" . $post_type . "_layout_id");
		// layout_id not found
		if (!$default_layout_id) {
			return $content;
		}

		$default_layout = get_post_meta($default_layout_id, "bring_content_object", true);
		// layout not found
		if (!$default_layout) {
			return $content;
		}

		$content["layout"] = $default_layout;

		// `main` and `layout` shouldn't be undefined at the same type - todo
		return $content;
	}
}
