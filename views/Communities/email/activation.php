<div style="text-align: initial">
        <?php echo Q_Html::img('img/icon/200.png') ?>
</div>

<p>
	<?php echo Q::text($activation['Thanks'], array($communityName)) ?>
</p>

<p>
	<?php echo Q::interpolate($resend['DomainCode'], compact('domain')) ?> 
	<span class="Users_activation_code" style="font-weight: bold; font-family: 'Courier New'; background: #eeeeee; border: dashed 1px #aaaaaa; color: black; cursor: pointer; width: 100px; font-size: 20px; margin: auto;"><?php echo $code ?></span>
</p>

<p>
	<?php echo Q::interpolate($activation['ReallyYourEmail'], 
		array($user->displayName(), $link)) ?> 
</p>


<p style="margin-top: 100px;">
	<?php echo Q::interpolate($LinkToUnsubscribe, array($unsubscribe)) ?> 
</p>