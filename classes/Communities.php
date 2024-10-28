<?php

/**
 * Communities
 * @module Communities
 * @main Communities
 */
/**
 * Static methods for the Communities plugin
 * @class Communities
 * @abstract
 */
abstract class Communities
{
	/**
	 * Create params for Q/tabs tool to create dashboard menu
	 * (including "tabs", "urls", "classes", "attributes")
	 * @method dashboardMenu
	 * @return {array}
	 */
	static function dashboardMenu () {
		$app = Q::app();
		$text = Q_Text::get(array("Communities/content", "$app/content"));
		$td = $text['dashboard'];

		$user = Users::loggedInUser();
		$isMobile = Q_Request::isMobile();
		$isAdmin = false;
		$isCalendarsAdmin = false;
		$adminLabels = Q_Config::get("Communities", "community", "admins", null);
		$calendarsAdminLabels = Q_Config::get("Calendars", "events", "admins", null);
		if ($user) {
			$isAdmin = $adminLabels ? (bool)Users::roles(null, $adminLabels, array(), $user->id) : false;
			$isCalendarsAdmin = $calendarsAdminLabels ? (bool)Users::roles(null, $calendarsAdminLabels, array(), $user->id) : false;
		}

		$pages = Q_Config::get('Communities', 'tabs', array(
			"community" => array(),
			"people" => array(),
			"events" => array(),
			"media" => array(),
			"discuss" => array(),
			"me" => array()
		));
		foreach ($pages as $k => &$v) {
			if (!is_array($v)) {
				$v = array();
			}
			if (!isset($v['name'])) {
				if ($k === 'me') {
					$v['name'] = Q::tool('Users/status', array(
						'avatar' => array('icon' => 80)
					), 'me');
				} else {
					$uck = ucfirst($k);
					$v['name'] = Q::ifset($td, $uck, $uck);
				}
			}
			$v['public'] = !in_array($k, array('community'));
			if (!isset($v['uri'])) {
				$event = "$app/$k/response/content";
				$uri = Q::canHandle($event) ? "$app/$k" : "Communities/$k";
			} else {
				$uri = $v['uri'];
			}
			$v['url'] = Q_Uri::url($uri);
		}
		$tabs = $urls = $classes = $attributes = array();
		foreach ($pages as $page => $options) {
			if (empty($options["public"])) {
				if (!$user) {
					continue;
				}
				if (!empty($options["admin"]) && !$isAdmin) {
					continue;
				}
				if (!empty($options["calendarsAdmin"]) && !$isCalendarsAdmin) {
					continue;
				}
			}
			if (!empty($options["admin"]) && !$isAdmin) {
				continue;
			}
			$tabs[$page] = Q::ifset($options, "name", Q::ifset($td, ucfirst($page), ''));
			$urls[$page] = Q::ifset($options, "url", Q_Uri::url("$app/$page"));
			$classes[$page] = "Communities_aspect_$page";
			if (Q_Request::url() == $options["url"]) {
				$classes[$page] .= " Q_current";
			}
			$attributes[$page]['data-touchlabel'] = $td[ucfirst($page)];
		}

		return compact("tabs", "urls", "classes", "attributes");
	}
	/**
	 * Get labels related to communities
	 * @method getLabels
	 * @return {array}
	 */
	static function getLabels()
	{
		$roles = Q_Config::expect("Users", "roles");
		return array_keys($roles);
	}
	/**
	 * Whether user is Users/owners or Users/admins of community
	 * @method isAdmin
	 * @param {string} [$userId=null] User id need to check. If null - current logged user.
	 * @param {string} [$communityId=null] The id of the community
	 * @param {boolean} [$startSession=true]
	 *   Whether to start a PHP session if one doesn't already exist.
	 * @return {bool}
	 */
	static function isAdmin ($userId = null, $communityId = null)
	{
		$communityId = $communityId ?: Users::communityId();
		$admins = Q_Config::expect('Communities', 'community', 'admins');
		return (bool)Users::roles($communityId, $admins, array(), $userId);
	}
	/**
	 * Get or create stream with name Communities/create/permissions which store permissions to create new community
	 * @method getPermissionStream
	 * @return {Stream_Stream}
	 */
	static function getPermissionStream ()
	{
		$publisherId = Users::communityId();
		$streamName = 'Communities/create/permissions';
		$stream = Streams_Stream::fetch($publisherId, $publisherId, $streamName, '*', array(
			"dontCache" => true
		));

		if (!$stream) {
			$stream = Streams::create($publisherId, $publisherId, "Communities/permissions", array(
				"name" => $streamName
			));
		}

		return $stream;
	}
	/**
	 * Check whether user authorized to create new communities
	 * @method canCreateCommunities
	 * @param {string} [$userId=null] User id need to check. If null - current logged user.
	 * @param {bool} [$throwIfNotAuthorized=false] Whether to throw exception if not authorized.
	 * @throws Exception
	 * @return {bool}
	 */
	static function canCreateCommunities($userId = null, $throwIfNotAuthorized = false)
	{
		if (!$userId) {
			$user = Users::loggedInUser(false, false);
			if ($user) {
				$userId = $user->id;
			} else {
				if ($throwIfNotAuthorized) {
					throw new Users_Exception_NotAuthorized();
				}

				return false;
			}
		}

		$authorized = self::isAdmin($userId);

		if (!$authorized) {
			$permissionStream = self::getPermissionStream();
			$authorized = $permissionStream->testAdminLevel('own');
		}

		if (!$authorized && $throwIfNotAuthorized) {
			throw new Users_Exception_NotAuthorized();
		}

		return $authorized;
	}
	/**
	 * Derive a community userId from a community's name,
	 * turning "My NYU community" into "MyNYUCommunity"
	 * @method idFromName
	 * @param {string} $string
	 * @return {string}
	 */
	static function idFromName($string)
	{
		$words = preg_split('/[\s]/', $string, null, PREG_SPLIT_NO_EMPTY);
		$ucwords = array();
		foreach ($words as $word) {
			$ucwords[] = ($word === strtoupper($word))
				? $word
				: Q_Utils::ucfirst(Q_Utils::normalize($word));
		}
		return implode('', $ucwords);
	}
	/**
	 * Create community as user
	 * @method createCommunity
	 * @param {string} $communityName The name of community.
	 *  The id of the community (its userId) is derived from this name.
	 * @param {array} [$options] An associative array of parameters, containing:
	 * 	@param {bool} [$options.throwIfExist=true] Whether throw exception if community already exist
	 * 	@param {bool} [$options.skipAccess=false] Whether skip checking for permissions to create community
	 * 	@param {bool} [$options.creditsConfirmed=null] Whether user confirmed to charge credits for new community
	 * @throws Exception
	 * @return {Users_User}
	 */
	static function create($communityName, $options = array())
	{
		$userId = Users::loggedInUser(true)->id;

		// default values
		$options = array_merge(array(
			'skipAccess' => false,
			'throwIfExist' => true,
			'creditsConfirmed' => null
		), $options);

		if (self::isAdmin($userId)) {
			$skipAccess = true;
		} else {
			$skipAccess = $options['skipAccess'];
		}

		$quotaName = "Communities/create";
		$quota = null;

		if (!$skipAccess) {
			// check whether user can create communities
			self::canCreateCommunities($userId, true);

			// check quota
			$roles = Users::roles();
			$quota = Users_Quota::check($userId, '', $quotaName, false, 1, array_keys($roles));

			// if quota exceeded
			if (!($quota instanceof Users_Quota)) {
				// check for credits
				$handler = "Assets/community/create";
				$res = Q::canHandle($handler) ? Q::event($handler) : null;
				if (is_array($res)) {
					$authorized = Q::ifset($res, 'authorized', true);

					if (!$authorized) {
						$needCredits = Q::ifset($res, 'needCredits', null);
						$myCredits = Q::ifset($res, 'myCredits', null);
						throw new Exception(Q::interpolate(Q_Text::get('Communities/content')['community']['NotEnoughCredits'], array($needCredits, $myCredits)));
					} elseif (is_null($options['creditsConfirmed'])) {
						$text = Q_Text::get('Communities/content');

						// ask user if he want to charge credits
						return array(
							'request' => Q::interpolate($text['composer']['creditsChargeRequest'], array(
								'price' => Q_Config::get('Assets', 'credits', 'spend', 'Communities/create', 20)
							))
						);
					}
				}
			}
		}

		Q::event("Communities/community/create", @compact('userId', 'community', 'skipAccess', 'quota'), 'before');

		$community = new Users_User();
		$communityId = self::idFromName($communityName);
		$community->id = $communityId;
		if ($community->retrieve()) {
			if ($options['throwIfExist']) {
				throw new Exception("Community with this name already exist");
			}
		} else {
			$community->url = Q_Config::expect('Q', 'web', 'appRootUrl');
			$community->icon = "{{baseUrl}}/Q/plugins/Communities/img/icons/default";
			$community->signedUpWith = 'none';
			$community->username = $communityName;
			$community->save();
		}

		$streams = self::prepareCommunity($communityId);

		// set logged user as Users/owners for new community
		$usersContact = new Users_Contact();
		$usersContact->userId = $community->id;
		$usersContact->contactUserId = $userId;
		$usersContact->label = "Users/owners";
		if (!$usersContact->retrieve()) {
			$usersContact->nickname = "Owner";
			$usersContact->save();
		}

		// join logged user to Streams/experience/main of new community
		if (Q::ifset($streams, 'experienceStream', null) instanceof Streams_Stream) {
			$streams['experienceStream']->join(array('userId' => $userId));
		}

		// set quota
		if (!$skipAccess && $quota instanceof Users_Quota) {
			$quota->used();
		}

		Q::event("Communities/community/create", @compact('userId', 'community', 'skipAccess', 'quota'), 'after');

		return $community;
	}
	/**
	 * Make required steps to prepare new community.
	 * (create streams Streams/user/username, Streams/experience/main, etc)
	 * @method prepareCommunity
	 * @param string $communityId id of community need to prepare
	 * @throws
	 * @return array Array of streams created
	 */
	static function prepareCommunity($communityId) {

		$community = new Users_User();
		$community->id = $communityId;
		if (!$community->retrieve()) {
			throw new Exception("Community with this id don't exist");
		}

		// create username stream
		$usernameStream = Streams_Stream::fetch($communityId, $communityId, "Streams/user/username");
		if (!$usernameStream) {
			$usernameStream = Streams::create($communityId, $communityId, 'Streams/text/name', array(
				'name' => "Streams/user/username",
				'title' => "Username",
				'content' => $community->username
			));
		} else {
			$usernameStream->content = $community->username;
			$usernameStream->save();
		}

		// main community experience stream, for community-wide announcements etc.
		$experienceStream = Streams_Stream::fetch($communityId, $communityId, "Streams/experience/main");
		if (!$experienceStream) {
			$experienceStream = Streams::create($communityId, $communityId, 'Streams/experience', array(
				'name' => 'Streams/experience/main',
				'title' => $community->username,
				'icon' => $community->iconUrl()
			), array(
				'skipAccess' => true
			));
		} else {
			$experienceStream->title = $community->username;
			$experienceStream->save();
		}

		// Places/user/locations
		$locationsStream = Streams_Stream::fetch($communityId, $communityId, "Places/user/locations");
		if (!$locationsStream) {
			$locationsStream = Streams::create($communityId, $communityId, 'Streams/category', array(
				'name' => "Places/user/locations",
				'title' => "Saved Locations",
				'readLevel' => 40,
				'writeLevel' => 0,
				'adminLevel' => 0
			));
		} else {
			$locationsStream->readLevel = 40;
			$locationsStream->writeLevel = 0;
			$locationsStream->adminLevel = 0;
			$locationsStream->save();
		}

		// add needed labels
		self::addCommunityLabels($communityId);

		return @compact('usernameStream', 'experienceStream', 'locationsStream');
	}
	/**
	 * Add standard roles to community
	 * @method addCommunityLabels
	 * @param {string} $communityId id of community need to add role
	 */
	static function addCommunityLabels($communityId)
	{
		$rolesLabels = Q_Config::expect("Users", "roles");
		foreach($rolesLabels as $role => $info) {
			if (empty($role)) {
				continue;
			}
			Users_Label::addLabel($role, $communityId, $info['title'], $info['icon'], false);
		}
	}
	/**
	 * Sets the default community artificially in the app.
	 * In most apps, you don't want to do this.
	 * @param {string} [$communityId=null] If null, checks the $_COOKIE
	 * @param {array} [$options=array()]
	 * @param {array} [$options.subscribe=array()] Array of stream names such as "Streams/experience/main".
	 *   User subscribes only if they have at least "join" write access, unless skipAccess option is true.
	 * @param {boolean} [$options.skipAccess=false] Skip access checks when joining the experience.
	 * @param {string} [$options.setLocation] If user's location is not set, sets it to location
	 *   of the stream named here, published by the community. It copies the atrributes:
	 *   'latitude', 'longitude', 'meters', 'timezone', 'postcode', 'placeName', 'state'
	 */
	static function setCommunity($communityId = null, $options = array())
	{
		if (!isset(self::$communities)) {
			self::$communities = Q_Config::get('Communities', 'communities', array());
		}
		Q_Session::start();
		$currentCommunityId = Users::currentCommunityId();
		if (!isset($communityId)) {
			$communityId = Q::ifset($_COOKIE, 'Q_Users_communityId', Q_Config::get('Communities', "community", "default", Users::communityId()));
		}
		foreach (self::$communities as $userId => $info) {
			if (is_string($info)) {
				$communityName = $info;
				$communitySuffix = null;
			} else {
				$communityName = $info['name'];
				$communitySuffix = Q::ifset($info, 'suffix', null);
			}
			if ($communityId === $userId) {
				Q_Config::set('Users', 'community', 'id', $communityId);
				Q_Config::set('Users', 'community', 'name', $communityName);
				if ($communitySuffix) {
					Q_Config::set('Users', 'community', 'suffix', $communitySuffix);
				}
				break;
			}
		}
		if ($communityId == $currentCommunityId) {
			return;
		}
		$_SESSION['Users']['communityId'] = $communityId;
		Q_Response::setCookie('Q_Users_communityId', $communityId);
		if ($user = Users::loggedInUser(false, false)) {
			if (!Streams_Stream::fetch($user->id, $user->id, "Streams/greeting/$communityId")) {
				Streams::create($user->id, $user->id, "Streams/greeting", array(
					'name' => "Streams/greeting/$communityId"
				));
			}
			if (!empty($options['subscribe'])) {
				foreach ($options['subscribe'] as $streamName) {
					$stream = Streams_Stream::fetch($user->id, $communityId, $streamName, true);
					$access = !empty($options['skipAccess']) || $stream->testWriteLevel('join');
					if ($access and !$stream->subscription()) {
						$stream->subscribe();
					}
				}
			}
			if (!empty($options['setLocation']) && class_exists('Places')) {
				$streamName = $options['setLocation'];
				$stream = Streams_Stream::fetch($user->id, $communityId, $streamName, true);
				Places::setUserLocation($stream, true);
			}

			// join user to Calendars/calendar/main
			if (class_exists('Calendars')) {
				$stream = Calendars::eventsMainCategory($communityId);
				if (!$stream->participant($user->id)) {
					$stream->join(array('userId' => $user->id));
				}
			}
		}
	}
	/**
	 * @method requestedId
	 * @param {array} $fields Any fields to check before the $_REQUEST or Q_Dispatcher::uri()
	 * @param {string} $type Id type. Can be 'eventId', 'tripId', 'conversationId'
	 * @static
	 * @return {string} The value of the "eventId", if any
	 */
	static function requestedId($fields, $type)
	{
		$uri = Q_Dispatcher::uri();
		return Q::ifset($fields, $type,
			Q::ifset($_REQUEST, $type,
				Q::ifset($uri, $type, null)
			)
		);
	}
	/**
	 * A callback function used to sort the area filenames
	 * when displaying invitations for the "areas" batch
	 */
	static function sortAreaFilenames($filename1, $filename2)
	{
		$parts = explode('-', $filename1);
		$parts = explode('_', $parts[count($parts)-2]);
		$l1 = $parts[count($parts)-2];
		$a1 = end($parts);
		$f1 = intval($a1);
		$c1 = substr($a1, strlen("$f1"));

		$parts = explode('-', $filename2);
		$parts = explode('_', $parts[count($parts)-2]);
		$l2 = $parts[count($parts)-2];
		$a2 = end($parts);
		$f2 = intval($a2);
		$c2 = substr($a2, strlen("$f2"));

		return ($l1 != $l2)
			? ($l1 > $l2 ? 1 : -1)
			: (($f1 != $f2) ? ($f1 > $f2 ? 1 : -1) : ($c1 != $c2 ? ($c1 > $c2 ? 1 : -1) : 0));
	}

