<?php

function Communities_people_response_content($params)
{
	Q::event('Communities/people/response/column', $params);
	return Q::view('Communities/content/columns.php');
}

