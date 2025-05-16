<?php

function Communities_discourseSSO_response($params)
{
    $params = array_merge($_REQUEST, $params);
    $sso = Q::ifset($params, 'sso', null);
    $sig = Q::ifset($params, 'sig', null);
	$app = Q::app();
	list($id, $appInfo) = Users::appInfo('discourse', $app);
	$secret = Q::ifset($appInfo, 'secret', Q_Config::get("Communities", "Discourse", "SSO", "secret", null));
    $step = Q::ifset($params, 'step', null);

    if(is_null($sso)) {
        throw new Q_Exception("sso is required");
    }
    if(is_null($sig)) {
        throw new Q_Exception("sig is required");
    }
    if(is_null($secret)) {
        throw new Q_Exception("Communities.Discourse.SSO.secret config is required");
    }

    $hash = hash_hmac('sha256', $sso, $secret);

    if($hash != $sig) {
        throw new Q_Exception("Wrong signature");
    }

    $decodedSSO = urldecode(base64_decode($sso));
    parse_str($decodedSSO, $ssoVars);

    $nonce = $ssoVars['nonce'];
    $returnSsoUrl = $ssoVars['return_sso_url'];

    $user = Users::loggedInUser();
    if($user && $step != 'onboarding') {
        $emailAddress = !empty($user->emailAddress) ? $user->emailAddress : $user->emailAddressPending;
        $name = $user->displayName();
        $externalId = $user->id;

        $usersAvatar = new Streams_Avatar();
        $usersAvatar->toUserId = '';
        $usersAvatar->publisherId = $user->id;
        $usersAvatar->retrieve();
        $avatarUrl = Q_Uri::interpolateUrl($usersAvatar->fields['icon']) . '/400.png';

        $urlParams = [
            'nonce' => $nonce,
            'email' => $emailAddress,
            'external_id' => $externalId,
            'name' => $name,
            'username' => $name,
            'avatar_url' => $avatarUrl
        ];
        $urlParams = http_build_query($urlParams);

        $encodedUrlParams = base64_encode($urlParams);
        $payloadHash = hash_hmac('sha256', $encodedUrlParams, $secret);
        $returnParams = http_build_query([
            'sso' => $encodedUrlParams,
            'sig' => $payloadHash
        ]);
        $returnFullUrl = $returnSsoUrl . '?' . $returnParams;
        header("Location: " . $returnFullUrl);
        return false;
    }

    $content = Q_Response::layoutView('Communities/content/discourseSSO.php');
    Q_Response::setSlot('content', $content);
    Q_Response::addScript('{{Communities}}/js/pages/discourseSSO.js');
}

