(function (window, Q, $, undefined) {

var Communities = Q.Communities;
var Users = Q.Users;
	
/**
 * @module Communities
 */
	
/**
 * Display user' roles in current community
 * @class Communities roles
 * @constructor
 * @param {Object} [options] Override various options for this tool
 *  @param {String} options.userId
 *  @param {String} [options.communityId] - by default main community
 *  @param {number} [icon=80] - label icon size
 *  @param {integer} [swapTimeout] - time before swap
 *  @param {Object|false} [transition] Set to false, to just show them all
 *  @param {Number} [transition.duration=300] - set to 0 to have instant transitions
 *  @param {Q.Event} [onInvoke] - event occur on click tool element
 */

Q.Tool.define("Communities/roles", function () {
	var tool = this;
	var state = this.state;
	var $toolElement = $(this.element);

	if (!state.userId) {
		return console.warn("userId not found");
	}

	$toolElement.on(Q.Pointer.fastclick, function () {
		Q.handle(state.onInvoke, tool);
	});

	this.refresh();
},
{
	userId: Q.Users.loggedInUserId(),
	communityId: Q.Users.communityId,
	icon: 80,
	swapTimeout: 3000,
	transition: {
		duration: 300
	},
    labelCanGrant: false,
    labelCanRevoke: false,
	onInvoke: new Q.Event(function () {
		var tool = this;
		var state = this.state;
		var canGrantRoles = state.labelCanGrant || Q.getObject('Q.plugins.Users.Label.canGrant') || [];
		var canRevokeRoles = state.labelCanRevoke || Q.getObject('Q.plugins.Users.Label.canRevoke') || [];
		var canHandleRoles = Array.from(new Set(canGrantRoles.concat(canRevokeRoles))); // get unique array from merged arrays
		if (!canHandleRoles.length) {
			return;
		}

		Users.getContacts.cache && Users.getContacts.cache.clear();

		Q.Dialogs.push({
			title: tool.text.profile.ManageRoles,
			content: Q.Tool.setUpElementHTML('div', 'Users/labels', {
				userId: state.communityId,
				contactUserId: state.userId,
				filter: {"replace": canHandleRoles}
			}),
			apply: true,
			onActivate: function (dialog) {
				var labelsTool = Q.Tool.from($(".Users_labels_tool", dialog), "Users/labels");
				if (Q.typeOf(labelsTool) !== 'Q.Tool') {
					return;
				}

				labelsTool.state.onClick.set(function (element, label, title, wasSelected) {
					if ((wasSelected && !canRevokeRoles.includes(label)) || (!wasSelected && !canGrantRoles.includes(label))) {
						Q.alert(tool.text.community.YouDontHavePermission);
						return false;
					}
				});
			},
			onClose: tool.fillRoles.bind(tool)
		});
	})
},
{
	refresh: function () {
		var tool = this;
		var state = this.state;

		tool.fillRoles();

		var swapRolesStop = false;
		var _swapRoles = function () {
			var $roles = $(".Communities_roles_label", tool.element);
			if ($roles.length <= 1) {
				return setTimeout(_swapRoles, state.swapTimeout);
			}

			$roles.each(function (i) {
				var $this = $(this);
				if (swapRolesStop || !$this.is(":visible")) {
					return;
				}

				swapRolesStop = true;

				$this.hide(state.transition.duration, function () {
					swapRolesStop = false;
					setTimeout(_swapRoles, state.swapTimeout);
				});
				var next = $roles[i === $roles.length-1 ? 0 : i+1];
				$(next).show(state.transition.duration);
			});
		};

		if (state.transition) {
			_swapRoles();
		}
	},
	fillRoles: function () {
		var tool = this;
		var $toolElement = $(tool.element);
		var state = tool.state;
		var p = Q.pipe(['contacts', 'labels'], function (params) {
			var contacts = params.contacts[1];
			var labels = params.labels[1];
			$toolElement.empty();
			Q.each(labels, function (i, label) {
				var found = null;
				Q.each(contacts, function (j, contact) {
					if (contact.label === label.label) {
						found = contact;
						return false;
					}
				});
				if (found) {
					$('<span class="Communities_roles_label" />')
						.text(label.title)
						.css("background-image", 'url(\"' + label.iconUrl(state.icon) + '\")')
						.appendTo($toolElement);
				}
			});

			$(tool.element).attr("data-transition", state.transition ? 'true' : 'false');
			$(".Communities_roles_label", tool.element)
			.each(function () {
				var lineHeight = parseFloat($(this).css('line-height'));
				this.setClassIf(
					$(this).height() > lineHeight + 1,
					'Communities_multiline'
				);
			});
		});
		Users.getLabels(state.communityId, p.fill('labels'));
		Users.getContacts(state.communityId, null, state.userId, p.fill('contacts'));
	}
});

})(window, Q, Q.jQuery);