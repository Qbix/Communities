<?php

function Communities_conversations_response_column($params)
{
	$text = Q_Text::get('Communities/content');

	$communityId = Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId(true));
	$loggedUser = Users::loggedInUser();
	$limit = Q::ifset($_REQUEST, 'limit', Q_Config::get('Communities', 'pageSizes', 'conversations', 20));
	$offset = Q::ifset($_REQUEST, 'offset', 0);
	$experienceId = Q::ifset($_REQUEST, 'experienceId', 'main');
	$columnsStyle = Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic');

	// get conversations related to current community
	$relations = Communities::conversationChats($communityId, $experienceId, $offset, $limit);
	$public = array();
	foreach ($relations as $r) {
		$public[$r->fromPublisherId][$r->fromStreamName] = true;
	}
	Streams::arePublic($public);

	Q_Response::setScriptData("Q.plugins.Communities.conversations.experienceId", $experienceId, '');

	$filterDates = Q::ifset($_REQUEST, 'filterDates', null);
	$communityIcon = Q_Html::img('{{Communities}}/img/colorful/community.png');
	$title = $text['conversations']['Title'];
	$url = Q_Uri::url('Communities/conversations');

	$controls = null;
	if ($columnsStyle == 'classic') {
		$showControls = Q_Config::get('Communities', 'conversations', 'controls', true);
		$controls = $showControls ? Q::view('Communities/controls/conversations.php', @compact('communityIcon', 'filterDates')) : null;
	}
	Communities::$columns['conversations'] = array(
		'title' => $title,
		'column' => Q::view('Communities/column/conversations.php', @compact('relations', 'loggedUser', 'columnsStyle')),
		'columnClass' => 'Communities_column_'.$columnsStyle,
		'controls' => $controls,
		'url' => $url,
		'close' => false
	);

	Q_Response::setSlot('controls', $controls);
	Q_Response::addScript('{{Communities}}/js/columns/conversations.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/conversations.css', "Communities");
	Q_Response::addTemplate('Communities/templates/conversations', null, 'handlebars');
	Q_Response::addTemplate('Communities/templates/newConversation', null, 'handlebars');

	$description = Q::text($text['conversations']['Description'], array(Q::app()));
	// $keywords = $text['conversations']['Keywords'];
	$image = Q_Html::themedUrl('img/icon/400.png');
	Q_Response::setCommonMetas(compact(
		'title', 'description', 'keywords', 'image', 'url'
	));
}

