<?php
/* Return list of conversations with messages older than fromTime */
function Communities_unseen_response_conversations()
{
	$user = Users::loggedInUser(true);

	$params = Q::take($_REQUEST, array('fromTime'));

	// get participated conversations
	$participating = Communities::participatingChats($user->id);
	$res = array();

	if (!isset($params['fromTime'])) {
		return $participating;
	}

	// if fromTime defined, collect messages created later than fromTime
	foreach ($participating as $participant) {
		$unseenMessages = Streams_Message::select("count(*)")->where(array(
			'publisherId' => $participant->publisherId,
			'streamName' => $participant->name,
			'type' => 'Streams/chat/message',
			'insertedTime > ' => $params['fromTime']
		))->execute()->fetch(PDO::FETCH_NUM)[0];

		if ((int)$unseenMessages > 0) {
			$res[] = array(
				'publisherId' => $participant->publisherId,
				'streamName' => $participant->name
			);
		}
	}

	return $res;
}