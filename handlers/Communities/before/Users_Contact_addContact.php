<?php

function Communities_before_Users_Contact_addContact($params, &$result)
{
	$userId = $params['userId'];
	$asUserId = $params['asUserId'];
	$label = $params['label'];
	$skipAccess = Q::ifset($params, 'skipAccess', false);

	if ($skipAccess) {
		$result = true;
		return;
	}

	$result = false;
	$asUserId = $asUserId ?: Users::loggedInUser(true)->id;

	if (!Users::isCommunityId($userId) || $asUserId === false) {
		$result = true;
		return;
	}

	$contacts = Users_Contact::fetch($userId, null, array(
		'contactUserId' => $asUserId,
		'skipAccess' => true
	));
	foreach ($contacts as $contact) {
		if (Users_Label::canGrantLabel($contact->label, $label, false)) {
			$result = true;
			return;
		}
	}
}