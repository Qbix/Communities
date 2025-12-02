<?php

function Communities_events_response_column(&$params, &$result)
{
	$app = Q::app();
	$user = Users::loggedInUser();
	$experienceId = Q::ifset($_REQUEST, 'experienceId', 'main');
	$limit = Q::ifset($_REQUEST, 'limit', Q_Config::get('Communities', 'pageSizes', 'events', 100));
	$offset = Q::ifset($_REQUEST, 'offset', 0);
	$communityId = Users::currentCommunityId(true);
	$columnsStyle = Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic');
	list($fromTime, $toTime) = Communities::defaultEventTimes();

	$allRelations = array();
	$cids = Q_Config::get('Communities', 'events', 'featured', 'publisherIds', array());
	array_unshift($cids, $communityId);
	$cids = array_unique($cids);
	foreach ($cids as $cid) {
		$allRelations = array_merge($allRelations, Communities::filterEvents(@compact("experienceId", "fromTime", "toTime", "communityId", "limit", "offset")));
	}
	$relations = Streams_RelatedTo::filter($allRelations, array('readLevel' => 'content'));

	$dates = Streams::experience($experienceId)->getAttribute('dates');
	Q_Response::setScriptData("Q.plugins.Communities.events.experienceId", $experienceId, '');
	Q_Response::setScriptData("Q.plugins.Communities.dates.$experienceId", $dates, '');
	Q_Response::setScriptData("Q.plugins.Communities.events.fromTime", $fromTime, '');
	Q_Response::setScriptData("Q.plugins.Communities.events.toTime", $toTime, '');

	Q_Response::addScript('{{Communities}}/js/columns/events.js');
	Q_Response::addStylesheet('{{Communities}}/css/columns/events.css');
	Q_Response::addStylesheet('{{Calendars}}/css/composer.css', "Calendars");
	Q_Response::addStylesheet('{{Places}}/css/PlacesLocationPreview.css', "Places");

	$filterDates = Q::ifset($_REQUEST, 'filterDates', null);
	$communityIcon = Q_Html::img('{{Communities}}/img/colorful/community.png');
	$src = Q_Config::get('Communities', 'video', 'src', null);
	$newEventAuthorized = !empty(Communities::newEventAuthorized());
	Q_Response::setScriptData('Q.plugins.Communities.newEventAuthorized', $newEventAuthorized);
	$text = Q_Text::get('Communities/content');
	$hideIfNoParticipants = Q_Config::get('Calendars', 'event', 'hideIfNoParticipants', null);
	$eventMode = Q_Config::get('Communities', 'event', 'mode', Q_Request::isMobile() ? "mobile" : "desktop", null);
	$textfill = Q_Config::get('Communities', 'event', 'preview', 'textfill', false);
	$column = Q::view('Communities/column/events.php', @compact(
		'user', 'relations', 'src', 'newEventAuthorized', 'text',
		'columnsStyle', 'hideIfNoParticipants', 'eventMode', 'textfill'
	));

	$title = Q::ifset(Communities::$options, 'events', 'title', $text['events']['Title']);
	$url = Q_Uri::url(Q::ifset(Communities::$options, 'events', 'url', "Communities/events"));

	// set readLevel=40 of main community Places/user/locations stream
	// so users can read related locations to filter events by location
	$locationCategory = new Streams_Stream();
	$locationCategory->publisherId = $app;
	$locationCategory->name = "Places/user/locations";
	if ($locationCategory->retrieve()) {
		if ($locationCategory->readLevel != 40) {
			$locationCategory->readLevel = 40;
			$locationCategory->save();
		}
	}

	$controls = null;
	if ($columnsStyle == 'classic') {
		$showControls = Q_Config::get('Communities', 'events', 'controls', true);
		$controls = $showControls ? Q::view('Communities/controls/events.php', @compact('communityIcon', 'filterDates')) : null;
	}
	Communities::$columns['events'] = array(
		'title' => $title,
		'column' => $column,
		'columnClass' => 'Communities_column_'.$columnsStyle,
		'controls' => $controls,
		'close' => false,
		'url' => $url
	);
	Q_Response::setSlot('controls', $controls);

	if (empty($params['skipMetas'])) {
		$communityName = Users::communityName();
		$image = Q_Uri::interpolateUrl(Users_User::fetch(Users::communityId())->iconUrl(400));
		$description = Q::text($text['events']['Description'], array($communityName));
		$keywords = Q::text($text['events']['Keywords'], array($communityName));
		$image = Q_Html::themedUrl('img/icon/400.png');
		Q_Response::setCommonMetas(compact(
			'title', 'description', 'keywords', 'image', 'url'
		));
	}
	Q_Response::setScriptData('Q.Communities.events.loadedCount', $offset + $limit);
	return $column;
}

