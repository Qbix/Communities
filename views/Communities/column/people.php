<?php if ($showImport): ?>
	<?php echo Q::tool('Streams/import', array(
		'link' => 'university.csv'
	)) ?>
<?php endif ?>

<?php if ($columnsStyle == 'facebook'): ?>
	<?php echo Q::tool('Streams/userChooser', array(
		'placeholder' => $people['SearchByName'],
		'onChoose' => 'Q.Communities.openUserProfile'
	)) ?>
<?php else: ?>
    <div class="Communities_top_controls">
        <?php echo Q::tool('Streams/userChooser', array(
            'placeholder' => $people['SearchByName'],
            'onChoose' => 'Q.Communities.openUserProfile'
        )) ?>
        <button id="Communities_invite_people_button" class="Q_button Q_aspect_who">
            <?php echo $people['Invite'] ?>
        </button>
    </div>
<?php endif ?>

<?php if (!$isCordova and !$devices): ?>
<div class="Communities_find_friends">
	<button class="Communities_app_button Q_button Q_aspect_who">&#x1f50d;&nbsp;<?php echo $people['FindAllMyFriends'] ?></button>
</div>
<?php endif; ?>
<?=Q::tool('Users/list', array(
	'userIds' => $userIds,
	'avatar' => array("icon" => 80),
	'clickable' => true
))?>
<?php
/*echo Q::tool('Users/people', array(
    "limit" => 100,
	"avatar" => array(
	    "short" => true,
        "icon" => '80'
    )
))*/
?>
<div class="Communities_fade_bottom"></div>