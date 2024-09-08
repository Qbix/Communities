<?php echo Q::tool("Q/gallery", array(
    'transition' => array (
        'duration' => 1000
    ),
    'interval' => array(
        'duration' => 5000
    ),
    'images' => $gallery
), 'welcome')?>

<?php
    $adminsAmount = count($admins);
    if ($adminsAmount) {
        echo Q::tool("Users/list", array(
			"userIds" => $admins,
			//"limit" => Q_Request::isMobile() ? 4 : 20,
			"avatar" => array("icon" => 200, "short" => true),
        ), array(
            "attributes" => array(
                    "data-amount" => $adminsAmount
            ),
            "classes" => "Communities_welcome_admins",
			"id" => "admins"
        ));
    }
?>

<div class="Q_button getStarted Q_pulsate"><?= Q::text($welcome['GetStarted']) ?></div>

<div class="Communities_user_count">
    <span><?= $users_count ?></span> users,
    <span><?= $events_count ?></span> events,
    <span><?= $rsvps_count ?></span> RSVPs
</div>
<?php
    if ($usersList) {
		echo Q::tool($usersList["type"], array(
			"userIds" => $userIds,
			"avatar" => array("icon" => Q_Request::isMobile() ? 50 : 80, "short" => true),
			"limit" => 100,
			"clickable" => true
		), array(
		    "classes" => "Communities_welcome_usersList",
            "id" => "common"
        ));
    }
?>