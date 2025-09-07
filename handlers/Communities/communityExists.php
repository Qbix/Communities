<?php

function Communities_communityExists($params)
{
    $communityId = $params['fields']['communityId'];
    return !!Users_User::fetch($communityId);
}