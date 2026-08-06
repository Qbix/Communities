<?php

function Communities_before_Q_reroute()
{
	$communityId = Q_Request::special('c', null);
	if (!$communityId) {
		$token = Q_Dispatcher::uri()->token;
		if ($token) {
			$invite = Streams_Invite::fromToken($token);
			if ($invite) {
				// Events can be published by a person and carry the community
				// in attributes.communityId, so the publisher is only a fallback
				$stream = Streams_Stream::fetch(
					null, $invite->publisherId, $invite->streamName
				);
				$c = $stream ? $stream->getAttribute('communityId', null) : null;
				if (!$c and Users::isCommunityId($invite->publisherId)) {
					$c = $invite->publisherId;
				}
				if ($c and $c != Users::currentCommunityId(true)) {
					$communityId = $c;
				}
			}
		}
	}

	if (!$communityId) {
		return;
	}

	if (!Users::isCommunityId($communityId)) {
		throw new Q_Exception_WrongValue(array(
			'field' => 'c',
			'range' => 'The ID of a community'
		));
	}

	if (Users::currentCommunityId(true) == $communityId) {
		return;
	}

	Users_User::fetch($communityId, true);
	Q_Response::setCookie('Q_Users_communityId', $communityId, time()+60*60*24*365);
	Communities::setCommunity($communityId, array(
		'subscribe' => array('Streams/experience/main')
	));
}