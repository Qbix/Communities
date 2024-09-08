<?php

function Communities_trip_response_content($params)
{
	Q::event('Communities/trip/response/column', $params);
	return Q::view('Communities/content/columns.php');
}