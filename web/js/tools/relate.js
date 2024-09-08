(function (window, Q, $, undefined) {
	
/**
 * @module Communities
 */
	
/**
 * Allows the user to relate a stream to various locations, floors, columns and areas
 *  in Places plugin, and announcements in Websites plugin.
 * @class Communities relate
 * @constructor
 * @param {Object} [options] Override various options for this tool
 *  @param {String} [options.publisherId] user id of the publisher of the stream to relate from
 *  @param {String} [options.streamName] name of the stream to relate from
 *  @param {String} [options.communityId] user id of the publisher of the stream to relate to
 */

Q.Tool.define("Communities/relate", function () {
	var tool = this;
	var state = tool.state;
	state.communityId = state.communityId || Q.Users.communityId;
	function _changed() {
		if (tool.$location.val()) {
			tool.$fc.removeAttr('disabled');
		} else {
			tool.$fc.attr('disabled', 'disabled')
				.val('');
		}
	}
	tool.$location = tool.$('.Communities_relate_location');
	tool.$floor = tool.$('.Communities_relate_floor');
	tool.$column = tool.$('.Communities_relate_column');
	tool.$fc = tool.$floor.add(tool.$column);
	tool.$button = tool.$('.Communities_relate_button');
	tool.$button.click(function () {
		var streamName;
		if (!tool.$location.val()) {
			streamName = 'Streams/experience/main';
		} else if (!tool.$floor.val() && !tool.$column.val()) {
			streamName = 'Places/user/location/'+tool.$location.val();
		} else if (tool.$floor.val() && !tool.$column.val()) {
			streamName = 'Places/floor/'+tool.$location.val()+'/'
				+tool.$floor.val();
		} else if (!tool.$floor.val() && tool.$column.val()) {
			streamName = 'Places/column/'+tool.$location.val()+'/'
				+tool.$column.val();
		} else {
			streamName = 'Places/area/'+tool.$location.val()+'/'
				+tool.$floor.val()+tool.$column.val();
		}
		Q.Streams.get(state.publisherId, state.streamName, function () {
			var stream = this;
			stream.relateTo('Websites/announcements', state.communityId, streamName, function (err) {
				Q.Streams.get(state.communityId, streamName, function () {
					if (err) {
						return alert(Q.firstErrorMessage(err));
					}
					alert('Announcement posted');
					var announced = stream.getAttribute('announced') || {};
					var timestamp = Math.floor(Date.now()/1000);
					announced[timestamp] = {
						name: this.fields.name, 
						title: this.fields.title
					};
					stream.setAttribute('announced', announced);
					stream.save(); // these are two operations, not atomic
				});
			});
		});
	});
	tool.$location.change(_changed);
	_changed();
}, 
{
	communityId: Q.Users.communityId
});

})(window, Q, Q.jQuery);