<?php
// remove execution time limit
set_time_limit(0);

$FROM_APP = defined('RUNNING_FROM_APP'); //Are we running from app or framework?

#Arguments
$argv = $_SERVER['argv'];
$count = count($argv);

#Usage strings
$usage = "Usage: php {$argv[0]} " . ($FROM_APP ? '' : '<app_root> '). ' [amount] [city]';

if(!$FROM_APP) {
	$usage.=PHP_EOL.PHP_EOL.'<app_root> must be a path to the application root directory';
}

$usage = <<<EOT
$usage

Parameters:

[amount]
Amount of events to create.
If omitted, default 10.

[city]
City where events will occur. For example "New York, USA", "Moscow, RU".
If omitted, default "New York, USA".

EOT;

// get all CLI options
$params = array(
	'h::' => 'help::',
	'a::' => 'amount::',
	'c::' => 'city::'
);
$options = getopt(implode('', array_keys($params)), $params);
if (empty($options['amount'])) {
	$options['amount'] = 10;
}
if (empty($options['city'])) {
	$options['city'] = "New York, USA";
}

$help = <<<EOT

Script to create demo events in particular app.

Options include:

--amount          Amount of events to create. If omitted, default 10.
           
--city            City where events occur. For example "New York", "Washington". 
                  If omitted, default "New York, USA".
EOT;

#Is it a call for help?
if (isset($argv[1]) and in_array($argv[1], array('--help', '/?', '-h', '-?', '/h')))
	die($help);

#Check primary arguments count: 2 if running /app/scripts/Q/invite.php, 3 if running /framework/scripts/invite.php
if ($count < ($FROM_APP ? 0 : 1))
	die($usage);

#Read primary arguments
$LOCAL_DIR = $FROM_APP ? APP_DIR : $argv[1];

$app = Q::app();
$amount = (int)$options['amount'];
$city = $options['city'];
$googleKey = Q_Config::expect('Places', 'google', 'keys', 'server');

echo "Starting to create " . $amount . " events located in " . $city . PHP_EOL;

// Collect Interests
$community = Users::communityId();

echo "Fetching users from system...". PHP_EOL;
$users = Users_User::select()->where(array('signedUpWith != ' => 'none'))->fetchDbRows();
$usersCount = count($users);
if ($usersCount <= 0) {
	die('[ERROR] users not found' . PHP_EOL);
}
echo 'Collected ' . $usersCount . ' users.' . PHP_EOL;

echo "Fetching interests for community " . $community . '...' . PHP_EOL;

$interestsSrc = Streams::interests($community);
recursiveUnset($interestsSrc, '#');
// reformat interests array
foreach ($interestsSrc as $key => $interest) {
	$interestsSrc[$key] = array_keys(reset($interest));
}
$interests = array();
foreach($interestsSrc as $key => $interestSrc) {
	foreach ($interestSrc as $interest) {
		$interests[] = $key.': '.$interest;
	}
}

$interestsCount = count($interests);

if ($interestsCount <= 0) {
	die('[ERROR] interests not found' . PHP_EOL);
}

echo 'Collected ' . $interestsCount . ' interests' . PHP_EOL;

// collect location for users
$query = http_build_query(array(
	'key' => $googleKey,
	'placeid' => Places::autocomplete($city)[0]['place_id']
));

echo "Request google to get location of " . $city . '...' . PHP_EOL;

// center around which events will place
$location = json_decode(Places::getRemoteContents("https://maps.googleapis.com/maps/api/place/details/json?$query"), true);
$centerLocation = implode(',', $location['result']['geometry']['location']);

echo "Location = " . $centerLocation . PHP_EOL;

// locations for each event
$location = $locations = array();

echo 'Fetching ' . $amount . ' locations from google...' . PHP_EOL;

// collect locations for events
while (count($locations) < $amount) {
	if ($next_page_token = Q::ifset($location, "next_page_token", null)) {
		$location = nearbySearch ($googleKey, null, $next_page_token);
	} else {
		$location = nearbySearch ($googleKey, $centerLocation, null);
	}

	$locations = array_merge($locations, $location);

	echo 'Received ' . count($locations) . PHP_EOL;
}

