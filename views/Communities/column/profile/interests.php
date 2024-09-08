<div class="Communities_profile_section <?= strcasecmp($currentTab, "interests") == 0 ? "Q_current" : "" ?>" id="Communities_profile_interests">
	<div class="Communities_profile_explanation">
		<?php echo Q::text($interests['Profile'], array(
			'clickOrTap' => $clickOrTap
		)) ?>
	</div>
	<?php echo Q::tool("Streams/interests", array(
		'userId' => $userId,
		'expandable' => array(
			'expanded' => true,
			'autoCollapseSiblings' => false
		)
	), uniqid()) ?>
</div>