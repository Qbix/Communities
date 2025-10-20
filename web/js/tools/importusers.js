(function (Q, $, window, document, undefined) {

var Users = Q.Users;
var Streams = Q.Streams;

/**
 * Communities Tools
 * @module Communities-tools
 */

/**
 * @class Communities importusers
 * @constructor
 * @param {array} [options] this array contains function parameters
 *   @param {String} [options.link] URL to the csv file to download, if any.
 *   @param {String} [options.taskStream] Task stream for current tool. If it null, it will bre created from client.
 *   @param {String} [options.communityId=Users::communityId] Community id on behalf of create events

 */
Q.Tool.define("Communities/importusers", function (options) {
	var tool = this;
	var state = this.state;

	tool.refresh();
},

{
	communityId: Q.Users.communityId,
	taskStream: null,
	link: null
},

{
	refresh: function () {
		var tool = this;
		var state = tool.state;

		var fields = {
			communityId: state.communityId,
			toMainCommunity: state.communityId === Q.getObject("mainCommunity", Q.Communities)
		};
		if (state.link) {
			fields.href = state.link.isUrl() || state.link[0] === '{'
				? state.link
				: Q.url('{{Communities}}/importing/' + state.link);
		}

		Q.Template.render('Communities/importusers/tool', fields, function (err, html) {
			Q.replace(tool.element, html);

			tool.$form = $("form", tool.element);
			tool.$fileLabel = $("label[for=Communities_importusers]", tool.element);
			tool.$processElement = $(".Communities_importusers_process", tool.element);
			tool.$progressElement = $(".Communities_importusers_progress", tool.element);

			$("button[name=sampleCSV]", tool.element).on('click', function () {
				window.location = Q.url("{{baseUrl}}/importusers/sample?communityId=" + state.communityId);
				return false;
			});

			tool.$('input[type=file]').click(function (event) {
				event.stopPropagation();
			}).change(function () {
				if (!this.value) {
					return; // it was canceled
				}

				$("span", tool.$fileLabel).html(`(processing ${this.value})`);

				// task stream already defined, no need define it again
				Streams.retainWith(tool).create({
					publisherId: Users.loggedInUserId(),
					type: 'Streams/task',
					title: 'Importing users into ' + state.communityId
				}, function (err) {
					if (err) {
						return;
					}

					state.taskStream = this;

					// join current user to task stream to get messages
					this.join(function (err) {
						if (err) {
							return;
						}

						state.taskStream.refresh(function () {
							tool.postFile();
						}, {
							evenIfNotRetained: true
						});
					});

					$("input[name=taskStreamName]", tool.element).val(state.taskStream.fields.name);
				});
			});
		});
	},
	/**
	 * send CSV file to server
	 * @method postFile
	 */
	postFile: function () {
		var tool = this;
		var state = this.state;

		if (!Streams.isStream(state.taskStream)) {
			throw new Q.Error("task stream invalid");
		}

		Q.Tool.remove(tool.$progressElement[0], true, false, "Streams/task/preview");
		tool.$progressElement.tool("Streams/task/preview", {
			publisherId: state.taskStream.fields.publisherId,
			streamName: state.taskStream.fields.name,
			//progress: "Q/pie"
		}).activate(function () {
			this.state.onComplete.set(tool.refresh.bind(tool), tool);
			this.state.onError.set(tool.refresh.bind(tool), tool);
		});
		tool.$processElement.show();

		Q.req("Communities/importusers", [], function (err, response) {
			var msg = Q.firstErrorMessage(err, response && response.errors);
			if (msg) {
				return Q.alert(msg);
			}

		}, {
			method: 'POST',
			form: tool.$form[0]
		});

		tool.$fileLabel.addClass("Q_disabled");
		$("input:visible", tool.$form).prop("disabled", true);
	},
	Q: {
		beforeRemove: function () {
			if (this.ival) {
				clearInterval(this.ival);
			}
		}
	}
});

Q.Template.set('Communities/importusers/tool',
	`{{#if href}}<a href="{{href}}">{{import.linkTitle}}</a>{{/if}}
	<form enctype="multipart/form-data">
		<fieldset>
			<legend>{{import.fileLabel}}</legend>
			<label for="Communities_importusers">{{import.ChooseFile}} <span></span></label>
			<input type="file" id="Communities_importusers" name="file">
			<button name="sampleCSV" type="button">{{import.sampleCSV}}</button>
		</fieldset>
		<fieldset>
			<legend>{{import.importOptions}}</legend>
			<label data-for="joinToRandomEvent"><input type="checkbox" name="joinToRandomEvent"> {{import.joinEventLabel}}</label>
			<label data-for="activateUsers"><input type="checkbox" name="activateUsers"> {{import.activateUsers}}</label>
			<label data-for="communityUsers"><input type="checkbox" name="communityUsers"> {{import.communityUsers}}</label>
			<label data-for="setUrlAsConversation"><input type="checkbox" name="setUrlAsConversation"> {{import.setAsConversation}}</label>
			{{#unless toMainCommunity}}
				<label data-for="toMainCommunityToo"><input type="checkbox" name="toMainCommunityToo"> {{import.toMainCommunityToo}}</label>
			{{/unless}}
		</fieldset>
		<div class="Communities_importusers_process">
			<fieldset>
				<legend>{{import.importProgress}}</legend>
				<div class="Communities_importusers_progress"></div>
			</fieldset>
		</div>
		<input type="hidden" name="communityId" value="{{communityId}}">
		<input type="hidden" name="taskStreamName" value="{{taskStreamName}}">
	</form>`, {text: ['Communities/content']}
);

})(Q, Q.jQuery, window, document);
