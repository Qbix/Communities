<?php

function Communities_before_Users_Contact_removeContact($params, &$result)
{
	$userId = $params['userId'];
	$label = $params['label'];
	$skipAccess = Q::ifset($params, 'skipAccess', false);

	if ($skipAccess) {
		$result = true;
		return;
	}
	
	$asUserId = Q::ifset($params, "asUserId", null) ?: Users::loggedInUser(true)->id;

	if (!Users::isCommunityId($userId)) {
		$result = true;
	}

	$contacts = Users_Contact::fetch($userId, null, array('contactUserId' => $asUserId));
	foreach ($contacts as $contact) {
		if (Users_Label::canRevokeLabel($contact->label, $label, false)) {
			$result = true;
		}
	}
}