<div class="Communities_schedule_column">
    <div class="Communities_subscribe"></div>
	<?php if (empty($participating)): ?>
		<div class="Communities_no_items">
			<?php echo $schedule['NoneYet'] ?> 
			<div class="Communities_buttons Communities_column_flex">
				<button class="Q_button Communities_events_link Q_aspect_when">
					<?php echo $schedule['AddSome'] ?>
				</button>
			</div>
		</div>
	<?php else: ?>
	    <div class="Communities_tabs">
	        <div class="Communities_tab <?php echo $scheduleSubTab == 'past' ? 'Q_current' : '' ?>" data-val="past">
	            <?php echo $schedule['past'] ?>
	        </div>
	        <div class="Communities_tab <?php echo $scheduleSubTab == 'future' ? 'Q_current' : '' ?>" data-val="future">
	            <?php echo $schedule['future'] ?>
	        </div>
	    </div>
	    <div class="Communities_schedule_column_tabContent <?php echo $scheduleSubTab == 'future' ? 'Q_current' : '' ?>" data-val="future">
            <div class="Communities_column_flex">
	        <?php if (empty($futureEvents)): ?>
	            <div class="Communities_no_items">
	                <?php echo $schedule['NoneYet'] ?>
					<div class="Communities_buttons">
						<button class="Q_button Communities_events_link Q_aspect_when">
							<?php echo $schedule['AddSome'] ?>
						</button>
					</div>
	            </div>
	        <?php else: ?>
		        <?php foreach ($futureEvents as $tool): ?>
			        <?php echo $tool ?>
		        <?php endforeach; ?>
				<div class="Communities_buttons">
					<button class="Q_button Communities_events_link Q_aspect_when">
						<?php echo $schedule['AddSome'] ?>
					</button>
				</div>
	        <?php endif; ?>
            </div>
	    </div>
	    <div class="Communities_schedule_column_tabContent <?php echo $scheduleSubTab == 'past' ? 'Q_current' : '' ?>" data-val="past">
            <div class="Communities_column_flex">
			<?php if (empty($pastEvents)): ?>
	            <div class="Communities_no_items">
					<?php echo $schedule['NoneYet'] ?>
					<div class="Communities_buttons">
						<button class="Q_button Communities_events_link Q_aspect_when">
							<?php echo $schedule['AddSome'] ?>
						</button>
					</div>
	            </div>
			<?php else: ?>
				<?php foreach ($pastEvents as $tool): ?>
					<?php echo $tool ?>
				<?php endforeach; ?>
			<?php endif; ?>
            </div>
	    </div>
	<?php endif; ?>
</div>