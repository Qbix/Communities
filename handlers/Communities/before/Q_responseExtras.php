<?php

function Communities_before_Q_responseExtras()
{
	$personal = Q_Config::get('Communities', 'profile', 'sections', 'personal', false);
	$affiliations = Q_Config::get('Communities', 'affiliations', null);
	$eventsOrdering = Q_Config::get('Communities', 'events', 'interests', 'ordering', null);
	$servicesOrdering = Q_Config::get('Communities', 'services', 'interests', 'ordering', null);
	Q_Response::addStylesheet('{{Communities}}/css/icons.css', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/dashboard.css', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/Communities.css', "Communities");
	Q_Response::addScript('{{Communities}}/js/Communities.js', "Communities");
	$src = Q_Config::get('Communities', 'video', 'src', null);
	
	$layoutColumns = Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic');
	if ($layoutColumns === 'facebook') {
		Q_Response::addScript('{{Communities}}/js/tools/columnFBStyle.js', 'Communities');
	}

	Q_Response::addScript('{{Q}}/js/tools/lazyload.js');

	// enable handOff
	$browsertabStartup = Q_Config::get('Communities', 'browsertab', 'startup', false);
	if ($browsertabStartup) {
		$browsertabStartup = !Q::ifset($_REQUEST, 'disableAutoLogin', false);
	}

	if (method_exists('Q_Response', 'addHtmlAttribute')) {
		$position = Q_Request::isMobile()
			? Q_Config::get('Communities', 'layout', 'dashboard', 'position', 'top')
			: 'top';
		Q_Response::addHtmlAttribute('data-dashboard-position', $position);	
		Q_Response::addHtmlAttribute('data-dashboard-style', Q_Config::get('Communities', 'layout', 'dashboard', 'mobile', 'contextual'));
	}

	Q_Response::setScriptData('Q.info.scheme', Q_Config::get('Users', 'apps', Q_Request::platform(), Q::app(), "scheme", null));
	Q_Response::setScriptData('Q.plugins.Communities.browsertabs.startup', $browsertabStartup);
	Q_Response::setScriptData('Q.plugins.Communities.labelsCanPromote', Q_Config::get('Communities', 'promote', 'labels', null));
	Q_Response::setScriptData('Q.plugins.Communities.video.src', $src);
	Q_Response::setScriptData('Q.plugins.Communities.events.interests.ordering', $eventsOrdering);
	Q_Response::setScriptData('Q.plugins.Communities.events.filters.limited', Q_Config::get('Communities', 'events', 'filters', 'limited', null));
	Q_Response::setScriptData('Q.plugins.Communities.services.interests.ordering', $servicesOrdering);
	Q_Response::setScriptData('Q.plugins.Communities.services.filters.limited', Q_Config::get('Communities', 'services', 'filters', 'limited', null));
	Q_Response::setScriptData('Q.plugins.Communities.community.hideUntilParticipants', Q_Config::get('Communities', 'community', 'hideUntilParticipants', null));
	Q_Response::setScriptData('Q.plugins.Communities.affiliations', $affiliations);
	Q_Response::setScriptData('Q.plugins.Communities.profile.personal', $personal);
	Q_Response::setScriptData('Q.plugins.Communities.layout.columns.style', Q_Config::get('Communities', 'layout', 'columns', 'style', 'classic'));
	Q_Response::setScriptData('Q.plugins.Communities.onboarding.steps', Q_Config::expect('Communities', 'onboarding', 'steps'));
	Q_Response::setScriptData('Q.plugins.Communities.event.mode', Q_Config::get('Communities', 'event', 'mode', Q_Request::isMobile() ? "mobile" : "desktop", null));
	Q_Response::setScriptData('Q.plugins.Communities.conversations.relationTypes', Q_Config::expect('Communities', 'conversations', 'relationTypes'));
	Q_Response::setScriptData('Q.Audio.speak.mute', Q_Config::get('Q', 'Audio', 'speak', 'mute', null));
	Q_Response::setScriptData('Q.plugins.Communities.event.preview.textfill', Q_Config::get('Communities', 'event', 'preview', 'textfill', false));
	Q_Response::setScriptData('Q.plugins.Communities.profile.social', Q_Config::get('Communities', 'profile', 'social', null));
	Q_Response::setScriptData('Q.plugins.Communities.people', Q_Config::get('Communities', 'people', null));

	$currentCommunity = Users::currentCommunityId();
	if ($currentCommunity) {
		Q_Response::addHtmlCssClass('Community_'.$currentCommunity);
	}
	if (!Q_Config::get('Communities', 'web', 'noAnimationFX', false)) {
		Q_Response::addHtmlCssClass('Q_dialogs_animationFX Q_columns_animationFX');
	}
	if (Q_Config::get('Communities', 'web', 'dontHideUntilLoaded', false)) {
		Q_Response::addHtmlCssClass('Q_hideUntilLoaded');
	}

	Q_Response::setScriptData('Q.plugins.Communities.focusAfterActivate', Q_Config::get('Communities', 'layout', 'columns', 'focusAfterActivate', true));

	// Get current mysql server time and send to client. Need to get diff between client local time and
	// backend time to create valid date for mysql filtering. Using in some tools.
	$mysqlTime = Streams::db()->getCurrentTimestamp();
	Q_Response::setScriptData('Q.plugins.Communities.mysqlTime', $mysqlTime);
}
