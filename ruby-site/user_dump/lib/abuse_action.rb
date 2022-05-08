class AbuseAction
	WARNING=1
	FORUM_BAN=2
	DELETE_PIC=3
	PROFILE_EDIT=4
	FREEZE_ACCOUNT=5
	DELETE_ACCOUNT=6
	NOTE=7
	SIG_EDIT=8
	IP_BAN=9
	EMAIL_BAN=10
	UNFREEZE_ACCOUNT=11
	USER_REPORT=12
	FORUM_WARNING=13
	FORUM_NOTE=14
	LOGGED_MSG=15
	BLOG_EDIT=16
	TAGLINE_EDIT=17

	@@action_descriptions = {
		AbuseAction::WARNING => "Official Warning",
		AbuseAction::FORUM_BAN => "Forum Ban",
		AbuseAction::DELETE_PIC => "Delete Picture",
		AbuseAction::PROFILE_EDIT => "Profile Edit",
		AbuseAction::FREEZE_ACCOUNT => "Freeze Account",
		AbuseAction::DELETE_ACCOUNT => "Delete Account",
		AbuseAction::NOTE => "Note",
		AbuseAction::SIG_EDIT => "Signature Edit",
		AbuseAction::IP_BAN => "IP Ban",
		AbuseAction::EMAIL_BAN => "Email Ban",
		AbuseAction::UNFREEZE_ACCOUNT => "Unfreeze Account",
		AbuseAction::USER_REPORT => "User Report",
		AbuseAction::FORUM_WARNING => "Forum Warning",
		AbuseAction::FORUM_NOTE => "Forum Note",
		AbuseAction::LOGGED_MSG => "Logged Message",
		AbuseAction::BLOG_EDIT => "Blog Edit",
		AbuseAction::TAGLINE_EDIT => "Tagline Edit"
	}

	def initialize(action_id)
		if @@action_descriptions.has_key?(action_id)
			@action_id = action_id
		else
			@action_id = nil
		end
	end

	def AbuseAction.description(action_id)
		if @@action_descriptions.has_key?(action_id)
			@@action_descriptions.fetch(action_id)
		else
			"No textual description available for abuse action ##{action_id}"
		end
	end

	def to_i()
		return @action_id
	end

	def to_s()
		self.description(@action_id)
	end
end
