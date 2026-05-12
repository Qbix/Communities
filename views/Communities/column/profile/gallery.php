<div class="Communities_profile_section" id="Communities_profile_gallery">
	<div class="Communities_profile_explanation">
		<?php echo Q::text($profile['gallery']['Explanation']) ?>
	</div>
	<?php echo Q::tool("Streams/image/coverflow", array(
		'publisherId' => $userId,
		'streamName' => "Streams/user/interests",
		'relationType' => "My/gallery",
		'image' => array(
			'size' => "1000x"
		),
		'myInterestedArt' => $myGallery
	), uniqid()) ?>
</div>