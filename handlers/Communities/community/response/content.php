<?php

function Communities_community_response_content($params)
{
	//Q::event('Communities/communities/response/column', $params);
	Q::event('Communities/community/response/column', $params);

	// no user found with this id
	// may be it is a page?
	if (Q::ifset(Communities::$columns, 'community', 'notFound', false)) {
		$action = Q::ifset(Communities::$columns, 'community', 'action', null);
		$app = Q::app();
		Q_Dispatcher::forward("$app/$action");
	}

	return Q::view('Communities/content/columns.php');
}

