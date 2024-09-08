<?php

function Communities_people_response_load()
{
	$options = Q_Config::get('Communities', 'people', 'userIds', array());
	$options['communityId'] = Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId(true));
	$options['offset'] = (int)Q::ifset($_REQUEST, 'offset', 0);
	$options['limit'] = (int)Q::ifset($_REQUEST, 'limit', Q_Config::get(
		'Communities', 'pageSizes', 'people', 50
	));

	$userIds = Communities::userIds($options);

	return $userIds;
}

