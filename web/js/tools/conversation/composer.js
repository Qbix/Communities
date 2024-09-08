(function (Q, $, window, undefined) {

	var Users = Q.Users;
	var Streams = Q.Streams;
	var Calendars = Q.Calendars;

	/**
	 * This tool lets the user create new conversation
	 * @class Communities/conversation/composer
	 * @constructor
	 * @param {Object} [options] this is an object that contains parameters for this function
	 *   @param {String} options.publisherId The publisher id for the conversation stream
	 *   @param {Objects} options.interests Override any options for Streams/interests tool
	 *   @param {Q.Event} [options.onCreate] This conversation fires when the tool successfully creates a new conversation
	 */
	Q.Tool.define("Communities/conversation/composer", function (options) {
			var tool = this;

			// wait when styles and texts loaded and then run refresh
			var pipe = Q.pipe(['styles', 'text'], function () {
				tool.refresh();
			});

			// loading styles
			Q.addStylesheet('{{Communities}}/css/conversation/composer.css', pipe.fill('styles'));

			// loading text
			Q.Text.get('Communities/content', function (err, text) {
				var msg = Q.firstErrorMessage(err);
				if (msg) {
					console.warn(msg);
				}

				tool.text = text;

				pipe.fill('text')();
			});
		},

		{
			publisherId: Users.loggedInUserId(),
			onCreate: new Q.Event(),
			chooseInterest: function (container) {
				var tool = this;
				var state = tool.state;
				var $interest = tool.$('.Communities_conversation_composer_interest');
				var $container = $(container);
				tool.communityId = Users.communityId;
				var title = Q.getObject(['newConversation', 'composer', 'interest', 'Title'], tool.text);
				var o = Q.extend({
					filter: tool.text.newConversation.composer.interest.Filter,
					onClick: function (element, normalized, category, interest, wasSelected) {
						tool.category = category;
						tool.interest = interest;
						tool.interestTitle = category + ': ' + interest;
						if (!Q.getObject(
								[tool.communityId, category, interest],
								Streams.Interests.all
							)) {
							// add it in the background, and hope it completes on time
							Streams.Interests.add(tool.interestTitle);
						}
						var cn = Q.normalize(category);
						$interest.val(tool.interestTitle);
						var src = Streams.Interests.categoryIconUrl(
							Users.communityId, category, 'colorful'
						);
						$container.find('.Communities_conversation_composer_interest_icon')
							.attr('src', src);
						$container.find('.Communities_conversation_composer_interest_button')
							.text(tool.interestTitle)
							.plugin('Q/clickable');
						$(element).addClass('Q_selected');
						tool.$('h2', element).addClass('Q_expanded');
						Q.Dialogs.pop();
						tool.prepareSteps();
						return false;
					}
				}, state.interests);
				Q.Dialogs.push({
					stylesheet: 'Q/plugins/Q/css/tools/expandable.css',
					title: title || "Choose an Activity",
					className: 'Streams_dialog_interests',
					stylesheet: '{{Q}}/css/tools/expandable.css',
					communityId: tool.communityId,
					content: Q.Tool.setUpElement('div', 'Streams/interests', o)
				});
			}
		},

		{
			refresh: function () {
				var tool = this;
				var state = tool.state;

				Q.Template.render('Communities/templates/newConversation', {
					newConversation: tool.text.newConversation
				}, function (err, html) {
					Q.replace(tool.element, html);;

					tool.$composer = tool.$('.Communities_conversation_composer').plugin('Q/placeholders');
					tool.$composer.children().not(':first-child').css({
						'opacity': 0.2,
						'pointer-events': 'none'
					});

					_continue();
				});

				function _continue() {
					tool.$interest = tool.$('.Communities_conversation_composer_interest');
					tool.$title = tool.$('.Communities_conversation_composer_title input');
					tool.$description = tool.$('.Communities_conversation_composer_description textarea');

					var $button = tool.$('.Communities_conversation_composer_interest_button');
					var $parent = $button.parent();
					var width = $parent.width() - parseInt($parent.css('padding')) * 2;
					$button.css('max-width', width + 'px').plugin('Q/clickable');

					tool.$('.Communities_conversation_composer_interest')
						.on(Q.Pointer.fastclick, function () {
							Q.handle(state.chooseInterest, tool, [this]);
						});

					tool.$title.add(tool.$description).on("change keyup", function () {
						tool.prepareSteps();
					});

					tool.$('.Communities_conversation_composer_share')
						.plugin('Q/clickable')
						.on(Q.Pointer.click, function () {
							if (!Users.loggedInUser) {
								alert('Please log in first');
								return;
							}
							var fields = {
								communityId: tool.communityId,
								publisherId: Users.loggedInUser.id,
								interestTitle: tool.interestTitle,
								title: tool.$title.val(),
								content: tool.$description.val()
							};
							var $this = $(this);
							$this.addClass('Q_working').attr('disabled', 'disabled');
							Q.req('Communities/newConversation', ['stream'], function (err, data) {
								var msg = Q.firstErrorMessage(
									err, data && data.errors
								);
								if (msg) {
									$this.removeClass('Q_working').removeAttr('disabled');
									return alert(msg);
								}
								var stream = Streams.Stream.construct(data.slots.stream, null, null, true);
								Q.handle(state.onCreate, tool, [stream]);
							}, {
								method: 'post',
								fields: fields
							});

						})[0].preventSelections();
				}
			},
			prepareSteps: function () {
				var tool = this;
				var steps = [
					[tool.$interest],
					[tool.$title],
					[tool.$description]
				];
				Q.each(steps, function (i, step) {
					var filledOut = false;
					Q.each(step, function (i, $element) {
						if (!$element) return;
						var val = ($element[0] instanceof Element)
							? $element.val()
							: $element;
						if (val && (val !== '+')) {
							filledOut = true;
							return false;
						}
					});
					if (filledOut) {
						tool.$composer.children().eq(i + 1)
							.css({'pointer-events': 'auto'})
							.stop().animate({'opacity': 1});
					} else {
						tool.$composer.children().slice(i + 1)
							.css({'pointer-events': 'none'})
							.stop().animate({'opacity': 0.2});
						return false;
					}
				});
			}
		}
	);

})(Q, Q.jQuery, window);