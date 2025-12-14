<?php

/**
 * Shows the interface for an event
 *
 * @param {array} $_REQUEST 
 * @param {string} [$_REQUEST.publisherId] Required. The user id of the publisher of the event.
 * @param {string} [$_REQUEST.eventId] Required. The part of the streamName that follows "Calendars/event/"
 * @optional
 * @return {string}
 */
function Communities_event_response_column($params)
{
	$user = Users::loggedInUser();
	$eventId = Q::ifset($params, 'eventId', Communities::requestedId($params, 'eventId'));
	$publisherId = Q::ifset($params, 'eventId', Communities::requestedId($params, 'publisherId'));
	$stream = $params['stream'] ?: Streams_Stream::fetch(null, $publisherId, 'Calendars/event/'.$eventId);
	if (!$stream) {
		return;
	}
	$publisherId = $stream->publisherId;
	Streams_Invite::possibleNotice($stream);

	Q_Response::setSlot('title', Q_Html::text($stream->title));
	
	$params = array_merge($params, array(
		'publisherId' => $stream->publisherId,
		'streamName' => $stream->name
	));

	$siteAdminRoles = array('Users/admins', 'Users/owners', 'Websites/admins');
	$communityId = Users::communityId();

	$column = Q::tool('Calendars/event', $params);
	if (Users::roles($communityId, $siteAdminRoles)) {
		$column .= Q::tool('Websites/metadata');
	}

	Q_Response::addScript('{{Communities}}/js/columns/events.js', "Communities");
	Q_Response::addScript('{{Communities}}/js/columns/event.js', "Communities");
	Q_Response::addScript('{{Streams}}/js/tools/preview.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/events.css');
	Q_Response::addStylesheet('{{Communities}}/css/columns/event.css');

	$url = Q_Uri::url("Communities/event publisherId=$publisherId eventId=$eventId");

	Communities::$columns['event'] = array(
		'name' => 'event',
		'title' => $stream->title,
		'column' => $column,
		'close' => false,
		'columnClass' => 'Communities_column_event',
		'url' => $url
	);

	$stream->addPreloaded();

	// set meta data
	Q_Response::setMeta($stream->metas());

	return $column;
}