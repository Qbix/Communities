<?php
	
function Communities_inbox_response_content()
{
	$user = Users::loggedInUser();

	$participating = Communities::participatingChats($user->id);

	$public = array();
	foreach ($participating as $stream) {
		if (!$stream->isPrivate()
		and $stream->readLevel >= Streams::$READ_LEVEL['messages']) {
			// can be fetched in bulk as a public stream
			$public[$stream->publisherId][$stream->name] = true;
		}	
	}
	Streams::arePublic($public);

	$column = Q::view('Communities/column/inbox.php', @compact(
		'user', 'participating'
	));
	$text = Q_Text::get('Communities/content');
	$title = Q::ifset(Communities::$options, 'title', $text['inbox']['Title']);
	$url = Q::ifset(Communities::$options, 'url', "Communities/inbox");
	Communities::$columns['inbox'] = array(
		'title' => $title,
		'column' => $column,
		'close' => false,
		'url' => Q_Uri::url($url)
	);

	Q_Response::addScript('{{Communities}}/js/pages/inbox.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/inbox.css');

	return Q::view('Communities/content/columns.php');
}