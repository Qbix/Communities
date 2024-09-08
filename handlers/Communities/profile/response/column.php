<?php
/**
 * Renders a profile
 * @param {array} $params Can be passed to the function or found in $_REQUEST
 * @param {array} $params.userId
 */
function Communities_profile_response_column ($params = array()) {
	$r = array_merge($_REQUEST, $params);
	$uri = Q_Dispatcher::uri();
	$userId = Q::ifset($r, 'userId', Q::ifset($uri, 'userId', null));
	$currentTab = Q::ifset($r, 'currentTab', Q::ifset($uri, 'currentTab', ''));
	$loggedInUser = Users::loggedInUser();
	$communityId = Users::currentCommunityId(true);
	$user = $userId ? Users_User::fetch($userId) : $loggedInUser;
	if (!$user) {
		// try fetching by username
		$identify = Users::identify('username', $userId);
		$user = Users_User::fetch($identify->userId);
		$userId = $identify->userId;
	}
	if (!$user) {
		Q_Response::redirect("Communities/events");
		return true;
	}
	$userId = $user->id;
	Communities::$cache['user'] = $user;
	$isAdmin = false;
	$labelTitles = array();
	$labelIcons = array();
	$chatStream = null;
	$loggedInUserId = $loggedInUser ? $loggedInUser->id : null;
	if ($loggedInUser) {
		$contacts = Users_Contact::fetch(
			$loggedInUser->id, null, array('contactUserId' => $userId)
		);
		$labels = array();
		foreach ($contacts as $c) {
			$labels[] = $c->label;
		}
		$rows = Users_Label::select()->where(array(
			'userId' => $loggedInUser->id,
			'label' => $labels
		))->fetchDbRows();
		foreach ($rows as $row) {
			$labelTitles[] = $row->title;
			$labelIcons[] = Users::iconUrl($row->icon, "40.png");
		}
		if ($userId != $loggedInUserId) {
			$chatStream = Streams_Stream::fetch(null, $userId, "Streams/chat/$loggedInUserId");
			if (!$chatStream) {
				$chatStream = Streams_Stream::fetch(null, $loggedInUserId, "Streams/chat/$userId");
			}
		}
		$isAdmin = (bool)Users::roles($communityId, Q_Config::expect("Communities", "community", "admins"), array(), $loggedInUserId);
	}
	$hasLabelsClass = $labelTitles ? 'Communities_has_labels' : 'Communities_no_labels';
	$classes = $loggedInUserId == $userId ? 'Communities_myProfile' : 'Communities_notMyProfile';
	$displayName = $user->displayName();

	$url = Q::ifset(Communities::$options, 'url', "Communities/people");

	$title = $displayName;

	$clickOrTap = Q_Text::clickOrTap(false);

	$tabs = Q_Config::expect("Communities", "profile", "tabs");
	$results = array();
	$contentParams = @compact("userId", "clickOrTap", "chatStream", "currentTab");
	// collect tabs content and controls
	foreach ($tabs as $tab => $content) {
		if (!$content) {
			continue;
		}

		if ($loggedInUserId && $userId == $loggedInUserId) {
			if ($tab == "chat") {
				continue;
			}
		}

		// check content
		if (is_file(COMMUNITIES_PLUGIN_VIEWS_DIR.DS."Communities".DS."column".DS."profile".DS.$tab.".php")) {
			$results[$tab]["content"] = Q::view("Communities/column/profile/".$tab.".php", $contentParams);
		} elseif (is_file(COMMUNITIES_PLUGIN_VIEWS_DIR.DS."Communities".DS."column".DS.$tab.".php")) {
			$results[$tab]["content"] = Q::view("Communities/column/".$tab.".php", $contentParams);
		}

		// check controls
		$controlView = COMMUNITIES_PLUGIN_VIEWS_DIR.DS."Communities".DS."column".DS."profile".DS.$tab."Controls.php";
		if (is_file($controlView)) {
			$results[$tab]["controls"] = Q::view("Communities/column/profile/".$tab."Controls.php");
		}
	}
	// collect tabs from other hooks
	Q::event("Communities/profile/tabs", @compact("results", "tabs", "userId"), "after", false, $results);

	// check if user registered with web3
	$allowedCryptoActions = false;
	$hasWallet = $user->getXid("web3_all");
	if ($hasWallet) {
		// check access to Streams/user/xid/web3 stream
		$walletStream = Streams::fetchOne(null, $user->id, "Streams/user/xid/web3");
		if ($walletStream && $walletStream->testReadLevel("content")) {
			$allowedCryptoActions = true;
		}
	}

	$emailStream = Streams::fetchOne(null, $user->id, "Streams/user/emailAddress");
	$allowedEmail = $emailStream->testReadLevel("content");

	$phoneStream = Streams::fetchOne(null, $user->id, "Streams/user/mobileNumber");
	$allowedSMS = Q_Request::isMobile() && $phoneStream->testReadLevel("content");

	$shortDisplayName = $user->displayName(array('short' => true));

	$can = Users_Label::can($communityId, $userId);
	$labelsInfo = Users_Label::getLabelsInfo($communityId);

	$blocked = false;
	if ($loggedInUser) {
		// get blocked users list
		$blockedUsers = (array)Communities::getBlockedUsersStream()->getAttribute('users');
		// whether user blocked
		$blocked = in_array($userId, $blockedUsers);
	}

	// collect social
	$xids = array();
	$supportedSocials = Q_Config::get("Communities", "profile", "social", array());
	$supportedSocials = array_keys($supportedSocials);
	foreach ($supportedSocials as $item) {
		$value = Q::event('Communities/profileInfo/response/social', array(
			'social' => $item,
			'userId' => $userId,
			'action' => 'get'
		));

		if (!empty($value)) {
			$xids[$item] = $value;
		}
	}

	// collect links
	$links = Streams::related(null, $userId, "Streams/user/urls",true, array(
		"type" => "Websites/webpage",
		"relationsOnly" => true
	));

	$greeting = Streams_Stream::fetch(null, $user->id, "Streams/greeting/$communityId");
	if ($greeting && $greeting->content) {
		$greeting = Q::view("Communities/profile/greeting.php", compact("greeting", "userId", "loggedInUserId", "anotherUser"));
	} else {
		$greeting = null;
	}

	if ($loggedInUserId) {
		$loggedInUserCan = Users_Label::can($communityId, $loggedInUserId);
	}

	// check if user can see roles
	$canSeeRoles = $loggedInUserId && ((count(Q::ifset($can, 'roles', array())) && array_intersect($can['roles'], $loggedInUserCan['see'])) || count(Q::ifset($loggedInUserCan, 'grant', array())));

	$sizes = Streams_Avatar::iconSizes($userId);
	if (empty($sizes)) {
		$sizes = Q_Image::getSizes('Users/icon');
	}
	end($sizes);
	$avatarIconSize = key($sizes);

	$column = Q::view("Communities/column/profile.php", @compact(
		'user', 'userId', 'loggedInUserId', 'loggedInUser', 'labelTitles', 'isAdmin', 'avatarIconSize',
		'labelIcons', 'hasLabelsClass', 'classes', 'results', 'currentTab', 'hasWallet', 'links',
		'allowedCryptoActions', 'shortDisplayName', 'can', 'labelsInfo', 'blocked', 'supportedSocials',
		'xids', 'greeting', 'loggedInUserCan', 'allowedEmail', 'allowedSMS', 'communityId', 'canSeeRoles', 'loggedInUserCan'
	));

	// set metas
	$userIcon = Q_Uri::interpolateUrl($user->icon."/$avatarIconSize.png");
	$userUrl = Q_Uri::interpolateUrl("{{baseUrl}}/profile/$user->id");
	Q_Response::setMeta(array(
		array('name' => 'name', 'value' => 'title', 'content' => $title),
		array('name' => 'property', 'value' => 'og:title', 'content' => $title),
		array('name' => 'property', 'value' => 'twitter:title', 'content' => $title),
		array('name' => 'name', 'value' => 'image', 'content' => $userIcon),
		array('name' => 'property', 'value' => 'og:image', 'content' => $userIcon),
		array('name' => 'property', 'value' => 'twitter:image', 'content' => $userIcon),
		array('name' => 'property', 'value' => 'og:url', 'content' => $userUrl),
		array('name' => 'property', 'value' => 'twitter:url', 'content' => $userUrl),
		array('name' => 'property', 'value' => 'twitter:card', 'content' => 'summary')
	));

	Q_Response::addScript('{{Communities}}/js/columns/profile.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/profile.css');

	// $greeting->addPreloaded();
	Q_Response::setSlot('title', $title);
	
	Q_Response::addScript('{{Communities}}/js/columns/people.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/people.css');

	Communities::$columns['profile'] = array(
		'title' => Q_Html::text($title),
		'column' => $column,
		'columnClass' => 'Communities_column_profile',
		'url' => Q_Uri::url($url)
	);
	
	return $column;
}