	/**
	 * Get a bunch of user ids in a given community,
	 * starting with contacts of the logged-in user (if any).
	 * Useful for passing to the Users/list tool, to render
	 * a list of users in a community.
	 * @method userIds
	 * @param {array} [$options=array()]
	 * @param {integer} [$options.limit=100] The maximum number to return
	 * @param {integer} [$options.offset=0] The offfset
	 * @param {string} [$options.communityId=null] The community from which to get stream participants, defaults to main community
	 * @param {string} [$options.experienceId='main'] Can be used to override the name of the experience
	 * @param {Users_User|false} [$options.userId=Users::loggedInUser()->id] The user, if any, whose contacts to get
	 * @param {boolean} [$options.includeUser=false] Whether to include the specified user in the list
	 * @param {boolean} [$options.includeCommunities=false] Whether to include community users
	 * @param {boolean} [$options.includeReverseContacts=false] Whether to show users who have you in their contacts already
	 * @param {boolean} [$options.includeFutureUsers=false] Whether to include users who have not yet signed in even once, but who have a custom icon at least
	 * @param {array} [$options.filterByRoles] you can pass an array of roles, user must have at least one
	 * @param {boolean} [$options.customIconsFirst=false] Whether to sort the non-contact users in a way to have the ones with custom photos be listed first.
	 * @param {array} [$options.idPrefixes]
	 * @param {array} [$options.idPrefixes.require] Only return users with this id prefix
	 * @param {array} [$options.idPrefixes.exclude] Exclude users with this id prefix
	 */
	static function userIds($options = array())
	{
		$options = Q::take($options, array(
			'limit' => 100,
			'offset' => 0,
			'communityId' => Users::currentCommunityId(true),
			'experienceId' => 'main',
			'userId' => null,
			'includeUser' => false,
			'includeCommunities' => false,
			'includeReverseContacts' => false,
			'includeFutureUsers' => false,
			'customIconsFirst' => false,
			'customIconsOnly' => false,
			'idPrefixes' => array()
		));
		if (isset($options['limit'])) {
			$maxLimit = Q_Config::get('Communities', 'userIds', 'maxLimit', 1000);
			$options['limit'] = min($options['limit'], $maxLimit);
		}

		$user = Users::loggedInUser();
		if (!isset($options['userId'])) {
			$options['userId'] = $user ? $user->id : '';
		}
		if (!isset($options['communityId'])) {
			$options['communityId'] = Users::currentCommunityId(true);
		}
		$criteria = array(
			'p.publisherId' => $options['communityId'],
			'p.streamName' => "Streams/experience/".$options['experienceId'],
			'p.state' => 'participating'
		);
		$orCriteria = null;
		if (!empty($options['idPrefixes']['require'])) {
			$criteria['u.id'] = new Db_Range($options['idPrefixes']['require'], true, false, true);
		} else if (!empty($options['idPrefixes']['exclude'])) {
			$ope = $options['idPrefixes']['exclude'];
			$criteria['u.id <'] = $ope;
			$orCriteria = array('u.id >' => $ope);
		}
		if ($options['userId']) {
			$rows = Users_Contact::select('u.id, u.sessionCount, u.icon', 'c')
				->join(Streams_Participant::table(true, 'p'), array(
					'c.userId' => 'p.userId'
				))->join(Users_User::table(true, 'u'), array(
					'c.contactUserId' => 'u.id'
				))->where(array('c.userId' => $options['userId']))
				->andWhere($criteria, $orCriteria)
				// ->groupBy('u.id')
				->orderBy('c.label', false)
				->orderBy('u.sessionCount', false)
				->limit($options['limit'], $options['offset'])
				->fetchDbRows(null, '', 'id');
			if ($options['includeReverseContacts']) {
				$rows2 = Users_Contact::select('u.id, u.sessionCount, u.icon', 'c')
					->join(Streams_Participant::table(true, 'p'), array(
						'c.contactUserId' => 'p.userId'
					))->join(Users_User::table(true, 'u'), array(
						'c.userId' => 'u.id'
					))->where(array('c.contactUserId' => $options['userId']))
					->andWhere($criteria)
					// ->groupBy('u.id')
					->orderBy('c.label', false)
					->orderBy('u.sessionCount', false)
					->limit($options['limit'])
					->fetchDbRows(null, '', 'id');
				$rows = array_merge($rows, $rows2);
			}
		} else {
			$rows = array();
		}
		$contactsCount = count($rows);
		$m = 2;
		$o = 0;
		// try finding more up to 10 times
		for ($i=0; $i<10; ++$i) {
			if (!empty($options['customIconsOnly'])) {
				$temp = array();
				foreach ($rows as $uid => $row) {
					if (Users::isCustomIcon($row->icon)) {
						$temp[$uid] = $row;
					}
				}
				$rows = $temp;
			} elseif (!empty($options['customIconsFirst'])) {
				// sort array with custom icons first
				$temp = array();
				foreach ($rows as $uid => $row) {
					if (Users::isCustomIcon($row->icon)) {
						$temp[$uid] = $row;
					}
				}
				foreach ($rows as $uid => $row) {
					if (empty($temp[$uid])) {
						$temp[$uid] = $row;
					}
				}
				$rows = $temp;
			}

			// filter futureUsers and Community users and current user
			$includeFutureUsers = !empty($options['includeFutureUsers']);
			$includeCommunities = !empty($options['includeCommunities']);
			$temp = array();
			foreach ($rows as $uid => $row) {
				if ($row->sessionCount == 0
				&& (!$includeFutureUsers && !Users::isCustomIcon($row->icon))) {
					continue;	
				}
				if ((!$includeCommunities && Users::isCommunityId($uid))) {
					continue;
				}
				if (empty($options['includeUser']) and $user and $uid === $options['userId']) {
					continue;
				}
				$temp[$uid] = $row;
			}
			$b = count($rows);
			$rows = $temp;
			$c = count($rows);
			if ($c) {
				$m = ceil($b/$c); // try to guess how many might be filtered in the next pass
			}
			if ($c >= $options['limit']) {
				break; // we got enough results to return
			}
			// get multiple times as many rows, since some may be removed
			$rows3 = Streams_Participant::select('u.id, u.sessionCount, u.icon', 'p')
				->join(Users_User::table(true, 'u'), array(
					'p.userId' => 'u.id'
				))
				->join(Users_Contact::table(true, 'c'), array(
					'p.userId' => 'c.contactUserId'
				), "LEFT")
				->where($criteria)
				->orWhere($orCriteria)
				->orderBy('c.label', false)
				->orderBy('u.sessionCount', false)
				->orderBy('p.insertedTime', false)
				->limit(
					$options['limit'] * $m - $contactsCount,
					$o + max(0, $options['offset'] - $contactsCount)
				)
				->fetchDbRows(null, '', 'id');
			if (empty($rows3)) {
				continue; // no more userIds are coming by
			}
			$o += count($rows3);
			$rows = array_merge($rows, $rows3);
		}
		$userIds = array_slice(array_keys($rows), 0, $options['limit']);
		$userIds = Q::event('Users/filter/users', array(
			'from' => 'Communities::userIds'
		), 'after', false, $userIds);
		return $userIds;
	}
	/**
	 * @method labelsOptions
	 * @static
	 * @param {boolean} [$filterMode=false]
	 * @param {boolean} [$newRelationType=true]
	 */
	static function labelsOptions($filterMode = false, $newRelationshipType = true)
	{
		$options = $filterMode ? array('*' => 'Everyone') : array();
		foreach (Users_Label::fetch(null, 'Users/') as $label => $contact) {
			$options[$label] = $contact->title;
		}
		foreach (Users_Label::fetch(null, 'Streams/') as $label => $contact) {
			$options[$label] = $contact->title;
		}
		if ($newRelationshipType) {
			$text = Q_Text::get('Communities/content');
			$options['+ New Relationship Type'] = $text['people']['labels']['New'];
		}
		return $options;
	}
	/**
	 * Get all public chat conversations for some community
	 * and order by message insertedTime
	 * @method conversationChats
	 * @param {string} $communityId
	 * @param {string} $experienceId
	 * @return {array} The relations, filtered by message insertedTime
	 */
	static function conversationChats($communityId = null, $experienceId = null, $offset=0, $limit=null) {
		$communityId = preg_replace('/[^\w]/', '', $communityId);
		$experienceId = preg_replace('/[^\w]/', '', $experienceId ?: 'main');
		$limit = Q::ifset($limit, Q_Config::get('Communities', 'pageSizes', 'conversations', Q_Config::expect('Streams', 'db', 'limits', 'stream')));

		$relationTypes = Q_Config::expect("Communities", "conversations", "relationTypes");

		// select public chats ordered by last message inserted time (last messages first)
		return Streams::related(
			$communityId, $communityId, "Streams/chats/$experienceId", true, array(
				'relationsOnly' => true,
				'limit' => $limit,
				'offset' => $offset,
				'type' => $relationTypes,
				'skipAccess' => true
			)
		);
	}
	/**
	 * Get all the chat conversations the user is participating in,
	 * as related to their "Streams/participating" category stream.
	 * @method participatingChats
	 * @param {string} $userId
	 * @param {array} [$streamTypes=array('Streams/chat','Websites/webpage')]
	 *  The types of streams to list
	 * @return {array} The streams, filtered by the above parameters
	 */
	static function participatingChats(
		$userId = null,
		$streamTypes = array('Streams/chat', 'Websites/webpage', 'Media/episode')
	) {
		if (!isset($userId)) {
			$userId = Users::loggedInUser(true)->id;
		}

		// get all chat streams related to Streams/participating
		// NOTE: should probably use LIMIT clauses for pagination later
		$relations = Streams_RelatedTo::select()->where(array(
			'toPublisherId' => $userId,
			'toStreamName' => "Streams/participating",
			'type' => $streamTypes
		))->fetchDbRows();

		// remove duplicates
		$criteria = array();
		foreach ($relations as $r) {
			$criteria[$r->fromPublisherId.$r->fromStreamName] = array($r->fromPublisherId, $r->fromStreamName);
		}

		$relatedStreams = Streams_Stream::select()->where(array(
			'publisherId,name' => $criteria,
			'closedTime' => NULL
		))->fetchDbRows();

		// sort streams by updatedTime (earlier first)
		usort( $relatedStreams, array('Communities', 'compareByUpdatedTime'));

		return $relatedStreams;
		//return Streams::participating("Streams/participating", $options);
	}

