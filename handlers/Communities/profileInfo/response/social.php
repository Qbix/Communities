<?php
function Communities_profileInfo_response_social($params) {
	$r = array_merge($_REQUEST, $params);
	$loggedInUser = Users::loggedInUser();
	$loggedInUserId = Q::ifset($loggedInUser, 'id', null);

	$social = Q::ifset($r, 'social', null);
	if (empty($social)) {
		throw new Exception("Social network not defined");
	}

	$action = Q::ifset($r, 'action', null);
	if (empty($action)) {
		throw new Exception("Action not defined");
	}

	if (!empty(Communities::$cache['user'])) {
		$user = Communities::$cache['user'];
		$userId = $user->id;
	} else {
		$userId = Q::ifset($r, 'userId', $loggedInUserId);
	}

	// get or create social stream
	$streamName = 'Streams/user/'.$social;
	$stream = Streams_Stream::fetch(null, $userId, $streamName);
	if (empty($stream)) {
		$stream = Streams::create($userId, $userId, 'Streams/text/username', array(
			'name' => $streamName
		));
	}

	if ($action === 'update') {
		if ($userId !== $loggedInUserId) {
			throw new Users_Exception_NotAuthorized();
		}

		$value = Q::ifset($r, 'value', null);

		$stream->content = $value;
		$stream->save();

		return $stream->content;
	} elseif ($action === 'get') {
		return $stream->content;
	} else {
		throw new Exception("Invalid action");
	}
}