<?php
function Communities_event_response_data($params) {
	$streamName = Q::ifset($params, 'streamName', Communities::requestedId($params, 'streamName'));
	$publisherId = Q::ifset($params, 'publisherId', Communities::requestedId($params, 'publisherId'));
	$stream = Q::ifset($params, 'stream', Streams_Stream::fetch(null, $publisherId, $streamName));
	if (!$stream) {
		return array();
	}

	$user = Users::loggedInUser();
	$userId = Q::ifset($user, "id", null);

	$columnClass = array();
	$controls = null;
	$eventMode = Q_Config::get('Communities', 'event', 'mode', Q_Request::isMobile() ? "mobile" : "desktop", null);
	$eventEnded = $stream->getAttribute('endTime') < time();
	if (!$eventEnded && ($eventMode == 'reservation' || $eventMode == 'services')) {
		$controls = Q::view('Communities/controls/eventReservation.php');
		$columnClass[] = 'Communities_event_reservation';

		if (Q_Config::get("Assets","service", "relatedParticipants", null)) {
			$columnClass[] = 'Communities_event_relatedParticipants';
		}

		if (Calendars_Event::getRsvp($stream) === 'yes') {
			$columnClass[] = 'Communities_event_reserved';
		}
	}

	$userInviteUrl = null;
	if ($userId) {
		// create invite url
		$eventId = explode('/', $streamName);
		$eventId = end($eventId);
		$url = Q_Uri::url("Communities/event publisherId=$publisherId eventId=$eventId");
		$userInviteUrl = Streams::userInviteUrl($userId, $url);

		// get info about payment
		if ($stream->getAttribute('payment')) {
			$assets_credits = Assets_Credits::checkJoinPaid($userId, $stream);
			if ($assets_credits) {
				$payment = array('amount' => $assets_credits->amount);
			} else {
				$payment = null;
			}
		} else {
			$payment = null;
		}
	}

	$payable = $stream->getAttribute('payment');

	return @compact("columnClass", "controls", "userInviteUrl", "payable", "payment");
}