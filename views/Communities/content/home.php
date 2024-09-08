<div id="content">
	<div class="Communities_account">
		<?php echo Q::tool('Users/avatar', array(
			'icon' => true,
			'editable' => true
		), 'home') ?>
		<div id="Communities_account_logout">
			<div id="Communities_logout">
				log out
			</div>
		</div>
		<div id="Communities_account_identifiers">
			<?php foreach ($identifiers as $type => $html): ?> 
				<div class="Communities_account_identifier" data-type="<?php echo $type ?>">
					<?php echo Q_Html::img("img/white/$type-64.png", $type) ?> 
					<?php echo $html ?> 
				</div>
			<?php endforeach; ?> 
		</div>
	</div>
	<?php if ($isAdmin): ?>
		<div class="Communities_admin">
			<?php echo Q::tool('Streams/related', array(
				'publisherId' => Users::communityId(),
				'streamName' => 'Streams/category/admins',
				'relationType' => 'Websites/announcements',
				'creatable' => array(
					'Websites/article' => array(
						'title' => 'New Announcement',
						'preprocess' => 'Q.Communities.selectUserId'
					)
				),
				'editable' => true,
				'.Websites_announcement_preview_tool' =>  array(
					'inplace' => array(
						'inplace' => array(
							'maxWidth' => 700
						)
					),
					'icon' => false
				),
				'toolName' => "Q.Communities.home.related.announcementToolName",
				//'realtime' => true
			), 'announcements') ?>
		</div>
	<?php else: ?>
		<div class="Communities_explanation">
			<?php echo Q::view('Communities/templates/home/greeting.php') ?>
		</div>
		<div class="Communities_announcements">
			<?php foreach ($streamNames as $streamName): ?>
				<?php echo Q::tool('Streams/related', array(
					'publisherId' => $communityId,
					'streamName' => $streamName,
					'relationType' => 'Websites/announcements',
					'.Websites_announcement_preview_tool' => $previewOptions,
					'toolName' => "Communities/announcement/preview",
				), Q_Utils::normalize($streamName)) ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<hr>
	<div class="Communities_shared">
		<?php echo Q::tool('Streams/related', array(
			'publisherId' => $communityId,
			'streamName' => 'Streams/category/occupants',
			'relationType' => 'Streams/shared',
			'creatable' => array(
				'Streams/file' => true,
				'Websites/article' => array(
					'title' => 'New Doc'
				),
				'Streams/category' => array(
					'title' => 'New Folder'
				)
			),
			'.Websites_article_preview_tool' => $previewOptions,
			'.Streams_file_preview_tool' => $previewOptions,
			'.Streams_category_preview_tool' => $previewOptions,
			'editable' => true,
			'sortable' => array()
			//'realtime' => true
		), 'shared') ?>
	</div>
</div>