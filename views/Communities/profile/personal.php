<div class="Communities_profile" data-val="personal">
    <?php if ($userId && ($userId != $loggedInUserId || $anotherUser)) : ?>
        <?php echo Q::tool('Communities/profile/summary', array(
            'publisherId' => $userId
        ), uniqid()) ?>
    <?php else: ?>
        <div class="Communities_profile_explanation">
            <?php echo $profile['explanation'] ?>:
        </div>
        <?php echo Q::tool('Streams/form', array(
            'fields' => array(
                'birthday' => array(
                    $user->id, 'Streams/user/birthday', 'content',
                    'date', null, null, array('year_to' => date('Y')-13)
                ),
                'gender' => array(
                    $user->id, 'Streams/user/gender', 'content',
                    'select', null, null, $genders, array('Gender')
                ),
                'height' => array(
                    $user->id, 'Streams/user/height', 'content',
                    'select', null, null, $heights, array('Height')
                ),
                'affiliation' => array(
                    $user->id, 'Streams/user/affiliation', 'content',
                    'select', null, null, $affiliations, array('Affiliation')
                ),
                'dating' => array(
                    $user->id, 'Streams/user/dating', 'content',
                    'select', null, null, $dating, array('Dating')
                )
            )
        ), uniqid()) ?>
    <?php endif; ?>
</div>