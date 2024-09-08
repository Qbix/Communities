<?php

function Communities_services_response_load()
{
	$experienceId = Q::ifset($_REQUEST, 'experienceId', 'main');
	$communityId = Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId());
	$offset = Q::ifset($_REQUEST, 'offset', 0);
	$limit = Q::ifset($_REQUEST, 'limit', Q_Config::get('Communities', 'pageSizes', 'services', 10));
	$textfill = Q_Config::get('Communities', 'availability', 'preview', 'textfill', false);

	$relations = Communities::filterServices(@compact("experienceId", "communityId", "offset", "limit"));

	$res = array();
	foreach ($relations as $relation) {
		$res[] = Q::tool(array(
			"Streams/preview" => array(
				'publisherId' => $relation->fromPublisherId,
				'streamName' => $relation->fromStreamName,
				'closeable' => false
			),
			"Calendars/availability/preview" => array(
				'textfill' => $textfill
			)
		), Q_Utils::normalize($relation->fromPublisherId . ' ' . $relation->fromStreamName));
	}

	return $res;
}

