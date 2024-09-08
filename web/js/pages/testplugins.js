Q.page("Communities/testplugins", function () {

	var plugins = {
		Browsertab: {
			command: "cordova.plugins.browsertabs",
			handler: function (index, command, $element) {
				var plugin = this;

				// openUrl
				var $openUrlElement = $(".method[data-name=openUrl]", $element);
				var $openUrlResultElement = $(".results", $openUrlElement);
				try {
					plugin.openUrl("https://google.com", null, function(){
						$openUrlElement.attr('data-complete', 'true');
						console.log("Trying to close...");

						var $closeMethodElement = $(".method[data-name=close]", $element);
						var $closeResultElement = $(".results", $closeMethodElement);
						plugin.close(function () {
							$closeMethodElement.attr('data-complete', 'true');
						}, function (err) {
							$closeMethodElement.attr('data-complete', 'false');
							$closeResultElement.html(err);
						});
					}, function (err) {
						$openUrlElement.attr('data-complete', 'false');
						$openUrlResultElement.html(err);
					});
				} catch(e) {
					$openUrlElement.attr('data-complete', 'false');
					$openUrlResultElement.html(e.message);
				}

				// close
				var $methodElement = $(".method[data-name=close]", $element);
				var $resultElement = $(".results", $methodElement);
				try {
					plugin.close();
					$methodElement.attr('data-complete', 'true');
				} catch(e) {
					$methodElement.attr('data-complete', 'false');
					$resultElement.html(e.message);
				}
			}
		},
		Users: {
			command: "Q.Users.Cordova.Labels",
			handler: function (index, command, $element) {
				var plugin = this;

				// getAll
				var $getAllElement = $(".method[data-name=getAll]", $element);
				var $getAllResultElement = $(".results", $getAllElement);

				try {
					plugin.getAll(function (labels) {
						$getAllElement.attr('data-complete', 'true');

						if (Q.isEmpty(labels)) {
							return $getAllResultElement.attr("data-type", 'warning').html("But labels empty");
						}

						$getAllResultElement.attr("data-type", 'common').html(JSON.stringify(labels, null, 4));

						// method get
						var $getElement = $(".method[data-name=get]", $element);
						var $getResultElement = $(".results", $getElement);
						try {
							plugin.get([labels[0].id], function (data) {
								$getElement.attr('data-complete', 'true');

								if (Q.isEmpty(data)) {
									return $getResultElement.attr("data-type", 'warning').html("But data empty");
								}

								$getResultElement.attr("data-type", 'common').html(JSON.stringify(data, null, 4));
							}, function (err) {
								$getElement.attr('data-complete', 'false');
								$getResultElement.attr("data-type", 'error').html(err);
							});
						} catch(e) {
							$getElement.attr('data-complete', 'false');
							$getResultElement.attr("data-type", 'error').html(e.message);
						}

						// method checkLabelsAccount
						var $claElement = $(".method[data-name=checkLabelsAccount]", $element);
						var $claResultElement = $(".results", $claElement);
						try {
							plugin.checkLabelsAccount([labels[0].id], function (data) {
								$claElement.attr('data-complete', 'true');

								if (Q.isEmpty(data)) {
									return $claResultElement.attr("data-type", 'warning').html("But data empty");
								}

								$claResultElement.attr("data-type", 'common').html(JSON.stringify(data, null, 4));
							}, function (err) {
								$claElement.attr('data-complete', 'false');
								$claResultElement.attr("data-type", 'error').html(err);
							});
						} catch(e) {
							$claElement.attr('data-complete', 'false');
							$claResultElement.attr("data-type", 'error').html(e.message);
						}

					}, function (err) {
						$getAllElement.attr('data-complete', 'false');
						$getAllResultElement.attr("data-type", 'error').html(err);
					});
				} catch(e) {
					$getAllElement.attr('data-complete', 'false');
					$getAllResultElement.attr("data-type", 'error').html(e.message);
				}

				// forContacts
				var $forContactsElement = $(".method[data-name=forContacts]", $element);
				var $forContactsResultElement = $(".results", $forContactsElement);
				try {
					navigator.contacts.find([navigator.contacts.fieldType.id], function (contacts) {

						if (Q.isEmpty(contacts)) {
							return $forContactsResultElement.attr("data-type", 'warning').html("But contacts empty");
						}

						plugin.forContacts([contacts[0].id], true, function (labels) {
							$forContactsElement.attr('data-complete', 'true');

							if (Q.isEmpty(labels)) {
								return $forContactsResultElement.attr("data-type", 'warning').html("But labels empty");
							}

							$forContactsResultElement.attr("data-type", 'common').html(JSON.stringify(labels, null, 4));

						}, function (err) {
							$forContactsElement.attr('data-complete', 'false');
							$forContactsResultElement.attr("data-type", 'error').html(err);
						});
					}, function (err) {
						$forContactsElement.attr('data-complete', 'false');
						$forContactsResultElement.attr("data-type", 'error').html(err);
					});
				} catch(e) {
					$forContactsElement.attr('data-complete', 'false');
					$forContactsResultElement.attr("data-type", 'error').html(e.message);
				}

				// addLabel
				var $addLabelElement = $(".method[data-name=addLabel]", $element);
				var $addLabelResultElement = $(".results", $addLabelElement);
				var testLabelTitle = "Test label";
				try {
					plugin.save({
						labelId: "-1",
						title: testLabelTitle
					}, function (label) {
						$addLabelElement.attr('data-complete', 'true');

						if (Q.isEmpty(label)) {
							return $addLabelResultElement.attr("data-type", 'warning').html("But label empty");
						}

						$addLabelResultElement.attr("data-type", 'common').html(JSON.stringify(label, null, 4));

						plugin.getAll(function (labels) {
							Q.each(labels, function (index, label) {
								if (label.title !== testLabelTitle) {
									return;
								}

								// editLabel
								var $editLabelElement = $(".method[data-name=editLabel]", $element);
								var $editLabelResultElement = $(".results", $editLabelElement);
								plugin.save({
									labelId: label.id,
									title: "Modified label"
								}, function (res) {
									$editLabelElement.attr('data-complete', 'true');

									$editLabelResultElement.attr("data-type", 'common').html(JSON.stringify(res, null, 4));

									// removeLabel
									var $removeLabelElement = $(".method[data-name=removeLabel]", $element);
									var $removeLabelResultElement = $(".results", $removeLabelElement);
									plugin.remove(label.id, function (res) {
										$removeLabelElement.attr('data-complete', 'true');

										if (Q.isEmpty(res)) {
											return $removeLabelResultElement.attr("data-type", 'warning').html("But label empty");
										}

										$removeLabelResultElement.attr("data-type", 'common').html(JSON.stringify(res, null, 4));
									}, function (err) {
										$removeLabelElement.attr('data-complete', 'false');
										$removeLabelResultElement.attr("data-type", 'error').html(err);
									});
								}, function (err) {
									$editLabelElement.attr('data-complete', 'false');
									$editLabelResultElement.attr("data-type", 'error').html(err);
								});
							});
						});
					}, function (err) {
						$addLabelElement.attr('data-complete', 'false');
						$addLabelResultElement.attr("data-type", 'error').html(err);
					});
				} catch(e) {
					$addLabelElement.attr('data-complete', 'false');
					$addLabelResultElement.attr("data-type", 'error').html(e.message);
				}

				// addContact
				var $addContactElement = $(".method[data-name=addContact]", $element);
				var $addContactResultElement = $(".results", $addContactElement);
				try {
					var pipe = new Q.Pipe(['label', 'contact'], function (params) {
						var label = params.label[0];
						var contact = params.contact[0];

						console.log(label, contact);

						plugin.addContact(label.id, [contact.id], function (data) {
							$addContactElement.attr('data-complete', 'true');

							if (Q.isEmpty(data)) {
								return $addContactResultElement.attr("data-type", 'warning').html("But label empty");
							}

							$addContactResultElement.attr("data-type", 'common').html(JSON.stringify(data, null, 4));

							// removeContact
							var $removeContactElement = $(".method[data-name=removeContact]", $element);
							var $removeContactResultElement = $(".results", $removeContactElement);
							try {
								plugin.removeContact(label.id, [contact.id], function (data) {
									$removeContactElement.attr('data-complete', 'true');

									if (Q.isEmpty(data)) {
										return $removeContactResultElement.attr("data-type", 'warning').html("But data empty");
									}

									$removeContactResultElement.attr("data-type", 'common').html(JSON.stringify(data, null, 4));

									//
								}, function (err) {
									$removeContactElement.attr('data-complete', 'false');
									$removeContactResultElement.attr("data-type", 'error').append($("<div>").html(err));
								});
							} catch (e) {

							}
						}, function (err) {
							$addContactElement.attr('data-complete', 'false');
							$addContactResultElement.attr("data-type", 'error').append($("<div>").html(err));
						});
					});

					plugin.getAll(function (labels) {
						pipe.fill('label')(labels[0]);
					}, function (err) {
						$addContactElement.attr('data-complete', 'false');
						$addContactResultElement.attr("data-type", 'error').append($("<div>").html(err));
					});

					navigator.contacts.find([navigator.contacts.fieldType.id], function (contacts) {
						pipe.fill('contact')(contacts[0]);
					}, function (err) {
						$addContactElement.attr('data-complete', 'false');
						$addContactResultElement.attr("data-type", 'error').append($("<div>").html(err));
					});
				} catch(e) {
					$addContactElement.attr('data-complete', 'false');
					$addContactResultElement.attr("data-type", 'error').html(e.message);
				}

				// smart
				$(".method[data-name=smart]", $element).each(function () {
					var $smartElement = $(this);
					var option = $smartElement.attr("data-param");
					var $smartResultElement = $(".results", $smartElement);
					try {
						plugin.smart(option, function (data) {
							$smartElement.attr('data-complete', 'true');

							if (Q.isEmpty(data)) {
								return $smartResultElement.attr("data-type", 'warning').html("But data empty");
							}

							$smartResultElement.attr("data-type", 'common').html(JSON.stringify(data, null, 4));
						}, function (err) {
							$smartElement.attr('data-complete', 'false');
							$smartResultElement.attr("data-type", 'error').append($("<div>").html(err));
						});
					} catch(e) {
						$smartElement.attr('data-complete', 'false');
						$smartResultElement.attr("data-type", 'error').html(e.message);
					}
				});

			}
		},
		Keyboard: {
			command: "cordova.plugins.keyboard",
			handler: function (index, command, $element) {

			}
		},
		Calendar: {
			command: "window.plugins.calendar",
			handler: function (index, command, $element) {
				var plugin = this;

				var myCalendarName = "MyCordovaCalendar";
				var title = 'My Event Title';
				var loc = 'My Event Location';
				var notes = 'My Event notes.';
				var startDate = new Date();
				var endDate = new Date();
				var calendarName = 'MyCalendar';
				var eventOptions = {
					url: 'https://qbix.com',
					calendarName: calendarName, // iOS specific
					calendarId: 1 // Android specific
				};

				// clean up the dates a bit
				startDate.setMinutes(0);
				endDate.setMinutes(0);
				startDate.setSeconds(0);
				endDate.setSeconds(0);

				// add a few hours to the dates
				startDate.setHours(startDate.getHours() + 2);
				endDate.setHours(endDate.getHours() + 3);

				function onSuccess(msg) {
					this.attr('data-type', "common").html('Success: ' + JSON.stringify(msg));
				}

				function onError(msg) {
					this.attr('data-type', "error").html('Error: ' + JSON.stringify(msg));
				}

				// openCalendar
				$("button[name=test]", $(".method[data-name=openCalendar]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.openCalendar(new Date(), onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// listCalendars
				$("button[name=test]", $(".method[data-name=listCalendars]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.listCalendars(onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// createCalendar
				$("button[name=test]", $(".method[data-name=createCalendar]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						var options = plugin.getCreateCalendarOptions();
						options.calendarName = myCalendarName;
						options.calendarColor = "#FF0000";
						plugin.createCalendar(options, function (id) {
							eventOptions.calendarId = id;
							onSuccess.bind($resultElement)(id);
						}, onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// deleteCalendar
				$("button[name=test]", $(".method[data-name=deleteCalendar]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();

					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.deleteCalendar(myCalendarName, onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// deleteEvent
				$("button[name=test]", $(".method[data-name=deleteEvent]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.deleteEvent(title, loc, notes, startDate, endDate, onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// createEvent
				$("button[name=test]", $(".method[data-name=createEvent]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.createEvent(title, loc, notes, startDate, endDate, onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// createEventInteractively
				$("button[name=test]", $(".method[data-name=createEventInteractively]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.createEventInteractively(title, loc, notes, startDate, endDate, onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// createEventWithOptions
				$("button[name=test]", $(".method[data-name=createEventWithOptions]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.createEventWithOptions(title, loc, notes, startDate, endDate, eventOptions, onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// createEventInteractivelyWithOptions
				$("button[name=test]", $(".method[data-name=createEventInteractivelyWithOptions]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.createEventInteractivelyWithOptions(title, loc, notes, startDate, endDate, eventOptions, onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

				// findEvent
				$("button[name=test]", $(".method[data-name=findEvent]", $element)).on(Q.Pointer.fastclick, function (event) {
					event.preventDefault();
					var $pluginElement = $(this).closest(".method");
					var $resultElement = $(".results", $pluginElement);

					try {
						plugin.findEvent(title, loc, notes, startDate, endDate, onSuccess.bind($resultElement), onError.bind($resultElement));
					} catch(e) {
						$resultElement.html(e.message);
					}
				});

			}
		}
	};

	Q.each(plugins, function (index, obj) {
		var plugin = Q.getObject(obj.command);
		var $element = $('.plugin[data-name=' + index + ']');

		if (Q.isEmpty(plugin)) {
			$element.attr('data-exist', 'false');
			return;
		}

		$element.attr('data-exist', 'true');

		Q.handle(obj.handler, plugin, [index, obj.command, $element]);
	});

	return function () {

	};

}, 'Communities');