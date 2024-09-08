<?php
	
function Communities_contribute_response_content()
{
	$amounts = Q_Config::expect('Communities', 'contribute', 'amounts');
	$amount = Q_Config::expect('Communities', 'contribute', 'amount');
	$currency = Q_Config::expect('Communities', 'contribute', 'currency');

	$tree = new Q_Tree();
	$tree->load(ASSETS_PLUGIN_CONFIG_DIR.DS.'currencies.json');
	$array = $tree->getAll();
	$symbol = Q::ifset($array, 'symbols', strtoupper($currency), '');

	Q_Response::addStylesheet("{{Communities}}/css/contribute.css");
	Q_Response::addScript("{{Communities}}/js/pages/contribute.js");

	if (!Q::isAssociative($amounts)) {
		$arr = array();
		foreach ($amounts as $a) {
			$arr[$a] = $symbol . $a;
		}
		$amounts = $arr;
	}

	$app = Q::app();
	$text = Q_Text::get($app.'/content');
	$description = Q::ifset($text, 'contribute', 'description', $app.' contribute');
	$communityName = Users::communityName();

	return Q::view('Communities/content/contribute.php', @compact(
		'amounts', 'amount', 'currency', 'symbol', 'description', 'communityName'
	));
}