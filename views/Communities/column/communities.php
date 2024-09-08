<div class="Communities_top_controls">
<?php
	echo Q::tool('Streams/userChooser', array(
		'placeholder' => $text['communities']['FindCommunityByName'],
		'onChoose' => 'Q.Communities.openCommunityProfile',
	    'communitiesOnly' => true,
		'hideUntilParticipants' => false
	), uniqid());

	if (!$skipComposer) {
		echo Q::tool("Communities/composer");
    }

	echo Q::tool("Users/list", array(
	    'userIds' => $communities,
	    'limit' => 1000,
	    'clickable' => true,
        'avatar' => array('icon' => 80)
	), uniqid());
?>
</div>