<?php

/**
 * Communities used to answer this event with "does this user still need
 * onboarding?", which is a different question from "should we show the
 * accept/decline dialog?". It also only ever set $result = true, so it could
 * never suppress the dialog for a complete user, and it paired with a
 * templateName override that replaced the accept form with the onboarding
 * tool -- leaving logged-in users with a dialog that had no way to accept.
 *
 * Onboarding is now decided separately, after the invite is resolved, by
 * Communities_Onboarding::needed(). All that legitimately belongs here is
 * not stacking a dialog on top of the onboarding page itself.
 */
function Communities_before_Streams_inviteDialog($params, &$result)
{
	$uri = Q_Dispatcher::uri();
	if ($uri->module === 'Communities' and $uri->action === 'onboarding') {
		// don't show onboarding dialog over onboarding page
		$result = false;
	}
}
