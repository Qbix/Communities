<?php $firstSection = true;
foreach ($sections as $section => $view) {
    if (empty($view)) {
		continue;
	}
	
	if ($loggedInUserId && $userId == $loggedInUserId && !$anotherUser) {
		echo Q::Tool("Q/expandable", array(
			'title' => $profile['sections'][$section],
			'content' => $view,
			'expanded' => $firstSection
		), $section);
    } else {
        echo $view;
    }
	$firstSection = false;
} ?>
<?php if ($loggedInUserId && $userId != $loggedInUserId) : ?>
	<button class="Q_button Users_block_button" data-action="<?php echo ($blocked ? "unblock" : "block"); ?>">
		<span data-for="block"><?php echo Q::text($profile['blockUser']) ?></span>
		<span data-for="unblock"><?php echo Q::text($profile['unblockUser']) ?></span>
	</button>
<?php endif; ?>
<div class="Communities_profile_manageNotifications">
    <button class="Q_button Communities_profile_manageNotifications">
		<?php echo Q::text($profile['ManageNotifications']) ?>
    </button>
</div>

<?php if ($showLogout) : ?>
	<div class="Communities_profile_logout">
		<button class="Q_button Communities_profile_logout">
			<?php echo Q::text($profile['LogOut']) ?>
		</button>
	</div>
	<div class="Communities_profile_userId">
		<?php echo $loggedInUserId ?>
	</div>
<?php endif; ?>

