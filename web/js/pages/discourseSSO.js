Q.page("Communities/discourseSSO", function () {
    var url = new URL(window.location.href);
    if (!Q.Users.loggedInUser) {
        return Q.Users.login({
            onSuccess: function () {
                //location.href = window.location.href;
                url.searchParams.set('step', 'onboarding');
                location.search = url.searchParams.toString();
            },
            identifierType: 'email'
        });
    } else {
        Q.Dialogs.push({
            title: 'Onboarding',
            className: 'Communities_dialog_community Communities_onboarding_overlay',
            noClose: true,
            content: Q.Tool.setUpElement(
                'div', // or pass an existing element
                "Communities/onboarding",
                {
                    communityId: Q.Users.currentCommunityId,
                    onComplete: function () {
                        url.searchParams.delete('step');
                        location.search = url.searchParams.toString();
                    }
                }
            )
        });
    }

    return function () {
        // code to execute before page starts unloading
    };
}, 'Communities');