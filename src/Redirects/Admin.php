<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Redirects;

// No direct access
defined("ABSPATH") or die("Hey, do not do this 😱");

/**
 * Admin functions for redirects
 *
 * @package Bring\BlocksWP\Redirects
 * @since 2.0.1
 */
class Admin {
	/**
	 * Initialize the admin functions
	 * @return void
	 */
	public static function init() {
		self::columns();
		self::editView();
		self::save();
		self::styles();
		self::notices();
	}

	/**
	 * Register the columns for the redirect post type
	 * @return void
	 */
	private static function columns() {
		add_filter("manage_edit-redirect_columns", function ($columns) {
			unset($columns["date"]);

			$columns["from"] = __("Redirect From");
			$columns["to"] = __("Redirect To");
			$columns["status_code"] = __("Status Code");
			$columns["hits"] = __("Hits");

			return $columns;
		});

		add_action(
			"manage_redirect_posts_custom_column",
			function ($column, $post_id) {
				$redirect = new Redirect($post_id);
				if ($redirect == null) {
					return;
				}

				switch ($column) {
					case "from":
						echo $redirect->getFrom();
						break;
					case "to":
						echo $redirect->getTo();
						break;
					case "status_code":
						echo $redirect->getStatusCode();
						break;
					case "hits":
						echo $redirect->getHits();
						break;
					default:
						break;
				}
			},
			10,
			2,
		);
	}

