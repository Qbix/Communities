<?php

function Communities_services_response_column(&$params, &$result)
{
	$app = Q::app();
	$user = Users::loggedInUser();
	$experienceId = Q::ifset($_REQUEST, 'experienceId', 'main');
	$limit = Q::ifset($_REQUEST, 'limit', Q_Config::get('Communities', 'pageSizes', 'services', 10));
	$offset = Q::ifset($_REQUEST, 'offset', 0);
	$communityId = Users::currentCommunityId();
	$columnsStyle = Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic');

	$relations = Communities::filterServices(@compact("experienceId", "communityId", "limit", "offset"));

	$dates = Streams::experience($experienceId)->getAttribute('dates');
	Q_Response::setScriptData("Q.plugins.Communities.services.experienceId", $experienceId, '');
	Q_Response::setScriptData("Q.plugins.Communities.dates.$experienceId", $dates, '');

	Q_Response::addScript('{{Communities}}/js/columns/availabilities.js');
	Q_Response::addStylesheet('{{Communities}}/css/columns/availabilities.css');
	Q_Response::addStylesheet('{{Calendars}}/css/serviceBrowser.css', "Calendars");
	Q_Response::addStylesheet('{{Calendars}}/css/availabilityPreview.css', "Calendars");
	Q_Response::addStylesheet('{{Places}}/css/PlacesLocationPreview.css', "Places");

	$filterDates = Q::ifset($_REQUEST, 'filterDates', null);
	$communityIcon = Q_Html::img('{{Communities}}/img/colorful/community.png');
	$src = Q_Config::get('Communities', 'video', 'src', null);
	$text = Q_Text::get('Communities/content');
	$textfill = Q_Config::get('Communities', 'availability', 'preview', 'textfill', false);
	$column = Q::view('Communities/column/services.php', @compact(
		'user', 'relations', 'src', 'text', 'columnsStyle', 'textfill'
	));

	Q_Response::setScriptData('Q.plugins.Communities.newEventAuthorized', !empty(Communities::newEventAuthorized()));

	$title = Q::ifset(Communities::$options, 'services', 'title', $text['services']['Title']);
	$url = Q_Uri::url(Q::ifset(Communities::$options, 'services', 'url', "Communities/services"));

	// set readLevel=40 of main community Places/user/locations stream
	// so users can read related locations to filter services by location
	$locationCategory = new Streams_Stream();
	$locationCategory->publisherId = $app;
	$locationCategory->name = "Places/user/locations";
	if ($locationCategory->retrieve()) {
		$locationCategory->readLevel = 40;
		$locationCategory->save();
	}

	$controls = null;
	if ($columnsStyle == 'classic') {
		$showControls = Q_Config::get('Communities', 'services', 'controls', true);
		$controls = $showControls ? Q::view('Communities/controls/services.php', @compact('communityIcon', 'filterDates')) : null;
	}
	Communities::$columns['availabilities'] = array(
		'title' => $title,
		'column' => $column,
		'columnClass' => 'Communities_column_'.$columnsStyle,
		'controls' => $controls,
		'close' => false,
		'url' => $url
	);
	Q_Response::setSlot('controls', $controls);

	$communityName = Users::communityName();
	$description = Q::text($text['services']['Description'], array($communityName));
	$keywords = Q::text($text['services']['Keywords'], array($communityName));
	$image = Q_Html::themedUrl('img/icon/400.png');
	Q_Response::setCommonMetas(compact(
		'title', 'description', 'keywords', 'image', 'url'
	));

	return $column;
}

