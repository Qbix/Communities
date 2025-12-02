<div id="Communities_community_column" class="Communities_profile_content" data-userId="<?php echo $communityId ?>">
	<div class="Communities_avatar_section">
		<?=Q::tool("Users/avatar", array(
			'userId' => $communityId,
			'icon' => Q_Request::isMobile() ? '50' : '80',
			'editable' => $can['manageIcon'] ? array('icon') : false,
			'className' => 'Communities_column_community_main_avatar',
			'imagepicker' => array(
				'onCropping' => 'Q.Communities.handlers.cropping'
			)
		), uniqid());
		?>
		<div class="communities_column_community_main_avatar_dummy">
			<?php if (!empty($roles) || !empty($can["grant"])) { echo Q::tool("Communities/roles", array("communityId" => $communityId)); } ?>
			<?php
			echo Q::tool("Streams/inplace", array(
				'publisherId' => $communityId,
				'streamName' => "Streams/user/username",
				'inplaceType' => 'text',
				'inplace' => array(
					'placeholder' => $community['usernamePlaceholder'],
					'showEditButtons' => false,
					'selectOnEdit' => false
				),
				'convert' => array("\n")
			), "community_main_inplace"); // don't change this id, because it used in community.js
			?>
		</div>
	</div>
    <div class="Communities_profile_main">
	
        <div class="Communities_tabs" data-style="icons">
        <?php
            $i = 0;
            
            foreach ($results as $tab => $column) {
                if ($tab == 'external') {
                    echo '<span class="Communities_tab '. ($i==0 ? 'Q_current' : '').' Streams_aspect_'.$tab.' icon-external" data-val="'.$tab.'" data-touchlabel="'.$tab.'"></span>';    
                } else {
                    echo '<span class="Communities_tab '. ($i==0 ? 'Q_current' : '').' Streams_aspect_'.$tab.'" data-val="'.$tab.'" data-touchlabel="'.$tab.'"></span>';
                }
                $i++;
            }
        ?>
        </div>
		<div class="Communities_profile_sections">
			
			<?php
			$i = 0;
			foreach ($results as $tab => $column) {
				echo $column["content"];
				$i++;
			}
			?>
		</div>
	</div>
</div>