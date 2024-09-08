(function (window, Q, $, undefined) {

var Communities = Q.Communities;
	
/**
 * @module Communities
 */
	
/**
 * Allow to select current community
 * @class Communities select
 * @constructor
 * @param {Object} [options] Override various options for this tool
 *  @param {String} [options.className] Any css classes to add to the tool element
 */

Q.Tool.define("Communities/select", function () {
	var tool = this;

	var byRole = JSON.parse(JSON.stringify(Q.getObject("byRole", Communities) || {}));
	tool.communityIds = [];
	Q.each(byRole, function (label, ids) {
		if (Array.isArray(ids)) {
			tool.communityIds = tool.communityIds.concat(ids);
		}
	});

	// remove duplicates
	tool.communityIds = Array.from(new Set(tool.communityIds));

	if (!tool.communityIds.length) {
		return;
	}

	Q.addStylesheet("{{Communities}}/css/tools/select.css");

	Q.Text.get('Communities/content', function (err, text) {
		tool.texts = text.communities;

		tool.refresh();
	});
},
{
	avatarOptions: {
		communityId: Q.Users.communityId,
		icon: 40,
		content: false,
		editable: false
	}
},
{
	refresh: function () {
		var tool = this;
		var state = this.state;
		var $te = $(tool.element);

		$te.on(Q.Pointer.fastclick, function () {
			Q.Dialogs.push({
				title: tool.texts.Title,
				className: 'Communities_dialog_community',
				content: Q.Tool.setUpElement('div', 'Users/list', {
					userIds: tool.communityIds,
					avatar: {icon: 80},
					clickable: true,
					onLoadMore: function (avatars) {
						Q.each(avatars, function () {
							$(this.element).on(Q.Pointer.fastclick, function (event) {
								event.stopPropagation();
								event.preventDefault();

								var avatarTool = Q.Tool.from(this);

								Q.handle(Q.url('/?Q_c=' + Q.getObject("state.userId", avatarTool)));

								Q.Dialogs.pop();

								return false;
							});
						});
					}
				})
			});
		});
	}
});

})(window, Q, Q.jQuery);