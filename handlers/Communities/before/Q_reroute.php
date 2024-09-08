<?php

function Communities_before_Q_reroute()
{
	$communityId = Q_Request::special('c', null);
	if ($communityId) {
		if (!Users::isCommunityId($communityId)) {
			throw new Q_Exception_WrongValue(array(
				'field' => 'c',
				'range' => 'The ID of a community'
			));
		}
		Users_User::fetch($communityId, true);
		Q_Response::setCookie('Q_Users_communityId', $communityId, time()+60*60*24*365);
		Communities::setCommunity($communityId, array(
			'subscribe' => array('Streams/experience/main')
		));
	}
}