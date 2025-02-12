<div class="Communities_me_column">
    <div class="Communities_avatar_section">
        <?php echo Q::tool("Users/avatar", array(
            'userId' => $user->id, 
            'icon' => 80, 
            'editable' => true,
            'show' => 'u f l'
        ), 'Communities_me'); ?>

        <?php
            if (!empty($roles)) {
                echo Q::tool("Communities/roles", array("userId" => $user->id));
            }
        ?>

        <div class="Communities_verified_icons">
            <?php if ($hasMobile): ?>
                <div class="Communities_me_icon" data-value="mobile" data-touchlabel="<?php echo $me['MobileNumber'] ?>" data-checked="<?php echo $user->mobileNumber ? 'true' : 'false' ?>"></div>
            <?php endif; ?>
            <?php if ($hasEmail): ?>
                <div class="Communities_me_icon" data-value="email" data-touchlabel="<?php echo $me['EmailAddress'] ?>" data-checked="<?php echo $user->emailAddress ? 'true' : 'false' ?>"></div>
            <?php endif; ?>
            <?php if ($hasWeb3): ?>
                <div class="Communities_me_icon" data-value="web3" data-touchlabel="<?php echo $me['Web3'] ?>" data-checked="<?php echo $user->getXid("web3_all") ? 'true' : 'false' ?>"></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="Communities_tabs" data-style="icons">
        <?php
            foreach($tabs as $tabName => $show):
                if (!$show) { continue; }
                if (!Q::ifset($columns, $tabName, null)) { continue; }
        ?>

            <div class="Communities_tab <?php 
                echo Q::ifset($tabClasses, $tabName, '') 
            ?> <?php echo $tab == $tabName ? 'Q_current' : ''
            ?>" data-val="<?php echo $tabName
            ?>" <?php if (Q_Request::isMobile()) echo "data-touchlabel='" . Q::text(Q::ifset($$tabName, "Title", ucfirst($tabName))) . "'"
            ?>>
                <?php if (!Q_Request::isMobile()) echo Q::ifset($$tabName, "Title", ucfirst($tabName)) ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
        foreach($tabs as $tabName => $show):
            if (!$show) { continue; }
			if (!Q::ifset($columns, $tabName, null)) { continue; }
    ?>

        <div class="Communities_me_column_tabContent <?php echo $tab == $tabName ? 'Q_current' : ' '?>
            <?php if ($tabName == 'profile') { echo 'Q_big_prompt Communities_profile_sections'; } ?>
            " data-val="<?php echo $tabName ?>">
            <?php echo $columns[$tabName] ?>
        </div>
    <?php endforeach; ?>
</div>