	/**
	 * The edit view for the redirect post type
	 * @return void
	 */
	private static function editView() {
		add_action("add_meta_boxes", function () {
			add_meta_box(
				"redirect_meta_box",
				__("Redirect Details", "blocks-wp"),
				function ($post) {
					wp_nonce_field("save_redirect_meta", "redirect_meta_nonce");

					$redirect = new Redirect($post->ID);
					if ($redirect == null) {
						return;
					}
					?>

                    <p>
                        <label for="redirect_from"><?php _e("From URL", "blocks-wp"); ?></label>
                        <input type="text" name="redirect_from" id="redirect_from" placeholder="/sample-page" value="<?php echo esc_attr(
                        	$redirect->getFrom(),
                        ); ?>" class="widefat">
                        <span class="description"><?php _e(
                        	"Enter the URL you want to redirect from",
                        	"blocks-wp",
                        ); ?></span>
                    </p>

                    <p>
                        <label for="redirect_to"><?php _e("To URL", "blocks-wp"); ?></label>
                        <input type="text" name="redirect_to" id="redirect_to" placeholder="/another-sample-page" value="<?php echo esc_attr(
                        	$redirect->getTo(),
                        ); ?>" class="widefat">
                            <span class="description"><?php _e(
                            	"Enter the URL you want to redirect to",
                            	"blocks-wp",
                            ); ?></span>
                    </p>

                   <p>
                       <label for="redirect_status_code"><?php _e(
                       	"Status Code",
                       	"blocks-wp",
                       ); ?></label>
                       <select name="redirect_status_code" id="redirect_status_code">
                           <option value="307" <?php selected(
                           	$redirect->getStatusCode(),
                           	307,
                           ); ?>>307</option>
                           <option value="308" <?php selected(
                           	$redirect->getStatusCode(),
                           	308,
                           ); ?>>308</option>
                       </select>
                       <span class="description"><?php _e(
                       	"Choose the status code for the redirect",
                       	"blocks-wp",
                       ); ?></span>
                   </p>

                   <p>
                          <label for="redirect_hits"><?php _e("Hits", "blocks-wp"); ?></label>
                          <input type="text" disabled name="redirect_hits" id="redirect_hits" value="<?php echo esc_attr(
                          	(string) $redirect->getHits(),
                          ); ?>">
                          <span class="description"><?php _e(
                          	"Number of times this redirect has been accessed.",
                          	"blocks-wp",
                          ); ?></span>


                    </p>
                   <?php
				},
				"redirect",
				"normal",
				"default",
			);
		});
	}

	/**
	 * Styles to hide default post functions for redirects
	 * @return void
	 */
	private static function styles() {
		add_action("admin_head", function () {
			global $post_type;

			if ($post_type === "redirect") { ?>
            <style>
                #titlediv {
                    display: none;
                }

                .misc-pub-post-status,
                .misc-pub-visibility,
                .edit-post-status,
                .edit-visibility {
                    display: none !important;
                }
            </style>
            <?php }
		});
	}

	/**
	 * Save the redirect post type
	 * @return void
	 */
	private static function save() {
		add_filter(
			"wp_insert_post_data",
			function ($data, $postarr) {
				if ($data["post_type"] === "redirect") {
					if (empty($postarr["ID"])) {
						return $data;
					}

					$post_id = $postarr["ID"];
					$data["post_title"] = "Redirect #" . $post_id;
					$data["post_name"] = sanitize_title($data["post_title"]);

					$redirect = new Redirect($post_id);
					if ($redirect == null) {
						return $data;
					}

					if ($redirect->getStatusCode() !== 307 && $redirect->getStatusCode() !== 308) {
						$redirect->setStatusCode(307);
					}
				}

				return $data;
			},
			10,
			2,
		);

		add_action("save_post", function ($post_id) {
			if (
				!isset($_POST["redirect_meta_nonce"]) ||
				!wp_verify_nonce($_POST["redirect_meta_nonce"], "save_redirect_meta")
			) {
				return $post_id;
			}

			if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
				return $post_id;
			}

			if (isset($_POST["redirect_from"]) && empty($_POST["redirect_from"])) {
				set_transient("redirect_error", __("The 'from url' is required.", "blocks-wp"), 30);
				return $post_id;
			}

			if (isset($_POST["redirect_to"]) && empty($_POST["redirect_to"])) {
				set_transient("redirect_error", __("The 'to url' is required.", "blocks-wp"), 30);
				return $post_id;
			}

			$from_redirects = Redirect::getRedirectsByFromPermalink($_POST["redirect_from"]);
			foreach ($from_redirects as $redirect) {
				if ($redirect->getId() === $post_id) {
					continue;
				}

				if ($redirect->getFrom() === $_POST["redirect_from"]) {
					set_transient(
						"redirect_error",
						__(
							"The 'from url' has already a redirect. Please use a different one.",
							"blocks-wp",
						),
						30,
					);
					return $post_id;
				}
			}

			$to_redirects = Redirect::getRedirectsByFromPermalink($_POST["redirect_to"]);
			foreach ($to_redirects as $redirect) {
				if ($redirect->getId() === $post_id) {
					continue;
				}

				if ($redirect->getFrom() === $_POST["redirect_to"]) {
					set_transient(
						"redirect_error",
						__(
							"The 'to url' has already a redirect. Please use a different one.",
							"blocks-wp",
						),
						30,
					);
					return $post_id;
				}
			}

			$redirect = new Redirect($post_id);
			if ($redirect == null) {
				return $post_id;
			}

			if (isset($_POST["redirect_from"])) {
				$from = sanitize_text_field($_POST["redirect_from"]);
				$from = rtrim($from, "/");
				$from = ltrim($from, "/");
				$from = "/" . $from;
				$redirect->setFrom($from);
			}

			if (isset($_POST["redirect_to"])) {
				$to = sanitize_text_field($_POST["redirect_to"]);
				$to = rtrim($to, "/");
				$to = ltrim($to, "/");
				$to = "/" . $to;
				$redirect->setTo($to);
			}

			if (isset($_POST["redirect_status_code"])) {
				$redirect->setStatusCode(intval($_POST["redirect_status_code"]));
			}
		});
	}

	/**
	 * Display notices for the redirect post type
	 * @return void
	 */
	private static function notices() {
		add_action("admin_notices", function () {
			if ($error = get_transient("redirect_error")) {
				add_settings_error(
					"redirect",
					"redirect_exists",
					is_string($error) ? $error : __("An error occurred.", "blocks-wp"),
					"error",
				);
				settings_errors("redirect");
				delete_transient("redirect_error");
			}
		});

		add_filter("post_updated_messages", function ($messages) {
			global $post;

			$messages["redirect"] = [
				0 => "", // Unused. Messages start from index 1.
				1 => __("Redirect updated.", "blocks-wp"),
				4 => __("Redirect updated.", "blocks-wp"),
				5 => isset($_GET["revision"])
					? sprintf(
						__("Redirect restored to revision from %s", "blocks-wp"),
						wp_post_revision_title((int) $_GET["revision"], false),
					)
					: false,
				6 => __("Redirect published.", "blocks-wp"),
				7 => __("Redirect saved.", "blocks-wp"),
			];

			return $messages;
		});
	}
}
