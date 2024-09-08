<?php

function Communities_broadcast_response_content($params)
{
	Q_Response::addScript('{{Streams}}/js/tools/webrtc/broadcast.js?ts=' .time());
	Q_Response::addScript('{{Communities}}/js/pages/broadcast.js?ts=' .time());

	return Q::view("Communities/content/meeting.php");
}

