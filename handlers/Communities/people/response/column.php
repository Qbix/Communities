<?php
	
function Communities_people_response_column()
{
	$user = Users::loggedInUser();
	$communityId = Users::communityId();
	$columnsStyle = Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic');

	if ($user) {
		$labels = Communities::labelsOptions(true);
	}
	$interests = array('*' => 'All Interests');
	foreach (Streams::interests($communityId) as $category => $list) {
		foreach ($list as $subcategory => $list2) {
			foreach ($list2 as $title => $info) {
				$interests["$category"]["$category: $title"] = $subcategory
					? "&nbsp;&nbsp;$subcategory: $title"
					: "&nbsp;&nbsp;$title";
			}
		}
	}
	$options = Q_Config::get('Communities', 'people', 'userIds', array());
	$options['limit'] = Q::ifset($_REQUEST, 'limit', Q_Config::get('Communities', 'pageSizes', 'people', 100));
	$options['communityId'] = Users::currentCommunityId(true);
	$userIds = Communities::userIds($options);
	// filter users if Users/filter config defined
	if ($usersFilter = Q_Config::get("Communities", "people", "filter", null)) {
		$userIds = $usersFilter;
	}

	$devices = Users_Device::byApp();
	$isCordova = Q_Request::isCordova();
	$showImport = Q_Config::get('Communities', 'people', 'import', false);
	$params = @compact(
		'communityId', // 'myInterests', 'interestsInitialIndex',
		'labels', 'categories', 'interests', 'userIds', 'devices',
		'showImport', 'isCordova', 'columnsStyle'
	);

	$text = Q_Text::get('Communities/content');
	$title = Q::ifset(Communities::$options, 'title', $text['people']['Title']);
	$url = Q_Uri::url(Q::ifset(Communities::$options, 'url', "Communities/people"));

	$column = Q::view("Communities/column/people.php", $params);

	Q_Response::addScript('{{Communities}}/js/columns/people.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/people.css');

	$controls = null;
	if ($columnsStyle == 'classic') {
		$controls = Q::view('Communities/controls/people.php', $params);
	}
	Communities::$columns['people'] = array(
		'title' => $title,
		'column' => $column,
		'columnClass' => 'Communities_column_'.$columnsStyle,
		'controls' => $controls,
		'close' => false,
		'url' => $url
	);
	Q_Response::setSlot('controls', $controls);

	$people = Q_Config::get('Communities', 'people', array('a' => 'b'));
	Q_Response::setScriptData('Q.plugins.Communities.people', $people);
	Q_Response::setScriptData('Q.plugins.Communities.people.communityId', null);

	$communityName = Users::communityName();
	$communityUser = Users_User::fetch($communityId);
	$description = Q::text($text['people']['Description'], array($communityName));
	$keywords = Q::text($text['people']['Keywords'], array($communityName));
	$communityIcon = Q_Uri::interpolateUrl($communityUser->icon.'/400.png');
	Q_Response::setMeta(array(
		array('name' => 'name', 'value' => 'title', 'content' => $title),
		array('name' => 'property', 'value' => 'og:title', 'content' => $title),
		array('name' => 'property', 'value' => 'twitter:title', 'content' => $title),
		array('name' => 'name', 'value' => 'description', 'content' => $description),
		array('name' => 'property', 'value' => 'og:description', 'content' => $description),
		array('name' => 'property', 'value' => 'twitter:description', 'content' => $description),
		array('name' => 'name', 'value' => 'keywords', 'content' => $keywords),
		array('name' => 'property', 'value' => 'og:keywords', 'content' => $keywords),
		array('name' => 'property', 'value' => 'twitter:keywords', 'content' => $keywords),
		array('name' => 'name', 'value' => 'image', 'content' => $communityIcon),
		array('name' => 'property', 'value' => 'og:image', 'content' => $communityIcon),
		array('name' => 'property', 'value' => 'twitter:image', 'content' => $communityIcon),
		array('name' => 'property', 'value' => 'og:url', 'content' => $url),
		array('name' => 'property', 'value' => 'twitter:url', 'content' => $url),
		array('name' => 'property', 'value' => 'twitter:card', 'content' => 'summary')
	));

	return $column;
}