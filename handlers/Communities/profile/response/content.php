<?php

function Communities_profile_response_content($params)
{
	Q::event('Communities/people/response/column', $params);
	Q::event('Communities/profile/response/column', $params);
	$blocked = false;
	return Q::view('Communities/content/columns.php', @compact('blocked'));
}