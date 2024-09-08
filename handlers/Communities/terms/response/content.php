<?php
	
function Communities_terms_response_content()
{
	$communityName = Users::communityName();
	$organizationName = Users::communityName() . ' ' . Users::communitySuffix();
	$dmcaEmail = Q_Config::get('Communities', 'emails', 'dmca', null);
	if (!$dmcaEmail) {
		$appRootUrl = Q_Config::expect('Q', 'web', 'appRootUrl');
		$host = parse_url($appRootUrl, PHP_URL_HOST);
		$dmcaEmail = "dmca@$host";
	}
	$jurisdiction = Q_Config::expect('Communities', 'terms', 'jurisdiction');
	return Q::view('Communities/content/terms.php', @compact(
		'communityName', 'organizationName', 'dmcaEmail', 'jurisdiction'
	));
}