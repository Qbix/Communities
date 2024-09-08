Q.page("Communities/featureboard", function () {

	var _changeContribute = function () {
		var paymentTool = Q.Tool.from($(this).siblings(".Q_tool.Assets_payment_tool")[0], "Assets/payment");
		if (Q.typeOf(paymentTool) !== 'Q.Tool') {
			return console.warn("Assets/payment tool not found");
		}

		paymentTool.state.amount = this.options[this.selectedIndex].value;
	};

	$("select[name=contribute]").on('change', _changeContribute);

	Q.Text.get('Communities/content', function (err, text) {
		var msg = Q.firstErrorMessage(err);
		if (msg) {
			return console.warn(msg);
		}

		$("button[name=requestNewFeature]").on(Q.Pointer.fastclick, function () {
			Q.Dialogs.push({
				title: text.featureboard.NewFeature,
				template: {
					name: 'Communities/featureboard/NewFeature'
				},
				className: 'Communities_featureboard_NewFeature',
				onActivate: function (dialog) {
					$("[placeholder]", dialog).plugin('Q/placeholders');
					$("textarea", dialog).plugin('Q/autogrow');

					$($("select[name=contribute]")[0]).clone()
						.prependTo($(".Communities_featureboard_button", dialog))
						.on('change', _changeContribute);
				}
			});
		});

		Q.Template.set('Communities/featureboard/NewFeature',
			'<span>' + text.featureboard.Text_5 + '</span>' +
			'<input type="text" name="title" class="Q_placeholder" placeholder="' + text.featureboard.FeaturePlaceholder + '" />' +
			'<textarea name="content" class="Q_placeholder" placeholder="' + text.featureboard.FeatureDescription + '"></textarea>' +
			'<div class="Communities_featureboard_button"><button name="submitNewFeature" class="Q_button">' + text.featureboard.AddYourRequest + '</button></div>'
		);
	});

	return function () {
		// code to execute before page starts unloading
	};
}, 'Communities');