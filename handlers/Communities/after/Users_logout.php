<?php
	
function Communities_after_Users_logout()
{
	Q_Response::clearCookie('Q_Users_communityId');
}