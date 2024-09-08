<?php

/**
 * Renders Assets/history tool
 */
function Communities_assetshistory_response_column($params = array())
{
	$text = Q_Text::get('Communities/content');
	$title = $text['assetshistory']['Title'];

	Q_Response::addScript('{{Communities}}/js/columns/assetshistory.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/assetshistory.css');

	Q_Response::setSlot('title', $title);

	$column = Q::view("Communities/column/assetshistory.php", @compact('text'));

	Communities::$columns['assetshistory'] = array(
		'title' => Q_Html::text($title),
		'column' => $column,
		'columnClass' => 'Communities_column_assetshistory',
		'url' => 'assets/history'
	);
	return $column;
}