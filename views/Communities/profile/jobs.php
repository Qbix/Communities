<?php
	$stream = Streams_Stream::fetch($userId, $userId, 'Streams/user/jobs');
	
	if ($stream instanceof Streams_Stream
	&& (($userId == $loggedInUserId && !$anotherUser) || !empty($stream->content))) {
?>
<div class="Communities_profile" data-val="jobs">
	<h2><?php echo $profile['MyJobs']; ?></h2>
	<?php echo Q::Tool('Streams/inplace', array(
		'inplaceType' => 'textarea',
		'publisherId' => $userId,
		'streamName' => 'Streams/user/jobs',
		'inplace' => array(
			'placeholder' => $profile['JobsPlaceholder'],
			'minWidth' => '100%'
		)
	), 'Communities_profile_jobs');?>
</div>
<?php } ?>