<div class="Communities_profile_section Communities_top_controls Q_current" id="Communities_profile_people">
	<?php echo Q::tool('Streams/userChooser', array(
		'placeholder' => $people['SearchByName'],
		'onChoose' => 'Q.Communities.openUserProfile'
	)) ?>
	<?php echo Q::tool('Users/list', array(
		'userIds' => $userIds,
		'clickable' => true,
		'avatar' => array('icon' => 80)
	), "communityProfile") ?>
	<?php if ($can["manageContacts"] && count($can['grant'])) : ?>
		<button class="Q_button Q_aspect_who Communities_manage_contacts"><?php echo Q::text($profile['inviteUsers']) ?></button>
	<?php endif; ?>
</div>

