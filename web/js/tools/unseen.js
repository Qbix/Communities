(function (window, Q, $, undefined) {
	
/**
 * @module Communities
 */
	
/**
 * Show unseen streams using Q/badge.
 * @class Communities
 * @constructor
 * @param {Object} [options] Override various options for this tool
 *  @param {string} options.goal What to count? Can be "events" or "conversations".
 *  @param {object} [options.badge] settings for Q/badge tool
 *  @param {Q.Event} [options.onUnseen] event execute when unseen stream appear
 *  @param {Q.Event} [options.onRemove] event execute when unseen stream closed
 */

Q.Tool.define("Communities/unseen", function () {
	var tool = this;
	var state = this.state;
	var userId = Q.Users.loggedInUserId();

	// only for logged users
	if (!userId) {
		return;
	}

	// we need mysql server time to get diff with client local time
	var mysqlTime = Q.getObject("Q.plugins.Communities.mysqlTime");
	if (!mysqlTime) {
		throw Error('Communities/unseen: mysqlTime undefined');
	}

	// calculate diff between server time and local time in milliseconds
	tool.timeDiff = mysqlTime*1000 - (new Date()).getTime();

	switch(state.goal) {
		case "events":
			tool.initEvents();
			break;
		case "conversations":
			tool.initConversations();
			break;
		case "services":
			tool.initServices();
			break;
	}
},
{
	badge: {
		size: "16px",
		'font-size': "10px",
		right: "5px",
		className: "Streams_chat_unseen",
		display: 'block'
	},
	onUnseen: new Q.Event()
},
{
	/**
	 * Init process looking for unseen conversations.
	 * @method initConversations
	 */
	initConversations: function () {
		var tool = this;

		// if we are not on Communities/me page
		if (!tool.onMePage()) {
			// get last message time from cache and request server for
			// suitable conversations (publisherId, streamName) with messages later than this date.
			// Then pass to updateBadge this array to create badge
			Q.req("Communities/unseen", "conversations", function (err, data) {
				var fem = Q.firstErrorMessage(err, data);
				if (fem) {
					return console.warn("Communities/unseen/conversations: " + fem);
				}

				var unseen = Q.getObject(["slots", "conversations"], data) || [];

				Q.each(unseen, function (index, unseen) {
					tool.updateBadge(unseen);
				});
			}, {
				fields: {
					fromTime: tool.cacheGet() || 0
				}
			});
		}

		// visit Communities/me page event. When visit this page, unseen counter reset.
		Q.page("Communities/me", function (err, response) {

			// hide badge on Communities/me page
			tool.hideBadge();

			// clear all conversations stored in menu tab
			$.data($(tool.element), "conversations", []);

			// update timestamp to current time, so next time
			// unseen messages will count starting this time
			tool.cacheSet();

			return function () {
				// update timestamp to current time, so next time
				// unseen messages will count starting this time
				tool.cacheSet();
			}
		}, tool.id);

		// collect streams to watch for unseen messages for "me" page
		Q.req("Communities/unseen", "conversations", function (err, data) {
			var fem = Q.firstErrorMessage(err, data);
			if (fem) {
				return console.warn("Communities/unseen/participating: " + fem);
			}

			var participating = Q.getObject(["slots", "conversations"], data) || [];

			Q.each(participating, function (index, participant) {
				// get stream to cache to listen for messages
				Q.Streams.get(participant.publisherId, participant.name, function () {
					// listen Streams/chat/message message to detect new messages
					this.onMessage('Streams/chat/message')
					.set(function (message) {
						// if we currently on Communities/me page - just save current time
						// so next time unseen messages will count starting this time
						if (tool.onMePage()) {
							return tool.cacheSet();
						}

						var param = {
							publisherId: message.publisherId,
							streamName: message.streamName
						};

						tool.updateBadge(param);
					}, tool);
				});
			});
		});
	},
	/**
	 * Init process looking for unseen events.
	 * @method initEvents
	 */
	initEvents: function () {
		var tool = this;
		var state = this.state;
		var userId = Q.Users.loggedInUserId();

		// if we are not on Communities/events page
		if (!tool.onEventsPage()) {
			// check for new non counted events
			Q.req("Communities/unseen", "events", function (err, response) {
				var msg;
				if (msg = Q.firstErrorMessage(err, response && response.errors)) {
					throw new Q.Error(msg);
				}
				var events = Q.getObject(["slots", "events"], response);

				if (!events) {
					return;
				}

				Q.each(events, function (index, value) {
					if (typeof value !== "object") {
						return;
					}

					// add it to badge
					tool.updateBadge(value);
				});
			}, {
				fields: {
					fromTime: tool.cacheGet() || 0
				}
			});
		}

		// visit Communities_events page event
		Q.page("Communities/events", function () {

			// hide badge on Communities/events page
			tool.hideBadge();

			// clear all events stored in menu tab
			$.data($(tool.element), "events", null);

			// update timestamp to current time, so next time
			// unseen events will count starting this time
			tool.cacheSet();

			return function () {
				// update timestamp to current time, so next time
				// unseen events will count starting this time
				tool.cacheSet();
			}
		}, tool.id);

		var communityId = Q.Users.currentCommunityId || Q.Users.communityId;
		// need to get this stream to listen if new events created
		// new events will relate to this category, but if we don't
		// get it on client side we will not get messages
		Q.Streams.retainWith(true).get(communityId, "Calendars/calendar/main", function(err){
			if (err) {
				return;
			}

			// listen Streams/relatedTo message to detect new events
			this.onMessage('Streams/relatedTo').set(function (message) {
				var instructions = message.getAllInstructions();
				var publisherId = instructions.fromPublisherId;
				var streamName = instructions.fromStreamName;

				// only Calendars/events streams
				if(Q.getObject(["type"], instructions) !== "Calendars/events") {
					return;
				}

				// some time the actions to relate new stream to needed categories to
				// detect that this event suitable for current user (for example Places/nearby)
				// need more time. So need this delay to wait while stream related to all needed categories.
				setTimeout(function(){
					// check if new event appropriate for current user
					Q.req("Communities/unseen", "events", function (err, response) {
						var msg;
						if (msg = Q.firstErrorMessage(err, response && response.errors)) {
							throw new Q.Error(msg);
						}

						// if new event doesn't fill current user conditions - exit
						if (!response.slots.events) {
							return;
						}

						// execute event to message all listeners that new event added.
						Q.handle(state.onUnseen, instructions, [publisherId, streamName]);

						// if we currently on Communities/events page - just save current time
						// so next time unseen events will count starting this time
						if (tool.onEventsPage()) {
							return tool.cacheSet();
						}

						tool.updateBadge({publisherId: publisherId, streamName: streamName});
					}, {
						fields: {
							publisherId: publisherId,
							streamName: streamName
						}
					});
				}, 5000);
			}, tool);

			// listen Streams/unrelatedTo message to remove closed events
			this.onMessage('Streams/unrelatedTo').set(function (message) {
				var instructions = message.getAllInstructions();
				var publisherId = instructions.fromPublisherId;
				var streamName = instructions.fromStreamName;

				// only Streams/chat streams
				if(Q.getObject(["type"], instructions) !== "Calendars/events") {
					return;
				}

				// execute event to message all listeners that new event added.
				Q.handle(state.onRemove, instructions, [publisherId, streamName]);

				// if we currently on Communities/events page - just save current time
				// so next time unseen events will count starting this time
				if (tool.onEventsPage()) {
					return tool.cacheSet();
				}

				tool.updateBadge({publisherId: publisherId, streamName: streamName}, '-');
			}, tool);
		});
	},
	/**
	 * Init process looking for unseen services.
	 * @method initServices
	 */
	initServices: function () {
		var tool = this;
		var state = this.state;
		var userId = Q.Users.loggedInUserId();

		// visit Communities_services page event
		Q.page("Communities/services", function () {

			// hide badge on Communities/services page
			tool.hideBadge();

			// clear all services stored in menu tab
			$.data($(tool.element), "services", null);

			// update timestamp to current time, so next time
			// unseen services will count starting this time
			tool.cacheSet();

			return function () {
				// update timestamp to current time, so next time
				// unseen services will count starting this time
				tool.cacheSet();
			}
		}, tool.id);

		var communityId = Q.Users.currentCommunityId || Q.Users.communityId;
		// need to get this stream to listen if new availabilities created
		// new availabilities will relate to this category, but if we don't
		// get it on client side we will not get messages
		Q.Streams.retainWith(true).get(communityId, "Calendars/availabilities/main", function(err){
			if (err) {
				return;
			}

			var stream = this;
			if (userId && this.testWriteLevel(10)) {
				// check if user participated this stream, and participate if not
				// because if not participated we will not get messages
				Q.Streams.Participant.get(this.fields.publisherId, this.fields.name, userId, function (err, participant) {
					if (err) {
						return;
					}

					if (Q.getObject("state", participant) !== "participating") {
						stream.join();
					}
				});
			}

			// listen Streams/relatedTo message to detect new services
			this.onMessage('Streams/relatedTo').set(function (message) {
				var instructions = message.getAllInstructions();
				var publisherId = instructions.fromPublisherId;
				var streamName = instructions.fromStreamName;

				// only Calendars/services streams
				if(Q.getObject(["type"], instructions) !== "Calendars/availability") {
					return;
				}

				// execute availability to message all listeners that new availability added.
				Q.handle(state.onUnseen, instructions, [publisherId, streamName]);

				// if we currently on Communities/services page - just save current time
				// so next time unseen services will count starting this time
				if (tool.onAvailabilitiesPage()) {
					return tool.cacheSet();
				}

				tool.updateBadge({publisherId: publisherId, streamName: streamName});
			}, tool);

			// listen Streams/unrelatedTo message to remove closed events
			this.onMessage('Streams/unrelatedTo').set(function (message) {
				var instructions = message.getAllInstructions();
				var publisherId = instructions.fromPublisherId;
				var streamName = instructions.fromStreamName;

				// only Streams/chat streams
				if(Q.getObject(["type"], instructions) !== "Calendars/events") {
					return;
				}

				// execute event to message all listeners that new event added.
				Q.handle(state.onRemove, instructions, [publisherId, streamName]);

				// if we currently on Communities/events page - just save current time
				// so next time unseen events will count starting this time
				if (tool.onAvailabilitiesPage()) {
					return tool.cacheSet();
				}

				tool.updateBadge({publisherId: publisherId, streamName: streamName}, '-');
			}, tool);
		});
	},
	/**
	 * Create Q/badge tool (if not exist) with unseen conversations counter.
	 * @method updateBadge
	 * @param {object} params Object in format {publisherId: ..., streamName: ...}
	 * @param {string} [action = '+'] + or -
	 */
	updateBadge: function (params, action) {
		if (typeof params !== "object" || !params.publisherId || !params.streamName) {
			return console.warn("Communities/unseen updateBadge: invalid params");
		}

		action = action || '+';

		var tool = this;
		var state = this.state;
		var $te = $(this.element);

		// if badge options empty - means no Q/badge tool needed
		if (!state.badge) {
			return;
		}

		// add style for badge
		Q.addStylesheet("{{Communities}}/css/tools/unseen.css");

		var streamName = params.publisherId + params.streamName;
		var streams = $.data($te[0], state.goal) || [];
		var inArray = (streams && streams.indexOf(streamName) >= 0);

		if (action === '+') {
			// if stream already counted - do nothing
			if (!inArray) {
				streams.push(streamName);
			}

		} else {
			// if stream not counted - do nothing
			if (inArray) {
				// remove streamName from array
				streams = $.grep(streams, function(value) {
					return value !== streamName;
				});
			}
		}

		// store streams counter to element data
		$.data($te[0], state.goal, streams);

		var total = streams.length;
		var qBadge = Q.Tool.from($te[0], "Q/badge");
		if (Q.typeOf(qBadge) === "Q.Tool") {
			qBadge.state.tr.content = total;
			qBadge.state.tr.display = total > 0 ? "block" : "none";
		} else {
			$te.tool("Q/badge", {
				tr: state.badge
			}).activate(function () {
				// set content of just create Q/badge tool
				var qBadge = Q.Tool.from(tool.element, "Q/badge");

				if (Q.typeOf(qBadge) !== "Q.Tool") {
					return console.warn("Communities/unseen error: wrong Q/badge tool");
				}

				qBadge.state.tr.content = total;
				qBadge.state.tr.display = total > 0 ? "block" : "none";
			});
		}
	},
	/**
	 * Check whether we currently on Communities/me page
	 * @method onMePage
	 */
	onMePage: function () {
		return Q.getObject(["info", "uriString"], Q) === "Communities/me";
	},
	/**
	 * Check whether we currently on Communities/events page
	 * @method onEventsPage
	 */
	onEventsPage: function () {
		return Q.getObject(["info", "uriString"], Q) === "Communities/events";
	},
	/**
	 * Check whether we currently on Communities/availabilities page
	 * @method onAvailabilitiesPage
	 */
	onAvailabilitiesPage: function () {
		return Q.getObject(["info", "uriString"], Q) === "Communities/services";
	},
	/**
	 * Found Q/badge tool and hide it.
	 * @method hideBadge
	 */
	hideBadge: function () {
		var qBadge = Q.Tool.from(this.element, "Q/badge");

		if (Q.typeOf(qBadge) === "Q.Tool") {
			qBadge.state.tr.display = "none";
		}
	},
	/**
	 * Create date string for server (with time diff between client and server) and save it to cache.
	 * Then use this date to get unseen messages (created later than this date)
	 * @method cacheSet
	 */
	cacheSet: function () {
		var userId = Q.Users.loggedInUserId();
		var cache = Q.Cache.local(this.name + '/' + this.state.goal);

		// IMPORTANT
		// get current timestamp, summed with time diff of server and create date string for server
		var date = new Date();
		date.setTime(date.getTime() + this.timeDiff);
		var mysqlDate = [date.getFullYear(), (date.getMonth() + 1), date.getDate()].join('-') + ' ' + [date.getHours(), date.getMinutes(), date.getSeconds()].join(':');

		cache.set(userId, mysqlDate);

		return mysqlDate;
	},
	/**
	 * Get timestamp from cache
	 * @method cacheGet
	 */
	cacheGet: function () {
		var userId = Q.Users.loggedInUserId();
		var cache = Q.Cache.local(this.name + '/' + this.state.goal);

		return Q.getObject("cbpos", cache.get(userId)) || 0;
	}
});

})(window, Q, Q.jQuery);