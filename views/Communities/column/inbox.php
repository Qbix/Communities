<div class="Communities_buttons Communities_column_flex">
    <button class="Q_button Communities_invite">
		<?php echo "\u{FF0B}" ?>
		<?php echo $inbox['AddNewContacts'] ?>
    </button>
</div>
<div class="Communities_inbox_column Communities_column_flex">
	<?php if (empty($participating)) : ?>
	    <div class="Communities_no_items">
			<div>
	        	<?php echo $inbox['NoneYet'] ?> 
			</div>
	    </div>
	<?php else: ?> 
    <?php
		foreach ($participating as $p){
            echo Q::tool(array(
                "Streams/preview" => array(
                    'publisherId' => $p->publisherId,
                    'streamName' => $p->name,
                    'closeable' => false,
	                'editable' => false,
                ),
	            $p->type."/preview" => array(
                    'publisherId' => $p->publisherId,
					'streamName' => $p->name
				)
            ), array(
            	'id' => Q_Utils::normalize($p->publisherId.' '.$p->name.' chat'),
				'lazyload' => true
            ));
		}
	?>
	<?php endif; ?>
</div>
<div class="Communities_buttons">
    <button class="Q_button Communities_people_link Streams_aspect_chats">
		<?php echo $inbox['FindPeople'] ?>
    </button>
    <button class="Q_button Communities_conversations_link Streams_aspect_chats">
		<?php echo $inbox['GroupConversations'] ?>
    </button>
</div>
