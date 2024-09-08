<div class="Communities_profile_section" id="Communities_profile_external">

    <?=Q::tool("Users/web3/community", array(
        "chains" => $chains,
        "communityId" => $communityId,
        "showSelectChainId" => true,
        
        "contractParams" => array(
            "hook" => '0x0000000000000000000000000000000000000000',
            "invitedHook" => '0x0000000000000000000000000000000000000000',
            "name" => $displayName,
            "symbol" => $displayName,
            "contractURI" => $contractURI
        )
        
    ))?>
            
</div>