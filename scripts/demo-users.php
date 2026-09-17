<?php
$FROM_APP = defined('RUNNING_FROM_APP'); //Are we running from app or framework?
ini_set('max_execution_time', 0);

#Arguments
$argv = $_SERVER['argv'];
$count = count($argv);

#Usage strings
$usage = "Usage: php {$argv[0]} " . ($FROM_APP ? '' : '<app_root> '). ' [amount] [city] [community]';

if(!$FROM_APP) {
	$usage.=PHP_EOL.PHP_EOL.'<app_root> must be a path to the application root directory';
}

$usage = <<<EOT
$usage

Parameters:

[amount]
Amount of users to create.
If omitted, default 10.

[city]
City where users will live. For example "New York", "Washington".
If omitted, default "New York".
For more precision, set country too. For example "Moscow, RU". 

[community]
Community user participated.

EOT;

// get all CLI options
$params = array(
	'h::' => 'help::',
	'a::' => 'amount::',
	'c::' => 'city::',
	'co::' => 'community::'
);
$options = getopt(implode('', array_keys($params)), $params);

$help = <<<EOT

Script to create fake users in particular app.

Options include:

--amount          Amount of users to create. If omitted, default 10.

--community       Community where new users will participated. Bu default using main community.
           
--city            City where users will live. For example "New York, USA", "Moscow, RU". 
                  If omitted, default "New York, USA".
EOT;

#Is it a call for help?
if (isset($argv[1]) and in_array($argv[1], array('--help', '/?', '-h', '-?', '/h')))
	die($help);

#Check primary arguments count: 2 if running /app/scripts/Q/demo-users.php, 3 if running /framework/scripts/demo-users.php
if ($count < ($FROM_APP ? 1 : 2))
	die($usage);

#Read primary arguments
$LOCAL_DIR = $FROM_APP ? APP_DIR : $argv[1];

$app = Q::app();
$amount = (int)Q::ifset($options,'amount', 10);
$city = Q::ifset($options,'city', 'New York, USA');
$community = Q::ifset($options,'community', Users::communityId());

// same email for all demo users
$emailTemplate = "@engageusers.ai";

echo "Starting to create " . $amount . " users located in " . $city . PHP_EOL;

echo "Request google to get location of " . $city . PHP_EOL;
// collect location for users
$location = Places::autocomplete($city);
$query = http_build_query(array(
	'key' => Q_Config::expect('Places', 'google', 'keys', 'server'),
	'placeid' => $location[0]['place_id']
));
$location = json_decode(Places::getRemoteContents("https://maps.googleapis.com/maps/api/place/details/json?$query"), true);
$status = Q::ifset($location, "status", null);
if ($status != "OK") {
	die('[ERROR] google request status: ' . $status . PHP_EOL);
}

$address_components = $location['result']['address_components'];
$country = null;

echo "Location = " . $location['result']['name'] . '(' . implode(',', $location['result']['geometry']['location']) . ')' . PHP_EOL;

// get country
foreach($address_components as $address_component) {
	if (in_array('country', $address_component['types'])) {
		$country = $address_component['short_name'];
		break;
	}
}

echo "Country = " . $country . PHP_EOL;

// random names
$firstNames = array(
	'male' => array(
		// Ashkenazi / Modern Orthodox / UWS
		'Ari','Aharon','Avi','Benjamin','Daniel','David','Eitan','Elie','Ezra',
		'Gabe','Hillel','Isaac','Jacob','Jeremy','Joel','Jonah','Joshua','Judah',
		'Levi','Micah','Noam','Reuven','Sam','Shai','Tzvi','Yonatan','Yosef',

		// Russian & Ukrainian Jewish NYC
		'Alexei','Lev','Misha','Maxim','Sasha','Maks','Arseny','Igor','Mikhail',
		'Daniil','Roman','Yuri','Vadim','Boris',

		// Israeli-American
		'Eliav','Omer','Idan','Lior','Tal','Eyal','Nadav','Yonah','Itai',

		// Persian/Bukharian Jewish NYC
		'Benyamin','Doron','Ariel','Oren','Shimon','Nissan'
	),

	'female' => array(
		// Ashkenazi / Modern Orthodox / UWS
		'Leah','Sara','Miriam','Rebecca','Talia','Naomi','Rachel','Devorah','Avigail',
		'Shoshana','Hadassah','Eliana','Ayelet','Rivka','Chana','Esther','Dina',
		'Tamar','Ronit','Yael','Shira','Batya',

		// Russian & Ukrainian Jewish NYC
		'Alina','Mila','Daria','Larisa','Svetlana','Ksenia','Liana','Yelena',
		'Marina','Anya','Polina',

		// Israeli-American
		'Avital','Galit','Shani','Noa','Sharon','Hila','Orly','Roni','Gili',

		// Persian/Bukharian Jewish NYC
		'Roxana','Liraz','Mazal','Sima','Mor','Nava'
	)
);

