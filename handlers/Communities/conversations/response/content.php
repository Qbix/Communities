<?php

function Communities_conversations_response_content($params)
{
	Q::event('Communities/conversations/response/column', $params);

	return Q::view('Communities/content/columns.php');
}