Q.page("Communities/schedule", function () {

	$(".Communities_tab").on(Q.Pointer.fastclick, function(){
		var $this = $(this);
		var val = $this.attr("data-val");

		if ($this.hasClass("Q_current")) {
			return false;
		}

		$(".Communities_tab").removeClass("Q_current");
		$this.addClass("Q_current");

		$(".Communities_schedule_column_tabContent").hide();
		$(".Communities_schedule_column_tabContent[data-val=" + val + "]").show();
	});

	// example using Q/badge tool
	/*Q.Tool.onActivate("Users/avatar").set(function () {
		$(this.element).tool("Q/badge", {
			tr: {
				icon: "{{Q}}/img/rating/star-yellow.png",
			},
			tl: {
				icon: "{{Q}}/img/rating/star-yellow.png",
			}
		}).activate();
	}, 'Communities/schedule');*/

	return function () {

	};

}, 'Communities');
