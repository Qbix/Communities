<?php
echo Q::tool('Streams/related', array(
	'publisherId' => $user->id,
	'streamName' => 'Streams/user/interests',
	'relationType' => 'My/gallery',
	'editable' => true,
	'closeable' => true,
	'realTime' => false,
	'sortable' => true,
    'previewOptions' => array(
		'imagepicker' => array(
			'showSize' => '200x',
			'saveSizeName' => "Streams/image",
			'save' => "Streams/image"

		),		
        'editable' => false,
		'closeable' => true
    ),
	'.Streams_image_preview_tool' => array(
		'dontSetSize' => true
	),
	'creatable' => array(
		'Streams/image' => array(
	    	'title' => ""
        )
	)
), 'Communities_gallery');
?>
