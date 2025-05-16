<?php
	
function Communities_after_Users_logout()
{
	Q_Response::clearCookie('Q_Users_communityId');

	$secret = Q_Config::get("Communities", "Discourse", "SSO", "secret", null);
	if (!$secret) {
		return;
	}
	$returnUrl = Q_Request::baseUrl('/Q/blank');
	$params = http_build_query(array(
		'return_url' => $returnUrl
	));
	$payload = base64_encode($params);
	$sig = hash_hmac('sha256', $payload, $secret);
	Users::$logoutFetch['Communities'] = array(
		'method' => 'GET',
		'url' => "https://forum.miracles.community/session/sso_logout",
		'fields' => array(
			'sso' => $payload,
			'sig' => $sig
		)
	);
}