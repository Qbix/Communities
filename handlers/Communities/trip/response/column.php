<?php

/**
 * Shows the interface for an trip
 *
 * @param {array} $_REQUEST 
 * @param {string} [$_REQUEST.publisherId] Required. The user id of the publisher of the trip.
 * @param {string} [$_REQUEST.tripId] Required. The part of the streamName that follows "Travel/trip/"
 * @optional
 * @return {string}
 */
function Communities_trip_response_column($options = array()) {
	$user = Users::loggedInUser();
	$publisherId = Streams::requestedPublisherId();
	$tripId = Q::ifset($options, 'tripId', Communities::requestedId($options, 'tripId'));
	$streamName = "Travel/trip/$tripId";
	$stream = Streams_Stream::fetch(null, $publisherId, $streamName, true, array(
		// 'withMessageTotals' => array(
		// 	$streamName => 'Calendars/going'
		// )
	));

	Streams_Invite::possibleNotice($stream);

	if ($user) {
		// if user participated, show ME/scheduled column under trip column
		$participant = new Streams_Participant();
		$participant->publisherId = $stream->publisherId;
		$participant->streamName = $stream->name;
		$participant->userId = $user->id;

		if ($participant->retrieve()) {
			Q::event('Communities/me/response/column', array(
				'tab' => 'schedule',
				'scheduleSubTab' => (int)$stream->getAttribute('endTime') > time() ? 'future' : 'past'
			));
		} elseif (class_exists('Travel_Trip')) {
			// if this trip related to some event, then show this event column under trip column on client
			$event = Travel_Trip::getEventByTrip($stream);
			if ($event) {
				Q::event('Communities/events/response/column');

				$shortName = explode('/', $event->name);
				$shortName = end($shortName);

				Q::event('Communities/event/response/column', array(
					'publisherId' => $event->publisherId,
					'eventId' => $shortName
				));
			}
		}
	}

	Q_Response::addStylesheet('{{Communities}}/css/columns/trip.css');

	$options = array_merge($options, @compact("publisherId", "streamName"));
	$column = Q::view('Communities/templates/trip.handlebars', $options);
	Communities::$columns['trip'] = array(
		'name' => 'trip',
		'title' => $stream->title,
		'column' => $column,
		'close' => false,
		'url' => Q_Uri::url("Communities/trip")
	);

	Q_Response::setSlot('title', Q_Html::text($stream->title));

	Q_Response::setMeta('description', preg_replace("/(\r?\n){2,}/", " ", $stream->content));
	Q_Response::setMeta('image', $stream->iconUrl('200.png'));

	return $column;
}