<?php

// ═══════════════════════════════════════════════════════════════════
// Communities plugin — community management
// File: plugins/Communities/handlers/Q/SafeScript/scope.php
// ═══════════════════════════════════════════════════════════════════

function Communities_before_Q_SafeScript_scope($params)
{
	$scope = &$params['scope'];

	// Current community
	$scope['Communities_communityId'] = function () {
		return Users::communityId();
	};
	$scope['Communities_communityName'] = function ($includeSuffix = false) {
		return Users::communityName($includeSuffix);
	};

	// Check admin
	$scope['Communities_isAdmin'] = function ($userId = null) {
		return Communities::isAdmin($userId);
	};

	// Get community labels
	$scope['Communities_getLabels'] = function () {
		return Communities::getLabels();
	};

	// Create community
	$scope['Communities_create'] = function ($name, $options = array()) {
		$community = Communities::create($name, $options);
		return array(
			'id' => $community->id,
			'username' => $community->username
		);
	};
}