	private static function compareByUpdatedTime($a, $b)
	{
		// if updatedTime absent - use insertedTime
		$aUpdatedTime = $a->updatedTime ?: $a->insertedTime;
		$bUpdatedTime = $b->updatedTime ?: $b->insertedTime;

		$c = preg_replace("/[^0-9]/", "", $aUpdatedTime);
		$d = preg_replace("/[^0-9]/", "", $bUpdatedTime);
		return ($c > $d) ? -1 : 1;
	}

	/**
	 * Get default times from the config
	 * @return {array} Returns array($fromTime, $toTime);
	 */
	static function defaultEventTimes()
	{
		$fromTime = Q_Config::get('Communities', 'events', 'fromTime', time());
		$fromTimeShift = Q_Config::get('Communities', 'events', 'fromTimeShift', 0);
		if ($fromTimeShift) {
			$fromTime = $fromTime - $fromTimeShift;
		}
		$toTime = Q_Config::get('Communities', 'events', 'toTime',
			time() + Calendars_Event::defaultListingDuration()
		);
		return array($fromTime, $toTime);
	}
	/**
	 * Select events appropriate only for current user (by location, by interest etc).
	 * @method filterEvents
	 * @param {array} [$params] Different params
	 * @param {string} [$params.communityId]
	 * @param {string} [$params.experienceId]
	 * @param {string} [$params.interest]
	 * @param {string} [$params.category]
	 * @param {string} [$params.offset]
	 * @param {string} [$params.limit]
	 * @param {string} [$params.fromTime]
	 * @param {string} [$params.toTime]
	 * @param {string} [$params.skipPreload=true] whether to skip preload interests, locations, area streams related to events
	 * @return {array} The relations, filtered by the above parameters.
	 *  Note that some relations might point to streams which the user doesn't
	 *  have readAccess to see. Check relation->get('Streams/public')
	 */
	static function filterEvents($params = array()) {
		$user = Users::loggedInUser();
		$communityId = Q::ifset($params, 'communityId', Users::currentCommunityId(true));
		$experienceId = Q::ifset($params, 'experienceId', Q::ifset($_REQUEST, 'experienceId', 'main'));

		$uri = Q_Dispatcher::uri();
		$interest = Q::ifset($params, 'interest', Q::ifset($_REQUEST, 'interest', Q::ifset($uri, 'interest', null)));
		$category = Q::ifset($params, 'category', Q::ifset($_REQUEST, 'category', Q::ifset($uri, 'category', null)));
		$skipPreload = Q::ifset($params, 'skipPreload', Q::ifset($_REQUEST, 'skipPreload', Q::ifset($uri, 'skipPreload', true)));
		$relationType = 'Calendars/events';
		$locationStream = Places_Location::userStream();

		list($fromTime, $toTime) = Communities::defaultEventTimes();
		$fromTime = Q::ifset($params, 'fromTime', $fromTime);
		$toTime = Q::ifset($params, 'toTime', $toTime);

		$weight = new Db_Range($fromTime, true, true, $toTime);

		$offset = Q::ifset($params, 'offset', 0);
		$limit = Q::ifset($params, 'limit', Q_Config::get('Communities', 'events', 'limit', 10));
		$orderBy = true;

		// default value
		$relations = array();

		if ($interest) {
			if (is_array($interest)) {
				$interest = $interest[0] . ': ' . implode(' ', array_slice($interest, 1));
			}

			$relations = Places_Interest::byTime(
				$communityId, $relationType, $interest, $fromTime, $toTime, $experienceId
			);
		} elseif ($category) {
			$min = Q_Utils::normalize($category);
			if (substr($min, -1) !== '_') {
				$min .= '_';
			}
			$titles = new Db_Range($min, true, true, true);
			$relations = Places_Interest::byTime(
				$communityId, $relationType, $titles, $fromTime, $toTime, $experienceId
			);
		} elseif (false and $locationStream instanceOf Streams_Stream
			&& $locationStream->getAttribute("latitude")
			&& $locationStream->getAttribute("longitude")) {
			$o = Q::take($locationStream->getAllAttributes(), array(
				'latitude', 'longitude', 'meters'
			));
			$relations = Places_Nearby::byTime(
				$communityId, $relationType, $fromTime, $toTime,
				"Calendars/calendar/".$experienceId, $o
			);
		} else { // show all future events
			$relations = Streams_RelatedTo::fetchAll(
				$communityId, array("Calendars/calendar/".$experienceId),
				$relationType, @compact("weight", "orderBy", "limit", "offset")
			);
		}

		// add events where logged user is a publisher
		if ($user) {
			$relations = array_merge($relations, Streams_RelatedTo::fetchAll(
				$communityId, array("Calendars/calendar/".$experienceId), $relationType, array(
					"weight" => $weight,
					"where" => array("fromPublisherId" => $user->id),
					"limit" => $limit,
					"offset" => $offset
				)
			));
		}

		// clear duplicates
		foreach ($relations as $i => $relation) {
			$relations[$relation->fromStreamName] = $relation;
			unset($relations[$i]);
		}

		// first, fetch all the public streams using one query
		// NOTE: this assumes all these streams are in the same database
		$publishersAndNames = array();
		foreach ($relations as $streamName => $relation) {
			$publishersAndNames[$relation->fromPublisherId][] = $relation->fromStreamName;
		}		
		$streams = Streams::fetchPublicStreams($publishersAndNames);

		foreach ($relations as $streamName => $relation) {
			// let the caller choose whether to fetch the
			// non-public streams and test their readLevel
			$isPublic = !empty($streams[$relation->fromPublisherId][$relation->fromStreamName]);
			$relation->set('Streams/public', $isPublic);
		}

		return $relations;
	}

