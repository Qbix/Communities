<?php

function Communities_after_Calendars_event_leave($params)
{
	$user = $params['user'];
	$stream = $params['stream'];
	$payment = $stream->getAttribute('payment');

	if ($payment['type'] != 'required') {
		return;
	}

	$assets_credits = Assets_Credits::checkJoinPaid($user->id, $stream);

	if ($assets_credits) {
		Assets_Credits::transfer(null, $assets_credits->amount, Assets::LEFT_PAID_STREAM, $user->id, $stream->publisherId, array(
			"fromPublisherId" => $stream->publisherId,
			"fromStreamName" => $stream->name
		));
	}
}