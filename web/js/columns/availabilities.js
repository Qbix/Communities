"use strict";
(function(Q, $, undefined) {

var Users = Q.Users;
var Streams = Q.Streams;
var Communities = Q.Communities;

Q.exports(function (options, index, column, data) {
	var $content = $(".Q_column_slot", column);
	var $trips = $('.Travel_trips_tool', column);
	if ($trips.length) {
		var tripsTool = Q.Tool.from($trips[0]);
		tripsTool.state.onInvoke && tripsTool.state.onInvoke.set(Communities.pushTripColumn, 'Communities');
	}
	var loggedUserId = Q.Users.loggedInUserId();
	var $servicesColumn = $(".Communities_availabilities_column", column);
	if (!$servicesColumn.length) {
		return;
	}
	var $servicesBox = $(".Communities_availabilities", $servicesColumn);
	var $titleContainer = $('.Q_columns_title_container', column);
	var underCommunityColumn = $servicesColumn.closest(".Communities_column_community").length;

	// if services column loaded from community column (services tab)
	// data.communityId defined to community selected
	var communityId = Q.getObject('communityId', data);

	Q.addStylesheet('{{Communities}}/css/columns/availabilities.css');

	$servicesBox[0].forEachTool("Calendars/availability/preview", function () {
		var availabilityPreview = this;
		availabilityPreview.state.onInvoke.set(function (stream) {
			Q.invoke({
				url: Q.url('newService' + (communityId ? '/' + communityId : '')),
				name: 'newService',
				trigger: availabilityPreview.element,
				onActivate: function (column) {
					var serviceBrowser = Q.Tool.from($(".Calendars_service_browser_tool", column)[0]);
					serviceBrowser.selectAvailability(availabilityPreview);
				}
			});
		}, this);
	});

	// listen for unrelatedFrom message of main category to remove preview tool
	Streams.get(Users.currentCommunityId, "Calendars/availabilities/main", function (err) {
		var msg = Q.firstErrorMessage(err);
		if (msg) {
			return;
		}

		this.onMessage('Streams/unrelatedTo').set(function (message) {
			var instructions = message.getAllInstructions();
			var relationType = Q.getObject(["type"], instructions);

			if (relationType !== "Calendars/availability") {
				return;
			}

			$(".Calendars_availability_preview_tool", $servicesColumn).each(function(i, element){
				var tool = Q.Tool.from(this, "Streams/preview");
				var streamName = Q.getObject(["state", "streamName"], tool) || Q.getObject(["preview", "state", "streamName"], tool);
				if(streamName !== Q.getObject(["fromStreamName"], instructions)) {
					return;
				}

				Q.Tool.remove(tool.element, true, true);
			});
		}, "Communities/services");
	});

	// apply infinitescroll tool
	$content.tool('Q/infinitescroll', {
		onInvoke: function () {
			var infiniteTool = this;
			var offset = $(">.Calendars_availability_preview_tool:visible", $servicesBox).length;

			// skip duplicated (same offsets) requests
			if (!isNaN(this.state.offset) && this.state.offset >= offset) {
				return;
			}

			infiniteTool.setLoading(true);
			this.state.offset = offset;

			// if this scripts loaded under community column, load services from this community
			var communityId = underCommunityColumn ? Q.getObject("Q.Communities.manageCommunityId") || null : null;

			Q.req('Communities/services', 'load', function (err, data) {
				infiniteTool.setLoading(false);
				err = Q.firstErrorMessage(err, data);
				if (err) {
					return console.error(err);
				}

				if (data.slots.load.length) {
					$(".Communities_no_items", $servicesColumn).hide();
				}

				Q.each(data.slots.load, function () {
					$(this).appendTo($servicesBox).activate();
				});
			}, {
				fields: {
					experienceId: Q.getObject('Q.Communities.services.experienceId') || null,
					communityId: communityId,
					offset: offset
				}
			});
		}
	}).activate();

	Q.Text.get('Communities/content', function (err, text) {
		var _filterServices = function () {
			var filter = $(this).val();
			var allServices = $(".Communities_availabilities_column .Communities_services .Calendars_availability_preview_tool");
			Q.each(allServices, function () {
				var $this = $(this);

				if (!filter || $(".Calendars_service_titleContent", this).text().toUpperCase().indexOf(filter.toUpperCase()) >= 0) {
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

		Q.Template.set("Communities_filter_location_loc",
			'<div class="Streams_preview_tool Places_location_preview_tool">' +
			'	<div class="Places_location_preview Q_clearfix">' +
			'		<img src="{{img}}" alt="icon" class="Places_location_preview_icon">' +
			'		<div class="Places_location_preview_contents">' +
			'			<h3>{{venue}}</h3>' +
			'			<div class="Places_location_preview_address">{{address}}</div>' +
			'			<input type="hidden" name="streamName" value="{{name}}">' +
			'		</div>' +
			'	</div>' +
			'</div>'
		);
		Q.Template.set("Communities_filter_location_area",
			'<div class="Streams_preview_tool Places_area_preview_tool">' +
			'	<div class="Streams_preview_container Streams_preview_view Q_clearfix">' +
			'		<img alt="icon" class="Streams_preview_icon" src="{{src}}">' +
			'		<div class="Streams_preview_contents " style="width: 205px;">' +
			'			<h3 class="Streams_preview_title Streams_preview_view">{{title}}</h3>' +
			'			<input type="hidden" name="streamName" value="{{title}}">' +
			'		</div>' +
			'	</div>' +
			'</div>'
		);
		Q.Template.set("Communities_filter_location",
			'<div class="Communities_locations_filter">'
			+ '	<input class="Communities_locations_filter_input" placeholder="{{text.services.FilterLocationsPlaceHolder}}">'
			+ '</div>'
			+ '<button class="Communities_filter_location_all">{{text.services.AllLocations}}</button>'
			+ '	{{{relatedTool}}}'
			+ '</div>'
		);

		function _filterByLocation() {
			var $this = $(this);
			var $value = $(".Communities_filter_value", this);
			var relatedTool = '';

			$this.addClass('Q_working');

			_getUsedLocations(function () {
				var preloadLocations = this;
				$this.removeClass('Q_working');

				Q.each(preloadLocations, function (index, location) {
					location.img = Q.url('{{Places}}/img/icons/location/40.png');
					Q.Template.render('Communities_filter_location_loc', location, function (err, html) {
						relatedTool += html;
					});

					if (location.areas){
						Q.each(location.areas, function (index, area) {
							area.src = Q.url('{{Places}}/img/icons/area/40.png');
							Q.Template.render('Communities_filter_location_area', area, function (err, html) {
								relatedTool += html;
							});
						});
					}
				});

				Q.Dialogs.push({
					title: text.services.SelectLocation,
					className: "Communities_filter_location",
					template: {
						name: "Communities_filter_location",
						fields: {
							text: text,
							relatedTool: relatedTool
						}
					},
					onActivate: function (dialog) {
						var possibleServices = 'keyup.Streams'
							+ ' blur.Streams'
							+ ' update.Streams'
							+ ' paste.Streams'
							+ ' filter'
							+ ' Q_refresh';
						$('.Communities_locations_filter_input', dialog)
							.plugin('Q/placeholders')
							.on(possibleServices, Q.debounce(function (evt) {
								var $this = $(this);
								if (evt.keyCode === 27) {
									$this.val('');
								}

								var filter = $this.val();
								var allLocations = $(".Streams_preview_tool", dialog);

								Q.each(allLocations, function () {
									if ($("h3", this).text().toUpperCase().indexOf(filter.toUpperCase()) >= 0) {
										$(this).show();
									} else {
										$(this).hide();
									}
								});
							}, 100));

						var _loadFilteredServices = function (text, location) {
							$value.text(text);
							Q.Dialogs.pop();

							$(".Calendars_availability_preview_tool", $servicesBox).each(function () {
								var tool = Q.Tool.from(this, "Calendars/availability/preview");
								if (!tool) {
									return;
								}
								var $toolElement = $(tool.element);

								if (!location) {
									return $toolElement.show();
								}

								var toolLocation = Q.Places.Location.fromStream(tool.stream);
								var venue = Q.getObject(["name"], toolLocation) || Q.getObject(["venue"], toolLocation);
								if (venue === text || Q.getObject(["area", "title"], toolLocation) === text) {
									$toolElement.show();
								} else {
									$toolElement.hide();
								}
							});
						};

						// all locations
						$(".Communities_filter_location_all", dialog).on(Q.Pointer.fastclick, function () {
							_loadFilteredServices($(this).text(), null);
						});

						// filter by location
						$(dialog).on(Q.Pointer.fastclick, ".Streams_preview_tool", function () {
							var text = $("h3", this).text() || $(".Places_location_preview_address", this).text();
							_loadFilteredServices(text, {
								streamName: $("input[name=streamName]", this).val()
							});
						});
					}
				});
			});
		}

		function _filterByInterests() {
			Q.Dialogs.push({
				title: text.services.FilterbyInterest,
				className: 'Streams_dialog_interests Streams_dialog_interests_preloading',
				stylesheet: '{{Q}}/css/tools/expandable.css',
				content: Q.Tool.setUpElement('div', 'Streams/interests', {
					filter: text.services.ShowActivities,
					all: text.services.AllInterests,
					ordering: Communities.services.interests.ordering,
					onClick: function (element, normalized, category, interest, wasSelected) {
						$(element).addClass('Q_selected');
						$('h2', element).addClass('Q_expanded');
						Q.Dialogs.pop();
						_updatedInterests(normalized, category, interest);
						return false;
					},
					onReady: function () {
						var te = this.element;
						var $dialog = $(te).closest(".Streams_dialog_interests");

						_getUsedInterests(function () {
							var existInterests = this;
							if (Q.isEmpty(existInterests)) {
								// remove preloader
								return $dialog.removeClass("Streams_dialog_interests_preloading");
							}

							// create list of allowed interests ids
							var allowedIds = [];
							for(var i in existInterests) {
								allowedIds.push("Streams_interest_title_" + existInterests[i].name.split(/\//).pop());
							}

							// remove interests out of allowedIds
							Q.each($(".Streams_interest_title", te), function (index, element) {
								if (allowedIds && allowedIds.indexOf(element.id) < 0) {
									element.remove();
								}
							});

							// remove subcategories and categories
							Q.each($(".Streams_interests_container .Q_expandable_tool", te), function (index, element) {
								if (!$(".Streams_interest_title", element).length) {
									element.remove();
								}
							});

							// remove preloader
							$dialog.removeClass("Streams_dialog_interests_preloading");
						});
					}
				})
			});
		}

		var _newAvailability = function () {
			var $this = $(this);
			$this.addClass('Q_pop');
			setTimeout(function(){
				$this.removeClass('Q_pop');
			}, 1000);
			var communityId = Users.currentCommunityId;

			// create dummy Calendars/availability tool
			var preview = Q.Tool.from($(".Calendars_availability_composer", $servicesColumn)[0], "Streams/preview");
			if (preview) {
				preview.create();
			} else {
				$("<div class='Calendars_availability_composer'>").appendTo($servicesColumn).tool("Streams/preview", {
					publisherId: communityId,
					closeable: true,
					editable: false,
					related: {
						publisherId: communityId,
						streamName: "Calendars/availabilities/main",
						type: "Calendars/availability"
					},
					creatable: {
						title: "",
						clickable: false,
						addIconSize: 0,
						streamType: "Calendars/availability"
					}
				}).tool("Calendars/availability/preview").activate(function () {
					Q.Tool.from(this.element, "Streams/preview").create();
				});
			}
		};

		$("#Communities_new_service_button", column).on(Q.Pointer.fastclick, _newAvailability);

		// <apply FaceBook column style>
		if (!underCommunityColumn && Q.getObject('layout.columns.style', Communities) === 'facebook') {
			// Create services search
			var $serviceFilter = $('<input name="query" class="Communities_serviceChooser_input" placeholder="' + text.services.filterServices + '">')
				.on('input', _filterServices);

			var icons = [
				$("<i class='qp-communities-interests'></i>").on(Q.Pointer.fastclick, _filterByInterests),
				$("<i class='qp-communities-location'></i>").on(Q.Pointer.fastclick, _filterByLocation),
				$("<i class='qp-communities-search Communities_chooser_trigger'></i>")
			];

			if (Q.getObject('Q.Communities.newEventAuthorized')) {
				icons.push($("<i class='qp-communities-plus'></i>").on(Q.Pointer.fastclick, _newAvailability));
			}

			$titleContainer.tool('Communities/columnFBStyle', {
				icons: icons,
				filter: [$serviceFilter]
			}, 'Services_column').activate();
		}
		// </apply FaceBook column style>

		($((underCommunityColumn ? '.Communities_community_controls[data-tab=services] ' : '') + '.Communities_filter_locations', column)).off(Q.Pointer.click).on(Q.Pointer.click, _filterByLocation);
		($((underCommunityColumn ? '.Communities_community_controls[data-tab=services] ' : '') + '.Communities_filter_interests', column)).off(Q.Pointer.click).on(Q.Pointer.click, _filterByInterests);
		$('input[name=query].Communities_serviceChooser_input')
			.plugin('Q/placeholders')
			.off('input')
			.on('input', _filterServices);

		// create dummy Communities/unseen/services tool to listen new services
		$(".Q_columns_title_container", column).tool("Communities/unseen", {
			goal: "services",
			badge: null, // means no need Q/badge tool
			onUnseen: function(publisherId, streamName){

				// if communityId defined - it means that services column loaded in community column
				// and don't show here services published not by current community
				if (communityId && communityId !== publisherId) {
					return;
				}

				if (loggedUserId === publisherId) {
					_addservice(publisherId, streamName);
				}

				(new unseenServicesCounter()).add(publisherId, streamName);

				// hide "no items" message if it exist
				$servicesColumn.attr("data-emptyServices", 0);
			},
			onRemove: function(publisherId, streamName){
				$(".Communities_availabilities_column .Calendars_availability_preview_tool").each(function(i, element){
					var tool = Q.Tool.from(element);

					if(Q.getObject(["preview", "state", "streamName"], tool) !== streamName) {
						return;
					}

					Q.Tool.remove(tool.element, true, true);
				});

				(new unseenServicesCounter()).remove(publisherId, streamName);

				if ($servicesBox.is(':empty')) {
					$servicesColumn.attr("data-emptyServices", 1);
				}
			}
		}).activate();

		$('#Communities_my_location_button')
		.off([Q.Pointer.fastclick, 'Communities'])
		.on([Q.Pointer.fastclick, 'Communities'], function () {
			var tool = Q.Tool.byId("Q_columns-Communities");
		});
		
		var _normalized = '*', _category, _interest;

		$('.Communities_filter_dates', column).on(Q.Pointer.click, true, function () {
			var experienceId = 'main';
			var dates = Communities.dates[experienceId];
			var $container = $('<div class="Communities_dates_container" />');
			var title;
			var $all = $('<div class="Communities_dates_day Communities_dates_all" />')
			.text("All Days")
			.appendTo($container)
			.on(Q.Pointer.fastclick, function () {
				var $this = $(this);
				$this.addClass('Q_selected');
				Q.Dialogs.pop();
				_updatedDate($this);
			}).attr('data-all', 'yes');
			$all.addClass('Q_selected');
			for (var year in dates) {
				var $year = $('<div class="Communities_dates_year_container">').appendTo($container);
				if (Object.keys(dates).length > 1) {
					$year.append($('<h2 />').text(year));
				}
				for (var month in dates[year]) {
					var $month = $('<div class="Communities_dates_month_container">').appendTo($year);
					if (Object.keys(dates).length > 1) {
						title = new Date(year, month-1, 1)
							.toLocaleString().split(', ').slice(1, 2).split(' ')[0];
						$month.append($('<h2 />').text(title));
					}
					var days = dates[year][month];
					for (var i=0, l=days.length; i<l; ++i) {
						var day = days[i];
						title = _dateTitle(new Date(year, month-1, day));
						var $day = $('<div class="Communities_dates_day" />').text(title)
						.appendTo($month)
						.css('cursor', 'pointer')
						.on(Q.Pointer.fastclick, function () {
							var $this = $(this);
							$this.addClass('Q_selected');
							Q.Dialogs.pop();
							_updatedDate($this);
						}).attr({
							'data-year': year,
							'data-month': month,
							'data-day': day
						});
						$day.addClass('Q_selected');
					}
				}
			}
			Q.Dialogs.push({
				title: text.services.FilterByDate,
				className: 'Communities_dialog_dates',
				content: $container[0]
			});
		});

		function _getUsedLocations(callback) {
			var $elements = $(".Calendars_availability_preview_tool", $servicesBox);
			var amount = $elements.length;
			var usedLocations = {};

			if (!amount) {
				return Q.handle(callback, usedLocations);
			}

			var pipe = new Q.Pipe(Array.from(Array(amount).keys()), function () {
				Q.handle(callback, usedLocations);
			});

			Q.each($elements, function (index, element) {
				var previewTool = Q.Tool.from(this, "Streams/preview");
				var attributes = JSON.parse($(this).attr('data-streams-preview') || '{}');
				var publisherId, streamName;

				if (previewTool) {
					publisherId = previewTool.state.publisherId;
					streamName = previewTool.state.streamName;
				} else if (attributes.publisherId && attributes.streamName) {
					publisherId = attributes.publisherId;
					streamName = attributes.streamName;
				} else {
					return;
				}

				Streams.get(publisherId, streamName, function () {
					var location = Q.Places.Location.fromStream(this);
					var locationKey = location.name || location.venue;

					if (!locationKey) {
						return pipe.fill(index)();
					}

					if (!Q.getObject(locationKey, usedLocations)) {
						usedLocations[locationKey] = location;
					}

					if (location.area) {
						var areaKey = location.area.name;

						if (!Q.getObject([locationKey, "areas", areaKey], usedLocations)) {
							Q.setObject([locationKey, "areas", areaKey], location.area, usedLocations);
						}
					}

					pipe.fill(index)();
				});
			});
		}

		function _getUsedInterests(callback) {
			var $elements = $(".Calendars_availability_preview_tool", $servicesBox);
			var amount = $elements.length;
			var usedInterests = [];

			if (!$elements.length) {
				return Q.handle(callback, usedInterests);
			}

			Q.each($elements, function (index, element) {
				var previewTool = Q.Tool.from(this, "Streams/preview");
				var attributes = JSON.parse($(this).attr('data-streams-preview') || '{}');
				var publisherId, streamName;

				if (previewTool) {
					publisherId = previewTool.state.publisherId;
					streamName = previewTool.state.streamName;
				} else if (attributes.publisherId && attributes.streamName) {
					publisherId = attributes.publisherId;
					streamName = attributes.streamName;
				} else {
					return;
				}

				Streams.get(publisherId, streamName, function () {
					var interests = Q.Calendars.Event.getInterests(this);

					if (!Q.isEmpty(interests)) {
						usedInterests = usedInterests.concat(interests);
					}

					if (index >= amount-1) {
						Q.handle(callback, usedInterests);
					}
				});
			});
		}
		function _updatedDate($this) {
			var $value = $('.Communities_filter_dates').find('.Communities_filter_value');
			var title = $this.text();
			var fields = {};
			if ($this.attr('data-all')) {
				$value.text(title);
			} else {
				var year = $this.attr('data-year');
				var month = $this.attr('data-month');
				var day = $this.attr('data-day');
				var d = new Date(year, month-1, day);
				title = _dateTitle(d, true);
				$value.text(title);
				$('.Communities_filter_dates').plugin('Q/clickable');
				fields = {
					filterDates: title
				};
			}
			var _n = _normalized, _c = _category, _i = _interest;
			var url = Q.url('services', fields);
			var column = $('.Q_column_availabilities')[0];
			Q.Tool.clear(column);
			$(column).append(
				$('<img />').attr('src', Q.url('Q/plugins/Q/img/throbbers/loading.gif'))
			);
			Q.handle(url, function () {
				_updatedInterests(_n, _c, _i);
				$value.text(title);
			});
		}
	
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
		
			var tools = Q.Tool.byName('Calendars/availability/preview');
			var interestName = 'Streams/interest/' + normalized;

			// unseen services
			(new unseenServicesCounter()).filter();

			Q.each(tools, function (id) {
				var $te = $(this.element);
				var stream = this.stream;
				if (!Streams.isStream(stream)) {
					return;
				}
				var interest = Q.Calendars.Event.getInterests(stream);
				var match = false;

				if (normalized === '*') {
					return $te.show();
				}

				for (var i in interest) {
					if (interestName === interest[i].name) {
						match = true;
					}
				}

				if (match) {
					$te.show();
				} else {
					$te.hide();
				}
			});
		}

		var addedServices = [];
		// add service tool to the list
		function _addService(publisherId, streamName) {
			var id = Q.normalize(publisherId + '_' + streamName);

			// check if this tool already added
			if (addedServices.includes(id)) {
				return;
			}

			addedServices.push(id);

			var servicePreviewOptions = {};
			$('<div>')
				.tool("Streams/preview", {
					publisherId: publisherId,
					streamName: streamName,
					closeable: false
				}).tool("Calendars/availability/preview", servicePreviewOptions)
				.prependTo($servicesBox)
				.activate(function () {
					$(this.element).addClass("Q_newsflash");
				});

			$servicesColumn.attr("data-emptyServices", 0);
		};

		/**
		 * Class to work with unseen services counter
		 * @class unseenServicesCounter
		 */
		var unseenServicesCounter = function () {
			var unseenServicesCounter = this;
			var $counter = $(".Communities_services_amount", $content);
			var newServicesText = Q.getObject(["services", "newServices"], text);
			if (!newServicesText) {
				return console.warn('Services Count: text not found');
			}

			if (!$counter.length) {
				$counter = $("<div class='Communities_services_amount'>");
				$counter.html(newServicesText.replace('{{1}}', '<span></span>'));
				$counter.prependTo($content);
				$counter.hide();
				$counter.on("click", function(){
					$counter.slideUp(function(){
						var services = $.data(this, "services") || {};
						var publisherId = null;
						var streamName = null;

						for (var key in services){
							if (!services.hasOwnProperty(key)) {
								continue;
							}

							// apply interests filter
							if (_normalized !== '*' && !services[key].interest.includes(_normalized)) {
								continue;
							}

							publisherId = key.split(':')[0];
							streamName = key.split(':')[1];

							// add service to services list
							_addService(publisherId, streamName);

							// remove service from counter
							unseenServicesCounter.remove(publisherId, streamName);
						}
					});
				});
			}

			/**
			 * add service to unseen
			 * @method add
			 * @param {String} publisherId Service stream publisher id
			 * @param {String} streamName Service stream name
			 */
			this.add = function (publisherId, streamName) {
				// ignore self added service
				if (loggedUserId === publisherId) {
					return;
				}

				var key = publisherId + ':' + streamName;
				var services = $.data($counter[0], "services") || {};

				if (services.hasOwnProperty(key)) {
					return;
				}

				Streams.get(publisherId, streamName, function () {

					services[key] = this.getAllAttributes();

					$.data($counter[0], "services", services);

					unseenServicesCounter._view();
				});
			};
			/**
			 * remove service from unseen
			 * @method remove
			 * @param {String} publisherId Service stream publisher id
			 * @param {String} streamName Service stream name
			 */
			this.remove = function (publisherId, streamName) {
				var key = publisherId + ':' + streamName;
				var services = $.data($counter[0], "services") || {};

				if (!services.hasOwnProperty(key)) {
					return;
				}

				// remove streamData from array
				delete services[key];

				$.data($counter[0], "services", services);

				unseenServicesCounter._view();
			};

			/**
			 * filter unseen services using interests
			 * @method filter
			 */
			this.filter = function () {
				this._view();
			};

			/**
			 * show filtered results
			 * @method _view
			 */
			this._view = function () {
				var services = Q.extend({}, $.data($counter[0], "services"));

				// apply interests filter
				for (var key in services){
					if (!services.hasOwnProperty(key)) {
						continue;
					}

					if (_normalized !== '*' && !String(Q.getObject("interest", services[key])).includes(_normalized)) {
						delete services[key];
					}
				}

				var count = Object.keys(services).length;
				$("span", $counter).html(count);

				// slowly scroll services list top
				$content.animate({scrollTop:0}, 'slow');

				if (count && $counter.is(':hidden')) {
					$counter.slideDown();
				} else if(count <= 0 && $counter.is(':visible')) {
					$counter.slideUp();
				}
			};
		};
	});
	
	Q.addScript("{{Calendars}}/js/tools/availabilities.js"); // start preloading before it opens
});

})(Q, Q.jQuery);