<?php

function Q_response_dashboard()
{
	$isMobile = Q_Request::isMobile();
	$dashboardStyle = Q_Config::get(
		'Communities', 'layout', 'dashboard', Q_Request::formFactor(), 'contextual'
	);
	$dashboardMenu = Communities::dashboardMenu();
	$withTitles = Q_Config::get('Communities', 'layout', 'dashboard', 'withTitles', false);
	$attributes = array();
	if ($avatar = Streams_Avatar::fetch(null, Users::communityId())) {
		$avatar->addPreloaded();
	}
	$defaultTabName = null;
	return Q::view('Communities/dashboard.php', array_merge(@compact(
		'isMobile', 'dashboardStyle', 'withTitles', 'attributes', 'defaultTabName'
	), $dashboardMenu));
}
