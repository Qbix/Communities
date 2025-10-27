<?php
	
function Communities_after_Places_location_changed($params)
{
	$stream = $params['stream'];
	$created = $params['created'];

	// if location just created, join user to nearest community
	if ($created) {
		$q = Places_Location::select()->where(array(
			'publisherId' => new Db_Range('A', true, false, chr(ord('Z')+1)),
			'streamName like ' => 'Places/location/%'
		));
		$res = Places_Geohash::fetchByDistance($q, 'geohash', Places_Geohash::encode(
			$stream->getAttribute('latitude'), 
			$stream->getAttribute('longitude')
		), 1);

		if (!empty($res)) {
			$row = reset($res);
			Streams::join($stream->publisherId, $row->publisherId, array('Streams/experience/main'), array('skipAccess' => true));
		}
	}
}