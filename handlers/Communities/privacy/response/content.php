<?php
	
function Communities_privacy_response_content()
{
	$communityName = Users::communityName();
	$organizationName = Users::communityName(true);
	$infoEmail = Q_Config::get('Communities', 'emails', 'info', null);
	$dmcaEmail = Q_Config::get('Communities', 'emails', 'dmca', null);
	$privacyEmail = Q_Config::get('Communities', 'emails', 'privacy', null);
	$appRootUrl = Q_Config::expect('Q', 'web', 'appRootUrl');
	$host = parse_url($appRootUrl, PHP_URL_HOST);
	if (!$dmcaEmail) {
		$dmcaEmail = "dmca@$host";
	}
	if (!$infoEmail) {
		$infoEmail = "info@$host";
	}
	if (!$privacyEmail) {
		$privacyEmail = "privacy@$host";
	}
	return Q::view('Communities/content/privacy.php', @compact(
		'communityName', 'appRootUrl', 'host', 'organizationName',
		'dmcaEmail', 'infoEmail', 'privacyEmail'
	));
}