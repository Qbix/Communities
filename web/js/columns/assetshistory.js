"use strict";
(function(Q, $, undefined) {
	Q.exports(function (options, index, column, data) {
		$(".Communities_column_assetshistory .Communities_tab").on(Q.Pointer.fastclick, function () {
			var $this = $(this);
			var val = $this.attr("data-val");

			if ($this.hasClass("Q_current")) {
				return false;
			}

			$this.addClass("Q_current").siblings(".Communities_tab").removeClass("Q_current");

			$(".Communities_column_assetshistory .Communities_tabContent[data-val=" + val + "]").show().siblings(".Communities_tabContent").hide();
		});

		column.forEachTool('Assets/history', function () {
			this.state.onClient.set(function (userId, name) {
				if (Q.Users.isCommunityId(userId)) {
					Q.Communities.openCommunityProfile.call(this, userId);
				} else {
					Q.Communities.openUserProfile.call(this, userId);
				}
			});
		});
	});
})(Q, Q.jQuery);