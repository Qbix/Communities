<?php

function Communities_events_response_content($params)
{
	Q::event('Communities/events/response/column', $params);
	return Q::view('Communities/content/columns.php');
}