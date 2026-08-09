<?php

function Communities_connect_response_content($params)
{
	Q::event('Communities/connect/response/column', $params);

	return Q::view('Communities/content/columns.php');
}
