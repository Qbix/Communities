<?php
function Communities_NFTcollections_response_column () {
	/*$loggedInUser = Users::loggedInUser(true);
	$loggedInUserId = Q::ifset($loggedInUser, 'id', null);
	$isAdmin = (bool)Users::roles(null, 'Users/admins');
	if (!$isAdmin) {
		throw new Users_Exception_NotAuthorized();
	}*/

	Q_Response::addScript("{{Communities}}/js/columns/NFTcollections.js");
	Q_Response::addStylesheet("{{Communities}}/css/columns/NFTcollections.css");

	$communityId = Users::communityId();
	$text = Q_Text::get("Assets/content");
	$title = Q::ifset($text, "NFT", "collections", "Title", null);
	$description = Q::ifset($text, "NFT", "collections", "Description", null);
	$keywords = Q::ifset($text, "NFT", "collections", "Keywords", null);
	$url = Q_Uri::url("Communities/NFTcollections");
	$image = Q_Html::themedUrl('img/icon/400.png');
	Q_Response::setCommonMetas(compact(
		'title', 'description', 'keywords', 'image', 'url'
	));

	$column = Q::view('Communities/column/NFTcollections.php', @compact("loggedInUserId", "communityId"));
	$columnsStyle = Q_Config::get(
		'Q', 'response', 'layout', 'columns', 'style', 'classic'
	);

	$controls = null;
	/*(if ($columnsStyle == 'classic') {
		$showControls = Q_Config::get('Assets', 'NFTprofile', 'controls', true);
		$controls = $showControls ? Q::view('Assets/controls/NFTprofile.php') : null;
	}*/
	Communities::$columns['NFTcollections'] = array(
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

