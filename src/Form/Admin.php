<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Form;

use Bring\BlocksWP\Config;
use Bring\BlocksWP\Utils;
use WP_Query;

class Admin {
	/**
	 * @return void
	 */
	public static function init() {
		// Form name filter
		add_filter("query_vars", self::addQueryVar(...));
		add_action("pre_get_posts", self::alterQuery(...));
		add_filter("views_edit-bring_form_subm", self::addFormList(...));

		// Form list column
		add_filter("manage_bring_form_subm_posts_columns", self::addColumn(...), 10, 1);
		add_action("manage_bring_form_subm_posts_custom_column", self::renderColumn(...), 10, 2);

		// Form page meta box
		add_action("add_meta_boxes", self::addMetabox(...));
	}

	/**
	 * Adds query var to admin
	 *
	 * @param array<string> $vars
	 * @return array<string>
	 */
	private static function addQueryVar($vars) {
		if (is_admin()) {
			$vars[] = "form_name";
		}
		return $vars;
	}

	/**
	 * Update query with meta query
	 *
	 * @param WP_Query $query
	 * @return void
	 */
	private static function alterQuery($query) {
		if (!is_admin() || "bring_form_subm" != $query->query["post_type"]) {
			return;
		}

		if (isset($query->query_vars["form_name"])) {
			$query->set("meta_key", "form_name");
			$query->set("meta_value", $query->query_vars["form_name"]);
			$query->set("meta_compare", "=");
		}
	}

	/**
	 * Add list of forms to view
	 *
	 * @param array<string, string> $views
	 * @return array<string, string>
	 */
	private static function addFormList($views) {
		if (isset($views["publish"])) {
			unset($views["publish"]);
		}

		$forms = Config::getForms();

		foreach ($forms as $form) {
			$args = [
				"post_type" => "bring_form_subm",
				"post_status" => "publish",
				"meta_key" => "form_name",
				"meta_value" => $form,
				"posts_per_page" => -1,
			];
			$count = count(get_posts($args));

			$current = get_query_var("form_name") == $form ? "current" : "";
			$name = ucfirst(str_replace("_", " ", $form));

			$views[
				$form
			] = "<a href='edit.php?post_type=bring_form_subm&form_name={$form}' class='{$current}'> {$name} form <span class='count'>({$count})</span></a>";
		}
		return $views;
	}

	/**
	 * Add column to form list
	 *
	 * @param array<string, string> $defaults
	 * @return array<string, string>
	 */
	private static function addColumn($defaults) {
		return array_merge(
			array_slice($defaults, 0, 2),
			["form_data" => "Form data"],
			array_slice($defaults, 2),
		);
	}

	/**
	 * Render form data column
	 *
	 * @param string $column_name
	 * @param int $form_submission_id
	 * @return void
	 */
	private static function renderColumn($column_name, $form_submission_id) {
		if ($column_name != "form_data") {
			return;
		}

		/**
		 * @var array<mixed>|false $form_data
		 */
		$form_data = get_post_meta($form_submission_id, "form_data", true);
		if (!is_array($form_data) || empty($form_data)) {
			return;
		}

		$content = "";
		foreach ($form_data as $name => $value) {
			$content .= "
				<div style='display: flex;'>
					<div style='width: 120px; font-weight:600;'>$name</div>
					<div style='overflow: hidden; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; width: calc(100% - 120px);'>$value</div>
				</div>
			";
		}

		echo "
            <div>
                $content
            </div>
        ";
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

		$form_name = get_post_meta($form_submission_id, "form_name", true);
		if (!is_string($form_name)) {
			return;
		}
		echo "<div style='margin-bottom: 16px;'>Form name: $form_name</div>";

		/**
		 * @var array<mixed>|false $form_data
		 */
		$form_data = get_post_meta($form_submission_id, "form_data", true);
		if (!is_array($form_data) || empty($form_data)) {
			return;
		}

		$content = "";
		foreach ($form_data as $name => $value) {
			if (is_array($value)) {
				$value = Utils\General::varDumpToString($value);
			}

			$content .= "
				<div style='display: flex; margin-bottom: 8px;'>
					<div style='width: 200px; font-weight:600;'>$name</div>
					<div style='width: calc(100% - 200px);'>$value</div>
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
