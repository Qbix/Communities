<?php
	
function Communities_profileInfo_response_content($params)
{
	$r = array_merge($_REQUEST, $params);
	$uri = Q_Dispatcher::uri();
	$communityId = Users::communityId();
	$loggedInUser = Users::loggedInUser();
	$loggedInUserId = Q::ifset($loggedInUser, 'id', null);
	if (!empty(Communities::$cache['user'])) {
		$user = Communities::$cache['user'];
		$userId = $user->id;
	} else {
		$userId = Q::ifset($r, 'userId', Q::ifset($uri, 'userId', $loggedInUserId));
		$user = $userId ? Users_User::fetch($userId, true) : $loggedInUser;
	}
	$anotherUser = Q::ifset($params, 'anotherUser', false);

	$text = Q_Text::get('Communities/content');

	$genders = array(
		'male' => $text['profile']['Male'],
		'female' => $text['profile']['Female'],
		'other' => $text['profile']['Other']
	);
	$heights = array();
	for ($in = 12*3+6; $in <= 12*8; ++$in) {
		$cm = floor($in * 2.54);
		$feet = floor($in / 12);
		$inches = $in % 12;
		$heights[$cm] = "$feet'$inches\"";
	}
	$affiliations = Q_Config::get('Communities', 'affiliations', array());
	$dating = array(
		'yes' => $text['profile']['dating']['Interested'],
		'no' => $text['profile']['dating']['NotInterested'],
		'matchmaker' => $text['profile']['dating']['MatchmakerOnly'],
	);
	$greeting = Streams_Stream::fetch(null, $user->id, "Streams/greeting/$communityId");

	$languages = array_keys(Q_Config::get('Q', 'web', 'languages', array('en' => 1)));

	$blocked = false;
	if ($loggedInUser) {
		// get blocked users list
		$blockedUsers = (array)Communities::getBlockedUsersStream()->getAttribute('users');
		// whether user blocked
		$blocked = in_array($userId, $blockedUsers);
	}

	// get showLogout config option
	$showLogout = Q_Config::get("Communities", "me", "tabs", "profile", true);
	if (gettype($showLogout) == 'array') {
		$showLogout = Q::ifset($showLogout, 'showLogout', true);
	}

	Q_Response::setMeta(array(
		array('name' => 'name', 'value' => 'description', 'content' => $greeting->content),
		array('name' => 'property', 'value' => 'og:description', 'content' => $greeting->content),
		array('name' => 'property', 'value' => 'twitter:description', 'content' => $greeting->content)
	));

	// collect social
	$app = Q::app();
	$xids = $user->getAllXids();
	$socials = Communities::fetchSocialStreams(
		Users::loggedInUser(),
		$userId
	);
	foreach ($socials as $social => $stream) {
		$xids[$social.'/'.$app] = $stream->content;
	}

	// community permissions
	$communityId = Q::ifset($_SESSION, 'Communities', 'manage', 'communityId', $communityId);
	$userRoles = array();
	if ($communityId) {
		$userRoles = Users::roles($communityId, null, array(), $userId);
		$can = Users_Label::can($communityId, $loggedInUserId);
		$can['handleRoles'] = array_unique(array_merge(
			array(), $can['grant'], $can['revoke']
		));
		// remove roles which user can't see
		foreach ($userRoles as $label => $row) {
			if (!in_array($label, $can['see'])) {
				unset($userRoles[$label]);
			}
		}
		$labelsInfo = Users_Label::getLabelsInfo($communityId);

		// collect roles
		$rolesRows = Users_Contact::select("ul.*", "uc")->where(array(
			"uc.userId" => $communityId,
			"uc.contactUserId" => $userId
		))->join(Users_Label::table() . ' ul', array(
			'uc.userId' => 'ul.userId',
			'uc.label' => 'ul.label'
		), 'LEFT')->fetchDbRows();
		$canSeeRoles = Q::ifset($can, "see", array());
		$roles = array();
		foreach ($rolesRows as $rolesRow) {
			if (!in_array($rolesRow->label, $canSeeRoles)) {
				continue;
			}

			$roles[] = array(
				"label" => $rolesRow->label,
				"title" => $rolesRow->title,
				"icon" => Users::iconUrl($rolesRow->icon, "40.png")
			);
		}
	}

	$viewSettings = Q_Config::get('Communities', 'profile', null);
	$sections = array();
	foreach ($viewSettings['ordering'] as $section) {
		// check if this section forbidden from config
		if (!Q::ifset($viewSettings, 'sections', $section, true)) {
			continue;
		}

		$sections[$section] = Q::view("Communities/profile/$section.php", @compact(
			"userId", "loggedInUserId", "user", "anotherUser", 'genders', 'heights', 'app',
			'affiliations', 'dating', 'greeting', 'viewSettings', 'languages', 'xids', 'supportedSocials',
			'roles', 'can', 'userRoles', 'labelsInfo'
		));
	}

	return Q::view("Communities/content/profile.php", @compact(
		"userId", "loggedInUserId", "user", "anotherUser",
		'blocked', 'showLogout', 'sections'
	));
}