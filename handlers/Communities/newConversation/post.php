<?php

/**
 * Used to plan a new conversation. Fills slots "stream" and "participant".
 *
 * @param {array} $_REQUEST
 * @param {string} [$_REQUEST.interestTitle] Required. Title of an interest that exists in the system.
 * @param {string} [$_REQUEST.publisherId] Optional. The user who would publish the event. Defaults to the logged-in user.
 * @param {string} [$_REQUEST.communityId] Optional. The user id of the community. Defaults to the app's name.
 * @param {string|array} [$options.experienceId="main"] Can set one or more ids of community experiences the event will be related to.
 * @optional
 */
function Communities_newConversation_post($options)
{
	$publisherId = Streams::requestedPublisherId();
	if (empty($publisherId)) {
		$user = Users::loggedInUser(true);
		$publisherId = $user->id;
	}
	$r = Q::take($_REQUEST, array(
		'interestTitle' => null,
		'communityId' => null,
		'publisherId' => null,
		'title' => null,
		'content' => null
	));
	$required = array('interestTitle', 'title', 'content');
	foreach ($required as $field) {
		if (!$r[$field]) {
			Q_Response::addError(new Q_Exception_RequiredField(@compact('field')));
		}
	}
	if (Q_Response::getErrors()) {
		return;
	}

	$communityId = Q::ifset($r, 'communityId', Users::communityId());

	// interest
	$interest = new Streams_Stream();
	$interest->publisherId = $communityId;
	$normalizedInterestTitle = Q_Utils::normalize($r['interestTitle']);
	$interest->name = "Streams/interest/$normalizedInterestTitle";
	if (!$interest->retrieve()) {
		throw new Q_Exception_MissingRow(array(
			'table' => 'Interest',
			'criteria' => 'name ' . $interest->name
		));
	}

	// save the event in the database
	$stream = Streams::create(null, $publisherId, 'Streams/chat', array(
		'icon' => $interest->icon,
		'title' => $r["title"],
		'content' => $r["content"],
		'attributes' => array(
			'communityId' => $communityId,
			'interest' => $interest->name,
			'interestTitle' => $interest->title
		),
		'readLevel' => Streams::$READ_LEVEL['max'],
		'writeLevel' => Streams::$WRITE_LEVEL['relate'],
		'adminLevel' => Streams::$ADMIN_LEVEL['invite']
	));

	// Since you created the event, it is assumed you're going
	$stream->subscribe();

	// now, relate it to a few streams, so it can be found
	$experienceId = Q::ifset($options, 'experienceId', 'main');
	$experienceIds = is_array($experienceId) ? $experienceId : array("$experienceId");
	$streamType = "Communities/conversations";
	foreach ($experienceIds as $experienceId) {
		$o = array('skipAccess' => true);
		$stream->relateTo($interest, $streamType, null, $o);
	}

	Q_Response::setSlot('stream', $stream->exportArray());
}