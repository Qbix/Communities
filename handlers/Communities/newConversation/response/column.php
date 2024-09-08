<?php

function Communities_newConversation_response_column($options = array())
{
	$text = Q_Text::get('Communities/content');
	$img = Q_Html::img('{{Communities}}/img/colorful/conversations.png', '');
	$title = Q::interpolate($text['newConversation']['Title'], array($img));
	Q_Response::setSlot('title', $title);
	Q_Response::addStylesheet('{{Communities}}/css/conversation/composer.css', 'Communities');
	Q_Response::addScript('{{Communities}}/js/columns/newConversation.js', 'Communities');
	if (!isset($options['publisherId'])) {
		$options['publisherId'] = Users::loggedInUser(true)->id;
	}
	//$column = Q::tool('Communities/conversation/composer', $options);
	$column = Q::tool('Websites/webpage/composer', array(
		//'publisherId' => 'ttgfsras', 'streamName' => 'Websites/webpage/https_www_youtube_com_watch_v_yzkyggw78ye'
			"categoryStream" => array(
				"publisherId" => Users::currentCommunityId(true),
				"streamName" => "Streams/chats/main"
			)
		)
	);
	Communities::$columns['newConversation'] = array(
		'title' => $title,
		'column' => $column
	);
	return $column;
}