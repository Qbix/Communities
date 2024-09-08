<?php
	
function Communities_importusers_response_content($params)
{
	$request = array_merge($_REQUEST, $params);
	$communityId = Q::ifset($request, "communityId", Users::currentCommunityId(true));
	$sampleFields = Q_Config::expect("Communities", "community", "importUsers", "sampleFields");

	//$allowed = Q_Config::expect('Communities', 'users', 'canImport');
	$isMainAdmin = Communities::isAdmin();
	$isCommunityAdmin = Communities::isAdmin(null, $communityId);
	if (!$isMainAdmin && !$isCommunityAdmin) {
		throw new Users_Exception_NotAuthorized();
	}

	$uri = Q_Dispatcher::uri();
	$action = Q::ifset($uri, 'value', false);

	// if download sample
	if ($action == 'sample') {
		$f = fopen('php://memory', 'w');
		fputs($f, implode(',', $sampleFields)."\n");
		fseek($f, 0);

		header('Content-Type: application/csv');
		header('Content-Disposition: attachment; filename="importUsersSample.csv";');

		fpassthru($f);
		exit;
	}

	Q_Response::addStylesheet('{{Communities}}/css/importusers.css');

	return Q::view('Communities/content/importusers.php', compact("communityId"));
}