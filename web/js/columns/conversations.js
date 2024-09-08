"use strict";
(function (Q, $, undefined) {

	var Users = Q.Users;
	var Streams = Q.Streams;
	var Communities = Q.Communities;
	var _normalized = '*', _category, _interest;

	Q.exports(function (options, index, column, data) {
		var $content = $(".Q_column_slot", column);
		var $conversationsBox = $(".Communities_conversations", $content);
		var $titleContainer = $('.Q_columns_title_container', column);
		var $titleSlot = $('.Q_title_slot', column);
		var conversationRelations = Q.getObject("conversations.relationTypes", Communities);

		// apply infinitescroll tool
		$content.tool('Q/infinitescroll', {
			onInvoke: function () {
				var infiniteTool = this;
				var offset = $(">.Streams_preview_tool:visible", $conversationsBox).length;

				// skip duplicated (same offsets) requests
				if (!isNaN(infiniteTool.state.offset) && infiniteTool.state.offset >= offset) {
					return;
				}

				infiniteTool.setLoading(true);
				infiniteTool.state.offset = offset;

				Q.req('Communities/conversations', 'load', function (err, data) {
					
					infiniteTool.setLoading(false);
					var err = Q.firstErrorMessage(err, data);
					if (err) {
						return console.error(err);
					}

					Q.each(data.slots.load, function () {
						$(this).appendTo($conversationsBox).activate();
					});
				}, {
					fields: {
						experienceId: Q.getObject('Q.plugins.Communities.events.experienceId') || null,
						offset: offset
					},
					loadExtras: 'session' // to mark streams as public
				}); 
			}
		}).activate();

		function _updatedInterests(normalized, category, interest) {
			_normalized = normalized;
			_category = category;
			_interest = interest;
			if (category) {
				var c = Q.normalize(category);
				var src = Streams.Interests.categoryIconUrl(
					Users.communityId, category, 'colorful'
				);
				var $filter = $('.Communities_filter_interests');
				$filter.find('.Communities_filter_icon').attr('src', src);
				$filter.find('.Communities_filter_value').text(interest);
			}

			var tools = Q.Tool.byName(['Streams/chat/preview', 'Websites/webpage/preview']);
			var interestName = 'Streams/interest/' + normalized;
			if (normalized === '*') {
				return Q.each(tools, function () {
					$(this.element).slideDown(300);
				});
			}
			Q.each(tools, function (id) {
				var $te = $(this.element);

				if(!this.preview){
					$te.slideUp(300);
					return;
				}

				var ps = this.preview.state;
				Streams.get(ps.publisherId, ps.streamName, function (err) {
					if (err) {
						return Q.error(Q.firstErrorMessage(err));
					}

					if (this.getAttribute('interest') === interestName) {
						$te.slideDown(300);
					} else {
						$te.slideUp(300);
					}
				});
			});
		}

		function _updatedAuthors(publisherId) {
			var tools = Q.Tool.byName(['Streams/chat/preview', 'Websites/webpage/preview']);

			if (publisherId === '*') {
				return Q.each(tools, function () {
					$(this.element).slideDown(300);
				});
			}

			Q.each(tools, function (id) {
				var $te = $(this.element);
				var ps = Q.getObject("state", this) || Q.getObject("preview.state", this);

				if (ps.publisherId === publisherId) {
					$te.slideDown(300);
				} else {
					$te.slideUp(300);
				}
			});
		}

		// code to execute after page finished loading
		Q.Text.get('Communities/content', function (err, text) {
			Q.Template.set("Communities_filter_publishers",
				'<div class="Communities_filter_publishers_userchooser"><input name="query" value="" type="text" class="text Streams_userChooser_input" placeholder="{{text.conversations.FilterAuthorPlaceHolder}}" autocomplete="off"></div>'
				+ '<button class="Communities_filter_publishers_all">{{text.conversations.AllAuthors}}</button>'
				+ '<div class="Communities_filter_publishers_users"></div>'
				+ '</div>'
			);

			var _newConversation = function () {
				var tool = Q.Tool.byId("Q_columns-Communities");
				tool.close({min:1}, null, {animation:{duration:0}});
				tool.open({
					title: text.newConversation.Title,
					url: Q.url('newConversation'),
					name: 'newConversation'
				}, 1);
			};
			var _filterByInterests = function () {
				Q.Dialogs.push({
					title: text.events.FilterbyInterest,
					className: 'Streams_dialog_interests',
					stylesheet: '{{Q}}/css/tools/expandable.css',
					content: Q.Tool.setUpElement('div', 'Streams/interests', {
						filter: text.events.ShowActivities,
						all: text.events.AllInterests,
						onClick: function (element, normalized, category, interest, wasSelected) {
							$(element).addClass('Q_selected');
							$('h2', element).addClass('Q_expanded');
							Q.Dialogs.pop();
							_updatedInterests(normalized, category, interest);
							return false;
						}
					})
				});
			};
			var _filterByPublishers = function () {
				var $filterElement = $(this);

				Q.Dialogs.push({
					title: text.conversations.FilterbyAuthors,
					className: 'Communities_filter_publishers_dialog',
					template: {
						name: "Communities_filter_publishers",
						fields: {
							text: text
						}
					},
					onActivate: function (dialog) {
						// create userChooser tool
						$(".Communities_filter_publishers_userchooser", dialog).tool("Streams/userChooser", {
							'onChoose': function (userId, avatar) {

								_updatedAuthors(userId);

								$(".Communities_filter_value", $filterElement).text(avatar.firstName);

								Q.Dialogs.pop();

								return false;
							}
						}).activate();

						// collect authors
						var authors = [];
						var tools = Q.Tool.byName(['Streams/chat/preview', 'Websites/webpage/preview']);

						Q.each(tools, function (id) {
							var publisherId = Q.getObject("state.publisherId", this) || Q.getObject("preview.state.publisherId", this);

							if (!publisherId || (authors && authors.indexOf(publisherId) >= 0)) {
								return;
							}

							authors.push(publisherId);
						});

						// create Users/list tool
						$(".Communities_filter_publishers_users", dialog).tool("Users/list", {
							userIds: authors,
							avatar: {icon: 80},
							clickable: true,
							onLoadMore: function (avatars) {
								Q.each(avatars, function () {
									$(this.element).on(Q.Pointer.fastclick, function (event) {
										event.stopPropagation();
										event.preventDefault();

										var avatarTool = Q.Tool.from(this);

										_updatedAuthors(avatarTool.state.userId);

										$(".Communities_filter_value", $filterElement).text(avatarTool.state.avatar.firstName);

										Q.Dialogs.pop();

										return false;
									});
								});
							}
						}).activate();

						// all authors
						$("button.Communities_filter_publishers_all", dialog).on(Q.Pointer.fastclick, function (event) {

							$(".Communities_filter_value", $filterElement).text(text.conversations.AllAuthors);

							_updatedAuthors('*');

							Q.Dialogs.pop();

							return false;
						});
					}
				});
			};

			var _filterConversations = function () {
				var filter = $(this).val();
				var allConversations = $(".Communities_conversations_column .Streams_preview_tool", column);
				Q.each(allConversations, function () {
					var $this = $(this);

					if (!filter || $(".Streams_preview_title .Q_inplace_tool_static", this).text().toUpperCase().indexOf(filter.toUpperCase()) >= 0) {
						if (Q.info.isMobile) {
							$this.attr('data-match', true);
						} else {
							$this.fadeIn(500);
						}
					} else {
						if (Q.info.isMobile) {
							$this.attr('data-match', false);
						} else {
							$this.fadeOut(500);
						}
					}
				});
			};

			// <apply FaceBook column style>
			if (Q.getObject('layout.columns.style', Communities) === 'facebook') {
				var icons = [
					$("<i class='qp-communities-people'></i>").on(Q.Pointer.fastclick, _filterByPublishers),
					$("<i class='qp-communities-interests'></i>").on(Q.Pointer.fastclick, _filterByInterests),
					$("<i class='qp-communities-search Communities_chooser_trigger'></i>")
				];

				if (Users.loggedInUserId()) {
					icons.push($("<i class='qp-communities-plus'></i>").on(Q.Pointer.fastclick, _newConversation));
				}

				var $conversationsFilter = $('<input name="query" class="Communities_conversationChooser_input" placeholder="' + text.conversations.filterConversations + '">')
					.on('input', _filterConversations);

				$titleContainer.tool('Communities/columnFBStyle', {
					icons: icons,
					filter: [$conversationsFilter]
				}, 'Conversations_column').activate();
			}
			// </apply FaceBook column style>

			$('#Communities_new_conversation_button', column)
			.off([Q.Pointer.fastclick, 'Communities'])
			.on([Q.Pointer.fastclick, 'Communities'], _newConversation).plugin('Q/clickable');

			$('.Communities_filter_interests', column).on(Q.Pointer.click, _filterByInterests);

			$('.Communities_filter_publishers').on(Q.Pointer.click, _filterByPublishers);

			function _updateChatsCount(instructions, action) {
				var publisherId = Q.getObject(["fromPublisherId"], instructions);
				var streamName = Q.getObject(["fromStreamName"], instructions);

				// ignore self added event
				if (Q.Users.loggedInUserId() === publisherId) {
					return;
				}

				var $content = $(".Q_columns_title_container", column);
				var $counter = $(".Communities_chats_amount", $content);
				var newChatsText = Q.getObject(["conversations", "newConversations"], text);

				if (!newChatsText) {
					return console.warn('Chats Count: text not found');
				}

				if (!$counter.length) {
					$counter = $("<div class='Communities_chats_amount'>").html(newChatsText.replace('{{1}}', '<span></span>')).appendTo($content);
				}

				var chats = $.data($counter[0], "chats") || [];
				var inArray = (chats && chats.indexOf(streamName) >= 0);

				if (action === '+') {
					if (inArray) {
						return;
					}

					chats.push(streamName);
				} else {
					if (!inArray) {
						return;
					}

					// remove streamName from array
					chats = $.grep(chats, function(value) {
						return value !== streamName;
					});
				}

				$.data($counter[0], "chats", chats);
				$("span", $counter).html(chats.length);

				if (chats.length) {
					$counter.show();
				} else {
					$counter.hide();
				}

			}

			// need to get this stream to listen if new chats created
			// new chats will relate to this category, but if we don't
			// get it on client side we will not get messages
			Streams.retainWith(true).get(Users.communityId, "Streams/chats/main", function (err) {
				if (err) {
					return console.warn(err);
				}

				// join Streams/chats/main category to get messages
				if (Users.loggedInUserId()) {
					this.join();
				}

				// listen Streams/relatedTo message to detect new chats
				this.onMessage('Streams/relatedTo').set(function (message) {
					var instructions = message.getAllInstructions();
					var relationType = Q.getObject(["type"], instructions);

					// only Streams/chat and Websites/webpage streams
					if(conversationRelations.indexOf(relationType) < 0) {
						return;
					}

					// check if this preview already exists
					var exists = false;
					Q.each($(".Streams_preview_tool", $conversationsBox), function () {
						var tool = Q.Tool.from(this, "Streams/preview");
						if (!tool) {
							return;
						}

						if (tool.state.publisherId === instructions.fromPublisherId && tool.state.streamName === instructions.fromStreamName) {
							exists = true;
						}
					});

					if (exists) {
						return;
					}

					$('<div>')
						.tool("Streams/preview", {
							'publisherId': instructions.fromPublisherId,
							'streamName': instructions.fromStreamName,
							'closeable': false,
							'editable': false
						})
						.tool(relationType + "/preview", {
							'publisherId': instructions.fromPublisherId,
							'streamName': instructions.fromStreamName
						})
						.prependTo($conversationsBox)
						.activate(function () {
							$(this.element).addClass("Q_newsflash");
						});

					_updateChatsCount(instructions, '+');

					// hide "no items" message if it exist
					$(".Communities_no_items", $content).hide();
				}, "Communities/conversations");

				// listen Streams/unrelatedTo message to remove closed conversations
				this.onMessage('Streams/unrelatedTo').set(function (message) {
					var instructions = message.getAllInstructions();
					var relationType = Q.getObject(["type"], instructions);

					// only Streams/chat and Websites/webpage streams
					if(conversationRelations.indexOf(relationType) < 0) {
						return;
					}

					$(".Communities_conversations_column .Streams_chat_preview_tool, .Communities_conversations_column .Websites_webpage_preview_tool").each(function(i, element){
						var tool = Q.Tool.from(element);
						var streamName = Q.getObject(["state", "streamName"], tool) || Q.getObject(["preview", "state", "streamName"], tool);

						if(streamName !== Q.getObject(["fromStreamName"], instructions)) {
							return;
						}

						Q.Tool.remove(tool.element, true, true);
					});

					_updateChatsCount(instructions, '-');
				}, "Communities/conversations");
			});
		});

		// set onUnseen and flash conversations when new message appear
		Q.Communities.conversationsPredefine();

		return function () {

		};

		var Cc = Communities.conversations = {
			showInterest: function (element, normalizedTitle, category, interest, wasSelected) {
				Q.Text.get('Communities/content', function (err, text) {
					Q.Tool.byId('Q_columns-Communities').close({min: 1}, function () {
						var streamName = 'Streams/interest/' + normalizedTitle;
						var publisherId = Q.Users.communityId;
						this.open({
							title: interest,
							template: 'Communities/templates/conversations',
							fields: {
								'Streams/related': {
									publisherId: publisherId,
									streamName: streamName,
									relationType: 'Communities/conversations',
									creatable: {
										'Streams/chat': {
											title: text.conversations.New,
											preprocess: 'Q.Communities.conversations.callbacks.newConversation'
										}
									},
									editable: false,
									closeable: false,
									onInvoke: function (tool, preview, stream) {
										
									},
									'.Streams_chat_preview_tool': {
										onInvoke: 'Q.Communities.conversations.callbacks.onInvoke'
									}
								}
							},
							onActivate: function () {
								var interestId = streamName.split('/').pop();
								Q.Page.push('conversations/i/' + publisherId + '/' + normalizedTitle);
							}
						}, 1, function (options, index, div, data) {

						});
					}, {animation: {duration: 0}});
				});
				$('.Streams_interest_title').removeClass('Communities_selected_topic');
				$(element).addClass('Communities_selected_topic');
				return false;
			},
			callbacks: {
				onInvoke: function (preview) {
					var ps = preview.state;
					Communities.pushConversationColumn(ps.publisherId, ps.streamName);
				}
			}
		};
	});
})(Q, Q.jQuery);