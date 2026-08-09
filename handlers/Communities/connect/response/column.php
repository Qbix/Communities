<?php

/**
 * The "connect" column: your QR code, whoever has scanned it, and the
 * community's public conversations underneath.
 *
 * Visitors appear via Streams/participants on your visits stream, because
 * Streams/qrConnect joins them to it rather than only posting messages.
 */
function Communities_connect_response_column($params)
{
	$text = Q_Text::get('Communities/content');

	$communityId = Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId(true));
	$loggedUser = Users::loggedInUser();
	$experienceId = Q::ifset($_REQUEST, 'experienceId', 'main');
	$columnsStyle = Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic');
	$limit = Q::ifset($_REQUEST, 'limit', Q_Config::get(
		'Communities', 'pageSizes', 'conversations', 20
	));
	$offset = Q::ifset($_REQUEST, 'offset', 0);

	// make sure the stream exists before the participants tool asks for it,
	// otherwise the column renders empty until the first person scans
	$visitsStream = $loggedUser
		? Streams_QrConnect::visitsStream($loggedUser->id, true)
		: null;
	if ($visitsStream) {
		$visitsStream->addPreloaded($loggedUser->id);
	}

	// same source the conversations column uses
	$relations = Communities::conversationChats(
		$communityId, $experienceId, $offset, $limit
	);
	$public = array();
	foreach ($relations as $r) {
		$public[$r->fromPublisherId][$r->fromStreamName] = true;
	}
	Streams::arePublic($public);

	Q_Response::setScriptData(
		"Q.plugins.Communities.connect.experienceId", $experienceId, ''
	);

	$title = Q::ifset($text, 'connect', 'Title', 'Connect');
	$url = Q_Uri::url('Communities/connect');

	Communities::$columns['connect'] = array(
		'title' => $title,
		'column' => Q::view('Communities/column/connect.php', @compact(
			'relations', 'loggedUser', 'columnsStyle', 'visitsStream', 'text'
		)),
		'columnClass' => 'Communities_column_'.$columnsStyle,
		'controls' => null,
		'url' => $url,
		'close' => false
	);

	Q_Response::addScript('{{Communities}}/js/columns/connect.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/connect.css', "Communities");

	$description = Q::ifset($text, 'connect', 'Description', '');
	$image = Q_Html::themedUrl('img/icon/400.png');
	Q_Response::setCommonMetas(compact(
		'title', 'description', 'image', 'url'
	));
}
