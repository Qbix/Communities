Q.page("Communities/broadcast", function () {
    var url = new URL(location.href);
    var roomId = url.searchParams.get("stream");
    var role = url.searchParams.get("role");

    if(roomId == null) {
        roomId = 'meeting4';
    }
    if(role == null) {
        role = 'receiver';
    }


    var broadcastCon = document.createElement('DIV');
    broadcastCon.style.position = 'absolute';
    /*broadcastCon.style.top = '100px';*/
    broadcastCon.style.left = '0px';
    broadcastCon.style.height = '100%';
    broadcastCon.style.width = '100%';
    broadcastCon.style.background = '#cccccc';
    broadcastCon.style.display = 'flex';
    broadcastCon.style.justifyContent = 'center';
    broadcastCon.style.alignItems = 'center';

    document.body.appendChild(broadcastCon);


    var levelCounterCon = document.createElement('DIV');
    levelCounterCon.style.position = 'absolute';
    levelCounterCon.style.top = '80px';
    levelCounterCon.style.left = '0';
    levelCounterCon.style.display = 'flex';
    levelCounterCon.style.flexDirection = 'column';
    levelCounterCon.style.zIndex = '99999999999';

    var levelCounter = document.createElement('DIV');
    levelCounter.style.marginTop = '10px';
    levelCounter.style.height = '30px';
    levelCounter.style.padding = '20px';
    levelCounter.style.fontSize = '16px';
    levelCounter.style.color = 'yellow';
    levelCounter.style.background = 'black';
    levelCounter.style.display = 'flex';
    levelCounter.style.justifyContent = 'start';
    levelCounter.style.alignItems = 'center';
    var localParticipantIdCon = document.createElement('DIV');
    localParticipantIdCon.style.height = '30px';
    localParticipantIdCon.style.padding = '20px';
    localParticipantIdCon.style.paddingRight = '5px';
    localParticipantIdCon.style.fontSize = '16px';
    localParticipantIdCon.style.color = 'yellow';
    localParticipantIdCon.style.background = 'black';
    localParticipantIdCon.style.display = 'flex';
    localParticipantIdCon.style.justifyContent = 'start';
    localParticipantIdCon.style.alignItems = 'center';
    var localParticipantId = document.createElement('DIV');
    localParticipantId.style.height = '30px';
    var localParticipantIdColor = document.createElement('DIV');
    localParticipantIdColor.style.height = '30px';
    localParticipantIdColor.style.width = '30px';
    localParticipantIdColor.style.marginLeft= '5px';

    var iFollowIdCon = document.createElement('DIV');
    iFollowIdCon.style.height = '30px';
    iFollowIdCon.style.padding = '20px';
    iFollowIdCon.style.fontSize = '16px';
    iFollowIdCon.style.color = 'yellow';
    iFollowIdCon.style.background = 'black';
    iFollowIdCon.style.display = 'flex';
    iFollowIdCon.style.justifyContent = 'start';
    iFollowIdCon.style.alignItems = 'center';
    iFollowIdCon.style.marginTop = '10px';
    var iFollowId = document.createElement('DIV');

    var iFollowIdColor = document.createElement('DIV');
    iFollowIdColor.style.height = '30px';
    iFollowIdColor.style.width = '30px';
    iFollowIdColor.style.marginLeft= '5px';

    localParticipantIdCon.appendChild(localParticipantId);
    localParticipantIdCon.appendChild(localParticipantIdColor);
    levelCounterCon.appendChild(localParticipantIdCon);
    iFollowIdCon.appendChild(iFollowId);
    iFollowIdCon.appendChild(iFollowIdColor);
    levelCounterCon.appendChild(iFollowIdCon);
    levelCounterCon.appendChild(levelCounter);
    broadcastCon.appendChild(levelCounterCon);

    Q.Streams.get(Q.Users.communityId, 'Media/webcast/' + roomId, function (err, stream) {
        console.log('broadcast: j pull stream');

        if(!stream) return;


        var socketServer = stream.getAttribute('nodeServer');
        console.log('start: socketServer ', socketServer)

        var broadcastClient = window.broadcastClient = window.WebRTCWebcastClient({
            mode:'node',
            role:'receiver',
            nodeServer: socketServer,
            roomName: roomId,
            //turnCredentials: turnCredentials,
        });

        broadcastClient.init(function () {
            console.log('initWithNodeServer: initConference: inited');

            var mediaElement = broadcastClient.mediaControls.getMediaElement();
            mediaElement.style.width = '100%';
            mediaElement.style.maxWidth = '100%';
            mediaElement.style.maxHeight = '100%';
            broadcastCon.appendChild(mediaElement);


            localParticipantId.innerHTML = 'My ID: ' + (broadcastClient.localParticipant().sid).replace('/broadcast#', '');

        });

        broadcastClient.event.on('trackAdded', onTrackAdded)
        broadcastClient.event.on('joinedCallback', function () {
            localParticipantIdColor.style.backgroundColor = broadcastClient.localParticipant().color;
        })

        function onTrackAdded(track) {
            levelCounter.innerHTML = 'Webcast level: ' + track.participant.distanceToRoot;
            iFollowId.innerHTML = 'I follow: ' + track.participant.sid.replace('/broadcast#', '');
            iFollowIdColor.style.backgroundColor = track.participant.color;

        }
    });
    return function () {
        // code to execute before page starts unloading
    };
}, 'Communities');