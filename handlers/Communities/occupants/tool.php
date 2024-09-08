<?php
	
function Communities_occupants_tool($params)
{
	$allowed = Q_Config::expect('Communities', 'occupants', 'canManage');
	$roles = Users::roles(null, $allowed);
	$isAdmin = !!$roles;
	if (!$isAdmin) {
		return '';
	}
	$communityId = Users::communityId();
	$rows = Streams::fetch(
		null, $communityId, new Db_Range('Places/location/', true, false, true)
	);
	$locations = array();
	foreach ($rows as $k => $row) {
		$parts = explode('/', $k);
		$locationId = end($parts);
		$locations[$locationId] = $row->title;
	}
	$floors = array();
	$suffixes = array('th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th');
	for ($f=2; $f<=23; ++$f) {
		$suffix = $suffixes[$f %10];
		$floors[$f] = $f.$suffix.' floor';
	}
	$columns = array();
	$AT = str_split('abcdefghjklmnoprst');
	foreach ($AT as $c) {
		$columns[$c] = "Column $c";
	}
	$selects = array();
	$selects[] = Q_Html::smartTag('select', array('name' => $key, 'class' => "Communities_occupants_location"), '', $locations, array('Location'));
	$selects[] = Q_Html::smartTag('select', array('name' => $key, 'class' => "Communities_occupants_floor"), '', $floors, array('Floor'));
	$selects[] = Q_Html::smartTag('select', array('name' => $key, 'class' => "Communities_occupants_column"), '', $columns, array('Column'));
	$filter = implode("\n", $selects);
	$results = "<div class='Communities_occupants_results'></div>";
	Q_Response::setToolOptions(@compact('publisherId', 'streamName'));
	return "$filter\n$results";
}