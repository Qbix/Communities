<?php
	
function Communities_me_response_content($params)
{
	Q::event('Communities/me/response/column', $params);
	return Q::view('Communities/content/columns.php');
}