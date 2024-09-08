<?php

/**
 * Shows the interface for an welcome page
 *
 * @param {array} $_REQUEST 
 * @optional
 * @return {string}
 */
function Communities_welcome_response_column($options = array()) {
	$text = Q_Text::get('Communities/content');
	$communityId = Users::communityId();

	// Do controller stuff here. Prepare variables
	if (Users::loggedInUser(false, false)) {
		Q_Response::redirect($communityId.'/home', array('querystring' => true));
		return true;
	}

	$userIds = Communities::userIds(array('uploadedIconsFirst' => true));
	$users_count = Users_User::select('COUNT(1)')->execute()->fetchColumn(0);
	$events_count = Streams_Stream::select('COUNT(1)')->where(array(
		'type' => 'Calendars/event'
	))->execute()->fetchColumn(0);
	$rsvps_count = Streams_Participant::select('COUNT(1)')->where(array(
		'streamType' => 'Calendars/event'
	))->execute()->fetchColumn(0);

	Q_Response::addScript('{{Communities}}/js/columns/welcome.js');
	Q_Response::addStylesheet('{{Communities}}/css/columns/welcome.css');

	$communityUser = Users_User::fetch($communityId);
	$title = Q::text($text['welcome']['Title'], array("appName" => $communityUser->displayName()));
	$description = Q::text($text['welcome']['Description'], array($communityId));
	$communityIcon = Q_Uri::interpolateUrl($communityUser->icon.'/400.png');
	$url = Q_Uri::url($communityId.'/welcome');
	Q_Response::setMeta(array(
		array('attrName' => 'name', 'attrValue' => 'title', 'content' => $title),
		array('attrName' => 'property', 'attrValue' => 'og:title', 'content' => $title),
		array('attrName' => 'property', 'attrValue' => 'twitter:title', 'content' => $title),
		array('attrName' => 'name', 'attrValue' => 'description', 'content' => $description),
		array('attrName' => 'property', 'attrValue' => 'og:description', 'content' => $description),
		array('attrName' => 'property', 'attrValue' => 'twitter:description', 'content' => $description),
		array('attrName' => 'name', 'attrValue' => 'image', 'content' => $communityIcon),
		array('attrName' => 'property', 'attrValue' => 'og:image', 'content' => $communityIcon),
		array('attrName' => 'property', 'attrValue' => 'twitter:image', 'content' => $communityIcon),
		array('attrName' => 'property', 'attrValue' => 'og:url', 'content' => $url),
		array('attrName' => 'property', 'attrValue' => 'twitter:url', 'content' => $url)
	));
	if ($fbApps = Q_Config::get('Users', 'apps', 'facebook', array())) {
		$app = Q::app();
		$fbApp = isset($fbApps[$app]) ? $fbApps[$app] : reset($fbApps);
		if ($appId = $fbApp['appId']) {
			Q_Response::setMeta(array(
				'attrName' => 'property', 'attrValue' => 'fb:app_id', 'content' => $appId
			));
		}
	}

	$gallery = Q_Config::get("Communities", "welcome", "gallery", null);
	foreach ($gallery as $i => $item) {
		if (empty($item["caption"])) {
			continue;
		}

		$parts = explode("/", $item["caption"]);
		$text = Q_Text::get(implode("/", array($parts[0], $parts[1])));
		$gallery[$i]["caption"] = Q::ifset($text, $parts[2], $parts[3], $parts[4], null);
	}

	$admins = Users_Contact::select("contactUserId")->where(array(
		"userId" => $communityId,
		"label" => array("Users/owners", "Users/admins", "Users/hosts")
	))->fetchAll(PDO::FETCH_COLUMN);
	$admins = array_unique($admins);

	$usersList = Q_Config::get("Communities", "welcome", "usersList", null);

	$column = Q::view('Communities/column/welcome.php', compact(
		'userIds', 'users_count', 'events_count', 'rsvps_count', 'communityId', 'gallery', 'admins', 'usersList'
	));
	Communities::$columns['welcome'] = array(
		'name' => 'welcome',
		'title' => $title,
		'column' => $column,
		'close' => false,
		'url' => Q_Uri::url("Communities/welcome")
	);

	return $column;
}