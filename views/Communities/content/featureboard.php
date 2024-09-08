<div id="content">
    <p><?php echo $text['Text_1']?></p>
    <p><?php echo $text['Text_2']?></p>
    <p><?php echo $text['Text_3']?></p>

    <ul class="contribute">
        <?php foreach ($text['Options'] as $option) {
            $optionText = preg_replace("/\{\{1\}\}/", $app, $option['text']);
            echo '<li><span class="contribute">'.$optionText.'</span>';
            echo '<div class="contribute">';
			echo Q_Html::smartTag('select', array('name' => 'contribute', 'autocomplete' => 'off'), $option['defaultAmount'], $amounts);
			echo Q::tool("Assets/payment", array(
				'payments' => 'stripe',
				'amount' => $option['defaultAmount'],
				'currency' => $currency,
				'description' => $optionText
			), Q_Utils::normalize($optionText));
            echo '</div></li>';
		} ?>
    </ul>
    <div style="text-align: center"><button name="requestNewFeature" class="Q_button"><?php echo $text['RequestNewFeature'] ?></button></div>
    <br>
</div>
