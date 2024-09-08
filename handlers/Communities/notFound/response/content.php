<?php

function Communities_notFound_response_content($params)
{
	header("HTTP/1.0 404 Not Found");
	$url = Q_Request::url();
	$tail = Q_Request::tail();
	if (Q_Request::isAjax()) {
		throw new Q_Exception_NotFound(@compact('url'));
	}
	return Q::view("Communities/content/notFound.php", @compact('url', 'tail'));
}

