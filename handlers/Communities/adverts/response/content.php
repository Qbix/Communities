<?php

/**
 * @module Streams
 */

/**
 * Tool used to view and manage advertising campaigns
 * @class HTTP Websites adverts
 * @method GET
 * @param {array} $_REQUEST
 * @param {string} [$_REQUEST.publisherId=Users::loggedInUser(true)->id]
 *  The publisher of the advertising campaigns
 */
function Communities_adverts_response_content()
{
	$communityName = Users::communityName();
	$user = Users::loggedInUser(true);
	$publisherId = Q::ifset($_REQUEST, 'publisherId', $user->id);
	$creatives = Q::tool('Streams/related', array(
		'publisherId' => $publisherId,
		'streamName' => 'Websites/advert/creatives',
		'relationType' => 'Websites/advert/creatives',
		'editable' => true,
		'creatable' => array(
			'Websites/advert/creative' => array(
				'title' => 'New Creative'
			)
		)
	), 'creatives');
	$campaigns = Q::tool('Streams/related', array(
		'publisherId' => $publisherId,
		'streamName' => 'Websites/advert/campaigns',
		'relationType' => 'Websites/advert/campaigns',
		'editable' => true,
		'creatable' => array(
			'Websites/advert/campaign' => array(
				'title' => 'New Campaign'
			)
		)
	), 'campaigns');
	return Q::view('Communities/content/adverts.php', @compact(
		'communityName', 'creatives', 'campaigns'
	));
}