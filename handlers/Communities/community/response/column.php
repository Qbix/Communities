<?php

/**
 * Renders a community profile
 * @param {array} $params Can be passed to the function or found in $_REQUEST
 * @param {array} $params.userId
 */
function Communities_community_response_column($params = array())
{
	$user = Users::loggedInUser();

	$request = array_merge($_REQUEST, $params);
	$uri = Q_Dispatcher::uri();
	$mainCommunity = Users::communityId();

	$communityId = Q::ifset($request, 'communityId', Q::ifset($uri, 'communityId', $mainCommunity));
	if (strtolower($communityId) == "community") {
		$communityId = $mainCommunity;
	}

	$communityUser = Users_User::fetch($communityId);
	if (!$communityUser) {
		throw new Q_Exception("Community '".$communityId."' not found");
	}

	$tabSelected = Q::ifset($request, 'tab', Q::ifset($uri, 'tab', null));

	$text = Q_Text::get('Communities/content');
	$communityName = preg_replace("/community$/i", "", $communityUser->displayName());
	$contractURI = "Users/contractMetadata/$communityId.json";
	$title = Q::text($text['community']['Title'], array($communityName));
	$url = Q::ifset(Communities::$options, 'url', "Communities/community");

	Q_Response::addScript('{{Communities}}/js/columns/community.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/community.css');
	Q_Response::setSlot('title', $title);
	Q_Response::addStylesheet('{{Communities}}/css/columns/people.css');

	// if no communities user attended, show special view
	if (!$communityId) {
		$column = Q::view("Communities/column/communityNoId.php");
		Communities::$columns['community'] = array(
			'title' => Q_Html::text($title),
			'column' => $column,
			'columnClass' => 'Communities_column_community',
			'url' => Q_Uri::url($url)
		);
		return $column;
	}

	// save selected community id to session, to use it later in diff places
	// (for example users profile to construct "Manage Roles" button)
	$_SESSION['Communities']['manage']['communityId'] = $communityId;

	// no user found with this id
	// may be it is a page?
	if (!$communityUser) {
		Communities::$columns['community'] = array(
			'notFound' => true,
			'action' => $communityId
		);

		return false;
	}

	$chatStream = null;
	$userId = Q::ifset($user, "id", null);
	// read all roles
	$allRoles = array();
	foreach (Q_Config::expect("Users", "roles") as $label => $role) {
		if (!$label) {
			continue;
		}
		$allRoles[$label] = array("title" => $role["title"], "icon" => $role['icon']);
	}

	$usersOptions = Q_Config::get('Communities', 'people', 'userIds', array());
	$usersOptions['communityId'] = $communityId;
	$userIds = Communities::userIds($usersOptions);

	$can = Users_Label::can($communityId, $userId);
	Q_Response::setScriptData('Q.plugins.Users.Label.canGrant', $can['grant']);
	Q_Response::setScriptData('Q.plugins.Users.Label.canRevoke', $can['revoke']);
	Q_Response::setScriptData('Q.plugins.Users.Label.canSee', $can['see']);
	$labelsInfo = Users_Label::getLabelsInfo($communityId);

	$tabs = Q_Config::expect("Communities", "community", "tabs");
	$results = array();
	$contentParams = @compact("user","communityId", "userIds", "labelsInfo", "can", "text");

	$isMainAdmin = false;
	$isCommunityAdmin = false;
	if ($user) {
		$isMainAdmin = Communities::isAdmin($user->id);
		$isCommunityAdmin = Communities::isAdmin($user->id, $communityId);
	}

	// collect tabs content and controls
	foreach ($tabs as $tab => $content) {
		if (!$content) {
			continue;
		}

		// permissions to edit labels
		if ($tab == "labels") {
			if (empty(Users::roles($communityId, Q_Config::get("Users", "communities", "admins", array("Users/owners"))))) {
				continue;
			}
		}

		// additional params for events
		if ($tab == "events") {
			$contentParams["eventsRelations"] = Communities::filterEvents(@compact("communityId"));
			$contentParams["newEventAuthorized"] = !empty(Communities::newEventAuthorized());
		}

		// skip importEvents if no permissions
		if($tab == "importEvents" && empty($can['manageEvents'])) {
			continue;
		}
    
        if($tab == "external") {
            if ($isMainAdmin || $isCommunityAdmin) {
               
                Q_Response::addStylesheet('{{Users}}/css/icons.css', "Communities");
                Q_Response::addStylesheet('{{Communities}}/css/columns/external.css', "Communities");
                //Q_Response::setScriptData('Q.Communities.pages.community.displayName',$communityUser->displayName());
                $contentParams["displayName"] = $communityUser->displayName();
                $contentParams["contractURI"] = $contractURI;
                
            } else {
                continue;
            }
        }
		// access to importUsers tab only for admins
		if($tab == "importUsers") {
			if ($isMainAdmin || $isCommunityAdmin) {
				Q_Response::addStylesheet('{{Communities}}/css/columns/importUsers.css', "Communities");
			} else {
				continue;
			}
		}

		// check content
		if (is_file(COMMUNITIES_PLUGIN_VIEWS_DIR.DS."Communities".DS."column".DS."community".DS.$tab.".php")) {
			$results[$tab]["content"] = Q::view("Communities/column/community/".$tab.".php", $contentParams);
		} elseif (is_file(COMMUNITIES_PLUGIN_VIEWS_DIR.DS."Communities".DS."column".DS.$tab.".php")) {
			$results[$tab]["content"] = Q::view("Communities/column/".$tab.".php", $contentParams);
		}

		// check controls
		$controlView = COMMUNITIES_PLUGIN_VIEWS_DIR.DS."Communities".DS."column".DS."community".DS.$tab."Controls.php";
		if (is_file($controlView)) {
			$results[$tab]["controls"] = Q::view("Communities/column/community/".$tab."Controls.php");
		}
	}
	// collect tabs from other sources
	Q::event("Communities/community/tabs", @compact("results", "tabs", "communityId"), "after", false, $results);

	$roles = Users::roles($communityId);
	$column = Q::view("Communities/column/community.php", @compact("communityId", "can", "labelsInfo", "results", "tabs", "isMainAdmin", "roles"));

	Q_Response::setScriptData('Q.plugins.Communities.manageCommunityId', $communityId);
	Q_Response::setScriptData('Q.plugins.Communities.allRoles', $allRoles);

	$controls = Q::view('Communities/controls/community.php', @compact("results"));
	Communities::$columns['community'] = array(
		'title' => Q_Html::text($title),
		'column' => $column,
		'controls' => $controls,
		'columnClass' => 'Communities_column_community',
		'url' => Q_Uri::url($url)
	);
	Q_Response::setScriptData('Q.plugins.Communities.people.communityId', $communityId);
	Q_Response::setScriptData('Q.plugins.Communities.people.communityName', $communityName);
	Q_Response::setScriptData('Q.plugins.Communities.people.contractURI', $contractURI);
	Q_Response::setScriptData('Q.plugins.Communities.community.tab', $tabSelected);
	Q_Response::setSlot('controls', $controls);

	// set metas
	$description = $title;
	$image = Q_Uri::interpolateUrl($communityUser->icon.'/400.png');
	$url = Q_Uri::interpolateUrl("{{baseUrl}}/community/$communityUser->id");
	Q_Response::setCommonMetas(compact(
		'title', 'description', 'keywords', 'image', 'url'
	));

	return $column;
}