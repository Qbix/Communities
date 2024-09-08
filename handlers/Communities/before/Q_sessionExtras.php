<?php

function Communities_before_Q_sessionExtras()
{
	$types = Users_Label::ofCommunities();
	$byRole = array();
	if ($user = Users::loggedInUser(false, false)) {
		$rowsUsersContact = Users_Contact::select()->where(array(
			'contactUserId' => $user->id,
			'label' => $types
		))->fetchAll();
		foreach ($rowsUsersContact as $row) {
			// skip non communities users
			if (!Users::isCommunityId($row['userId'])) {
				continue;
			}

			$byRole[$row['label']][] = $row['userId'];
		}
	}
	Q_Response::setScriptData('Q.plugins.Communities.byRole', $byRole);
	Q_Response::setScriptData('Q.plugins.Communities.canCreateCommunities', Communities::canCreateCommunities());
}
