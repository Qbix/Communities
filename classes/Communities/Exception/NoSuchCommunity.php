<?php

/**
 * @module Users
 */
class Communities_Exception_NoSuchCommunity extends Q_Exception
{
	/**
	 * Thrown when the user is required to be logged in, but they aren't
	 * @class Communities_Exception_NoSuchCommunity
	 * @constructor
	 * @extends Q_Exception
	 * @param {string} $communityId
	 */
};

Q_Exception::add('Communities_Exception_NoSuchCommunity', 'Community not found with id {{communityId}}', 404);
