<h2 class="Communities_me_credits_amount"><?= Q::text($credits['YouHave'], array(Assets_Credits::format($myCredits))) ?></h2>
<div class="Communities_me_credits">
    <div class="Communities_tabs">
        <div class="Communities_tab <?= (!$tab1 || $tab1=="credits" ? "Q_current" : "") ?>" data-val="credits"><?= $assetshistory['Credits'] ?></div>
        <div class="Communities_tab <?= ($tab1=="charges" ? "Q_current" : "") ?>" data-val="charges"><?= $assetshistory['Charges'] ?></div>
    </div>
    <div class="Communities_tabContent Q_current" data-val="credits">
		<?= Q::tool('Assets/history', array('type' => 'credits', 'mergeRows' => true), 'Assets_history_credits') ?>
    </div>
    <div class="Communities_tabContent" data-val="charges">
		<?= Q::tool('Assets/history', array('type' => 'charges'), 'Assets_history_charges') ?>
    </div>
</div>
<br>
<button class="Q_button Q_aspect_who Communities_connected_account_button" data-ready="<?=$accountReady?>">
	<?= $payment['ConnectAccount'] ?>
</button>
<button class="Q_button Q_aspect_who Communities_buy_credits_button">
	<?= $people['BuyCredits'] ?>
</button>
