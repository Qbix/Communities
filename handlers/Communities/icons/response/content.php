<?php
function Communities_icons_response_content ($params)
{
	Q_Response::addStylesheet("{{Communities}}/css/icons.css");
	Q_Response::addScript("{{Communities}}/js/pages/icons.js");

	return Q::view('Communities/content/icons.php');
}