<div class="Communities_conversations_column">
    <?php if ($loggedUser && $columnsStyle == 'classic'): ?>
        <div id="Communities_new_conversation" class="Communities_buttons">
            <button id="Communities_new_conversation_button" class="Q_button Streams_aspect_chats">
                <?php echo $conversations['New'] ?>
            </button>
        </div>
	<?php endif; ?>
    <div class="Communities_conversations Communities_column_flex">
        <?php if (empty($relations)): ?>
            <div class="Communities_no_items">
                <?php echo $conversations['NoneYet'] ?>
            </div>
        <?php else: ?>
            <?php foreach ($relations as $r) {
                $fromPublisherId = $r->fromPublisherId;
                $fromStreamName = $r->fromStreamName;
                Streams::arePublic(array(
                    $fromPublisherId => array(
                        $fromStreamName => true
                    )
                ));
                echo Q::tool(array(
                    "Streams/preview" => array(
                        'publisherId' => $fromPublisherId,
                        'streamName' => $fromStreamName,
                        'closeable' => false,
                        'editable' => false,
                        'public' => true
                    ),
                    $r->type."/preview" => array(
                        'hideIfNoParticipants' => false,
                        'publisherId' => $r->fromPublisherId,
                        'streamName' => $r->fromStreamName
                    )
                ), array(
                	'id' => Q_Utils::normalize(
						$r->fromPublisherId . ' ' . $r->fromStreamName
					),
					'lazyload' => true
                ));
            }?>
        <?php endif; ?>
    </div>
</div>