	/**
	 * Select services appropriate only for current user (by location, by interest etc).
	 * @method filterServices
	 * @param {array} [$params] Different params
	 * @param {string} [$params.communityId]
	 * @param {string} [$params.experienceId]
	 * @param {string} [$params.interest]
	 * @param {string} [$params.category]
	 * @param {string} [$params.offset]
	 * @param {string} [$params.limit]
	 * @param {string} [$params.fromTime]
	 * @param {string} [$params.toTime]
	 * @param {string} [$params.skipPreload=true] whether to skip preload interests, locations, area streams related to events
	 * @return {array} The relations, filtered by the above parameters.
	 *  Note that some relations might point to streams which the user doesn't
	 *  have readAccess to see. Check relation->get('Streams/public')
	 */
	static function filterServices($params = array()) {
		$user = Users::loggedInUser();
		$communityId = Q::ifset($params, 'communityId', Users::currentCommunityId(true));
		$experienceId = Q::ifset($params, 'experienceId', Q::ifset($_REQUEST, 'experienceId', 'main'));

		$relationType = 'Calendars/availability';

		$offset = Q::ifset($params, 'offset', 0);
		$limit = Q::ifset($params, 'limit', Q_Config::get('Communities', 'services', 'limit', 10));
		$orderBy = true;

		$relations = Streams_RelatedTo::fetchAll(
			$communityId, array("Calendars/availabilities/".$experienceId),
			$relationType, @compact("orderBy", "limit", "offset")
		);

		// add events where logged user is a publisher
		if ($user) {
			$relations = array_merge($relations, Streams_RelatedTo::fetchAll(
				$communityId, array("Calendars/availabilities/".$experienceId), $relationType, array(
					"where" => array("fromPublisherId" => $user->id),
					"limit" => $limit,
					"offset" => $offset
				)
			));
		}

		// clear duplicates
		foreach ($relations as $i => $relation) {
			$relations[$relation->fromStreamName] = $relation;
			unset($relations[$i]);
		}

		// first, fetch all the public streams using one query
		// NOTE: this assumes all these streams are in the same database
		$publishersAndNames = array();
		foreach ($relations as $streamName => $relation) {
			$publishersAndNames[$relation->fromPublisherId][] = $relation->fromStreamName;
		}		
		$streams = Streams::fetchPublicStreams($publishersAndNames);

		foreach ($relations as $streamName => $relation) {
			// let the caller choose whether to fetch the
			// non-public streams and test their readLevel
			$isPublic = !empty($streams[$relation->fromPublisherId][$relation->fromStreamName]);
			$relation->set('Streams/public', $isPublic);
		}

		return $relations;
	}

