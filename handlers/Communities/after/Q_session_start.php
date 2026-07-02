<?php

function Communities_after_Q_session_start($params)
{
	if (Q::ifset($_SESSION, 'Users', 'communityId', null)) {
		return;
	}
	$communityId = Q::ifset($_COOKIE, 'Q_Users_communityId', null);
	if (!$communityId || !Users::isCommunityId($communityId)) {
		return;
	}
	$_SESSION['Users']['communityId'] = $communityId;
}
