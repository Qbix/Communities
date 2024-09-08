<div class="Communities_profile_section Communities_profile_chat_container <?= strcasecmp($currentTab, "chat") == 0 ? "Q_current" : "" ?>"><?php if (!empty($chatStream)): ?>
    <?php echo Q::tool("Streams/chat", array(
        'publisherId' => $chatStream->publisherId,
        'streamName' => $chatStream->name,
        array('templates' => array(
            'main' => array(
                'fields' => array(
                    'placeholder' => $profile['chats']['placeholder']
                )
            )
        ))
    ), uniqid()) ?>
<?php endif; ?></div>