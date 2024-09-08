(function (window, Q, $, undefined) {
	
/**
 * @module Communities
 */
	
/**
 * Lets an administrator manage occupants
 * @class Communities occupants
 * @constructor
 * @param {Object} [options] Override various options for this tool
 *  @param {String} [options.communityId] user id of the community
 */

Q.Tool.define("Communities/occupants", function () {
	var tool = this;
	var state = tool.state;
	var $te = $(tool.element);
	tool.$location = tool.$('.Communities_occupants_location').change(_changed);
	tool.$floor = tool.$('.Communities_occupants_floor').change(_changed);
	tool.$column = tool.$('.Communities_occupants_column').change(_changed);
	tool.$results = tool.$('.Communities_occupants_results');
	_changed();
	
	function _changed() {
		if (!tool.$location.val() || !tool.$column.val() || !tool.$floor.val()) {
			return;
		}
		var streamName = 'Places/area/'+tool.$location.val()+'/'
			+tool.$floor.val()+tool.$column.val();
		Q.Streams.get(state.communityId, streamName, function (err, stream, extra) {
			var msg = Q.firstErrorMessage(err);
			if (msg) {
				Q.replace(tool.$results[0], '');
				Q.activate(tool.$results[0]);
				return;
			}
			var fields = {
				participants: extra.participants
			};
			state.currentStream = this;
			Q.Template.render('Communities/occupants/results', fields,
			function (err, html) {
				var msg;
				if (msg = Q.firstErrorMessage(err)) {
					return alert(msg);
				}
				Q.replace(tool.$results[0], html); // TODO: override .html() to use Q.replace
				Q.activate(tool.$results[0]);
				tool.$('.Communities_occupants_setIdentifier').plugin('Q/clickable');
				tool.$('.Communities_occupants_invite').plugin('Q/clickable');
				$te.off('.Communities_occupants');
				$te.on('click.Communities_occupants', '.Communities_occupants_setIdentifier', tool,
				function () {
					Q.Users.setIdentifier({
						identifierType: "email,mobile",
						userId: $(this).attr('data-userId')
					});
				});
		
				$te.on('click.Communities_occupants', '.Communities_occupants_invite', tool,
				function () {
					if (!state.currentStream) {
						return;
					}
					Q.Streams.invite(state.communityId, state.currentStream.fields.name, {
						uri: 'Communities/invite',
						communityId: Q.Users.communityId,
						addLabel: ['Communities/occupants', 'Communities/members'],
						appUrl: Q.urls['Communities/home'],
						writeLevel: 'post'
					}, function () {
						alert("Successfully invited this user to " + state.currentStream.fields.title);
					});
				});
			});
		}, {
			participants: 100
		});
	}
}, 
{
	communityId: Q.Users.communityId
});

Q.Template.set('Communities/occupants/results', 
	'<div class="Communities_occupants_container">'
	+ '{{#each participants}}'
	+ '<div class="Communities_occupants_occupant">'
	+ '{{{tool "Users/avatar" userId=userId}}}'
	+ '<button class="Q_button Communities_occupants_setIdentifier" data-userId="{{userId}}">'
	+ 'Set email or mobile number'
	+ '</button>'
	+ '</div>'
	+ '{{/each}}'
	+ '<hr>'
	+ '<button class="Q_button Communities_occupants_invite">Invite Another occupant</button>'
	+ '</div>'
);

})(window, Q, Q.jQuery);