	/**
	 * Get or create Communities/users/blocked stream for logged user
	 * @method getBlockedUsersStream
	 * @return Streams_Stream
	 */
	static function getBlockedUsersStream() {
		$currentUser = Users::loggedInUser();
		$streamName = 'Communities/user/blocked';

		// get or create Communities/user/blocked stream
		$usersBlockedStream = Streams::fetchOneOrCreate($currentUser->id, $currentUser->id, $streamName);

		return $usersBlockedStream;
	}

	/**
	 * Get all communities
	 * @method getAllCommunities
	 * @param {bool} [$withMainCommunity=true] Whether to add app name to communities list.
	 * @return array
	 */
	static function getAllCommunities($withMainCommunity = true) {
		$communitiesMysql = Users_User::select()->where(array('signedUpWith' => 'none'))->fetchDbRows();

		$communities = array();
		foreach ($communitiesMysql as $community) {
			// Check whether string is community id
			if (!Users::isCommunityId($community->id)) {
				continue;
			}

			// skip main community, which is app name
			if (!$withMainCommunity && $community->id == Q::app()) {
				continue;
			}

			$communities[$community->id] = array(
				'icon' => $community->icon,
				'name' => $community->username
			);
		}

		return $communities;
	}

	/**
	 * Check whether string is community id
	 * @method isCommunityId
	 * @param {string} $communityId
	 * @return bool
	 */
	static function isCommunityId($communityId) {
		// first char not capital
		if (!ctype_upper($communityId[0])) {
			return false;
		}

		// skip plugin users
		if (in_array($communityId, Q_Config::expect("Q", "plugins"))) {
			return false;
		}

		return true;
	}

