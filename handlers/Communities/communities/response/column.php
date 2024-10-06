<?php

/**
 * Renders a communities list user involved in
 * @param {array} $params Can be passed to the function or found in $_REQUEST
 * @param {array} $params.userId
 */
function Communities_communities_response_column($params = array())
{
	$text = Q_Text::get('Communities/content');
	$title = $text['communities']['Title'];
	$url = Q_Uri::url(Q::ifset(Communities::$options, 'url', "Communities/communities"));
	$communities = array(Users::communityId());
	$columnsStyle = Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic');

	// collect communities where current user have some roles...
	foreach (Users::byRoles(null, array('onlyCommunities' => true)) as $role) {
		$communities[] = $role->userId;
	}

	// add communities filtered by participants
	foreach (Users_User::select()->where(array(
		'signedUpWith' => 'none'
	))->limit(1000)->fetchDbRows() as $item) {
		if (!Users::isCommunityId($item->id) || in_array($item->id, $communities, true)) {
			continue;
		}

		// check participants
		$participants = Streams_Participant::select()->where(array(
			'publisherId' => $item->id,
			'streamName' => "Streams/experience/main",
			'state' => 'participating'
		))->fetchDbRows();
		// ignore communities with less participants
		if (count($participants) < Q_Config::get('Communities', 'community', 'hideUntilParticipants', 10)) {
			continue;
		}

		$communities[] = $item->id;
	}

	Q_Response::addScript('{{Communities}}/js/columns/communities.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/communities.css', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/community.css', "Communities");

	Q_Response::setSlot('title', $title);

	$skipComposer = Q_Config::get('Communities', 'community', 'skipComposer', false);

	$communities = array_values(array_unique($communities));
	$column = Q::view("Communities/column/communities.php", @compact(
		'communities', 'text', 'skipComposer', 'columnsStyle'
	));

	Communities::$columns['communities'] = array(
		'title' => Q_Html::text($title),
		'column' => $column,
		'columnClass' => 'Communities_column_communities Communities_column_'.$columnsStyle,
		'url' => $url
	);

	$description = Q::text($text['communities']['Description'], array(Q::app()));
	$keywords = Q::ifset($text, 'communities', 'Keywords', null);
	$image = Q_Html::themedUrl('img/icon/400.png');
	Q_Response::setCommonMetas(compact(
		'title', 'description', 'keywords', 'image', 'url'
	));
	return $column;
}