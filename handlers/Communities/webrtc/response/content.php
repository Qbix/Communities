<?php

function Communities_webrtc_response_content($params)
{
	$user = Users::loggedInUser(true);
	$publisherId = Q::ifset($params, 'publisherId', Streams::requestedPublisherId());
	$uri = Q_Dispatcher::uri();
	$webrtcId = Q::ifset($params, 'webrtcId', Q::ifset($_REQUEST, 'webrtcId', Q::ifset($uri, 'webrtcId', null)));
	$webrtcId = preg_replace("/\W.*/", '', $webrtcId);
	$streamName = "Media/webrtc/$webrtcId";

	// if stream related to some conversation
	$related = Streams::related($publisherId, $publisherId, $streamName, false, array(
		'type' => 'Media/webrtc'
	));

	$relations = Q::ifset($related, 0, null);
	if (is_array($relations)) {
		foreach ($relations as $item) {
			if (preg_match("#Websites/webpage|Streams/chat#", $item->toStreamName)) {
				$part = explode('/', $item->toStreamName);
				$part = end($part);
				Q_Response::redirect(Q_Uri::url('Communities/conversation publisherId=' . $item->toPublisherId . ' conversationId=' . $part) . '?startWebRTC');
				return false;
			} elseif (preg_match("#Calendars/event#", $item->toStreamName)) {
					$part = explode('/', $item->toStreamName);
					$part = end($part);
					Q_Response::redirect(Q_Uri::url('Communities/event publisherId='.$item->toPublisherId.' eventId='.$part).'?startWebRTC');
					return false;
			} elseif (preg_match("#Streams/live#", $item->toStreamName)) {
				$stream = Streams_Stream::fetch(null, $item->toPublisherId, $item->toStreamName);
				Q_Response::redirect($stream->url("startWebRTC"));
				return false;
			}
		}
	}
	//Q::event('Communities/webrtc/response/column', $params);
	//return Q::view('Communities/content/columns.php');
}