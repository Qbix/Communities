<div class="Communities_availabilities_column" data-emptyServices="<?=empty($relations)?>">
	<?php if ($columnsStyle == 'classic'): ?>
        <div id="Communities_new_service" class="Communities_top_controls">
            <input name="query" value="" type="text" id="Communities_serviceChooser_input" class="Communities_serviceChooser_input" placeholder="<?=$text['services']['filterServices']?>">
            <?php if (!empty(Communities::newEventAuthorized())) { ?>
                <button id="Communities_new_service_button" class="Q_button Q_aspect_when"><?=$services['NewAvailability']?></button>
            <?php } ?>
        </div>
	<?php endif; ?>

    <div class="Communities_no_items">
        <?=$services['NoneYet']?>
    </div>
    <div class="Communities_availabilities Communities_column_flex">
        <?php foreach ($relations as $relation) {
            echo Q::tool(array(
                "Streams/preview" => array(
                    'publisherId' => $relation->fromPublisherId,
                    'streamName' => $relation->fromStreamName,
                    'closeable' => false
                ),
                "Calendars/availability/preview" => array(
                    'textfill' => $textfill
                )
            ), array(
				'id' => Q_Utils::normalize(
					$relation->fromPublisherId . ' ' . $relation->fromStreamName
				),
				'lazyload' => true
			));
        } ?>
    </div>
</div>