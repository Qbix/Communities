<?php

function Communities_conversations_response_load()
{
	$experienceId = Q::ifset($_REQUEST, 'experienceId', 'main');
	$communityId = Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId(true));
	$offset = Q::ifset($_REQUEST, 'offset', 0);
	$limit = Q::ifset($_REQUEST, 'limit', Q_Config::get('Communities', 'pageSizes', 'conversations', 20));

	$relations = Communities::conversationChats($communityId, $experienceId, $offset, $limit);
	$public = array();
	foreach ($relations as $r) {
		$public[$r->fromPublisherId][$r->fromStreamName] = true;
	}
	Streams::arePublic($public);

	$res = array();
	foreach ($relations as $relation) {
		$res[] = Q::tool(array(
			"Streams/preview" => array(
				'publisherId' => $relation->fromPublisherId,
				'streamName' => $relation->fromStreamName,
				'closeable' => false,
				'editable' => false
			),
			$relation->type."/preview" => array(
				'hideIfNoParticipants' => false,
				'publisherId' => $relation->fromPublisherId,
				'streamName' => $relation->fromStreamName
			)
		), Q_Utils::normalize($relation->fromPublisherId . ' ' . $relation->fromStreamName));
	}

	return $res;
}

