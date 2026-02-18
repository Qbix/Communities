<div id="Communities_profile_column" class="Q_big_prompt Communities_profile_content <?=$classes?>" data-userId="<?=$user->id?>">
	<?php echo Q::tool("Users/avatar", array(
		'userId' => $user->id,
		'icon' => $avatarIconSize,
		'editable' => ($userId == $loggedInUserId) ? array('icon', 'name') : false,
		'className' => 'Q_square Communities_column_profile_main_avatar',
		'iconAttributes' => array(
			'dontLazyload' => true
		),
		'imagepicker' => array(
			'onCropping' => 'Q.Communities.handlers.cropping'
		)
	), uniqid()); ?>
    <div class="Communities_manage_contacts <?=$hasLabelsClass?>">
        <div class="Communities_has_labels">
			<?php foreach ($labelTitles as $i => $t): ?>
                <span class="Communities_label">
                    <?=Q_Html::img($labelIcons[$i], $t)?>
                    <span class="Communities_label_title">
                        <?=Q_Html::text($t)?>
                    </span>
                </span>
			<?php endforeach; ?>
        </div>
		<?php if ($userId != $loggedInUserId) : ?>
            <div class="Communities_no_labels">
                <button id="Communities_labels_trigger" class="Q_button Q_aspect_who">+<?=$profile['addToContacts']?></button>
            </div>
		<?php endif; ?>
    </div>
    <div class="Communities_profile_icons">
        <div class="Communities_profile_chat" data-touchlabel="<?=Q::interpolate($profile["PersonalChatWith"], array("name" => $shortDisplayName))?>"><?=$profile['chat']?></div>
        <div class="Communities_profile_pay" data-touchlabel="<?=Q::interpolate($profile["SendPaymentTo"], array("name" => $shortDisplayName))?>"><?=$profile['SendCrypto']?></div>
        <?php if ($allowedEmail) { ?>
            <div class="Communities_profile_email" data-touchlabel="<?=Q::interpolate($profile["SendEmailTo"], array("name" => $shortDisplayName))?>"><?=$profile['Email']?></div>
        <?php } ?>
        <?php if ($allowedSMS) { ?>
            <div class="Communities_profile_sms" data-touchlabel="<?=Q::interpolate($profile["SendSMSTo"], array("name" => $shortDisplayName))?>">SMS</div>
        <?php } ?>
        <?php if ($loggedInUserId) {?>
        <div class="Communities_profile_block" data-touchlabel="<?= $profile[$blocked ? "UnblockThisUser" : "BlockThisUser"]?>" data-action="<?=($blocked ? "unblock" : "block")?>"><span data-for="unblock"><?=$profile['Unblock']?></span><span data-for="block"><?=$profile['Block']?></span></div>
        <?php } ?>
        <?php if ($isAdmin and $canSeeRoles) { ?>
            <div class="Communities_profile_roles" data-touchlabel="<?=$profile["ManageRoles"]?>"><?=$profile["Roles"]?></div>
        <?php } ?>
    </div>
	<?php if (!empty($links) || !empty($xids) || $canSeeRoles) { ?>
        <div class="Communities_profile_links_socials">
			<?php if ($canSeeRoles || !empty($can["grant"])) {
				echo Q::tool("Communities/roles", array(
					"userId" => $user->id,
					"communityId" => $communityId,
					"transition" => false,
					"labelCanGrant" => $loggedInUserCan['grant'],
					"labelCanRevoke" => $loggedInUserCan['revoke'],
				));
			}
			?>
            <div class="Communities_profile" data-val="social"><?php
				foreach ($xids as $key => $val) {
					echo '<i class="Communities_social_icon" data-type="'.$key.'" data-connected="'.$val.'">'.$key.'</i>';
				}
				?></div>
            <div class="Communities_profile" data-val="links"><?php
				foreach ($links as $link) {
					echo Q::tool("Websites/webpage/preview", array(
						"publisherId" => $link->fromPublisherId,
						"streamName" => $link->fromStreamName
					), array('id' => Q_Utils::normalize($link->fromPublisherId . ' ' . $link->fromStreamName)));
				}
				?></div>
        </div>
	<?php }
	if (!empty($greeting)) { echo $greeting; }
	$i = 0;
	foreach ($results as $tab => $column) {
		echo $column["content"];
		$i++;
	}
	?>
</div>