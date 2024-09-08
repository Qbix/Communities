<?php

/**
 * Creates a conversation between two people.
 * Later, will support requests and payments to start a conversation.
 * @class HTTP Communities chat
 * @method POST
 * @static
 * @param {array} $_REQUEST
 * @param {string} $_REQUEST.publisherId The user id to chat with.
 */
function Communities_chat_post()
{
	Q_Request::requireFields(array('publisherId'), true);
	$user = Users::loggedInUser(true);
	$userId = $user->id;
	$publisherId = $_REQUEST['publisherId'];
	$name = "Streams/chat/$userId";
	$userAvatar = Streams_Avatar::fetch($publisherId, $userId);
	$publisherAvatar = Streams_Avatar::fetch($userId, $publisherId);
	$a = $userAvatar->displayName(array('short' => true));
	$b = $publisherAvatar->displayName(array('short' => true));
	$arrow = html_entity_decode('&hArr;',ENT_NOQUOTES,'UTF-8');
	$title = "$a $arrow $b";
	$private = true;
	$notices = true;
	$readLevel = $writeLevel = $adminLevel = 0;
	$stream = Streams_Stream::fetch($publisherId, $publisherId, $name);
	if (empty($stream)) {
		$stream = Streams::create($publisherId, $publisherId, 'Streams/chat', @compact(
			'name', 'title',
			'readLevel', 'writeLevel', 'adminLevel'
		), @compact(
			'private', 'notices'
		));
	}
	$stream->subscribe(array('userId' => $userId));
	$stream->subscribe(array('userId' => $publisherId));

	$access = new Streams_Access();
	$access->publisherId = $publisherId;
	$access->streamName = $name;
	$access->ofUserId = $userId;
	if (!$access->retrieve()) {
		$access->readLevel = Streams::$READ_LEVEL['max'];
		$access->writeLevel = Streams::$WRITE_LEVEL['relate'];
		$access->adminLevel = Streams::$ADMIN_LEVEL['invite'];
		$access->save();
	}
	Q_Response::setSlot('stream', $stream->exportArray());
}