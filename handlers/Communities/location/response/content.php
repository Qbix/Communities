<?php
	
function Communities_location_response_content()
{
	Q_Response::addStylesheet('{{Communities}}/css/columns/location.css');
	return Q::view('Communities/content/location.php');
}