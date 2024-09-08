<?php

function Communities_relate_tool($params)
{
	$allowed = Q_Config::expect('Communities', 'occupants', 'canRelate');
	$roles = Users::roles(null, $allowed);
	$isAdmin = !!$roles;
	
	$communityId = Users::communityId();
	$publisherId = $params['publisherId'];
	$streamName = $params['streamName'];
	$rows = Streams::fetch(
		null, $communityId, new Db_Range('Places/location/', true, false, true)
	);
	$locations = array(
		'' => 'Entire community',
	);
	foreach ($rows as $k => $row) {
		$parts = explode('/', $k);
		$locationId = end($parts);
		$locations[$locationId] = $row->title;
	}
	$floors = array(
		'' => 'All floors'
	);
	$suffixes = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
	for ($f=2; $f<=23; ++$f) {
		$suffix = $suffixes[$f %10];
		$floors[$f] = $f.$suffix.' floor';
	}
	$columns = array(
		'' => 'All columns'
	);
	$AT = str_split('abcdefghjklmnoprst');
	foreach ($AT as $c) {
		$columns[$c] = "Column $c";
	}
	$result = 'Post to: '
		. Q_Html::smartTag('select', array('name' => 'location', 'class' => 'Communities_relate_location'), '', $locations)
		. Q_Html::smartTag('select', array('name' => 'floor', 'class' => 'Communities_relate_floor'), '', $floors)
		. Q_Html::smartTag('select', array('name' => 'column', 'class' => 'Communities_relate_column'), '', $columns)
		. Q_Html::tag('button', array('class' => 'Communities_relate_button Q_button'), 'Post it');
	Q_Response::setToolOptions(@compact('publisherId', 'streamName'));
	return $result;
}