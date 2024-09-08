"use strict";
(function(Q, $, undefined) {
	
var Users = Q.Users;
var Streams = Q.Streams;
var Communities = Q.Communities;

Q.exports(function (options, index, column, data) {
	var $usersBox = $(".Users_list_tool", column);
	var usersList = Q.Tool.from($usersBox, 'Users/list');
	var $titleSlot = $('.Q_title_slot', column);
	var $titleContainer = $('.Q_columns_title_container', column);
	var $columnSlot = $(".Q_column_slot", column);
	var _adjustHeight = function () {
		var siblingsHeight = 0;
		Q.each($usersBox.siblings(":visible"), function () {
			siblingsHeight += $(this).outerHeight();
		});
		$usersBox.height($columnSlot.innerHeight() - siblingsHeight);
	};
	_adjustHeight();
	Q.onLayout($columnSlot[0]).set(_adjustHeight, true);

	if (!Q.getObject(Communities.people.skipInfinitescroll)) {
		// apply infinitescroll tool
		$usersBox.tool('Q/infinitescroll', {
			onInvoke: function () {
				var infiniteTool = this;
				var offset = $(">.Users_avatar_tool:visible", $usersBox).length;

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
						if (usersList.state.userIds.indexOf(userId) >= 0) {
							return;
						}

						usersList.state.userIds.push(userId);
					});

					usersList.loadMore();
				}, {
					fields: {
						offset: offset
					}
				});
			}
		}).activate();
	}

	Q.Text.get('Communities/content', function (err, text) {
		var _invitePeople = function () {
			if (!Users.loggedInUserId()) {
				return Q.Users.login();
			}
			Streams.invite(Users.communityId, 'Streams/experience/main', {
				appUrl: Q.urls['Communities/events']
			});
		};
		var _filterByInterests = function () {
			var $filter = $(this);
			Q.Dialogs.push({
				title: text.people.interests.Title,
				className: 'Streams_dialog_interests',
				stylesheet: '{{Q}}/css/tools/expandable.css',
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
						_updatedInterests(normalized);
						return false;
					}
				})
			});
		};
		var _filterByLabels = function () {
			var $filter = $(this);
			Q.Dialogs.push({
				title: text.people.labels.Title,
				className: 'Communities_dialog_labels',
				content: Q.Tool.setUpElement('div', 'Users/labels', {
					canAdd: text.people.labels.New,
					filter: ['Users/','Streams/',Users.communityId + '/'],
					all: text.people.labels.All,
					onClick: function (element, label, title, wasSelected) {
						$filter.find('.Communities_filter_icon')
							.attr('src', $(element).find('img').attr('src'));
						$filter.find('.Communities_filter_value').text(title);
						$(element).addClass('Q_selected');
						Q.Dialogs.pop();
						_updatedLabel(label);
						return false;
					}
				})
			});
		};

		// <apply FaceBook column style>
		if (Q.getObject('layout.columns.style', Communities) === 'facebook') {
			var icons = [];
			icons.push($("<i class='qp-communities-people'></i>", column).on(Q.Pointer.fastclick, _filterByLabels));
			icons.push($("<i class='qp-communities-interests'></i>", column).on(Q.Pointer.fastclick, _filterByInterests));

			var userChooser = Q.Tool.from($(".Streams_userChooser_tool", column), 'Streams/userChooser');
			var $userChooser = null;
			if (userChooser) {
				$userChooser = $(userChooser.element);
				icons.push($("<i class='qp-communities-search Communities_chooser_trigger'></i>"));
			}

			if (Users.loggedInUserId()) {
				icons.push($("<i class='qp-communities-plus'></i>").on(Q.Pointer.fastclick, _invitePeople));
			}

			$titleContainer.tool('Communities/columnFBStyle', {
				icons: icons,
				filter: [$userChooser.find('input')],
				applyPlaceholder: false
			}, 'People_column').activate();
		}
		// </apply FaceBook column style>

		$('.Communities_filter_labels', column).off(Q.Pointer.click).on(Q.Pointer.click, _filterByLabels);
		$('.Communities_filter_interests', column).off(Q.Pointer.click).on(Q.Pointer.click, _filterByInterests);
		$('#Communities_invite_people_button', column).plugin('Q/clickable').off(Q.Pointer.click).on(Q.Pointer.click, _invitePeople);

		$('#Streams_userChooser_tool').plugin('Q/placeholders');
		$('#identifier').keypress(function(event) {
			if (event.keyCode != 13) return;
			$('#invite').click();
		});
		var originalUserIds = usersList.state.userIds;
		var labelUserIds = null;
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
		function _filter(userIds, callback) {
			return _filterInterests(userIds, callback);
		}
		if (!usersList) {
			return;
		}
		if (Communities.people.randomAngles) {
			usersList.state.onLoadMore.add(function (avatars) {
				Q.each(avatars, function () {
					var angle = Math.random() * 10 - 5;
					this.element.style.transform = 'rotate(' + angle + 'deg)';
				});
			});
		}
		var p = new Q.Pipe(['participants', 'contacts'], function (params, subjects) {
			labelUserIds = params.contacts[0];
			var err = params.participants[0];
			var extra = params.participants[2];
			interestUserIds = extra && extra.participants && Object.keys(extra.participants);
			if (err) {
				usersList.state.userIds = [];
			} else if (!labelUserIds) {
				usersList.state.userIds = _filter(originalUserIds);
			} else {
				usersList.state.userIds = _filter(labelUserIds);
			}
			usersList.refresh();
		});
		// var $label = $('select[name=filter-label]')
		// .on('change', _updatedLabel);
		// var $interests = $('select[name=filter-interests]')
		// .on('change', _updatedInterests);
		p.fill('participants')();
		p.fill('contacts')();
		
		var originalTitle = null;
		function _updatedLabel(label) {
			var $title = $('.Q_column_people .Communities_columnFBStyle_tool .Q_title_slot');
			var userId = Q.getObject("Q.Communities.people.communityId") || Users.loggedInUserId();
			if (label === undefined) {
				label = $label.val();
			}
			if (label === '*' || !userId) {
				if (originalTitle) {
					$title.text(originalTitle);
				}
				return p.fill('contacts')(originalUserIds);
			}
			Users.getContacts(userId, label, function (err, contacts) {
				var userIds = [];
				Q.each(contacts, function () {
					userIds.push(this.contactUserId);
				});
				p.fill('contacts')(userIds);
			});
			originalTitle = originalTitle || $title.text();
			$title.text(Q.Users.Label.labelTitle(label));
		}
	
		function _updatedInterests(val) {
			//if (val === undefined) {
			// 	val = $interests.val();
			// }
			if (val === '*') {
				return p.fill('participants')();
			}
			var publisherId = Users.communityId;
			var streamName = 'Streams/interest/' + Q.normalize(val);
			Streams.get(publisherId, streamName, p.fill('participants'), { participants: 100 });
		}
	});
});

})(Q, Q.jQuery);