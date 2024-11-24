<?php

function Communities_after_Users_register($params)
{
	// if this the first time the user has ever logged in...
	$user = $params['user'];
	$useRandomIcon = Q_Config::get('Users', 'register', 'icon', 'useRandom', false);

	// skip communities and not new users
	if (Users::isCommunityId($user->id)) {
		return;
	}

	// join user to category Streams/chats/main of current community
	$currentCommunityId = Users::currentCommunityId(true);
	$chatsMainCategory = Communities::chatsMainCategory($currentCommunityId);
	if (!$chatsMainCategory->participant($user->id)) {
		$chatsMainCategory->join(array('userId' => $user->id, 'skipAccess' => true));
	}

	// set icon to random image from COMMUNITIES_PLUGIN_FILES_DIR/Communities/faces
	if ($useRandomIcon && !Users::isCustomIcon($user->icon)) {
		// get random file
		$facesDir = implode(DS, array(COMMUNITIES_PLUGIN_FILES_DIR, 'Communities', 'faces'));
		$files = glob($facesDir . DS . '*.*');
		$file = array_rand($files);
		$randomFile = $files[$file];

		$icon = Q_Image::iconArrayWithUrl($randomFile, 'Users/icon');
		Users::importIcon($user, $icon);
		$user->save();
	}
}