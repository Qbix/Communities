<?php
	
function Communities_featureboard_response_content()
{
	$amounts = Q_Config::expect('Communities', 'featureboard', 'amounts');
	$currency = Q_Config::expect('Communities', 'featureboard', 'currency');

	Q_Config::load(ASSETS_PLUGIN_CONFIG_DIR.DS.'currencies.json');
	$symbol = Q_Config::get('symbols', strtoupper($currency), '');

	Q_response::addStylesheet("{{Communities}}/css/featureboard.css");
	Q_response::addScript("{{Communities}}/js/pages/featureboard.js");

	if (!Q::isAssociative($amounts)) {
		$arr = array();
		foreach ($amounts as $a) {
			$arr[$a] = $symbol . $a;
		}
		$amounts = $arr;
	}

	$app = Q::app();
	$text = Q_Text::get('Communities/content')['featureboard'];

	return Q::view('Communities/content/featureboard.php', @compact(
		'amounts', 'currency', 'symbol', 'text', 'app'
	));
}