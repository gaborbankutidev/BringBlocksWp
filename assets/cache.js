(function ($) {
	// On page load ==>
	$(document).ready(function () {
		$(".bringUpdateCache").click(function () {
			const $this = $(this);
			const entityType = $this.data("entityType");
			const entityId = $this.data("entityId");
			const token = $this.data("token");
			const status = $($this.children("span")[0]);

			status.removeClass("dashicons-yes");
			status.removeClass("dashicons-no");
			status.addClass("dashicons-update");
			status.addClass("dashicons");

			fetch("/wp-json/bring/cache/trigger", {
				method: "POST",
				headers: {Authorization: token, "Content-Type": "application/json"},
				body: JSON.stringify({entityType, entityId}),
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (responseBody) {
					console.log(responseBody);
					if (responseBody.success) {
						status.removeClass("dashicons-update");
						status.removeClass("dashicons-no");
						status.addClass("dashicons-yes");
					} else {
						status.removeClass("dashicons-update");
						status.removeClass("dashicons-yes");
						status.addClass("dashicons-no");
					}
				})
				.catch(function (response) {
					console.error(response);
					status.removeClass("dashicons-update");
					status.removeClass("dashicons-yes");
					status.addClass("dashicons-no");
				});
		});
	});
})(jQuery);
