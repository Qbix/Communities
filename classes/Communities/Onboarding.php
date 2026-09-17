<?php

/**
 * Decides whether a user still has onboarding to do for a given community.
 *
 * This is deliberately separate from the invite dialog. "Should we ask them to
 * accept?" and "have they filled in what this community requires?" are two
 * different questions that used to be answered by one handler on the
 * Streams/inviteDialog event, which is why an already-onboarded user could end
 * up staring at an onboarding tool with no way to accept anything.
 *
 * Lives in its own file rather than as a method on Communities so it can be
 * dropped in without reproducing that whole class.
 *
 * @module Communities
 * @class Communities_Onboarding
 */
class Communities_Onboarding
{
	/**
	 * Which user streams each onboarding step is responsible for filling.
	 * Mirrors the step handlers in Communities/web/js/tools/onboarding.js, and
	 * is overridable under Communities/onboarding/streams so the two can be
	 * kept in sync from config rather than in two places in code.
	 * @method streamsByStep
	 * @static
	 * @return {array}
	 */
	static function streamsByStep()
	{
		return Q_Config::get('Communities', 'onboarding', 'streams', array(
			'name' => array('Streams/user/firstName', 'Streams/user/lastName'),
			'icon' => array('Streams/user/icon'),
			'location' => array('Places/user/location'),
			'interests' => array('Streams/user/interests'),
			'relationships' => array()
		));
	}

	/**
	 * The onboarding steps this user has not completed for this community.
	 *
	 * @method needed
	 * @static
	 * @param {string} [$userId=null] Defaults to the logged-in user.
	 * @param {string} [$communityId=null] Defaults to the current community.
	 * @return {array} Step names still outstanding, in configured order.
	 *   An empty array means there is nothing to onboard.
	 */
	static function needed($userId = null, $communityId = null)
	{
		if (!isset($userId)) {
			$user = Users::loggedInUser(false, false);
			if (!$user) {
				return array(); // nobody to onboard
			}
			$userId = $user->id;
		}
		if (!isset($communityId)) {
			$communityId = Users::currentCommunityId(true);
		}

		$steps = Q_Config::get('Communities', 'onboarding', 'steps', array());
		if (empty($steps)) {
			return array();
		}
		$byStep = self::streamsByStep();

		// Fetch every stream any step cares about, in one round trip
		$names = array();
		foreach ($steps as $step) {
			foreach (Q::ifset($byStep, $step, array()) as $n) {
				$names[$n] = true;
			}
		}
		$streams = $names
			? Streams::fetch($userId, $userId, array_keys($names))
			: array();

		$outstanding = array();
		foreach ($steps as $step) {
			if (self::stepIsOutstanding($step, $userId, $communityId, $byStep, $streams)) {
				$outstanding[] = $step;
			}
		}

		/**
		 * Lets a plugin or app add or remove outstanding steps, e.g. a
		 * community that requires something the default steps don't cover.
		 * @event Communities/onboarding/needed {after}
		 * @param {string} userId
		 * @param {string} communityId
		 * @param {array} steps
		 */
		Q::event('Communities/onboarding/needed',
			@compact('userId', 'communityId', 'steps'), 'after', false, $outstanding
		);

		return $outstanding;
	}

	/**
	 * Convenience boolean form.
	 * @method isNeeded
	 * @static
	 * @param {string} [$userId=null]
	 * @param {string} [$communityId=null]
	 * @return {boolean}
	 */
	static function isNeeded($userId = null, $communityId = null)
	{
		$needed = self::needed($userId, $communityId);
		return !empty($needed);
	}

	/**
	 * @method stepIsOutstanding
	 * @static
	 * @protected
	 */
	protected static function stepIsOutstanding(
		$step, $userId, $communityId, $byStep, $streams
	) {
		// relationships is community-scoped rather than stream-backed: the
		// question is whether this user has any relationship label in THIS
		// community, which is what makes onboarding re-run for a new community
		// even when the user's profile is otherwise complete.
		if ($step === 'relationships') {
			return !self::hasRelationshipIn($userId, $communityId);
		}

		$names = Q::ifset($byStep, $step, array());
		if (empty($names)) {
			return false; // nothing configured for this step, treat as done
		}
		foreach ($names as $name) {
			$stream = Q::ifset($streams, $name, null);
			if (!$stream or !self::streamIsFilled($stream)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * A stream counts as filled when it has content, or an icon that isn't
	 * still the default placeholder.
	 * @method streamIsFilled
	 * @static
	 * @protected
	 */
	protected static function streamIsFilled($stream)
	{
		if ($stream->name === 'Streams/user/icon') {
			$icon = $stream->icon;
			return $icon && Users::isCustomIcon($icon);
		}
		if (trim((string)$stream->content) !== '') {
			return true;
		}
		// some steps store their answer in attributes rather than content
		$attributes = $stream->getAllAttributes();
		return !empty($attributes);
	}

	/**
	 * Whether the user holds any of the community's relationship labels.
	 * @method hasRelationshipIn
	 * @static
	 * @protected
	 */
	protected static function hasRelationshipIn($userId, $communityId)
	{
		$prefix = Q_Config::get(
			'Communities', 'onboarding', 'relationshipPrefix', 'Communities/'
		);
		$contacts = Users_Contact::select()->where(array(
			'userId' => $communityId,
			'contactUserId' => $userId
		))->fetchDbRows();
		foreach ($contacts as $contact) {
			if (Q::startsWith($contact->label, $prefix)) {
				return true;
			}
		}
		return false;
	}
}
