<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use Bring\BlocksWP\Render\Content;
use Bring\BlocksWP\Render\Props;
use Bring\BlocksWP\Render\Site;

class Render {
	public static function init() {
		//add_shortcode("bringRenderContent", self::render_content(...));
		add_filter("the_content", self::render_content(...), 99);
		add_action("wp", self::get_content_for_csr(...));

		/* remove wordpress hooks that manipulate the content before hydration */

		// add_filter("bring_disable_hydration", "__return_true");
		remove_action("wp_footer", "the_block_template_skip_link");
		// Removes the decoding attribute from images added inside post content.
		add_filter("wp_img_tag_add_decoding_attr", "__return_false");
		// Remove the decoding attribute from featured images and the Post Image block.
		add_filter("wp_get_attachment_image_attributes", function ($attributes) {
			unset($attributes["decoding"]);
			return $attributes;
		});
	}

	private static function render_content($content) {
		// FIXME: unused variable
		// return if bring cache
		if (Cache::is_bring_cache()) {
			return "<div id='bringCache'></div>";
		}

		// header
		$header = "";
		$default_header_id = get_option("bring_default_header_id");
		if ($default_header_id) {
			$default_header = get_post_meta($default_header_id, "bring_content_html", true);
			if ($default_header) {
				$default_header = stripcslashes(base64_decode($default_header));
				$header = "<header>$default_header</header>";
			}
		}

		// footer
		$footer = "";
		$default_footer_id = get_option("bring_default_footer_id");
		if ($default_footer_id) {
			$default_footer = get_post_meta($default_footer_id, "bring_content_html", true);
			if ($default_footer) {
				$default_footer = stripcslashes(base64_decode($default_footer));
				$footer = "<footer>$default_footer</footer>";
			}
		}

		// main
		$entity_id = get_queried_object_id();
		$main = "";

		if (is_author()) {
			// TODO author support
		}

		if (is_tax() || is_tag() || is_category()) {
			$main = stripcslashes(base64_decode(get_term_meta($entity_id, "bring_content_html", true)));
		}

		if (is_singular()) {
			$main = stripcslashes(base64_decode(get_post_meta($entity_id, "bring_content_html", true)));
		}

		$disable_hydration = apply_filters("bring_disable_hydration", false)
			? "data-disable-hydration='1'"
			: "";

		return "<div id='bringContent' $disable_hydration>$header<main>$main</main>$footer</div>";
	}

	public static function get_bring_cache() {
		// handle header and footer for cache service
		$header_or_footer_id = Cache::header_or_footer_id();
		if ($header_or_footer_id) {
			$header_or_footer = get_post_meta($header_or_footer_id, "bring_content_object", true);
			if ($header_or_footer) {
				return [
					"siteProps" => Site::get_site_props(),
					"entityContent" => [
						"main" => $header_or_footer,
					],
					"entityProps" => Props::get_entity_props(),
					"dynamicCache" => apply_filters("bring_dynamic_cache", []),
				];
			}
		}

		// render cache for client
		$content = [
			"main" => [],
		];

		$content = Content::get_main($content);

		// get default header & footer
		$content = Content::get_default_header($content);
		$content = Content::get_default_footer($content);

		// get layout
		$content = Content::get_default_layout($content);

		// this return is probably unnecessary
		return [
			"siteProps" => Site::get_site_props(),
			"entityContent" => $content,
			"entityProps" => Props::get_entity_props(),
			"dynamicCache" => apply_filters("bring_dynamic_cache", []),
		];
	}

	public static function get_content_for_csr() {
		if (is_admin() || !isset($_GET["bringCSR"]) || !$_GET["bringCSR"] == 1) {
			return;
		}

		// render cache for client
		$content = [
			"main" => [],
		];

		$content = Content::get_main($content);

		// get default header & footer
		$content = Content::get_default_header($content);
		$content = Content::get_default_footer($content);

		// get layout
		$content = Content::get_default_layout($content);

		// todo return content & props
		wp_send_json(
			[
				"bringCSR" => true,
				"entityContent" => $content,
				"entityProps" => Props::get_entity_props(),
			],
			200,
		);
	}
}
