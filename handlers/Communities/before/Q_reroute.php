<?php

function Communities_before_Q_reroute()
{
	$communityId = Q_Request::special('c', null);
	if (!$communityId) {
		$token = Q_Dispatcher::uri()->token;
		if ($token) {
			$invite = Streams_Invite::fromToken($token);
			if ($invite && Users::isCommunityId($invite->publisherId) && $invite->publisherId != Users::currentCommunityId(true)) {
				$communityId = $invite->publisherId;
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
	Communities::switchCommunity($communityId);
}