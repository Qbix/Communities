<div class="Communities_profile_section" id="Communities_profile_labels"><?=Q::tool("Users/labels", array(
    "userId" => $communityId,
    "canAdd" => true,
    "editable" => true,
    "filter" => array("Users/", $communityId."/"),
    "exclude" => array("Users/business", "Users/dating", "Users/family", "Users/friends")
))?></div>