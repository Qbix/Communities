<?php
$streamName = 'Streams/user/urls';
$relationType = 'Websites/webpage';
$relations = Streams_relatedTo::select()->where(array(
    'toPublisherId' => $userId,
    'toStreamName' => $streamName,
    'type' => $relationType
))->fetchDbRows();

if (($userId == $loggedInUserId && !$anotherUser) || !empty($relations)) {
?>
<div class="Communities_profile" data-val="links">
    <h2><?php echo $profile['MyLinks'] ?></h2>

    <?php echo Q::Tool('Streams/related', array(
		'publisherId' => $userId,
		'streamName' => $streamName,
		'relationType' => $relationType,
		'editable' => true,
		'realtime' => $userId != $loggedInUserId ? true : false,
		'sortable' => $userId == $loggedInUserId ? array() : false
	), 'Communities_profile_links'); ?>

    <?php
        if ($userId == $loggedInUserId) {
			echo Q::Tool('Websites/webpage/composer', array(
				'categoryStream' => array(
				    'publisherId' => $loggedInUserId,
                    'streamName' => 'Streams/user/urls',
					'relationType' => 'Websites/webpage'
                ),
			));
        }
    ?>
</div>
<?php } ?>