"use strict";
(function(Q, $, undefined) {

var Communities = Q.Communities;

Q.exports(function (options, index, div, data) {
	var $column = $(div);

	Q.addStylesheet('{{Communities}}/css/columns/connect.css', {
		slotName: 'Communities'
	});

	div.forEachTool('Streams/QRconnect', function () {
		var qrTool = this;

		// when someone scans the code, open their profile — but only while
		// this column is actually on screen, so a backgrounded tab doesn't
		// yank the user somewhere unexpected. Returning false suppresses the
		// tool's own Q.invoke either way; the visitor list still updates,
		// since Streams/participants watches Streams/joined itself.
		qrTool.state.onScanned.set(function (userId, url) {
			if (!$column.is(':visible')) {
				return false;
			}
			if (Q.Users.isCommunityId(userId)) {
				Communities.openCommunityProfile.call(this, userId);
			} else {
				Communities.openUserProfile.call(this, userId);
			}
			return false;
		}, 'Communities/connect/column');
	});
});

})(Q, Q.jQuery);
