<?php

/**
 * @module Communities
 */

/**
 * @class HTTP Communities importusers
 * @method post
 * @param {array} [$_REQUEST]
 * @param {string} [$_REQUEST.taskStreamName] Pass the name of a task stream to resume it.
 *    In this case, you don't need to pass the file, because it was saved.
 * @param {array} [$_FILES] Array consisting of one or more CSV files.
 *  The first line consists of titles or names of streams loaded from
 *  JSON files named under Streams/userStreams config.
 * @throws Users_Exception_NotAuthorized
 */
function Communities_importusers_post()
{
	ini_set('memory_limit', '1024M');

	$luid = Users::loggedInUser(true)->id;
	$taskStreamName = Q::ifset($_REQUEST, 'taskStreamName', null);
	if (empty($taskStreamName)) {
		throw new Exception("field taskStreamName required");
	}

	$texts = Q_Text::get('Calendars/content')['import'];

	// check permissions
	$communityId = Q::ifset($_REQUEST, 'communityId', Users::currentCommunityId(true));
	if (!Users::roles($communityId, array("Users/admins", "Users/owners"))) {
		throw new Users_Exception_NotAuthorized();
	}

	// get the instructions from uploaded file
	if (!empty($_FILES)) {
		$file = reset($_FILES);
		$tmp = $file['tmp_name'];

		// create array of csv lines from file
		$handle = fopen($tmp,'r');
		$instructions = array();
		while (($data = fgetcsv($handle)) !== FALSE ) {
			$instructions[] = $data;
		}

		if (empty($instructions)) {
			throw new Exception($texts['fileEmpty']);
		}

		// encode to json to save it to DB
		$instructions = json_encode($instructions);
	}

	$taskStream = Streams_Stream::fetch($luid, $luid, $taskStreamName);
	if (!$taskStream) {
		throw new Exception($texts['taskStreamInvalid']);
	}

	// if task stream not related to global category
	Streams::relate(
		null,
		Q::app(),
		"Streams/tasks/app",
		'Communities/importusers',
		$taskStream->publisherId,
		$taskStream->name,
		array(
			'skipAccess' => true,
			'weight' => time()
		)
	);

	// if new file uploaded, replace instructions in task stream
	if (!empty($instructions)) {
		$taskStream->instructions = $instructions;
	}

	// task stream reusing
	if ($taskStream->getAttribute('complete') == 1) {
		$taskStream->clearAllAttributes();
	}

	$communityUsers = Q::ifset($_REQUEST, 'communityUsers', $taskStream->getAttribute("communityUsers"));

	$taskStream->setAttribute("joinToRandomEvent", Q::ifset($_REQUEST, 'joinToRandomEvent', false));
	$taskStream->setAttribute("setUrlAsConversation", Q::ifset($_REQUEST, 'setUrlAsConversation', false));
	$taskStream->setAttribute("toMainCommunityToo", Q::ifset($_REQUEST, 'toMainCommunityToo', false));
	$taskStream->setAttribute("activateUsers", Q::ifset($_REQUEST, 'activateUsers', false));
	$taskStream->setAttribute("communityUsers", Q::ifset($_REQUEST, 'communityUsers', false));
	$taskStream->setAttribute("communityId", $communityId);
	$taskStream->save();

	// call import only when task stream created and instructions loaded
	if ($communityUsers) {
		Communities_Import::communities($taskStream);
	} else {
		Communities_Import::users($taskStream);
	}
}