<div id="content">
    <p><?php echo Q::text($contribute['ContributeExplanation'], @compact('communityName')) ?></p>

    <p><?php echo Q::text($contribute['ReasonToContribute'], @compact('communityName')) ?></p>

    <div class="contribute">
		<?php echo Q_Html::smartTag('select', array('name' => 'contribute', 'autocomplete' => 'off'), $amount, $amounts) ?>
		<?php
			echo Q::tool("Assets/payment", array(
				'payments' => 'stripe',
				'amount' => $amount,
				'currency' => $currency,
	            'description' => $description
			));
		?>
    </div>
</div>
