<?php
    $isMobile = Q_Request::isMobile();
	$isSidebar = Q_Config::get('Q', 'response', 'layout', 'sidebar', false);
?>
<div id='dashboard' data-style="<?php echo $dashboardStyle ?>">
    <?php echo Q::tool(array('Users/avatar' => array(
        'userId' => Users::currentCommunityId(true),
        'icon' => $isMobile || !$isSidebar ? 40 : 200,
        'content' => false,
        'editable' => false
    )//, 'Communities/select' => array()
    ), 'main_logo') ?>
    <div id="dashboard_community_contextual" class="Q_contextual" data-handler="<?php echo Q::app() ?>.communityContextual">
        <ul class="Q_listing">
            <?php foreach ($communities as $cid => $communityName): ?>
                <li data-action="<?php echo $cid ?>" class="<?php echo $classes2[$cid] ?>">
                    <?php echo Q_Html::text($communityName) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

<?php
	$options = array_merge(array(
		'vertical' => !$isMobile and Q_Config::get('Q', 'response', 'layout', 'sidebar', false),
		'overflow' => array(
			'content' => '{{text}}',
			'defaultHtml' => $dashboard['Menu'],
			'glyph' => <<<EOT
				<svg class="Q_overflow_glyph_svg" viewBox="0 0 100 100" width="40" height="40">
				    <rect y="20" width="100" height="10" rx="8"></rect>
				    <rect y="50" width="100" height="10" rx="8"></rect>
				    <rect y="80" width="100" height="10" rx="8"></rect>
				</svg>				
EOT
		),
		'compact' => $isMobile && $dashboardStyle != 'icons',
		'tabs' => $tabs,
		'urls' => $urls,
		'classes' => $classes,
        'retain' => true
	), isset($options) ? $options : array());

    $withTitles = Q_Config::get('Communities', 'layout', 'dashboard', 'withTitles', false);
    if ($withTitles && $isMobile) {
		$options['attributes'] = $attributes;
	}
	$options['attributes']['me']['data-touchlabel'] = '';
	$options['touchlabels'] = ($dashboardStyle == 'icons' && !$withTitles);
	if (Q_Request::isMobile()) {
		if ($themeColors = Q_Config::get(
			'Q', 'response', 'tabs', 'mobile', 'windowThemeColors', null
		)) {
			$options['windowThemeColors'] = $themeColors;
		}
	}
    $options['defaultTabName'] = isset($defaultTabName) ? $defaultTabName : null;
    echo Q::tool('Q/tabs', $options, 'Communities');
	
	if ($withTitles) {
		Q_Response::addHtmlCssClass("Communities_dashboard_withTitles");
	}
	Q_Response::addHtmlCssClass("Communities_dashboard_$dashboardStyle");
?>
	<?php if ($dashboardStyle === 'hamburger'): ?>
		<h1></h1>
	<?php endif; ?>

	<div id="dashboard_user">
		<?php echo Q::tool('Users/status', array(
			'avatar' => array('icon' => 80)
		)) ?>
	</div>

</div>
