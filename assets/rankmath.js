;(function () {
	function updateRankMathGutenbergAnalyser() {
		const originalCollectGutenbergData =
			window.rankMathGutenberg?.assessor?.dataCollector?.collectGutenbergData

		// return if not gutenberg
		if (!originalCollectGutenbergData) {
			console.log("RankMath Gutenberg is not set")
			return
		}

		// return if content html is not available
		if (window.bringContentHtml === undefined) {
			return
		}

		// Override collectGutenbergData
		window.rankMathGutenberg.assessor.dataCollector.collectGutenbergData = function (...args) {
			// Call the original function
			const result = originalCollectGutenbergData.apply(this, args)

			// Modify the result
			result.content = window.bringContentHtml.value ?? ""

			// Return the result
			return result
		}

		// Refresh score
		rankMathGutenberg.refresh()
	}

	function updateRankMathEditorAnalyser() {
		const dataCollector = window.rankMathEditor?.assessor?.dataCollector

		// return if not editor
		if (!dataCollector) {
			console.log("RankMath Editor is not set")
			return
		}

		// return if content html is not available
		if (window.bringContentHtml === undefined) {
			return
		}

		// Override content on _data
		window.rankMathEditor.assessor.dataCollector._data.content = window.bringContentHtml.value ?? ""

		// Refresh score
		rankMathEditor.refresh()
	}

	function init($attempt = 0) {
		if (window.rankMathEditor) {
			updateRankMathGutenbergAnalyser()
			updateRankMathEditorAnalyser()
			return
		}

		if ($attempt > 10) {
			return
		}

		setTimeout(function () {
			init($attempt + 1)
		}, 500)
	}

	document.addEventListener("DOMContentLoaded", function () {
		init()
	})
})()
