<?php

function Communities_welcome_response_content($params)
{
	Q::event('Communities/welcome/response/column', $params);
	return Q::view('Communities/content/columns.php');
}