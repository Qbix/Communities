<?php

function Communities_newConversation_response_content()
{
	$columns = array(
		'conversations' => Q::event('Communities/conversations/response/column'),
		'newConversation' => Q::event('Communities/newConversation/response/column')
	);
	$text = Q_Text::get('Communities/content');
	Q_Response::addScript('{{Communities}}/js/columns/conversations.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/conversations.css', "Communities");
	Q_Response::addTemplate('Communities/templates/conversation', null, 'handlebars');
	Q_Response::addTemplate('Communities/templates/conversations', null, 'handlebars');
	Q_Response::addTemplate('Communities/templates/newConversation', null, 'handlebars');

	Q_Response::setSlot('title', $text['newConversation']['Title']);
	return Q::view('Communities/content/columns.php', @compact('user', 'columns'));
}