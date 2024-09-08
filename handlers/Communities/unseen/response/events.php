<?php
// if defined $_REQUEST[publisherId] and $_REQUEST[streamName], check if this event stream suitable for current user.
// Otherwise, return suitable streams details.
function Communities_unseen_response_events($params)
{
	$fields = array(
		'publisherId' => null,
		'streamName' => null,
		'fromTime' => 0
	);

	$r = Q::take($_REQUEST, $fields);

	// unset fields from $_REQUEST to avoid conflicts
	foreach (array_keys($fields) as $key) {
		unset($_REQUEST[$key]);
	}

	// get appropriate events
	$relations = Communities::filterEvents();

	// if defined stream details, check whether this stream suitable for current user
	if (!empty($r['publisherId']) && !empty($r['streamName'])) {
		// check whether new event exist in appropriate relations
		foreach($relations as $relation) {
			if ($relation->fromPublisherId == $r['publisherId'] && $relation->fromStreamName == $r['streamName']) {
				return true;
			}
		}

		return false;
	} else { // else return suitable streams details
		$res = array();

		foreach($relations as $relation) {
			if (strtotime($relation->insertedTime) >= strtotime($r['fromTime'])) {
				$res[] = array("publisherId" => $relation->fromPublisherId, "streamName" => $relation->fromStreamName);
			}
		}

		return $res;
	}
}