// calculate participants amount depend of total users amount
$possibleParticipantsAmount = array();
for ($i = 2; $i <= 4; $i++) {
	if ($usersCount >= $i) {
		$possibleParticipantsAmount[] = $i;
	}
}

// include Streams/interest/post handler to simulate POST request when interest will selected
Q::includeFile(implode(DS, array(
	Q_PLUGINS_DIR, 'Streams', 'handlers', 'Streams', 'interest', 'post.php'
)));

echo '===Starting to create events===' . PHP_EOL;

// start to create events
for ($i = 0; $i < $amount; $i++) {
	echo 'Event ' . ($i+1). ':' . PHP_EOL;

	// get publisher
	$publisher = $i < $usersCount ? $users[$i] : $users[array_rand($users)];
	echo "\t" . 'publisher = ' . $publisher->displayName() . '(id='.$publisher->id.')' . PHP_EOL;
	Users::setLoggedInUser($publisher);

	// start time from current + week + $i * days
	$startTime = time() + 604800 + $i * 86400;
	echo "\t" . 'start time = ' . date("j M Y h:i:s a", $startTime) . PHP_EOL;

	// get location
	$placeId = $locations[$i]['place_id'];
	echo "\t" . 'location = ' . $locations[$i]['name'] . '(place_id='.$placeId.')' . PHP_EOL;

	// get interest
	// get each interest, then get random interest
	$interest = $i < $interestsCount ? $interests[$i] : $interests[array_rand($interests)];
		// simulate POST request for interest
		$_REQUEST['title'] = $interest;
		Streams_interest_post();
	echo "\t" . 'interest = ' . $interest . PHP_EOL;

	$event = Calendars_Event::create(array(
		'interestTitle' => $interest,
		'placeId' => $placeId,
		'startTime' => $startTime,
		'peopleMin' => 2,
		'peopleMax' => 10
	));

	echo "\t" . 'event created, streamName = ' . $event->name . PHP_EOL;

	$participantsAmount = $possibleParticipantsAmount[array_rand($possibleParticipantsAmount)];
	$participantsUsers = array($publisher->id);
	// select random users
	while (count($participantsUsers) < $participantsAmount) {
		$tempUser = $users[array_rand($users)];
		if (!in_array($tempUser->id, $participantsUsers)) {
			$participantsUsers[] = $tempUser->id;
		}
	}

	// participate users to event
	foreach ($participantsUsers as $participantsUser) {
		Calendars_Event::rsvp($event, $participantsUser);
	}
	echo "\t" . count($participantsUsers) . ' users participated' . PHP_EOL;
}

/**
 * Search google places around some location
 * @method nearbySearch
 * @param {string} $googleKey
 * @param {string} $location
 * @param {string} $next_page_token
 * @return array google request results
 */
function nearbySearch ($googleKey, $location = null, $next_page_token = null) {
	// query for nearbysearch
	if (!empty($next_page_token)) {
		$query = http_build_query(array(
			'key' => $googleKey,
			'pagetoken' => $next_page_token
		));
	} elseif (!empty($location)) {
		$query = http_build_query(array(
			'key' => $googleKey,
			'location' => $location,
			'rankby' => 'distance'
		));
	} else {
		die('[ERROR] nearbySearch: $location and $next_page_token empty' . PHP_EOL);
	}

	$location = json_decode(Places::getRemoteContents("https://maps.googleapis.com/maps/api/place/nearbysearch/json?$query"), true);
	$status = Q::ifset($location, "status", null);
	$results = Q::ifset($location, "results", null);

	if ($status != "OK") {
		die('[ERROR] google request status: ' . $status . PHP_EOL);
	} elseif (empty($results)) {
		die('[ERROR] google results empty' . PHP_EOL);
	}

	return $results;
}

/**
 * Recursively remove element with some key from array
 * @method recursiveUnset
 * @param {array} $array
 * @param {string} $unwantedKey
 */
function recursiveUnset (&$array, $unwantedKey) {
	unset($array[$unwantedKey]);
	foreach ($array as &$value) {
		if (is_array($value)) {
			recursiveUnset($value, $unwantedKey);
		}
	}
}