<?php
function Communities_NFTcollections_response_content ($params) {
	Q::event('Communities/NFTcollections/response/column', $params);
	return Q::view('Communities/content/columns.php');
}