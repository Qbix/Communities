<?php

function Communities_after_Calendars_event_leave($params)
{
	$user = $params['user'];
	$stream = $params['stream'];
	$payment = $stream->getAttribute('payment');

	if ($payment['type'] != 'required') {
		return;
	}

	$payments = Assets_Credits::getPaymentsInfo($user->id, $stream);
    $amount = Q::ifset($payments, 'conclusion', 'amount', 0);

	if ($amount) {
		Assets_Credits::transfer(null, $amount, Assets::LEFT_PAID_STREAM, $user->id, $stream->publisherId, array(
			"fromPublisherId" => $stream->publisherId,
			"fromStreamName" => $stream->name
		));
	}
}