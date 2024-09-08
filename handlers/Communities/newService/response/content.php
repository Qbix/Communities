<?php

function Communities_newService_response_content()
{
	$columns = array(
		'services' => Q::event('Communities/services/response/column'),
		'newService' => Q::event('Communities/newService/response/column')
	);
	$text = Q_Text::get('Communities/content');
	Q_Response::setSlot('title', $text['services']['MakeReservation']);
	return Q::view('Communities/content/columns.php', @compact('user', 'columns'));
}