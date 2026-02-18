"use strict";
(function(Q, $, undefined) {
	
var Users = Q.Users;

Q.exports(function (options, index, column, data) {
	Q.addStylesheet('{{Communities}}/css/columns/profile.css', { slotName: 'Communities' });
	var $communitiesProfileContent = $('.Communities_profile_content', column);
	var $columnSlot = $('.Q_column_slot', column);
	var userId = $communitiesProfileContent.attr('data-userId');
	var loggedInUserId = Users.loggedInUserId();

	// split profile content to 2 columns if width > 700
	var columnWidth = $(column).width();
	if (columnWidth > 700) {
		var $rightColumn = $("<div>").appendTo($communitiesProfileContent);
		$(">*", $communitiesProfileContent).appendTo($rightColumn);

		var $leftColumn = $("<div>").prependTo($communitiesProfileContent);
		$(".Communities_column_profile_main_avatar", $communitiesProfileContent).appendTo($leftColumn);
		$(".Communities_manage_contacts", $communitiesProfileContent).appendTo($leftColumn);
		$(".Communities_profile_greeting", $communitiesProfileContent).appendTo($leftColumn);

		$communitiesProfileContent.addClass("Communities_wide_column");
		$columnSlot.show();
	}

	var _socialHandler = function () {
		var $this = $(this);
		var url = $this.attr('data-connected');

		if (!url) {
			return;
		}

		if (/^https?:\/\//.test(url)) {
			return Q.openUrl(url);
		}

		var socialUrls = Q.Communities.profile.social;
		var social = $this.attr('data-type');
		var redirectUrl = url.includes(socialUrls[social]) ? url : socialUrls[social] + url;
		if (social === "telegram") {
			redirectUrl = redirectUrl.replace("@", "");
		}
		if (!/^https?:\/\//.test(redirectUrl)) {
			redirectUrl = "https://" + redirectUrl;
		}
		Q.openUrl(redirectUrl);
	};
	Q.each(Object.keys(Q.Communities.profile.social), function (i, value) {
		$(".Communities_social_icon[data-type=" + value + "]", column).on(Q.Pointer.fastclick, _socialHandler);
	});

	//adjust profile sections height
	/*var $sections = $(".Communities_profile_section", $columnSlot);
	var $section = $(".Communities_profile_section:visible", $columnSlot).first();
	var sectionHeight = $section.height();
	var minHeightMeasure = 1;
	if ($columnSlot[0].isOverflowed()) {
		while ($columnSlot[0].isOverflowed()) {
			sectionHeight -= minHeightMeasure;
			$sections.innerHeight(sectionHeight);
		}
	} else {
		while (!$columnSlot[0].isOverflowed()) {
			sectionHeight += minHeightMeasure;
			$sections.innerHeight(sectionHeight);
		}
	}*/

	// interests tool
	var interestsTool = Q.Tool.from($(".Streams_interests_tool", column)[0], "Streams/interests");
	if (interestsTool) {
		interestsTool.state.onReady.add(function () {
			if ($(".Q_expandable_tool:not(.Streams_interests_anotherUserNone)", this.element).length) {
				$("#Communities_profile_interests", column).show();
			}
		}, true);
	}

	// transfer
	$(".Communities_profile_pay", column).on("click", function () {
		if (!loggedInUserId) {
			return Q.Users.login();
		}
		Q.Dialogs.push({
			title: "Send",
			className: "Communities_profile_transferTokens",
			content: $("<div>").tool("Assets/web3/transfer", {
				recipientUserId: userId,
				withHistory: true
			}),
			onActivate: function () {

			}
		});
	});

	// transfer
	$(".Communities_profile_roles", column).on("click", function () {
		var tool = Q.Tool.from($(".Communities_roles_tool", column)[0], "Communities/roles");
		if (!tool) {
			return console.warn("Communities/roles tool not found");
		}
		
		Q.handle(tool.state.onInvoke, tool);
	});

	// Websites/webpage/preview
	column.forEachTool("Websites/webpage/preview", function () {
		var webpagePreview = this;
		var state = this.state;

		Q.Streams.get(state.publisherId, state.streamName, function () {
			$(webpagePreview.element).attr("data-touchlabel", this.getAttribute("url"));
		});
	});

	Q.Text.get('Communities/content', function (err, text) {
		$(".Communities_profile_email", column).off(Q.Pointer.fastclick).on(Q.Pointer.fastclick, function () {
			var $this = $(this);
			$this.addClass("Q_working");
			Q.Streams.get(userId, "Streams/user/emailAddress", function (err) {
				$this.removeClass("Q_working");
				if (err) {
					return;
				}

				window.location = Q.Links.email("", "", this.fields.content);
			});
		});
		$(".Communities_profile_sms", column).off(Q.Pointer.fastclick).on(Q.Pointer.fastclick, function () {
			var $this = $(this);
			$this.addClass("Q_working");
			Q.Streams.get(userId, "Streams/user/mobileNumber", function (err) {
				$this.removeClass("Q_working");
				if (err) {
					return;
				}

				window.location = Q.Links.sms("", this.fields.content);
			});
		});

		// set onUnseen and flash conversations when new message appear
		Q.Communities.conversationsPredefine();

		$(column)
		.off(Q.Pointer.click, ".Communities_manage_contacts button, .Communities_manage_contacts .Communities_label")
		.on(Q.Pointer.click, ".Communities_manage_contacts button, .Communities_manage_contacts .Communities_label", function () {
			if (!loggedInUserId && Q.info.isMobile) {
				location.href = Q.url("{{baseUrl}}/Users/" + userId + ".vcf");
				return;
			}

			Q.Users.login({
				unlessLoggedIn: true,
				onSuccess: { Users: function () {
					Q.Dialogs.push({
						title: text.profile.relationships,
						content: Q.Tool.setUpElementHTML('div', 'Users/labels', {
							contactUserId: userId,
							canAdd: true
						}),
						apply: true,
						onClose: function () {
							if (!loggedInUserId) {
								return;
							}
							var p = Q.pipe(['contacts', 'labels'], function (params) {
								var contacts = params.contacts[1];
								var labels = params.labels[1];
								var hasLabels = false;
								var $c = $('.Communities_manage_contacts .Communities_has_labels', column);
								$c.empty();
								Q.each(labels, function (i, label) {
									var found = null;
									Q.each(contacts, function (j, contact) {
										if (contact.label === label.label) {
											found = contact;
											return false;
										}
									});
									if (found) {
										hasLabels = true;
										$c.append($('<span class="Communities_label">').append(
											$("<img />", {src: label.iconUrl()}),
											$("<span class='Communities_label_title' />").text(label.title)
										));
									}
								});

								if (hasLabels) {
									$('.Communities_manage_contacts', column).removeClass('Communities_no_labels').addClass('Communities_has_labels');
								} else {
									$('.Communities_manage_contacts', column).removeClass('Communities_has_labels').addClass('Communities_no_labels');
								}
							});
							Users.getLabels.force(loggedInUserId, p.fill('labels'));
							Users.getContacts.force(loggedInUserId, null, userId, p.fill('contacts'));
							Q.Users.hint('Communities/profile/openChat', $('.Communities_profile_chat', column), {
								show: { delay: 1000 }
							});
						}
					});
				}}
			});
		});

        Q.Users.hint('Communities/profile/manageContact', $('.Communities_manage_contacts', column), {
            show: { delay: 1000 }
        });

		$('.Communities_profile_block', column).off(Q.Pointer.fastclick).on(Q.Pointer.fastclick, function(){
			var $this = $(this);
			var action = $this.attr("data-action");

			$this.addClass("Q_working");

			Q.req("Communities/usersBlock", ["result"], function(err, response){
				$this.removeClass("Q_working");

				var msg = Q.firstErrorMessage(err, response && response.errors);
				if (msg) {
					return Q.alert(msg);
				}

				if (action === "block") {
					Q.Streams.Avatar.get(userId, function (err, avatar) {
						if (err) {
							return
						}

						Q.alert(text.profile.blockUserAlert, {
							title: text.profile.blockUserAlertTitle.interpolate({displayName: avatar.displayName()})
						});
					});
				}

				$this.attr({
					"data-action": action === "block" ? "unblock" : "block",
					"data-touchlabel": action === "block" ? text.profile.UnblockThisUser : text.profile.BlockThisUser
				});
			}, {
				method: "POST",
				fields: {
					action: action,
					userId: userId
				}
			});
		});

		// chat tool
		var $chatIcon = $(".Communities_profile_chat", column);
		var chatTool = Q.Tool.from($(".Streams_chat_tool", column)[0], "Streams/chat");
		var _chatIconBadge = function () {
			$chatIcon.tool("Q/badge", {
				tr: {
					className: "Communities_profile_chat_approved",
					top: "-5px",
					right: "-5px",
					size: "20px"
				}
			}).activate();
		};
		if (chatTool) {
			_chatIconBadge();
		}
		$chatIcon.on(Q.Pointer.fastclick, function () {
			if (!loggedInUserId) {
				return Q.Users.login();
			}
			if (loggedInUserId === userId) {
				return Q.alert(text.profile.YouCantChatWithYourself);
			}

			if (chatTool) {
				return chatTool.$input.focus();
			}

			var credits = Q.Assets.credits.spend.chat.private;
			if (credits) {
				Q.confirm(text.profile.CreatePrivateChat.interpolate({credits: credits}), _send);
			} else {
				_send(true);
			}
			function _send (proceed) {
				if (!proceed) {
					return;
				}

				var $trigger = $('.Communities_profile_chat', column);
				$trigger.addClass('Q_working');

				Q.Assets.pay({
					amount: credits,
					currency: "credits",
					userId: userId,
					reason: "CreatePrivateChat",
					onSuccess: function () {
						Q.req('Communities/chat', ['stream'], function (err, data) {
							var fem = Q.firstErrorMessage(err, data);
							if (fem) {
								return Q.alert(fem);
							}
							var $chat = $('<div />').tool('Streams/chat', {
								publisherId: data.slots.stream.publisherId,
								streamName: 'Streams/chat/' + loggedInUserId
							});
							$(".Communities_profile_chat_container", column).empty()
							.append($chat).activate(function (container) {
								this.state.onRefresh.set(function () {
									chatTool = this;
									this.$input.focus();
								}, "Communities_columns_profile");
							});
							_chatIconBadge();
						}, {
							method: 'post',
							fields: {
								publisherId: userId
							}
						});
						$trigger.removeClass('Q_working');
					},
					onFailure: function () {
						$trigger.removeClass('Q_working');
						Q.Assets.Credits.buy({
							title: Q.text.Assets.credits.NeedMoreCredits,
							onSuccess: function () {
								_send(proceed);
							}
						})
					}
				});
			}
		});
	});

	const $prevColumn = $(column).prev();
	if ($prevColumn.length && $prevColumn.hasClass('Communities_column_event')) {
		const eventTool = Q.Tool.from($(".Calendars_event_tool", $prevColumn)[0], "Calendars/event");
		if (eventTool) {
			eventTool.handleRoles(userId, $communitiesProfileContent);
		}
	}
});

})(Q, Q.jQuery);