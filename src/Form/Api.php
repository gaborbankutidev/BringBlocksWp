<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Form;

use WP_REST_Request;
use WP_REST_Response;
use Bring\BlocksWP\Config;

class Api {
	/**
	 * @return void
	 */
	public static function init() {
		add_action("rest_api_init", self::routes(...));
	}

	/**
	 * @return void
	 */
	private static function routes() {
		// options for controls in editor
		register_rest_route("bring", "/form/submit", [
			"methods" => "POST",
			"callback" => self::submit(...),
			"permission_callback" => "__return_true", // TODO add token and disable cors
		]);
	}

	/**
	 * @param WP_REST_Request<array<mixed>> $request
	 * @return WP_REST_Response
	 */
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
		if (!in_array($form_name, Config::getForms())) {
			return new WP_REST_Response(
				[
					"success" => false,
					"message" => "Form is not supported",
				],
				403,
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

		// check form fields are valid
		$registered_fields = apply_filters("bring_" . $form_name . "_form_fields", []);
		$form_data = [];
		foreach ($registered_fields as $registered_field) {
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

			// store array field data in a php serialized format
			if (is_array($submitted_fields[$field_name])) {
				$form_data[$field_name] = $submitted_fields[$field_name];
			} elseif (is_scalar($submitted_fields[$field_name])) {
				$form_data[$field_name] = sanitize_text_field((string) $submitted_fields[$field_name]);
			} else {
				$form_data[$field_name] = "";
			}
		}

		// insert form submission
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
			!update_post_meta($submission_id, "form_data", $form_data)
		) {
			return new WP_REST_Response(
				[
					"success" => false,
					"message" => "Error while saving form data",
				],
				500,
			);
		}

		// hooks of form submission
		do_action("bring_form_submission", $submission_id, $form_data, $form_name);
		do_action("bring_{$form_name}_form_submission", $submission_id, $form_data);

		// filters for custom data
		$custom_data = apply_filters(
			"bring_form_submission_response",
			[],
			$submission_id,
			$form_data,
			$form_name,
		);
		$custom_data = apply_filters(
			"bring_{$form_name}_form_submission_response",
			$custom_data,
			$submission_id,
			$form_data,
		);

		// send mails
		Mail::send($submission_id, $form_data, $form_name);

		return new WP_REST_Response(
			[
				"success" => true,
				"custom_data" => $custom_data,
			],
			201,
		);
	}
}
