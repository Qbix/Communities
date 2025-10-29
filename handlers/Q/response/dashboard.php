<?php

function Q_response_dashboard()
{
	$isMobile = Q_Request::isMobile();
	$dashboardStyle = Q_Config::get(
		'Communities', 'layout', 'dashboard', Q_Request::formFactor(), 'contextual'
	);
	$withTitles = Q_Config::get('Communities', 'layout', 'dashboard', 'withTitles', false);
	$attributes = array();
	if ($avatar = Streams_Avatar::fetch(null, Users::communityId())) {
		$avatar->addPreloaded();
	}echo 'a'; exit;
	return Q::view('Communities/dashboard.php', @compact(
		'isMobile', 'dashboardStyle', 'withTitles', 'attributes'
	));
}
