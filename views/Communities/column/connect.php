<div class="Communities_connect">

	<?php if ($loggedUser): ?>

		<div class="Communities_connect_qr">
			<?php echo Q::tool("Streams/qrConnect", array(
				'size' => 260
			)) ?>
		</div>

		<?php if ($visitsStream): ?>
			<div class="Communities_connect_section Communities_connect_visitors">
				<h3><?php echo Q_Html::text(
					Q::ifset($text, 'connect', 'Visitors', 'Who scanned your code')
				) ?></h3>
				<?php echo Q::tool("Streams/participants", array(
					'publisherId' => $visitsStream->publisherId,
					'streamName' => $visitsStream->name,
					'invite' => false,
					'showSummary' => true,
					'showBlanks' => false,
					'hideIfNoParticipants' => false,
					'maxShow' => 20,
					'avatar' => array('short' => false, 'icon' => 40)
				)) ?>
			</div>
		<?php endif ?>

	<?php else: ?>

		<div class="Communities_connect_loggedOut">
			<?php echo Q_Html::text(
				Q::ifset($text, 'connect', 'LogInToConnect',
					'Log in to show your code')
			) ?>
		</div>

	<?php endif ?>

	<div class="Communities_connect_section Communities_connect_conversations">
		<h3><?php echo Q_Html::text(
			Q::ifset($text, 'connect', 'Conversations', 'Public conversations')
		) ?></h3>
		<?php foreach ($relations as $relation) {
			// same preview pair the conversations column renders
			echo Q::tool(array(
				"Streams/preview" => array(
					'publisherId' => $relation->fromPublisherId,
					'streamName' => $relation->fromStreamName,
					'closeable' => false,
					'editable' => false
				),
				$relation->type."/preview" => array(
					'hideIfNoParticipants' => false,
					'publisherId' => $relation->fromPublisherId,
					'streamName' => $relation->fromStreamName
				)
			), Q_Utils::normalize(
				$relation->fromPublisherId . ' ' . $relation->fromStreamName
			));
		} ?>
	</div>

</div>
