<div class="Communities_profile_section" id="Communities_profile_interests"><?php echo Q::tool("Streams/interests", array(
    'communityId' => $communityId,
    'filter' => Q::ifset($interests, "filter", null),
    'canAdd' => true
), uniqid()) ?></div>