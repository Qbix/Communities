<?php

function Communities_newEvent_response_content()
{
	$columns = array(
		'events' => Q::event('Communities/events/response/column'),
		'newEvent' => Q::event('Communities/newEvent/response/column')
	);
	$text = Q_Text::get('Communities/content');
	Q_Response::setSlot('title', $text['newEvent']['Title']);
	return Q::view('Communities/content/columns.php', @compact('user', 'columns'));
}