<?php
	$stream = Streams_Stream::fetch($userId, $userId, 'Streams/user/education');

	if ($stream instanceof Streams_Stream
	&& (($userId == $loggedInUserId && !$anotherUser) || !empty($stream->content))) {
?>
<div class="Communities_profile" data-val="education">
	<h2><?php echo $profile['MyEducation']; ?></h2>
	<?php echo Q::Tool('Streams/inplace', array(
		'inplaceType' => 'textarea',
		'publisherId' => $userId,
		'streamName' => 'Streams/user/education',
		'inplace' => array(
			'placeholder' => $profile['EducationPlaceholder'],
			'minWidth' => '100%'
		)
	), 'Communities_profile_education');?>
</div>
<?php } ?>