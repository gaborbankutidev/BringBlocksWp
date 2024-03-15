<?php

declare(strict_types=1);

namespace Bring\BlocksWP\Form;

class Mail {
	/**
	 * @param int $submission_id
	 * @param mixed $form_data
	 * @param string $form_name
	 * @return void
	 */
	public static function send($submission_id, $form_data, $form_name) {
		$form_submission_mails = apply_filters(
			"bring_form_submission_mails",
			[],
			$submission_id,
			$form_data,
			$form_name,
		);
		$form_submission_mails = apply_filters(
			"bring_{$form_name}_form_submission_mails",
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
}