	/**
	 * Check whether user authorized to create new event
	 * @method newEventAuthorized
	 * @param {string} [$userId=null] User id need to check. If null - logged user.
	 * @return array
	 */
	static function newEventAuthorized($userId = null) {
		$contacts = array();

		if (!$userId) {
			$user = Users::loggedInUser();

			if (!$user) {
				return $contacts;
			}
			$userId = $user->id;
		}

		$anyoneNewEvent = Q_Config::get('Communities', 'events', 'anyoneNewEvent', false);
		if ($anyoneNewEvent) {
			$contacts[] = $userId;
		}

		$labelsAuthorized = Q_Config::get("Calendars", "events", "admins", null);
		if ($labelsAuthorized) {
			// check if user have permissions in current community
			if (!in_array($userId, $contacts) && !empty(Users::roles(Users::communityId(), $labelsAuthorized))) {
				$contacts[] = $userId;
			}

			$communitiesContacts = Users::byRoles($labelsAuthorized, array(), $userId);
			$contacts = array_merge($contacts, array_keys($communitiesContacts));
		}

		return $contacts;
	}
	/**
	 * Get or create Streams/chats/main stream for some community
	 * @method chatsMainCategory
	 * @param $communityId
	 * @return Streams_Stream
	 */
	static function chatsMainCategory($communityId) {
		$streamName = 'Streams/chats/main';
		$stream = Streams_Stream::fetch($communityId, $communityId, $streamName);
		if (!$stream) {
			$stream = Streams::create($communityId, $communityId, 'Streams/category', array(
				'name' => $streamName,
				'title' => "$communityId chats"
			));
		}
		return $stream;
	}

