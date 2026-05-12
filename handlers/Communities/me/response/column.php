<?php
	
function Communities_me_response_column ($options)
{
	$user = Users::loggedInUser(true);
	$userId = $user->id;
	$uri = Q_Dispatcher::uri();
	$app = Q::app();
	$columns = array();
	$defaultTab = null;
	$tabClasses = array();

	$title = Q::tool("Users/avatar", array('userId' => $user->id, 'icon' => 40), 'Communities_schedule');
	// $title .= Q::ifset(Communities::$options, 'title', $text['me']['Title']);

	$url = Q::ifset(Communities::$options, 'url', "Communities/me");

	$tabs = Q_Config::expect('Communities', 'me', 'tabs');

	foreach ($tabs as $tabName => $tabValue) {
		if ($tabValue === false) {
			continue;
		}

		if (Q::ifset($tabValue, "default", null) === true) {
			$defaultTab = $tabName;
		} else {
			$defaultTab = $defaultTab ?: $tabName;
		}

		if ($tabName == 'inbox') {
			//****************** Inbox **************************
			Q_Response::addStylesheet('{{Communities}}/css/columns/inbox.css');
			$tabClasses['inbox'] = 'Streams_aspect_chats';
			$participating = Communities::participatingChats($user->id);
			Streams::arePublic(Streams::justPublicStreams($participating));
			$columns['inbox'] = Q::view('Communities/column/inbox.php', @compact(
				'user', 'participating'
			));
		} elseif ($tabName == 'schedule') {
			//****************** Schedule **************************
			Q_Response::addStylesheet('{{Communities}}/css/columns/schedule.css', 'Communities');
			$tabClasses['schedule'] = 'Q_aspect_when';
			$fromTime = 0; // select all past events
			$travelParticipating = array();
			$calendarsParticipating = array();
			if (class_exists('Travel_Trip')) {
				$travelParticipating = Travel_Trip::participating($user->id, $fromTime, null, null, array(
					'streamsOnly' => true
				));
			}
			if (class_exists('Calendars')) {
				$calendarsParticipating = Calendars::participating($user->id, $fromTime, null, array('yes', 'maybe'), array(
					'streamsOnly' => true
				));
			}
			$participating = array_merge($travelParticipating, $calendarsParticipating);

			// get default tab
			if (empty($participating)) {
				// user didn't joined some events/trips, make schedule the default tab
				$defaultTab = 'schedule';
			}

			$getConditionValue = function ($x) {
				// default time for compare is updatedTime
				$cond = strtotime($x->fields['updatedTime']);

				// for events use startTime time to compare
				if ($x->fields["type"] == "Calendars/event") {
					$cond = (int)$x->getAttribute("startTime");
				}

				// for trips use arrive time to compare
				if ($x->type == "Travel/trip") {
					if ($x->getAttribute("type") == "Travel/to") {
						$timeToCompare = "endTime";
						$cond = (int)$x->getAttribute($timeToCompare) - 1;
					} else {
						$timeToCompare = "startTime";
						$cond = (int)$x->getAttribute($timeToCompare) + 1;
					}
				}

				return $cond;
			};

			// sort trip and event streams in ascending order
			usort($participating, function ($a, $b) use ($getConditionValue) {
				$a2 = $getConditionValue($a);
				$b2 = $getConditionValue($b);
				return ($a2 < $b2) ? -1 : ($a2 > $b2 ? 1 : 0);
			});

			$currTime = time();
			$pastEvents = array();
			$futureEvents = array();
			foreach ($participating as $p) {
				if ($p->type == "Calendars/event") {
					$tool = Q::tool(array(
						"Streams/preview" => array(
							'publisherId' => $p->publisherId,
							'streamName' => $p->name,
							'closeable' => false
						),
						"Calendars/event/preview" => array()
					), array(
						'prefix' => "Communities_me_schedule_",
						'id' => uniqid(),
						'lazyload' => true
					));

					if ((int)$p->getAttribute("startTime") > $currTime) {
						$futureEvents[] = $tool;
					} else {
						$pastEvents[] = $tool;
					}
				}

				if ($p->type == "Travel/trip") {
					$tool = Q::tool("Travel/trip/preview", array(
						'publisherId' => $p->publisherId,
						'streamName' => $p->name
					), array(
						'prefix' => "Communities_me_schedule_",
						'id' => uniqid(),
						'lazyload' => true
					));

					if ((int)$p->getAttribute("endTime") > $currTime) {
						$futureEvents[] = $tool;
					} else {
						$pastEvents[] = $tool;
					}
				}
			}
			$pastEvents = array_reverse($pastEvents);
			$scheduleSubTab = Q::ifset($options, 'scheduleSubTab', Q::ifset($uri, 'scheduleSubTab', 'future'));
			$columns['schedule'] = Q::view('Communities/column/schedule.php', @compact(
				'user',
				'participating',
				'pastEvents',
				'futureEvents',
				'scheduleSubTab'
			));
		} elseif ($tabName == 'interests') {
			//****************** Interests **************************
			Q_Response::addStylesheet('{{Communities}}/css/columns/interests.css');
			$tabClasses['interests'] = 'Streams_aspect_interests';
			$columns['interests'] = Q::view('Communities/content/interests.php');
		} elseif ($tabName == 'gallery') {
			//****************** Gallery **************************
			Q_Response::addStylesheet("{{Communities}}/css/columns/gallery.css");
			Q_Response::addScript("{{Communities}}/js/columns/gallery.js");
			$tabClasses['gallery'] = 'Streams_aspect_gallery';
			$columns['gallery'] = Q::view("Communities/content/gallery.php", compact("user"));
		} elseif ($tabName == 'location') {
			//****************** Location **************************
			Q_Response::addStylesheet('{{Communities}}/css/columns/location.css');
			$tabClasses['location'] = 'Q_aspect_where';
			$columns['location'] = Q::view('Communities/content/location.php');
		} elseif ($tabName == 'profile') {
			//****************** Profile **************************
			Q_Response::addStylesheet('{{Communities}}/css/columns/profile.css');
			$tabClasses['profile'] = 'Q_aspect_who';
			$columns['profile'] = Q::event('Communities/profileInfo/response/content', array(
				"showLogout" => Q::ifset($tabValue, "showLogout", false)
			));
		} elseif ($tabName == 'credits' && Q_Config::get('Q', 'appInfo', 'requires', 'Assets', null)) {
			//****************** Credits **************************
			Q_Response::addStylesheet('{{Communities}}/css/columns/assetshistory.css');
			$tabClasses['credits'] = 'Assets_aspect_credits';
			$inviteCredits = Q_Config::get('Assets', 'credits', 'amounts', 'Users/inviteUser', 10);
			$myCredits = Assets_Credits::amount();
			if (!$myCredits) {
				$myCredits = 0;
			}

			$accountReady = false;
			try {
				$assetsPaymentsStripe = new Assets_Payments_Stripe();
				$accountReady = method_exists($assetsPaymentsStripe, "connectedAccountReady") ? $assetsPaymentsStripe->connectedAccountReady() : false;
			} catch (Exception $e) {}

			$columns['credits'] = Q::view('Communities/content/credits.php', @compact(
				'inviteCredits',
				'myCredits',
				'tab1',
				'accountReady'
			));
		}
	}

	// collect tabs from other hooks
	Q::event("Communities/me/tabs", @compact("columns"), "after", false, $columns);

	//****************** ME **************************
	$tabAttributes = array();
	$tabsOrdering = Q_Config::get('Communities', 'me', 'tabsOrdering', array());
	if ($tabsOrdering) {
		$tabs2 = array();
		foreach ($tabsOrdering as $tabName) {
			$tabs2[$tabName] = $tabs[$tabName];
		}
		$tabs = $tabs2;
	}

	// collect any app-defined tab content
	foreach ($tabs as $tabName => $info) {
		if (!empty($info['classes'])) {
			$tabClasses[$tabName] = $info['classes']." Communities_tab";
		}
		if (!empty($info['attributes'])) {
			$tabAttributes[$tabName] = $info['attributes']." Communities_tab";
		}
		if (!empty($info['event'])) {
			$columns[$tabName] = Q::event($info['event'], compact('user', 'app', 'uri'));
		} else if (!empty($info['view'])) {
			$columns[$tabName] = Q::view($info['view'], compact('user', 'app', 'uri'));
		}
	}

	$identifierTypes = explode(',',
		Q_Config::get('Users', 'login', 'identifierType', '')
	);
	$hasMobile = in_array('mobile', $identifierTypes);
	$hasEmail = in_array('email', $identifierTypes);
	$using = Q_Config::get('Users', 'login', 'using', '');
	$using = is_array($using) ?: explode(',', $using);
	$hasWeb3 = in_array('web3', $using);
	$roles = Users::roles(Users::currentCommunityId(true));
	$tab = Q::ifset($options, 'tab', Q::ifset($uri, 'tab', $defaultTab));
	$tab1 = Q::ifset($options, 'tab1', Q::ifset($uri, 'tab1', null));

	$column = Q::view('Communities/column/me.php', @compact(
		'user', 'columns', 'tab', 'tab1', 'tabs', 'tabClasses',
		'tabAttributes', 'hasMobile', 'hasEmail', 'hasWeb3', 'roles'
	));

	Communities::$columns['me'] = array(
		'title' => $title,
		'column' => $column,
		'close' => false,
		'url' => Q_Uri::url($url)
	);
 
	$url = Q_Uri::url("Communities/profile userId=$userId");

	if (!empty($tabs['myqr'])) {
		// no need to generate this unless we show the myqr tab
		$userInviteUrl = Streams::userInviteUrl($userId, $url);
		Q_Response::setScriptData('Q.Communities.userInviteUrl', $userInviteUrl, '');
	}

	Q_Response::setScriptData("Q.plugins.Calendars.capability", Calendars::capability($user->id)->exportArray());
	Q_Response::addStylesheet('{{Communities}}/css/columns/me.css');
}