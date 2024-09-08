<?php
	
function Communities_before_Streams_inviteDialog($params, &$result)
{
	$uri = Q_Dispatcher::uri();
	if ($uri->module === 'Communities' and $uri->action === 'onboarding') {
		// don't show onboarding dialog over onboarding page
		$result = false;
		return;
	}

	/*$user = $params['user'];
	if (empty($user)) {
		// don't show onboarding dialog for non logged user
		$result = false;
		return;
	}*/

	$steps = Q_Config::expect('Communities', 'onboarding', 'steps');
	foreach ($steps as $step) {
		if ($step === 'name' and !$params['displayName']) {
			$result = true;
		} else if ($step === 'icon' and !Users::isCustomIcon($user->icon)) {
			$result = true;
		} else if ($step === 'location') {
			$location = Streams_Stream::fetch(null, $user->id, 'Places/user/location');
			if (!$location
			or !$location->getAttribute('latitude')
			or !$location->getAttribute('longitude')
			or !$location->getAttribute('meters')
			) {
				$result = true;
			}
		} else if ($step === 'interests' and !Streams_Category::getRelatedTo(
			$user->id, 'Streams/user/interests', 'Streams/interests'
		)) {
			$result= true;
		}
	}
}