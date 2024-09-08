<?php

function Communities_newService_response_column($options = array())
{
	$options = array_merge($_REQUEST, $options);
	$uri = Q_Dispatcher::uri();
	$options['publisherId'] = Q::ifset($options, 'publisherId', Q::ifset($uri, 'publisherId', null));

	$text = Q_Text::get('Communities/content');

	$column = Q::tool('Calendars/service/browser', $options);
	$title = $text['services']['MakeReservation'];

	Communities::$columns['newService'] = array(
		'title' => $title,
		'column' => $column
	);

	Q_Response::setSlot('title', $title);
	Q_Response::addStylesheet('{{Calendars}}/css/composer.css', 'Calendars');
	return $column;
}