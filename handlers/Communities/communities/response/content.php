<?php

function Communities_communities_response_content($params)
{
	Q::event('Communities/communities/response/column', $params);
	return Q::view('Communities/content/columns.php');
}

