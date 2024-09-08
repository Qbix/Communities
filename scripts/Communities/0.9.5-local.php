<?php

function Communities_0_9_5_local()
{
	$from = COMMUNITIES_PLUGIN_VIEWS_DIR.DS.'Communities'.DS.'templates';
	$dir = APP_WEB_DIR.DS.'Q'.DS.'views'.DS.'Communities';
	$to = $dir.DS.'templates';
	if (!file_exists($to)) {
		if (!file_exists($dir)) {
			mkdir($dir, 0777, true);
		}
		Q_Utils::symlink($from, $to);
	}
}

Communities_0_9_5_local();