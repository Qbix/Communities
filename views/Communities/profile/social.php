<?php
$ownUser = ($userId == $loggedInUserId) && !$anotherUser;
if ($ownUser || count($xids)) {
?>
    <ul class="Communities_profile" data-val="social">
    <?php
    foreach ($supportedSocials as $supportedSocial) {
		$value = Q::ifset($xids, $supportedSocial.'/'.$app, null);
		if ($ownUser || $value) {
			echo '<i class="Communities_social_icon" data-type="'.$supportedSocial.'" data-connected="'.$value.'"></i>';
		}
	}
    ?>
    </ul>
<?php } ?>