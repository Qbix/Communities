<?php
	
function Communities_onboarding_response_content()
{
	$user = Users::loggedInUser();
	if (!$user) {
		Q_Response::redirect(Q_Request::baseUrl(true));
		return '';
	}
	if ($stream = Streams_Stream::fetch(null, $user->id, 'Places/user/location')) {
		$stream->addPreloaded();
	}
	$limit = Q_Config::get('Communities', 'people', 'userIds', 'limit', 100);
	Q_Response::addStylesheet("{{Communities}}/css/tools/onboarding.css");
	return Q::tool('Communities/onboarding', array(
		'usersList' => array(
			'userIds' => Communities::userIds(array('limit' => $limit))
		),
		'communityId' => Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId(true))
	));
}