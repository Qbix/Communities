<div class="Communities_profile_section" id="Communities_profile_locations"><?php echo Q::tool("Places/location", array(
    "publisherId" => $communityId,
    "showCurrent" => false,
    "showAreas" => true
), uniqid()) ?></div>