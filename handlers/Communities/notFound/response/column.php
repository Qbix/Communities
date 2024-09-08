<?php

function Communities_notFound_response_column($params)
{
	header("HTTP/1.0 404 Not Found");
	$url = Q_Request::url();
	$tail = Q_Request::tail();
	if (Q_Request::isAjax()) {
		throw new Q_Exception_NotFound(@compact('url'));
	}
	Q_Dispatcher::uri()->action = 'notFound';
	return Q::view("Communities/content/column.php", @compact('url', 'tail'));
}

