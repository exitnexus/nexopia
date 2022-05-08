class AbuseReason
	NUDITY=1
	RACISM=2
	VIOLENCE=3
	SPAMMING=4
	FLAMING=5 # harassment
	PEDOFILE=6
	FAKE=7
	OTHER=8
	UNDERAGE=9
	ADVERT=10
	NOT_USER=11
	DRUGS=12
	BLOG=13
	CREDIT=14
	DISCRIM=15 # discrimination
	WEAPONS=16
	THREATS=17
	HACKED=18
	REQUEST=19 # by request of the user

	@@reason_descriptions = {
		AbuseReason::NUDITY => "Nudity/Porn",
		AbuseReason::RACISM => "Racism",
		AbuseReason::VIOLENCE => "Gore/Violence",
		AbuseReason::SPAMMING => "Spamming",
		AbuseReason::FLAMING => "Harassment",
		AbuseReason::PEDOFILE => "Pedophile",
		AbuseReason::FAKE => "Fake",
		AbuseReason::OTHER => "Other",
		AbuseReason::UNDERAGE => "Underage",
		AbuseReason::ADVERT => "Advertising",
		AbuseReason::NOT_USER => "User not in Picture",
		AbuseReason::DRUGS => "Drugs",
		AbuseReason::BLOG => "Blog",
		AbuseReason::CREDIT => "Credit Card",
		AbuseReason::DISCRIM => "Discrimination",
		AbuseReason::WEAPONS => "Weapons",
		AbuseReason::THREATS => "Threats",
		AbuseReason::HACKED => "Hacked",
		AbuseReason::REQUEST => "User Request"
	}

	def initialize(reason_id)
		if @@reason_descriptions.has_key?(reason_id)
			@reason_id = reason_id
		else
			@reason_id = nil
		end
	end

	def AbuseReason.description(reason_id)
		if @@reason_descriptions.has_key?(reason_id)
			@@reason_descriptions.fetch(reason_id)
		else
			"No textual description available for abuse reason ##{reason_id}"
		end
	end

	def to_i()
		return @reason_id
	end

	def to_s()
		self.description(@reason_id)
	end
end
