<?php

function Communities_newEvent_response_column($options = array())
{
	$options = array_merge($_REQUEST, $options);
	$uri = Q_Dispatcher::uri();
	$options['publisherId'] = Q::ifset($options, 'publisherId', Q::ifset($uri, 'publisherId', null));

	$text = Q_Text::get('Communities/content');
	$title = $text['newEvent']['Title'];
	$options['publishers'] = Communities::newEventAuthorized();

	if (empty($options['publishers'])) {
		throw new Users_Exception_NotAuthorized();
	}

	$column = Q::tool('Calendars/event/composer', $options);

	Communities::$columns['newEvent'] = array(
		'title' => $title,
		'column' => $column
	);

	Q_Response::setSlot('title', $title);
	Q_Response::addStylesheet('{{Calendars}}/css/composer.css', 'Calendars');
	return $column;
}