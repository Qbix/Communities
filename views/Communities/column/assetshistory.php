<div class="Communities_tabs">
	<div class="Communities_tab <?=(!$tab1 || $tab1=="credits" ? "Q_current" : "")?>" data-val="credits"><?=$text['assetshistory']['Credits']?></div>
	<div class="Communities_tab <?=($tab1=="charges" ? "Q_current" : "")?>" data-val="charges"><?=$text['assetshistory']['Charges']?></div>
</div>
<div class="Communities_tabContent Q_current" data-val="credits">
	<?=Q::tool('Assets/history', array('type' => 'credits', 'mergeRows' => true), 'Assets_history_credits')?>
</div>
<div class="Communities_tabContent" data-val="charges">
	<?=Q::tool('Assets/history', array('type' => 'charges'), 'Assets_history_charges')?>
</div>
