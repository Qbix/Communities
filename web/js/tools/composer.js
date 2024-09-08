(function (window, Q, $, undefined) {

var Communities = Q.Communities;
	
/**
 * @module Communities
 */
	
/**
 * Create communities and invite adming to created communities.
 * @class Communities composer
 * @constructor
 * @param {Object} [options] Override various options for this tool
 *  @param {String} [options.className] Any css classes to add to the tool element
 */

Q.Tool.define("Communities/composer", function Communities_composer_tool() {
	var tool = this;

	// check whether authorized to create communities
	if (!Q.getObject("Q.plugins.Communities.canCreateCommunities")) {
		return;
	}

	Q.addStylesheet("{{Communities}}/css/tools/composer.css");

	if (this.state.className) {
		$(tool.element).addClass(this.state.className);
	}

	Q.Text.get('Communities/content', function (err, text) {
		tool.texts = text.composer;

		tool.refresh();
	});
},
{
	className: null,
	onCreated: new Q.Event()
},
{
	refresh: function () {
		var tool = this;

		Q.Template.render('Communities/composer/main', {
			text: tool.texts
		}, function (err, html) {
			Q.replace(tool.element, html);;

			// create community button
			$("button[name=newCommunity]", tool.element).on(Q.Pointer.fastclick, tool.invoke.bind(tool));
		});
	},
	invoke: function () {
		var tool = this;

		Q.prompt("", function (communityName) {
			if (!communityName){
				return;
			}

			tool.sendRequest(communityName, null, function (community) {
				var request = Q.getObject("request", community);

				if (!request) {
					return Q.handle(tool.state.onCreated, tool, [community]);
				}

				Q.confirm(request, function (proceed) {
					if (!proceed) {
						return;
					}

					tool.sendRequest(communityName, true, function (community) {
						return Q.handle(tool.state.onCreated, tool, [community]);
					});
				}, {
					ok: tool.texts.yes,
					cancel: tool.texts.no
				});
			});
		}, {
			title: tool.texts.newCommunityTitle,
			hidePrevious: true,
			placeholder: tool.texts.setCommunityTitle,
			maxLength: 31
		});
	},
	sendRequest: function (communityName, creditsConfirmed, callback) {
		var tool = this;
		var te = tool.element;

		$(te).addClass('Q_working');

		Q.req("Communities/composer", ["community"], function (err, response) {
			$(te).removeClass('Q_working');

			var msg;
			if (msg = Q.firstErrorMessage(err, response && response.errors)) {
				return Q.alert(msg);
			}

			var community = Q.getObject("slots.community", response);

			Q.handle(callback, tool, [community]);
		}, {
			method: 'post',
			fields: {
				name: communityName,
				creditsConfirmed: creditsConfirmed
			}
		});
	}
});

Q.Template.set('Communities/composer/main',
	'<button class="Q_button" name="newCommunity">{{text.createCommunity}}</button>'
);

})(window, Q, Q.jQuery);