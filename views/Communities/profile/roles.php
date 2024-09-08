<?php
$ownUser = ($userId == $loggedInUserId) && !$anotherUser;
if (!$ownUser) {
?>
    <div class="Communities_profile <?=sizeof($roles) ? "Communities_has_labels " : "Communities_no_labels "?>" data-val="roles"><?php
        foreach ($roles as $role) {
            if (is_array(Q::ifset($can,'handleRoles', null)) && in_array($role["label"], $can['handleRoles'])) {
                continue;
            }
    ?><div class="Communities_label">
	    <img src="<?=$role["icon"]?>" alt="<?=$role["title"]?>">
	    <span class="Communities_label_title"><?=$role["title"]?></span>
	    </div>
	<?php } ?></div>
    <div class="Communities_manage_roles <?php
	echo empty($can['handleRoles']) ? "" : "Communities_labels_permissions ";
	echo empty($userRoles) ? "Communities_no_labels " : "Communities_has_labels ";
	?>">
        <div class="Communities_has_labels"><?php foreach ($userRoles as $label => $row) {
				if (in_array($label, $can['handleRoles'])) { ?>
                    <span class="Communities_label">
	                            <?=Q_Html::img($labelsInfo[$label]["icon"], $labelsInfo[$label]["title"])?>
	                            <span class="Communities_label_title">
									<?=$labelsInfo[$label]["title"]?>
								</span>
							</span>
				<?php }
			} ?></div>
        <div class="Communities_no_labels">
            <button class="Q_button Q_aspect_who"><?=Q::text($profile['ManageRoles'])?></button>
        </div>
    </div>
<?php } ?>