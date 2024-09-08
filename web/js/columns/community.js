"use strict";
(function(Q, $, undefined) {
	
var Users = Q.Users;
var Streams = Q.Streams;

Q.exports(function (options, index, column, data) {
	var $column = $(column);
	var columnsTool = this;
	var $usersList = $(".Q_tool.Users_list_tool", column);
	var usersListTool = Q.Tool.from($usersList, "Users/list");
	var composerNewbie = Q.Tool.from($(".Communities_composer_newbie", column), "Communities/composer");
	var communityId = $('.Communities_profile_content', column).attr('data-userId');
	var $controlsSlot = $(".Q_controls_slot", column);
	var $columnSlot = $(".Q_column_slot", column);
    // onclick handle inside tool "Users/labels"
//	var labelTool = Q.Tool.from($("#Communities_profile_labels .Users_labels_tool", column), "Users/labels");
//	if (labelTool) {
//		labelTool.state.onClick.set(function (element, label, title, wasSelected) {
//			console.log(label);
//			return false;
//		}, labelTool);
//	}

	// if events tab exists
	if ($(".Communities_tab.Streams_aspect_events", $column).length) {
		// execute events column handler
		Q.require("{{Communities}}/js/columns/events.js", function (js) {
			Q.handle(js, columnsTool, [options, index, column, {communityId}]);
		});
	}

	// if feeds tab exists
	if ($(".Communities_tab.Streams_aspect_feeds", $column).length) {
		// execute feeds column handler
		Q.require("{{Media}}/js/columns/feeds.js", function (js) {
			Q.handle(js, columnsTool, [options, index, column, {communityId}]);
		});
	}

	if (Q.typeOf(composerNewbie) === 'Q.Tool') {
		composerNewbie.state.onCreated.set(function () {
			Q.handle(Q.url("community"), {
				quiet: true
			});
		});
	}

	var originalUserIds = [];
	if (Q.typeOf(usersListTool) === 'Q.Tool') {
		originalUserIds = usersListTool.state.userIds;
	}

	Q.addStylesheet('{{Q}}/css/tools/inplace.css');
	Q.addStylesheet('{{Communities}}/css/columns/community.css', { slotName: 'Communities' });
	Q.addStylesheet('{{Users}}/css/tools/labels.css', { slotName: 'Communities' });

	Q.Text.get('Communities/content', function (err, text) {
		
		$('.Communities_manage_contacts', column).off(Q.Pointer.fastclick).on(Q.Pointer.fastclick, function () {
			if (!Users.loggedInUserId()) {
				return Q.Users.login();
			}
			Streams.invite(communityId, 'Streams/experience/main', {
				appUrl: Q.urls['Communities/events'],
				addMyLabel: []
			});
		});

		var allRoles = Q.getObject("Q.Communities.allRoles");
		var allLabels = {};
		Q.each(allRoles, function (label, info) {
			allLabels[label] = Q.extend({}, info, {
				icon: Users.iconUrl(info.icon, 80),
				label: label
			});
		});

		var interestUserIds = null;
		function _filterInterests(userIds) {
			if (!interestUserIds) {
				return userIds;
			}
			var result = [];
			for (var i=0, l=userIds.length; i<l; ++i) {
				if (!interestUserIds || interestUserIds.indexOf(userIds[i]) >= 0) {
					result.push(userIds[i]);
				}
			}
			return result;
		}
		var p = new Q.Pipe(['participants', 'contacts'], function (params, subjects) {
			var labelUserIds = params.contacts[0];
			var err = params.participants[0];
			var extra = params.participants[2];
			interestUserIds = extra && extra.participants && Object.keys(extra.participants);
			if (err) {
				usersListTool.state.userIds = [];
			} else if (!labelUserIds) {
				usersListTool.state.userIds = _filterInterests(originalUserIds);
			} else {
				usersListTool.state.userIds = _filterInterests(labelUserIds);
			}
			usersListTool.refresh();
		});
		p.fill('participants')();
		p.fill('contacts')();

		// filter persons by label
		$column.on(Q.Pointer.click, '.Communities_community_controls[data-tab=people] .Communities_filter_labels', function () {
			var $filter = $(this);

			if (Q.typeOf(allRoles) !== 'object' || Q.isEmpty(allRoles)) {
				return;
			}

			Q.Dialogs.push({
				title: text.community.AllRoles,
				className: 'Communities_dialog_community',
				template: {
					name: 'Communities/labels/filter',
					fields: {
						labels: allLabels,
						all: {
							title: text.people.labels.All,
							icon: Q.url("{{Users}}/img/icons/labels/all/80.png")
						}
					}
				},
				onActivate: function (dialog) {
					$(".Users_labels_label", dialog).on(Q.Pointer.fastclick, function () {
						var $this = $(this);
						var labelsFilter = $this.attr('data-label');

						$filter.attr('data-label', labelsFilter);
						$('.Communities_filter_value', $filter).text($(".Users_labels_title", $this).text());
						$this.addClass('Q_selected');

						Q.Dialogs.pop();

						if (labelsFilter === '*') {
							return p.fill('contacts')(originalUserIds);
						}
						Users.getContacts(communityId, labelsFilter, function (err, contacts) {
							var userIds = [];
							Q.each(contacts, function () {
								userIds.push(this.contactUserId);
							});
							p.fill('contacts')(userIds);
						});

						return false;
					});
				}
			});
		});

		// filter persons by interests
		$column.on(Q.Pointer.click, '.Communities_community_controls[data-tab=people] .Communities_filter_interests', true, function () {
			var $filter = $(this);
			Q.Dialogs.push({
				title: text.people.interests.Title,
				className: 'Streams_dialog_interests',
				content: Q.Tool.setUpElement('div', 'Streams/interests', {
					filter: text.people.interests.Filter,
					all: text.people.interests.All,
					onClick: function (element, normalized, category, interest, wasSelected) {
						var src = Streams.Interests.categoryIconUrl(
							Users.communityId, category, 'colorful'
						);
						$filter.find('.Communities_filter_icon').attr('src', src);
						$filter.find('.Communities_filter_value').text(interest);
						$(element).addClass('Q_selected');
						$('h2', element).addClass('Q_expanded');
						Q.Dialogs.pop();

						//_updatedInterests();
						if (normalized === '*') {
							return p.fill('participants')();
						}
						var publisherId = Users.communityId;
						var streamName = 'Streams/interest/' + Q.normalize(normalized);
						Streams.get(publisherId, streamName, p.fill('participants'), { participants: 100 });

						return false;
					}
				})
			});
		});

		var _selectTab = function (tab) {
			var $this;

			if (this instanceof HTMLElement) {
				$this = $(this);
			} else if (Q.typeOf(tab) === 'string') {
				$this = $('.Communities_tab[data-val=' + tab + ']', column);
			} else {
				throw new Q.Error("Invalid tab");
			}

			if (!$this.length) {
				return console.warn("Tab " + tab + " not found");
			}

			var $parentBox = $this.closest(".Communities_profile_content");

			// handle with controls
			var $controlsSlot = $('.Q_controls_slot', column);
			var $currentTab = $this.siblings('.Q_current');
			$(".Communities_community_controls[data-tab=" + $currentTab.attr('data-val') + "]", $controlsSlot).attr("data-hidden", 1);
			var $currentControls = $(".Communities_community_controls[data-tab=" + $this.attr('data-val') + "]", $controlsSlot).removeAttr("data-hidden");
			if ($currentControls.length) {
				$controlsSlot.show();
				$column.addClass('Q_columns_hasControls');
			} else {
				$controlsSlot.hide();
				$column.removeClass('Q_columns_hasControls');
			}
			columnsTool.refresh();

			// handle with content
			$this.siblings().removeClass('Q_current');
			$this.addClass('Q_current');
			var section = $this.attr('data-val');
			$('.Communities_profile_section', $parentBox).removeClass('Q_current');
			var $currentSection = $('#Communities_profile_' + section, $parentBox).addClass('Q_current');
			$this[0].scrollingParent().adjustScrolling();

			// set current section heigh to make it scrollable
			$currentSection.outerHeight(($controlsSlot.is(":visible") ? $controlsSlot.offset().top : $columnSlot.height()) - $currentSection.offset().top);

			// events tab
			if (section === 'events') {
				$("input[name=query]", $currentSection).plugin('Q/placeholders');
			}
		};

		$('.Communities_tab', column).on(Q.Pointer.start, _selectTab);

		var tabSelected = Q.getObject("Q.Communities.community.tab");
		if (tabSelected) {
			_selectTab(tabSelected);
		}

		$usersList.css("height", $controlsSlot.offset().top - $usersList.offset().top);
		$usersList.tool('Q/infinitescroll', {
			onInvoke: function () {
				var infiniteTool = this;
				var offset = $(">.Users_avatar_tool:visible", $usersList).length;

				// skip duplicated (same offsets) requests
				if (!isNaN(this.state.offset) && this.state.offset >= offset) {
					return;
				}

				infiniteTool.setLoading(true);
				this.state.offset = offset;

				Q.req('Communities/people', 'load', function (err, data) {
					infiniteTool.setLoading(false);
					err = Q.firstErrorMessage(err, data);
					if (err) {
						return console.error(err);
					}

					Q.each(data.slots.load, function (i, userId) {
						if (usersListTool.state.userIds.indexOf(userId) >= 0) {
							return;
						}

						usersListTool.state.userIds.push(userId);
					});

					usersListTool.loadMore();
				}, {
					fields: {offset, communityId}
				});
			}
		}).activate();
	});
});

Q.Template.set('Communities/labels/filter',
	'<div class="Users_labels_tool">'
	+ '<ul>'
	+ '{{#if all}}'
	+ '<li class="Users_labels_label" data-label="*">'
	+   '<img class="Users_labels_icon" src="{{all.icon}}" alt="all">'
	+   '<div class="Users_labels_title">{{all.title}}</div>'
	+ '</li>'
	+ '{{/if}}'
	+ '{{#each labels}}'
	+ '<li class="Users_labels_label" data-label="{{this.label}}">'
	+   '<img class="Users_labels_icon" src="{{this.icon}}" alt="label icon">'
	+   '<div class="Users_labels_title">{{this.title}}</div>'
	+ '</li>'
	+ '{{/each}}'
	+ '</ul>'
	+ '</div>'
);


})(Q, Q.jQuery);