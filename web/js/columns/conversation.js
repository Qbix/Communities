"use strict";
(function(Q, $, undefined) {

	Q.exports(function (options, index, column, data) {
		Q.addStylesheet('{{Communities}}/css/columns/conversation.css', {slotName: 'Communities'});

		var $container = $(".Q_columns_title_container", column);
		var $content = $(".Q_column_slot", column);

		if (!$container.length) {
			return;
		}

		var chatTool = Q.Tool.from($(".Streams_chat_tool", column), "Streams/chat");
		if (Q.typeOf(chatTool) === 'Q.Tool') {
			// apply infinitescroll tool
			chatTool.state.onRefresh.add(function () {
				setTimeout(function () {
					$content.tool('Q/infinitescroll', {
						flipped: true,
						onInvoke: function () {
							chatTool.earlierMessages(chatTool.renderMore);
						}
					}).activate();
				}, 1000);
			}, chatTool);

			Q.Streams.get(chatTool.state.publisherId, chatTool.state.streamName, function (err) {
				if (err) {
					return;
				}

				var stream = this;
				var url = stream.getAttribute("url");
				var src = this.iconUrl(40);

				if (stream.getAttribute(['Streams', 'private'])) {
					return;
				}
				$container.empty();
				$("<div>").tool("Streams/preview", {
					publisherId: stream.fields.publisherId,
					streamName: stream.fields.name,
					closeable: false,
					editable: false
				}).tool(stream.fields.type + "/preview", {
					editable: false,
					mode: 'title'
				}).appendTo($container)
					.activate({
						onRefresh: function () {
							var tool = Q.Tool.byId('Q_columns-Communities', 'Q/columns');
							Q.layout(tool && tool.element);
						}
					}, function () {
						$(this.element).on('click', function (event) {
							event.stopPropagation();
						});
						Q.Users.hint("Communities/conversation/website", this.element, {
							show: {
								delay: 2000
							},
							dontStopBeforeShown: true,
							waitUntilVisible: true
						});
					});
			});
		} else {
			console.warn("column/conversation: chat tool not found");
		}
	});

})(Q, Q.jQuery);