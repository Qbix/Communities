<?php
	
function Communities_testplugins_response_content()
{
	if (!Users::roles(null, array("Users/admins", "Users/owners"))) {
		//throw new Users_Exception_NotAuthorized();
	}

	Q_Response::addStylesheet('{{Communities}}/css/testplugins.css');
	Q_Response::addScript('{{Communities}}/js/pages/testplugins.js');

	return Q::view('Communities/content/testplugins.php');
}