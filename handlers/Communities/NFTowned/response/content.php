<?php
function Communities_NFTowned_response_content ($params) {
	Q::event('Communities/NFTowned/response/column', $params);
	return Q::view('Communities/content/columns.php');
}