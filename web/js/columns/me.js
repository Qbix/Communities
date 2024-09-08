"use strict";
(function(Q, $, undefined) {
	
var Users = Q.Users;
var Streams = Q.Streams;
var Communities = Q.Communities;
var Assets = Q.Assets;

Q.exports(function (options, index, columnElement, data) {
	var userId = Q.Users.loggedInUserId();

	// if tab myqr active
	if($(".Communities_tab.Q_current[data-val=myqr]", columnElement).length) {
		var timerId = setInterval(function(){
			// wait till QR code image created
			if (!$(".Communities_me_column_qrcode img", columnElement).length) {
				return;
			}

			clearInterval(timerId);
			// _scrollColumnDown();
		}, 500);
	}

	var icon = Q.getObject('Q.Users.loggedInUser.icon');
	if (icon && !Q.Users.isCustomIcon(icon)) {
		Q.Users.hint('Communities/me avatar', $("#Users_avatar-Communities_me_tool img", columnElement)[0], {
			waitUntilVisible: true
		});
	}

	// tabs
	$(columnElement).off(Q.Pointer.end).on(Q.Pointer.end, ".Communities_tab", function(e){
		var x = Q.Pointer.getX(e);
		var y = Q.Pointer.getY(e);
		var $this = $(document.elementFromPoint(x, y));
		if (!$this.hasClass('Communities_tab')) {
			$this = $(this);
		}
		var val = $this.attr("data-val");

		if ($this.hasClass("Q_current")) {
			return false;
		}

		var $parent = $this.closest(".Communities_tabs");
		$this.addClass("Q_current").siblings(".Communities_tab").removeClass("Q_current");

		$parent.siblings("[class*='_tabContent']").removeClass("Q_current");
		$parent.siblings("[class*='_tabContent'][data-val=" + val + "]").addClass("Q_current");

		// save this tab to browser history
		if ($parent.attr("data-style") === "icons") {
			Q.Page.push('/me/' + val);
		}

		// if QR code tab activated, scroll down
		if(val === 'myqr') {
			_scrollColumnDown();
		}
		Q.Pointer.cancelClick();
	});

	Q.Text.get('Communities/content', function (err, content) {
		// fill QR code
		// Q.addScript("{{Q}}/js/qrcode/qrcode.js", function(){
		// 	var $qrIcon = $(".Communities_me_column_qrcode", columnElement);
		// 	if ($qrIcon.length) {
		// 		new QRCode($qrIcon[0], {
		// 			text: Q.Communities.userInviteUrl,
		// 			width: 250,
		// 			height: 250,
		// 			colorDark : "#000000",
		// 			colorLight : "#ffffff",
		// 			correctLevel : QRCode.CorrectLevel.H
		// 		});
		// 	}
		// });

		// set onUnseen and flash conversations when new message appear
		Q.Communities.conversationsPredefine();
	});

	// add icons to .Q_tool.Users_avatar_tool
	$(".Communities_me_icon[data-value=mobile]", columnElement).off('click').on('click', function () {
		Q.Users.setIdentifier({
			identifierType: "mobile"
		});
	});
	$(".Communities_me_icon[data-value=email]", columnElement).off('click').on('click', function () {
		Q.Users.setIdentifier({
			identifierType: "email"
		});
	});
	$(".Communities_me_icon[data-value=web3]", columnElement).off('click').on('click', function () {
		var $this = $(this);
		Q.Users.setIdentifier({
			identifierType: "web3",
			onSuccess: function () {
				$this.attr('data-checked', true);
			}
		});
	});

	// Watch for new conversations created and create Streams/chat/preview tool
	Q.Streams.get(userId, "Streams/participating", function () {
		var $contents = $(".Communities_inbox_column", columnElement);
		var $noItems = $(".Communities_no_items", columnElement, $contents);

		// create Streams/chat/preview tool on new conversation related
		this.onRelatedTo().set(function(to, from){
			if(from.fromType !== "Streams/chat"){ // only for trip streams
				return;
			}

			// hide message
			$noItems.hide();

			// check if preview stream already exist
			var toolExist = false;
			Q.each($(".Streams_chat_preview_tool", $contents), function () {
				var tool = Q.Tool.from(this, 'Streams/preview');
				var publisherId = Q.getObject("state.publisherId", tool);
				var streamName = Q.getObject("state.streamName", tool);

				if (publisherId === from.fromPublisherId && streamName === from.fromStreamName) {
					toolExist = true;
				}
			});

			if (!toolExist) {
				// create tool
				$("<div>").tool("Streams/preview", {
					publisherId: from.fromPublisherId,
					streamName: from.fromStreamName,
					closeable: false,
					editable: false
				}).tool("Streams/chat/preview")
					.prependTo($contents)
					.activate();
			}
		}, true);

		// remove Streams/chat/preview tool on conversation unrelated
		this.onUnrelatedTo().set(function(to, from){
			if(from.type !== "Streams/chat"){ // only for trip streams
				return;
			}

			// watch for chat streams 10 seconds
			var i = 0, p = 100;
			var timerId = setInterval(function () {
				if ($(".Streams_chat_preview_tool:visible", $contents).length === 0) {
					$noItems.show();
					clearInterval(timerId);
				}

				i++;

				// if more than 10 seconds
				if(i * p > 10000) {
					clearInterval(timerId);
				}
			}, p);
		}, true);
	});

	$(".Communities_events_link", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			Q.handle(Q.urls['Communities/events']);
		}
	});

	$(".Communities_invite", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			Q.prompt(
				Q.text.Streams.invite.chat.prompt,
				function (title) {
					if (!title) {
						return;
					}
					Q.Streams.create({
						publisherId: Q.Users.loggedInUser.id,
						type: 'Streams/chat',
						private: ["invite"],
						readLevel: 0,
						writeLevel: 0,
						adminLevel: 0,
						title: title
					}, function (err, stream) {
						if (!stream) {
							return;
						}
						this.subscribe(); // should be automatic I thought
						this.invite({
							readLevel: 'max',
							writeLevel: 'relate',
							adminLevel: 'invite',
							addLabel: [],
							addMyLabel: []
						});
						// {
						// 	appUrl: Q.urls['Communities/people/' + userId]
						// }
					});
				}
			);
		}
	});

	$(".Communities_people_link", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			Q.handle(Q.urls['Communities/people']);
		}
	});

	$(".Communities_conversations_link", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			Q.handle(Q.urls['Communities/conversations']);
		}
	});

	$(".Communities_profile_logout button", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			$(this).addClass('Q_working');
			Q.Users.logout({
				onSuccess: function () {
					window.location.href = Q.info.baseUrl;
				}
			});
		}
	});

	$(".Communities_profile_manageNotifications button", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			Q.handle(Q.url("participants"), {
				quiet: true
			});
		}
	});

	$(".Communities_invite_people_button", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			Q.Streams.invite(Q.Users.currentCommunityId, 'Streams/experience/main', {
				appUrl: Q.url("welcome"),
				addMyLabel: 'Users/friends'
			});
		}
	});

	Assets.Payments.load();
	$(".Communities_buy_credits_button", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			Assets.Credits.buy();
		}
	});

	$(".Communities_connected_account_button", columnElement).plugin('Q/clickable', {
		onInvoke: function () {
			document.location.href = Q.url('Assets/connected');
		}
	});

	Assets.onCreditsChanged.set(function (credits) {
		var $creditsAmount = $(".Communities_me_credits_amount", columnElement);
		$creditsAmount.text($creditsAmount.text().replace(/\d+/, credits));
	}, true);

	// function _scrollColumnDown() {
	// 	var $obj = $(".Communities_me_column_qrcode", columnElement).closest(".Q_column_slot");
	// 	$obj.scrollTop($obj[0].scrollHeight);
	// }

	// Links section
	var websitesComposer = Q.Tool.from($(".Websites_webpage_composer_tool", columnElement), 'Websites/webpage/composer');
	var linksRelated = Q.Tool.from($("#Streams_related-Communities_profile_links_tool", columnElement), 'Streams/related');
	if (websitesComposer && linksRelated) {
		websitesComposer.state.onScrape.set(function () {
			websitesComposer.createStream();
			websitesComposer.refresh();
		});

		websitesComposer.state.onCreate.set(function () {
			linksRelated.refresh();
		});
	}

	// Social section
	$(".Communities_social_icon[data-type=facebook]", columnElement).off(Q.Pointer.click).on(Q.Pointer.fastclick, function () {
		var $this = $(this);

		$this.addClass("Q_working");

		if ($this.attr('data-connected') === '1') {
			Q.Text.get('Communities/content', function (err, text) {
				Q.confirm(text.accounts.remove.content, function (res) {
					if (!res) {
						return $this.removeClass("Q_working");
					}

					Q.req('Communities/social', 'data', function (err, response) {
						$this.removeClass("Q_working");
						var r = response && response.errors;
						var msg = Q.firstErrorMessage(err, r);
						if (msg) {
							return Q.alert(msg, {
								title: "Sorry"
							});
						}

						$this.attr('data-connected', '0');
					}, {
						method: 'delete',
						fields: {platform: 'facebook'}

					});
				}, {
					title: text.accounts.remove.title,
					ok: text.accounts.remove.ok,
					cancel: text.accounts.remove.cancel
				});
			});

			return;
		}

		Users.login({
			using: "facebook",
			onSuccess: function () {
				$this.attr('data-connected', '1');
			},
			onResult: function () {
				$this.removeClass("Q_working");
			}
		});

		return false;
	});

	var _socialHandler = function () {
		var $this = $(this);
		var social = $this.attr('data-type');
		$this.addClass('Q_working');

		Q.Text.get('Communities/content', function (err, content) {
			Q.req('Communities/profileInfo', 'social', function (err, data) {
				$this.removeClass('Q_working');

				var msg = Q.firstErrorMessage(err, data && data.errors);
				if (msg) {
					return;
				}

				var value = data.slots.social;
				Q.prompt(null, function (username) {
					// dialog closed
					if (username === null) {
						return;
					}

					$this.addClass('Q_working');
					Q.req('Communities/profileInfo', 'social', function (err, data) {
						$this.removeClass('Q_working');
						var msg = Q.firstErrorMessage(err, data && data.errors);
						if (msg) {
							return;
						}

						$this.attr('data-connected', data.slots.social);
					}, {
						fields: {
							social: social,
							value: username,
							action: "update"
						}
					});
				}, {
					title: content.me.UpdateSocialTitle.replace('{{1}}', social),
					initialText: value
				});
			}, {
				fields: {
					social: social,
					action: "get"
				}
			});
		});

		return false;
	};

	$(".Communities_social_icon[data-type!=facebook]", columnElement).on(Q.Pointer.fastclick, _socialHandler);
	$(".Communities_profile[data-val=language] select", columnElement).off('change').on('change', function () {
		var $this = $(this);

		$this.addClass('Q_working');

		Q.req('Users/language', ['result'], function (err, data) {
			$this.removeClass('Q_working');

			var msg = Q.firstErrorMessage(err, data && data.errors);
			if (msg) {
				return alert(msg);
			}

			Q.handle(location.href); // reload interface

		}, {
			method: 'post',
			fields: {
				language: $this.val().toLowerCase()
			}
		});
	});

	columnElement.forEachTool("Assets/history", function () {
		// open stream column
		this.state.onStream.set(function (publisherId, streamName) {
			// Calendars/event streams
			if (streamName.startsWith('Calendars/event')) {
				Communities.navigate('event/' + publisherId + '/' + streamName.split('/').pop());
			}
		});

		// open profile column
		this.state.onClient.set(function (userId, name) {
			if (Users.isCommunityId(userId)) {
				Communities.openCommunityProfile.call(this.element, userId);
			} else {
				Communities.openUserProfile.call(this.element, userId);
			}
		});
	});

    var subscribeEl = $('.Communities_subscribe');
    if(subscribeEl) {
        $(subscribeEl).tool("Calendars/ics/subscribe", {
			onRefresh: function () {
				Q.Users.hint(
					'Communities/schedule subscribe',
					$('.Calendars_subscribe_btn', this.element), {
						waitUntilVisible: true
					}
				);
			},
			waitUntilVisible: true
		}).activate();
    }

	$('.Communities_profile_userId').off('click').on('click', function () {
		Q.Clipboard.copy($(this).text().trim());
		Q.alert(Q.text.Users.clipboard.Copied);
	});

	if (!Q.info.isMobile) {
		function _resizeTabs() {
			var $tabs = $(".Communities_tabs");
			if ($tabs.width() < 800 && $tabs.children().length > 3) {
				$('.Communities_tab', $tabs).each(function () {
					var c = this.computedStyle('content', '::before');
					if (!c || c == 'none') {
						return;
					}
					$(this).css('font-size', 0);
					$(this).attr('data-touchlabel', $(this).text().trim());
				})
			}
		}
	
		_resizeTabs();
		Q.Tool.byId('Q_columns-Communities').state.onActivate.set(_resizeTabs, true);
	}
});

})(Q, Q.jQuery);