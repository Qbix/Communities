<?php

function Communities_0_7_local()
{	
	if (!file_exists(USERS_PLUGIN_FILES_DIR.DS.'Users'.DS.'icons'.DS.'Communities')) {
		Q_Utils::symlink(
			COMMUNITIES_PLUGIN_FILES_DIR.DS.'Communities'.DS.'icons'.DS.'labels'.DS.'Communities',
			USERS_PLUGIN_FILES_DIR.DS.'Users'.DS.'icons'.DS.'labels'.DS.'Communities'
		);
	}
}

Communities_0_7_local();