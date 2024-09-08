<div id="content">
    <h2 class="Communities_interests_prompt">
		<?php echo Q_Html::text($interests['prompt']) ?>
    </h2>
    <div class="Communities_interests_explanation">
		<?php echo Q_Html::text($interests['explanation'])?>
    </div>
	<?php echo Q::tool('Streams/interests', array(
		'filter' => $interests['filter'],
		'canAdd' => true,
		'communityId' => Users::communityId()
	)) ?>
</div>