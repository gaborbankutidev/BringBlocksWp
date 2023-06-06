<?php

declare(strict_types=1);

namespace Bring\BlocksWP;

use WP_REST_Request;
use WP_REST_Response;

class Form {
	public static function init() {
		add_action("init", self::register(...));
		add_action("rest_api_init", self::register_api_routes(...));
		add_action("add_meta_boxes", self::add_form_data_metabox(...));
	}

	private static function register() {
		/*
		 * Post type: Bring form submissions
		 */
		register_post_type("bring_form_subm", [
			"labels" => [
				"name" => "Form submissions",
				"singular_name" => "Form submission",
			],
			"description" => "",
			"public" => false,
			"publicly_queryable" => false,
			"show_ui" => true,
			"show_in_rest" => false,
			"rest_base" => "",
			"rest_controller_class" => "WP_REST_Posts_Controller",
			"rest_namespace" => "wp/v2",
			"has_archive" => false,
			"show_in_menu" => true,
			"show_in_nav_menus" => false,
			"delete_with_user" => false,
			"exclude_from_search" => true,
			"capability_type" => "post",
			"map_meta_cap" => true,
			"hierarchical" => false,
			"can_export" => false,
			"query_var" => true,
			"menu_icon" => "dashicons-feedback",
			"supports" => ["title"],
			"show_in_graphql" => false,
			"show_in_admin_bar" => true,
		]);

		register_post_meta("bring_form_subm", "form_data", [
			"type" => "string",
			"description" => "Raw data from submitted form",
			"single" => true,
			"show_in_rest" => false, // Show in the WP REST API response. Default: false.
		]);

		register_post_meta("bring_form_subm", "form_name", [
			"type" => "string",
			"description" => "Form name",
			"single" => true,
			"show_in_rest" => false,
		]);
	}

	private static function register_api_routes() {
		// options for controls in editor
		register_rest_route("bring-form", "/submit", [
			"methods" => "POST",
			"callback" => self::submit(...),
			"permission_callback" => "__return_true", // todo add token and disable cors
		]);
	}

	private static function submit(WP_REST_Request $request) {
		$request_body = $request->get_json_params();

		// return if form name is not set
		if (!isset($request_body["formName"])) {
			return new WP_REST_Response(
				[
					"success" => false,
					"message" => "Form name is required",
				],
				400,
			);
		}

		$form_name = sanitize_text_field($request_body["formName"]);

		// return if form name is not supported
		if (!in_array($form_name, apply_filters("bring_supported_forms", []))) {
			return new WP_REST_Response(
				[
					"success" => false,
					"message" => "Form is not supported",
				],
				400,
			);
		}

		// return if form fields is wrong format
		if (!is_array($request_body["formData"])) {
			return new WP_REST_Response(
				[
					"success" => false,
					"message" => "Wrong data",
				],
				400,
			);
		}
		$submitted_fields = $request_body["formData"];

		$registered_fields = apply_filters("bring_" . $form_name . "_form_fields", []);
		$form_data = [];
		foreach ($registered_fields as $key => $registered_field) {
			$field_name = $registered_field["name"];
			if (!isset($submitted_fields[$field_name])) {
				// return if not set but required
				if ($registered_field["required"]) {
					return new WP_REST_Response(
						[
							"success" => false,
							"message" => $field_name . " field is required!",
						],
						400,
					);
				}

				// continue if not required
				continue;
			}

			$form_data[$field_name] = sanitize_text_field($submitted_fields[$field_name]);
		}

		$submission_id = wp_insert_post([
			"post_type" => "bring_form_subm",
			"post_title" => $form_name . " form - " . date("Y-m-d H:i:s"),
			"post_status" => "publish",
		]);

		if (!$submission_id) {
			return new WP_REST_Response(
				[
					"success" => false,
					"message" => "Error while creating form submission.",
				],
				500,
			);
		}

		if (
			!update_post_meta($submission_id, "form_name", $form_name) ||
			!update_post_meta($submission_id, "form_data", serialize($form_data))
		) {
			return new WP_REST_Response(
				[
					"success" => false,
					"message" => "Error while saving form data",
				],
				500,
			);
		}

		do_action("bring_form_submission", $submission_id);
		do_action("bring_form_submission_$form_name", $submission_id);
		self::send_mails($submission_id, $form_data, $form_name);

		return new WP_REST_Response(
			[
				"success" => true,
			],
			201,
		);
	}

	private static function send_mails($submission_id, $form_data, $form_name) {
		$form_submission_mails = apply_filters(
			"bring_form_submission_mails",
			[],
			$submission_id,
			$form_data,
			$form_name,
		);
		$form_submission_mails = apply_filters(
			"bring_form_submission_mails_$form_name",
			$form_submission_mails,
			$submission_id,
			$form_data,
		);

		if (!$form_submission_mails || !count($form_submission_mails)) {
			return;
		}

		foreach ($form_submission_mails as $mail) {
			$headers = [];
			$headers[] = "From: {$mail["from"]["name"]}<{$mail["from"]["email"]}>";
			if (isset($mail["reply"])) {
				$headers[] = "Reply-To: {$mail["reply"]["name"]} <{$mail["reply"]["email"]}>";
			}

			add_filter("wp_mail_content_type", function ($content_type) {
				return "text/html";
			});

			wp_mail(
				$mail["emails"],
				$mail["subject"],
				$mail["body"]($submission_id, $form_data, $form_name),
				$headers,
			);

			// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
			remove_filter("wp_mail_content_type", "set_html_content_type");
		}
	}

	private static function add_form_data_metabox() {
		add_meta_box(
			"form_submission_data_metabox",
			"Form data",
			self::render_form_data_metabox(...),
			"bring_form_subm",
			"normal",
			"high",
			null,
		);
	}

	private static function render_form_data_metabox($form_submission) {
		$form_name = get_post_meta($form_submission->ID, "form_name", false);
		echo "<div style='margin-bottom: 16px;'>Form name: $form_name</div>";

		$form_data = get_post_meta($form_submission->ID, "form_data");
		if (!$form_data || !is_array($form_data) || !count($form_data)) {
			return;
		}

		$form_data = unserialize($form_data[0]);
		if (!$form_data || !is_array($form_data) || !count($form_data)) {
			return;
		}

		$content = "";
		foreach ($form_data as $name => $value) {
			$content .= "
				<div style='display: flex;'>
					<div style='width: 200px; font-weight:600;'>$name</div>
					<div>$value</div>
				</div>
			";
		}

		echo "
            <div>
                $content
            </div>
        ";
	}
}
