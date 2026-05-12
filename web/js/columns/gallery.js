Q.page("Communities/me", function () { 
	const $element = $("#Streams_related-Communities_gallery_tool");
	if (!$element.length) {
		return;
	}

	$element[0].forEachTool("Streams/image/preview", function () {
		const tool = this.preview;
		const state = tool.state;
		const $te = $(tool.element);

		if (!state.publisherId || !state.streamName || !state.closeable) {
			return;
		}

		Q.Streams.get(state.publisherId, state.streamName, function (err) {
			if (err) {
				return;
			}

			if (this.testWriteLevel("close")) {
				return;
			}

			state.actions.actions = {
				remove: function () {
					Q.Streams.unrelate(
						Q.Users.loggedInUserId(),
						"Streams/user/interests",
						"My/gallery",
						state.publisherId,
						state.streamName,
						function (err, result) {
							if (err || !result) {
								return;
							}

							Q.Tool.remove(tool.element, true, true);
						}
					);
				}
			};
			tool.actions();
		});
	});
	
	return function () {
		// code to execute before page starts unloading
	};
	
}, 'Communities');