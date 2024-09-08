"use strict";
(function(Q, $, undefined) {
	
var Users = Q.Users;
var Communities = Q.Communities;

Q.exports(function (options, index, column, data) {
	var communitiesListTool = Q.Tool.from($(".Q_tool.Users_list_tool", column), "Users/list");
	var composerNewbie = Q.Tool.from($(".Communities_composer_tool", column), "Communities/composer");
	var $titleSlot = $('.Q_title_slot', column);
	var $titleContainer = $('.Q_columns_title_container', column);

	if (Q.typeOf(composerNewbie) === 'Q.Tool') {
		composerNewbie.state.onCreated.set(function (community) {
			if (Q.typeOf(communitiesListTool) === 'Q.Tool') {
				communitiesListTool.state.userIds.push(community.id);
				communitiesListTool.refresh();

				// open this community
				Communities.openCommunityProfile(community.id);
			}
		});
	}

	// <apply FaceBook column style>
	if (Q.getObject('layout.columns.style', Communities) === 'facebook') {
		var icons = [];

		// Create community search
		var userChooser = Q.Tool.from($(".Streams_userChooser_tool", column), 'Streams/userChooser');
		var $userChooser = null;
		if (userChooser) {
			$userChooser = $(userChooser.element);
			icons.push($("<i class='qp-communities-search Communities_chooser_trigger'></i>"));
		}
		if (Q.getObject("Q.plugins.Communities.canCreateCommunities") && Q.typeOf(composerNewbie) === 'Q.Tool') {
			icons.push($("<i class='qp-communities-plus'></i>").on(Q.Pointer.fastclick, composerNewbie.invoke.bind(composerNewbie)));
		}

		$titleContainer.tool('Communities/columnFBStyle', {
			icons: icons,
			filter: [$userChooser.find('input')],
			applyPlaceholder: false
		}, 'Communities_column').activate();
	}
	// </apply FaceBook column style>

	$("input[name=query]", column).plugin('Q/placeholders');
});

})(Q, Q.jQuery);