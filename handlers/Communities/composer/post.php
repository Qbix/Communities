<?php

/**
 * Create new community. Respects "Communities/create" user quota.
 * @class HTTP Communities composer
 * @method post
 * @param {array} $_REQUEST
 * @param {string} $_REQUEST.name Name of the new community.
 */
function Communities_composer_post()
{
	$required = array('name');
	Q_Valid::requireFields($required, $_REQUEST, true);

	$r = Q::take($_REQUEST, array('name' => null, 'creditsConfirmed' => null));

	$community = Communities::create($r['name'], array(
		'creditsConfirmed' => $r['creditsConfirmed']
	));

	Q_Response::setSlot('community', array(
		'id' => Q::ifset($community, 'id', null),
		'name' => Q::ifset($community, 'username', null),
		'icon' => Q::ifset($community, 'icon', null),
		'request' => Q::ifset($community, 'request', null)
	));
}