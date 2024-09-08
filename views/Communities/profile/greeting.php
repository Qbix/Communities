<div class="Communities_profile_greeting" data-val="greeting"><?php
if ($userId != $loggedInUserId || $anotherUser) {
    echo Q_Html::text($greeting->content, array("\n"));
} else {
    echo Q::tool('Streams/inplace', array(
        'stream' => $greeting,
        'inplaceType' => 'textarea',
        'inplace' => array(
            'placeholder' => $profile['aboutPlaceholder'],
            'editing' => empty($greeting->content),
            'showEditButtons' => true,
            'selectOnEdit' => false
        ),
        'convert' => array("\n")
    ), uniqid());
} ?>
</div>