<?php

function Communities_event_response_content($params)
{
	$user = Users::loggedInUser();
	$publisherId = Q::ifset($params, 'publisherId', Streams::requestedPublisherId());
	$eventId = Q::ifset($params, 'eventId', Communities::requestedId($params, 'eventId'));
	$streamName = "Calendars/event/$eventId";
	$stream = Streams_Stream::fetch(null, $publisherId, $streamName, true);

	if ($user) {
		$participant = new Streams_Participant();
		$participant->publisherId = $stream->publisherId;
		$participant->streamName = $stream->name;
		$participant->state = 'participating';
		$participant->userId = $user->id;

		// if user participated, show ME/scheduled column under event column
		if ($participant->retrieve()) {
			Q::event('Communities/me/response/column', array(
				'tab' => 'schedule',
				'scheduleSubTab' => (int)$stream->getAttribute('startTime') > time() ? 'future' : 'past'
			));
		} else { // if not participated, then just events column
			$params['skipMetas'] = true;
			Q::event('Communities/events/response/column', $params);
		}
	} else {
		Q::event('Communities/events/response/column', $params);
	}

	$params['stream'] = $stream;
	Q::event('Communities/event/response/column', $params);
	return Q::view('Communities/content/columns.php');
}