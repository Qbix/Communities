<?php

/**
 * The "connect" column: your QR code, whoever has scanned it, and the
 * community's public conversations underneath.
 *
 * Visitors appear via Streams/participants on your visits stream, because
 * Streams/QRconnect joins them to it rather than only posting messages.
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
		? Streams_QRconnect::visitsStream($loggedUser->id, true)
		: null;
	if ($visitsStream) {
		$visitsStream->addPreloaded($loggedUser->id);
	}

	// Whose profiles you connected with. This needs no work from us:
	// Streams_Invite::accept() subscribes you to the profile stream, and the
	// "*" type relates every join into your own Streams/participating, so the
	// relation is already there by the time you land here.
	$connectedUserIds = array();
	$commonInterests = array();
	if ($loggedUser) {
		$res = Streams::related(
			$loggedUser->id, $loggedUser->id, 'Streams/participating', true,
			array('type' => 'Streams/user/profile', 'limit' => 100)
		);
		$connectedRelations = (is_array($res) and isset($res[0]) and is_array($res[0]))
			? $res[0]
			: array();
		foreach ($connectedRelations as $r) {
			if ($r->fromPublisherId !== $loggedUser->id) {
				$connectedUserIds[$r->fromPublisherId] = true;
			}
		}
		$connectedUserIds = array_keys($connectedUserIds);

		// interests in common, for the people shown -- bounded by the list,
		// and cheap because interests are plain relations
		$commonInterests = array();
		foreach ($connectedUserIds as $otherId) {
			$commonInterests[$otherId] = Streams_QRconnect::commonInterests(
				$loggedUser->id, $otherId, 5
			);
		}
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
			'relations', 'loggedUser', 'columnsStyle', 'visitsStream',
			'connectedUserIds', 'commonInterests', 'text'
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
