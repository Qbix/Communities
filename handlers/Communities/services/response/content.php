<?php

function Communities_services_response_content($params)
{
	Q::event('Communities/services/response/column', $params);
	return Q::view('Communities/content/columns.php');
}