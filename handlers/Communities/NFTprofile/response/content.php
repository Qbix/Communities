<?php
function Communities_NFTprofile_response_content ($params) {
	Q::event('Communities/NFTprofile/response/column', $params);
	return Q::view('Communities/content/columns.php');
}