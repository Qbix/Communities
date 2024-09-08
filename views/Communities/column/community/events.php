<div class="Communities_profile_section Communities_events" id="Communities_profile_events">
<?php
	Q_Response::addScript('{{Communities}}/js/columns/events.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/events.css', "Communities");
    Q_Response::addStylesheet('{{Places}}/css/PlacesLocationPreview.css', "Communities");
	$textfill = Q_Config::get('Communities', 'event', 'preview', 'textfill', false);
    echo Q::view("Communities/column/events.php", array(
		"user" => $user,
		"relations" => $eventsRelations,
		"newEventAuthorized" => $newEventAuthorized,
		"text" => $text,
		"textfill" => $textfill
	));
?>
</div>