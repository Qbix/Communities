<?php
function Communities_social_delete($options)
{
	$user = Users::loggedInUser(true);
	$r = Q::take($_REQUEST, array(
		'platform' => null
	));
	$required = array('platform');
	foreach ($required as $field) {
		if (!$r[$field]) {
			Q_Response::addError(new Q_Exception_RequiredField(@compact('field')));
		}
	}
	if (Q_Response::getErrors()) {
		return;
	}

	$app = Q::app();

	if ($r['platform'] == 'facebook') {
		$user->clearXid($r['platform']."\t".Q_Config::get('Users', 'apps', 'facebook', $app, 'appId', ''));
		$user->save();
	}

	Q_Response::setSlot('data', true);
}