	/**
	 * Join random users to some streams with participants not less than $lessThan
	 * @method joinRandomUsers
	 * @param {integer} $streamType Type of stream
	 * @param {integer} [$publisherId=null] Stream publisher id, if null main community id used.
	 * @param {integer} [$lessThan=5] Streams with more than this amount of participants will ignored.
	 *
	 * @return {array} with keys "event" and "participant"
	 */
	static function joinRandomUsers($streamType, $publisherId = null, $lessThan = 5) {
		$publisherId = $publisherId ?: Users::communityId();
		Q_Config::set('Streams', 'db', 'limits', 'stream', 1000);
		$streams = Streams_Stream::select()->where(array(
			'publisherId' => $publisherId,
			'type' => $streamType
		))->fetchDbRows();

		$users = Users_User::select()->where(array('username' => '', 'sessionCount > ' => 1))->fetchDbRows();

		if(empty($streams) || !is_array($streams)) {
			return;
		}

		foreach ($streams as $stream) {
			$stream = Streams_Stream::fetch($stream->publisherId, $stream->publisherId, $stream->name);

			if (!empty($stream->closedTime)) {
				continue;
			}

			// check whether events have max participated users
			$participated = Streams_Participant::select("count(*) as res")
				->where(array(
					"publisherId" => $publisherId,
					"streamName" => $stream->name,
					"state" => 'participating'
				))
				->execute()
				->fetchAll(PDO::FETCH_ASSOC)[0]["res"];

			if ($participated >= $lessThan) {
				continue;
			}

			$randomAmount = rand(5, 10);

			shuffle($users);

			foreach ($users as $user) {
				if ($randomAmount <= 0) {
					break;
				}
				$participated = Streams_Participant::select("count(*) as res")
					->where(array(
						"publisherId" => $publisherId,
						"streamName" => $stream->name,
						"userId" => $user->id,
						"state" => 'participating'
					))
					->execute()
					->fetchAll(PDO::FETCH_ASSOC)[0]["res"];
				if ($participated >= 1) {
					continue;
				}

				try {
					$stream->join(array('userId' => $user->id));
				} catch (Exception $e) {}

				$randomAmount--;
			}
		}
	}

	/**
	 * Return Unix timestamp with milliseconds
	 * @method microtime_float
	 * @return float
	 */
	static function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	static $columns = array();
	static $events = array();
	static $options = array();
	static $communities = array();

	static $cache = array(); // info for the current page
}