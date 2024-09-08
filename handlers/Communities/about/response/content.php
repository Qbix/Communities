<?php
	
function Communities_about_response_content()
{
	$publisherId = Users::communityId();
	$streamName = 'Communities/about';
	Q_Response::addStylesheet('{{Websites}}/css/Websites.css', "Websites");
	Q_Response::addScript("{{Websites}}/js/Websites.js", "Websites");
	$users_count = Users_User::select('COUNT(1)')->execute()->fetchColumn(0);
	$events_count = Streams_Stream::select('COUNT(1)')->where(array(
		'type' => 'Calendars/event'
	))->execute()->fetchColumn(0);
	$rsvps_count = Streams_Participant::select('COUNT(1)')->where(array(
		'streamType' => 'Calendars/event'
	))->execute()->fetchColumn(0);
	$header = <<<EOT
		<div class="Communities_counts">
			<span>$users_count</span> users,
			<span>$events_count</span> events,
			<span>$rsvps_count</span> RSVPs
		</div>	
EOT;
	return Q::view("Websites/content/article.php", @compact(
		'publisherId', 'streamName', 'header'
	));
}