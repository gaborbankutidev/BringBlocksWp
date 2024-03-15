<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Form;

class Admin {
	/**
	 * @return void
	 */
	public static function init() {
		add_action("add_meta_boxes", self::addMetabox(...));
	}

	/**
	 * @return void
	 */
	private static function addMetabox() {
		add_meta_box(
			"form_submission_data_metabox",
			"Form data",
			self::renderMetabox(...),
			"bring_form_subm",
			"normal",
			"high",
			null,
		);
	}

	/**
	 * @param mixed $form_submission
	 * @return void
	 */
	private static function renderMetabox($form_submission) {
		/* @phpstan-ignore-next-line */ // TODO: this will take serious work
		$form_submission_id = $form_submission->ID;

		$form_name = get_post_meta($form_submission_id, "form_name", false);
		if (!is_string($form_name)) {
			return;
		}
		echo "<div style='margin-bottom: 16px;'>Form name: $form_name</div>";

		$form_data = get_post_meta($form_submission_id, "form_data");

		if (!is_array($form_data) || !count($form_data)) {
			return;
		}

		$form_data = unserialize($form_data[0]);
		if (!is_array($form_data) || !count($form_data)) {
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
