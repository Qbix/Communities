<?php

function Communities_events_response_load()
{
	$experienceId = Q::ifset($_REQUEST, 'experienceId', 'main');
	$communityId = Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId());
	list($fromTime, $toTime) = Communities::defaultEventTimes();
	$fromTime = Q::ifset($_REQUEST, 'fromTime', $fromTime);
	$toTime = Q::ifset($_REQUEST, 'toTime', $toTime);
	$offset = Q::ifset($_REQUEST, 'offset', 0);
	$limit = Q::ifset($_REQUEST, 'limit', Q_Config::get('Communities', 'pageSizes', 'events', 10));

	$allRelations = Communities::filterEvents(@compact("experienceId", "fromTime", "toTime", "communityId", "offset", "limit"));
	$relations = Streams_RelatedTo::filter($allRelations, array('readLevel' => 'fields'));

	$res = array();
	foreach ($relations as $relation) {
		$res[] = Q::tool(array(
			"Streams/preview" => array(
				'publisherId' => $relation->fromPublisherId,
				'streamName' => $relation->fromStreamName,
				'closeable' => false
			),
			"Calendars/event/preview" => array(
				'hideIfNoParticipants' => !Users::isCommunityId($relation->fromPublisherId)
			)
		), Q_Utils::normalize($relation->fromPublisherId . ' ' . $relation->fromStreamName));
	}

	Q_Response::setScriptData('Q.Communities.events.loadedCount', $offset + $limit);

	return $res;
}

