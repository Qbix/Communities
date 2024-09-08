<?php
	
function Communities_inbox_response_content()
{
	$user = Users::loggedInUser();

	$participating = Communities::participatingChats($user->id);

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