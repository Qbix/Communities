Q.page("Communities/inbox", function () {

	var _onMessage = function(){
		var tool = this;
		var ps = this.preview;
		var $toolElement = $(tool.element);

		//console.log(ps.state.publisherId);
		Q.Streams.Stream.onMessage(ps.state.publisherId, ps.state.streamName, 'Streams/chat/message')
		.set(function() {
			// add highlighted class
			$toolElement.addClass("Q_newsflash");

			// remove class when transaction ended
			$toolElement.one('webkitAnimationEnd oanimationend msAnimationEnd animationend', function(e) {
				$toolElement.removeClass("Q_newsflash");
			});

			// move tool top of parent
			$toolElement.parent().prepend($toolElement);
		}, tool);
	};

	// set onMessage for tools already activated
	$(".Streams_chat_preview_tool").each(function(){
		Q.handle(_onMessage, Q.Tool.from(this));
	});

	// set onMessage for future tools
	Q.Tool.onActivate('Streams/chat/preview').set(function () {
		Q.handle(_onMessage, this);
	}, 'Communities/inbox');

	return function () {

	};

}, 'Communities');
