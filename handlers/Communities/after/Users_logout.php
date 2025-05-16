<?php
	
function Communities_after_Users_logout()
{
	Q_Response::clearCookie('Q_Users_communityId');

	$app = Q::app();
	list($id, $appInfo) = Users::appInfo('discourse', $app);
	$secret = Q::ifset($appInfo, 'secret', Q_Config::get("Communities", "Discourse", "SSO", "secret", null));
	if (!$secret) {
		return;
	}
	$returnUrl = Q_Request::baseUrl('/Q/blank');
	$params = http_build_query(array(
		'return_url' => $returnUrl
	));
	$payload = base64_encode($params);
	$sig = hash_hmac('sha256', $payload, $secret);
	$discourseUrl = $appInfo['url'];
	Users::$logoutFetch['Communities'] = array(
		'method' => 'GET',
		'url' => $discourseUrl . "/session/sso_logout",
		'fields' => array(
			'sso' => $payload,
			'sig' => $sig
		)
	);
}