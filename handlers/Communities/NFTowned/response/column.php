<?php
function Communities_NFTowned_response_column () {
	$userId = Users::loggedInUser(true)->id;

	Q_Response::addScript("{{Communities}}/js/columns/NFTowned.js");
	Q_Response::addStylesheet("{{Communities}}/css/columns/NFTowned.css");

	$column = Q::view('Communities/column/NFTowned.php', compact("userId"));

	$title = "NFT owned";

	$url = Q_Uri::url("Communities/NFTowned");
	$columnsStyle = Q_Config::get(
		'Q', 'response', 'layout', 'columns', 'style', 'classic'
	);

	$controls = null;
	/*(if ($columnsStyle == 'classic') {
		$showControls = Q_Config::get('Assets', 'NFTprofile', 'controls', true);
		$controls = $showControls ? Q::view('Assets/controls/NFTprofile.php') : null;
	}*/
	Communities::$columns['NFTowned'] = array(
		'title' => $title,
		'column' => $column,
		'columnClass' => 'Communities_column_'.$columnsStyle,
		'controls' => $controls,
		'close' => false,
		'url' => $url
	);
	Q_Response::setSlot('controls', $controls);

	return $column;
}

