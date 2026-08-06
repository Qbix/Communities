<?php

function Communities_accountStatus_response_accountStatus()
{
    $user = Users::loggedInUser();
    if (!$user) {
        return 'notLoggedIn';
    }
    $onboarding = Q_Config::get('Communities', 'onboarding', array());
    $steps = Q::ifset($onboarding, 'steps', array());
    $keepImported = Q::ifset($onboarding, 'icon', 'keepImported', false);
    // check if name is set
    $avatar = Streams_Avatar::fetch($user->id, $user->id);
    $needs = array();
    if (!$avatar || !$avatar->displayName(array('short' => true), '')) {
        $needs[] = 'name';
    }
    if (in_array('icon', $steps)) {
        // check if user has a custom icon
        if (!Users::isCustomIcon($user->icon, !$keepImported)) {
            $needs[] = 'icon';
        }   
    }
    if (in_array('interests', $steps)) {
        $interests = Streams_Category::getRelatedTo(
            $user->id, 'Streams/user/interests', 'Streams/interests'
        );
        if (empty($interests)) {
            $needs[] = 'interests';
        }
    }
    if (in_array('location', $steps)) {
        // check if user has set a location -- the stream may not exist yet
        // for a freshly registered user, which is itself a "needs location"
        $stream = Streams_Stream::fetch($user->id, $user->id, 'Places/user/location');
        if (!$stream
        or (!$stream->getAttribute('latitude') and !$stream->getAttribute('longitude'))) {
            $needs[] = 'location';
        }
    }
    // check if user has interests
    return $needs ? "needs:" . json_encode($needs) : 'complete';
}
