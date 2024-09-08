<?php
	
function Communities_interests_response_content()
{
	Q_Response::addStylesheet('{{Communities}}/css/columns/interests.css');
	return Q::view('Communities/content/interests.php');
}