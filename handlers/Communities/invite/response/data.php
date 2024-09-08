<?php
	
function Communities_invite_response_data()
{
	return Q::event('Streams/invite/response/data');
}