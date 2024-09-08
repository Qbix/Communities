<?php

function Communities_conversation_response_content($params)
{
	$params = array_merge($_REQUEST, $params);
	$user = Users::loggedInUser();
	$publisherId = Q::ifset($params, 'publisherId', Streams::requestedPublisherId());
	$streamName = Q::ifset($params, 'streamName', Communities::requestedId($params, 'streamName'));
	$streamName = implode('/', $streamName);
	$streamNames = array();

	// streamName can be just last part of stream name or full stream name with _ instead /
	if (strpos($streamName, '_') === false) {
		$streamNames[] = Q::event('Communities/chatStreamName', array('streamId' => $streamName));
		$streamNames[] = Q::event('Communities/chatStreamName', array('streamId' => ucfirst($streamName)));
	} else {
		$parts = explode('_', $streamName);
		$firstPart = array_shift($parts);
		$secondPart = array_shift($parts);
		$lastPart = implode('_', $parts);
		$streamNames[] = ucfirst($firstPart)."/".$secondPart."/".$lastPart;
		$streamNames[] = ucfirst($firstPart)."/".$secondPart."/".ucfirst($lastPart);
	}

	if (!$publisherId) {
		throw new Exception("publisherId required");
	}
	if (!$streamName) {
		throw new Exception("conversation stream name required");
	}

	// get stream name by conversationId
	$rows = Streams_Stream::select()->where(array(
		"publisherId" => $publisherId,
		"name" => $streamNames
	))->fetchDbRows();
	if (!count($rows)) {
		throw new Exception("Conversation stream not found");
	}
	$row = reset($rows);
	if (!is_null($row->closedTime)) {
		throw new Exception("Conversation stream closed");
	}

	$stream = Streams_Stream::fetch(null, $publisherId, $row->name, true);

	if ($user) {
		$participant = new Streams_Participant();
		$participant->publisherId = $stream->publisherId;
		$participant->streamName = $stream->name;
		$participant->userId = $user->id;

		// if user participated, show ME/scheduled column under event column
		if ($participant->retrieve()) {
			Q::event('Communities/me/response/column', array(
				'tab' => 'inbox'
			));
		} else { // if not participated, then just conversations column
			Q::event('Communities/conversations/response/column', $params);
		}
	} else {
		Q::event('Communities/conversations/response/column', $params);
	}

	$params['stream'] = $stream;
	Q::event('Communities/conversation/response/column', $params);

	return Q::view('Communities/content/columns.php');
}