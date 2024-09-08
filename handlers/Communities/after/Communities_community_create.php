<?php

function Communities_after_Communities_community_create($params)
{
	$userId = $params['userId'];
	$community = $params['community'];
	$skipAccess = $params['skipAccess'];
	$quota = $params['quota'];

	// if for some reason skippAccess or quota not exceeded
	if ($skipAccess || $quota instanceof Users_Quota) {
		return;
	}

	$amountToSpend = (int)Q_Config::expect('Assets', 'credits', 'spend', 'Communities/create');

	Assets_Credits::spend(null, $amountToSpend, Assets::CREATED_COMMUNITY, $userId, array(
		'communityId' => $community->id
	));
}