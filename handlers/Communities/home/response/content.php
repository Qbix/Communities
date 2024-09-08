<?php

function Communities_home_response_content($params)
{
	// Implement a home page for the user
	Q_Response::redirect('Communities/me tab=schedule');
	return true;
	$user = Users::loggedInUser(true);
	
	$types = array(
		'mobile' => 'mobileNumber', 
		'email' => 'emailAddress'
	);
	$prompts = array(
		'mobile' => 'Add a mobile number',
		'email' => 'Add an email address'
	);
	$identifiers = array();
	foreach ($types as $type => $field) {
		$prompt = $prompts[$type];
		$fieldPending = $field.'Pending';
		$identifiers[$type] = $user->$field
			? "<div class='Communities_identifier'>{$user->$field}</div>"
			: ($user->$fieldPending 
				? "<div class='Communities_moreinfo'><span>(pending)</span> {$user->$fieldPending}</div>"
				: "<div class='Communities_moreinfo'>$prompt</div>");	
	}
	
	$allowed = Q_Config::expect('Communities', 'articles', 'canManage');
	$roles = Users::roles(null, $allowed);
	$isAdmin = !!$roles;

	$communityId = Users::communityId();
	$participating = Streams::participating(null, array(
		'streamsOnly' => true
	));
	$streamNames = array();
	foreach ($participating as $p) {
		$streamNames[] = $p->name;
	}

	$previewOptions = array(
		'inplace' => array(
			'inplace' => array(
				'maxWidth' => '.Streams_preview_contents',
				'editOnClick' => false
			)
		),
		'icon' => false
	);
	
	return Q::view('Communities/content/home.php', @compact(
		'user', 'identifiers', 'isAdmin', 'communityId', 'streamNames', 'previewOptions'
	));
}