$lastNames = array(
	// Classic Ashkenazi & UWS
	'Cohen','Levi','Katz','Katzman','Goldstein','Goldberg','Goldfarb',
	'Stein','Steinberg','Weiss','Weissman','Rosen','Rosenberg','Rosenthal',
	'Bernstein','Adler','Friedman','Feldman','Kaplan','Kessler','Shapiro',
	'Rubin','Rabinowitz','Greenberg','Silverman','Hoffman','Klein',

	// Russian / Ukrainian Jewish NYC
	'Feldman','Sokolov','Abramov','Kogan','Vayner','Malakhov','Spector',
	'Federman','Markov','Kozlov','Novik','Zaitsev','Volkov',

	// Israeli last names
	'Ben-Ari','Ben-David','Shalev','Rahmani','Mizrahi','Carmi','Navon',
	'Eldar','Gilon','Hadad','Peretz','Barak','Sharabi',

	// Persian / Bukharian Jewish NYC
	'Rahmani','Mizrahi','Dayan','Yaghoobyan','Rahamim','Azizi','Benisti',
	'Almaleh','Yerushalmi'
);
$uniqueNames = array();
$uniqueFaces = array();

$radius = Q_Config::expect('Places', 'nearby', 'defaultMeters');

echo 'Starting to create users.' . PHP_EOL;


for ($i = 1; $i <= $amount; $i++) {
	echo 'User ' . $i . ':' . PHP_EOL;

	$gender = rand() % 2 ? 'male' : 'female';

	// get random indexes for first and last names
	$firstIndex = rand ( 0 , count($firstNames[$gender]) -1);
	$lastIndex = rand ( 0 , count($lastNames) -1);

	// if duplicated - random again
	$uniqueKey = implode(',', array($gender, $firstIndex, $lastIndex));
	while(!empty($uniqueNames[$uniqueKey])) {
		$firstIndex = rand ( 0 , count($firstNames) -1);
		$lastIndex = rand ( 0 , count($lastNames) -1);
	}
	$uniqueNames[$uniqueKey] = true;
	
	// result name
	$name = $firstNames[$gender][$firstIndex] . ' ' . $lastNames[$lastIndex];

	echo "\t" . ' name = ' . $name . PHP_EOL;

	$email = uniqid().$emailTemplate;

	echo "\t" . ' email = ' . $email . PHP_EOL;

	// create user
	$user = Streams::register($name, $email, function () use ($gender) {
		$dir = COMMUNITIES_PLUGIN_FILES_DIR . DS . 'Communities' . DS . 'faces' . DS . $gender;
		$files = glob("$dir/*");
		for ($i = 0; $i < 10000; ++$i) {
			$index = rand() % count($files);
			$uniqueKey = implode(',', array($gender, $index));
			if (empty($uniqueFaces[$uniqueKey])) {
				break;
			}
		}
		$uniqueFaces[$uniqueKey] = true;
		echo "\t choosing faces/$gender/" . basename($files[$index]) . PHP_EOL;
		return $files[$index];
	}, array(
		'activation' => false,
		'idPrefix' => 'bot-',
		'leaveDefaultIcon' => false
	));

	// set sessionCount to make this user appear on the site
	$user->sessionCount = 1;
	$user->save();

	echo "\t" . ' user created' . PHP_EOL;

	// set logged user to complete Communities plugin actions
	// (join Streams/experience/main stream)
	Users::setLoggedInUser($user);

	echo "\t" . ' user set as logged in' . PHP_EOL;
	echo "\t" . ' setting location...' . PHP_EOL;
	// set user location
	$locationStream = Places_Location::userStream();
	$locationStream->setAttribute("latitude", $location['result']['geometry']['location']['lat']);
	$locationStream->setAttribute("longitude", $location['result']['geometry']['location']['lng']);
	$locationStream->setAttribute("placeName", $location['result']['name']);
	$locationStream->setAttribute("country", $country);
	$locationStream->setAttribute("meters", $radius);
	$locationStream->save();
	echo "\t" . ' location defined to ' . implode(',', $location['result']['geometry']['location']) . ' with radius ' . $radius . PHP_EOL;

	echo "\t" . ' participating to '.$community.' : Streams/experience/main ...' . PHP_EOL;
	// participate user to main community
	Streams::join($user->id, $community, array("Streams/experience/main"), array("skipRelationMessages" => true));

	Users::logout();
}