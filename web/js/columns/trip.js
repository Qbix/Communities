"use strict";
(function(Q, $, undefined) {
	
	var Users = Q.Users;
	var Streams = Q.Streams;
	var Communities = Q.Communities;

	Q.addStylesheet('{{Communities}}/css/columns/trip.css', { slotName: 'Communities' });

	Q.exports(function (options, index, div, data) {
		var tripTool = Q.Tool.from($('.Travel_trip_tool', div));
		var $tripToolElement = $(tripTool.element); //.css("height", 370)
		var columns = Q.Tool.byId('Q_columns-Communities');

		var state = tripTool.state;
		var $column = $(tripTool.element).closest('.Q_column_slot');

		var publisherId = tripTool.state.publisherId;
		var streamName = tripTool.state.streamName;
		var tripId = tripTool.state.streamName.split('/').pop();

		$('.Q_button[data-invoke=chat]', $column).click(function () {
			var $this = $(this);
			var aspect = $this.attr('data-invoke');

			Communities.pushChatColumn({
				fields: {
					publisherId: state.publisherId,
					name: state.streamName,
					type: "Travel/trip"
				}
			}, $this);
		});

		var $unseen = $('.Streams_aspect_chats .Communities_info_unseen', div);
		Q.Streams.Message.Total.setUpElement(
			$unseen[0],
			state.publisherId,
			state.streamName,
			'Streams/chat/message',
			tripTool
		);

		state.onFinish.set(function () {
			columns.close(index, null, {animation: {duration: 0}});
		});

		setTimeout(Communities.getChatMessages(publisherId, streamName), 300);
	});

})(Q, Q.jQuery);