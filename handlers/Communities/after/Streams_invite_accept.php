<?php
	
function Communities_after_Streams_invite_accept($params)
{
	// subscribe to a few streams, if you haven't already
	$invite = $params['invite'];
	$userId = $invite->userId;
	$streams = Streams::participating(null, array(
		'publisherId' => $userId,
		'streamsOnly' => true,
		'category' => 'Places/participating'
	));
	foreach ($streams as $s) {
		if (!Q::startsWith($s->name, 'Places/area/')) {
			continue;
		}
		$related = $s->related(null, false, array('streamsOnly' => true));
		foreach ($related as $stream) {
			if (!substr($stream->name, 0, 7) === 'Places/') continue;
			if ($stream and !$stream->subscription($userId)) {
				$stream->subscribe();
			}
		}
	}
	// make the user have a default location, if they don't already
	if (Streams_Stream::fetch($userId, $userId, 'Places/user/location')) {
		return;
	}
	$stream = Streams_Stream::fetch(null, $invite->publisherId, $invite->streamName);
	if (!$stream) {
		return;
	}
	$location = $stream->getAttribute('location');
	$locationStreamName = Q::ifset($location, 'name', null);
	$locationPublisherId = Q::ifset($location, 'publisherId', null);
	if (!$locationStreamName || !$locationPublisherId) {
		return;
	}
	$stream = Streams_Stream::fetch(null, $locationPublisherId, $locationStreamName);
	if (!$stream) {
		return;
	}
	Places::setUserLocation($stream, true, true);
}