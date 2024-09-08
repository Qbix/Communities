<?php if ($userId && $userId == $loggedInUserId && !$anotherUser) : ?>
<div class="Communities_profile" data-val="language">
    <label><?php echo $profile['YourPreferredLanguage'] ?>:
    <select>
    <?php
        foreach($languages as $language) {
            echo '<option '.($user->preferredLanguage == $language ? 'selected' : '').'>'.strtoupper($language).'</option>';
        }
    ?>
    </select>
    </label>
</div>
<?php endif; ?>