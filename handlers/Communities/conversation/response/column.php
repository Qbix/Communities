<?php

function Communities_conversation_response_column($params)
{
	$conversation = $params['stream'];
	$publisherId = $conversation->publisherId;
	$streamName = $conversation->name;

	Streams_Invite::possibleNotice($conversation);
	
	if (!$conversation->inviteIsAllowed()) {
		$invite = false;
	}
	if (!$conversation->testReadLevel('see')) {
		throw new Users_Exception_NotAuthorized();
	}
	Communities::$columns['conversation'] = array(
		'title' => $conversation->title,
		'column' => Q::view('Communities/templates/conversation.handlebars', array(
			'Streams/chat' => @compact('publisherId', 'streamName'),
			'Users/avatar' => array(
				'userId' => $conversation->publisherId,
				'icon' => true
			),
			'stream' => $conversation->fields,
			'content' => $conversation->content
		)),
		'columnClass' => 'Q_column_' . Q_Utils::normalize($conversation->type),
		'controls' => Q::tool('Streams/participants', @compact(
			'publisherId', 'streamName', 'invite'
		))
	);

	Q_Response::addScript('{{Communities}}/js/columns/conversation.js', "Communities");
	Q_Response::addStylesheet('{{Communities}}/css/columns/conversation.css', "Communities");
	Q_Response::addTemplate('Communities/templates/conversation', null, 'handlebars');
	Q_Response::setMeta($conversation->metas());
}

