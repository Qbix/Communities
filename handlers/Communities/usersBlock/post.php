<?php

/**
 * Block/unblock users in profile
 * @param {array} $_REQUEST
 * @param {string} $_REQUEST.userId The id of the user need to block/unblock
 * @param {string} $_REQUEST.action The action need to do (block, unblock)
 */
function Communities_usersBlock_post($params)
{
	$communityId = Q::ifset($_REQUEST, 'communityId', Users::communityId());
	$community = Users_User::fetch($communityId);
	if (!$community) {
		throw new Q_Exception("No community found with id $communityId");
	}

	$r = array_merge($_REQUEST, $params);
	$required = array('action', 'userId');
	Q_Valid::requireFields($required, $r, true);

	$currentUser = Users::loggedInUser(true);
	$userId = $r["userId"]; // user id need to block/unblock
	$action = $r["action"]; // action need to do with user

	if (!in_array($action, array("block", "unblock"))) {
		throw new Q_Exception("Possible action values: block, unblock");
	}

	// get or create Communities/users/blocked stream
	$usersBlockedStream = Communities::getBlockedUsersStream();
	$blockedUsersList = (array)$usersBlockedStream->getAttribute('users');

	// private chats
	$chat_1 = Streams_Stream::fetch($currentUser->id, $currentUser->id, "Streams/chat/".$userId);
	$chat_2 = Streams_Stream::fetch($userId, $userId, "Streams/chat/".$currentUser->id);

	// block user
	if ($action == 'block') {
		// current user is a publisher of chat stream
		if ($chat_1 instanceof Streams_Stream) {
			$chat_1->close($chat_1->publisherId);
		}
		// current user participated to chat stream
		if ($chat_2 instanceof Streams_Stream) {
			$chat_2->leave();
			$chat_2->unsubscribe();
		}

		$blockedUsersList[] = $userId;
	}
	if ($action == 'unblock') {
		// current user is a publisher of chat stream
		if ($chat_1 instanceof Streams_Stream) {
			$chat_1->closedTime = null;
			$chat_1->save();
		}

		if ($chat_2 instanceof Streams_Stream) {
			$chat_2->join();
			$chat_2->subscribe();
		}

		$key = array_search($userId, $blockedUsersList);
		if ($key !== false) {
			unset($blockedUsersList[$key]);
		}

	}

	$usersBlockedStream->setAttribute('users', $blockedUsersList);
	$usersBlockedStream->save();

	Q_Response::setSlot("result", true);
}