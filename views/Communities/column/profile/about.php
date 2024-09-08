<div class="Communities_profile_section <?= empty($currentTab) || strcasecmp($currentTab, "about") == 0 ? "Q_current" : "" ?> <?php echo $userId ? 'Communities_profile_anotherUser' : 'Communities_profile_loggedInUser' ?>" id="Communities_profile_about"><?php echo Q::event('Communities/profileInfo/response/content', array(
    'anotherUser' => true
)) ?></div>