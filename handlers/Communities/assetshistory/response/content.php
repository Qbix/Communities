<?php

function Communities_assetshistory_response_content($params)
{
	Q::event('Communities/assetshistory/response/column', $params);
	return Q::view('Communities/content/columns.php');
}

