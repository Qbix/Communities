"use strict";
(function(Q, $, undefined) {

	Q.addStylesheet('{{Communities}}/css/conversation/composer.css');

	Q.exports(function (options, index, column, data) {
		column.forEachTool('Websites/webpage/composer', function () {
			var tool = this;
			if (Q.typeOf(tool) !== 'Q.Tool') {
				return console.warn("Websites/webpage/composer tool not found");
			}

			tool.state.onRefresh.add(function () {
				$('input', this.element).plugin('Q/clickfocus');
			}, 'Websites/newConversation');

			// this happen when user click on "Start Conversation" button inside composer
			tool.state.onCreate.set(function () {
				var publisherId = this.state.publisherId;
				var streamName = this.state.streamName;

				Q.Communities.pushConversationColumn(publisherId, streamName, null, function () {
					Q.Streams.invite(publisherId, streamName, {
						appUrl: location.href
					});
				});
			}, tool);
		});
	});

})(Q, Q.jQuery);