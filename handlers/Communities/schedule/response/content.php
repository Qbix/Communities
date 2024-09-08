<?php
	
function Communities_schedule_response_content()
{
	$user = Users::loggedInUser();
	$fromTime = 0; // select all past events
	$untilTime = time() + Calendars_Event::defaultListingDuration();

	$travelParticipating = array();
	$calendarsParticipating = array();
	if (class_exists('Travel_Trip')) {
		$travelParticipating = Travel_Trip::participating($user->id, $fromTime, null, null, array(
			'streamsOnly' => true
		));
	}
	if (class_exists('Calendars')) {
		$calendarsParticipating = Calendars::participating($user->id, $fromTime, null, 'yes', array(
			'streamsOnly' => true
		));
	}
	$participating = array_merge($travelParticipating, $calendarsParticipating);

	$getConditionValue = function($x) {
		// default time for compare is updatedTime
		$cond = strtotime($x->fields['updatedTime']);

		// for events use startTime time to compare
		if ($x->fields["type"] == "Calendars/event") {
			$cond = (int)$x->getAttribute("startTime");
		}

		// for trips use arrive time to compare
		if ($x->type == "Travel/trip") {
			if ($x->getAttribute("type") == "Travel/to") {
				$timeToCompare = "endTime";
				$cond = (int)$x->getAttribute($timeToCompare) - 1;
			} else {
				$timeToCompare = "startTime";
				$cond = (int)$x->getAttribute($timeToCompare) + 1;
			}
		}

		return $cond;
	};

	// sort trip and event streams in ascending order
	usort($participating, function ($a, $b) use($getConditionValue)	{
		$a2 = $getConditionValue($a);
		$b2 = $getConditionValue($b);
		return ($a2 < $b2) ? -1 : ($a2 > $b2 ? 1 : 0);
	});

	$currTime = time();
	$pastEvents = array();
	$futureEvents = array();
	foreach ($participating as $p){
		if ($p->type == "Calendars/event"){
			$tool = Q::tool(array(
				"Streams/preview" => array(
					'publisherId' => $p->publisherId,
					'streamName' => $p->name,
					'closeable' => false
				),
				"Calendars/event/preview" => array()
			), array(
				'id' => Q_Utils::normalize($p->publisherId.' '.$p->name.'_Communities_schedule'),
				'lazyload' => true
			));

			if ((int)$p->getAttribute("startTime") > $currTime) {
				$futureEvents[] = $tool;
			} else {
				$pastEvents[] = $tool;
			}
		}

		if ($p->type == "Travel/trip"){
			$tool = Q::tool("Travel/trip/preview", array(
				'publisherId' => $p->publisherId,
				'streamName' => $p->name
			), array(
				'id' => Q_Utils::normalize($p->publisherId.' '.$p->name.'_Communities_schedule'),
				'lazyload' => true
			));

			if ((int)$p->getAttribute("endTime") > $currTime) {
				$futureEvents[] = $tool;
			} else {
				$pastEvents[] = $tool;
			}
		}
	}
	$pastEvents = array_reverse($pastEvents);

	$column = Q::view('Communities/column/schedule.php', @compact(
		'user', 'participating', 'pastEvents', 'futureEvents'
	));
	$text = Q_Text::get('Communities/content');
	$title = Q::ifset(Communities::$options, 'title', $text['schedule']['Title']);
	$url = Q::ifset(Communities::$options, 'url', "Communities/schedule");
	Communities::$columns['schedule'] = array(
		'title' => $title,
		'column' => $column,
		'close' => false,
		'url' => Q_Uri::url($url)
	);

	Q_Response::setScriptData("Q.plugins.Calendars.capability", Calendars::capability($user->id)->exportArray());
	Q_Response::addScript('{{Communities}}/js/pages/schedule.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/schedule.css', 'Communities');

	return Q::view('Communities/content/columns.php');
}