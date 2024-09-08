Q.page("Communities/contribute", function () {

	var paymentTool = Q.Tool.from($(".Q_tool.Assets_payment_tool"), "Assets/payment");

	if (Q.typeOf(paymentTool) !== 'Q.Tool') {
		return console.warn("Assets/payment tool not found");
	}

	$("select[name=contribute]").on('change', function () {
		paymentTool.state.amount = this.options[this.selectedIndex].value;
		paymentTool.stateChanged('amount');
	});

	Q.Text.get('Communities/content', function (err, text) {
		var msg = Q.firstErrorMessage(err);
		if (msg) {
			return console.warn(msg);
		}

		paymentTool.state.onPay.set(function () {
			Q.confirm(text.payment.onContribute, function (result) {
				if (!result) {
					return;
				}

				Q.Streams.invite(Q.Users.communityId, 'Streams/experience/main');
			});
		});
	});

	return function () {
		// code to execute before page starts unloading
	};
}, 'Communities');