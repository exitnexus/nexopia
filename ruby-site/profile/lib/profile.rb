lib_require :Core, "storable/cacheable"
lib_require :Core, "storable/storable"
lib_require :Profile, "profile_block"

lib_want :Observations, "observable"

module Profile
	class Profile < Cacheable
		set_enums(
			:firstnamevisibility => {:none => 0, :friends => 1, :friends_of_friends => 2, :all => 3 },
			:lastnamevisibility => {:none => 0, :friends => 1, :friends_of_friends => 2, :all => 3 }
		);		
		
		set_prefix("ruby_profile")
		init_storable(:usersdb, "profile")
		relation_multi_cached :blocks, :userid, ProfileBlock, "ruby_profile_blocks"

		WEIGHT = [
			['0',	"No Comment"],
			['1',	"Less than 41 Kg (less than   90 lbs)"],
			['2',	"41 Kg - 45 Kg  (  90 lbs - 100 lbs)"],
			['3',	"46 Kg - 50 Kg  ( 101 lbs - 110 lbs)"],
			['4',	"51 Kg - 55 Kg  ( 111 lbs - 120 lbs)"],
			['5',	"56 Kg - 59 Kg  ( 121 lbs - 130 lbs)"],
			['6',	"60 Kg - 64 Kg  ( 131 lbs - 140 lbs)"],
			['7',	"65 Kg - 68 Kg  ( 141 lbs - 150 lbs)"],
			['8',	"69 Kg - 73 Kg  ( 151 lbs - 160 lbs)"],
			['9',	"74 Kg - 77 Kg  ( 161 lbs - 170 lbs)"],
			['a',	"78 Kg - 82 Kg  ( 171 lbs - 180 lbs)"],
			['b',	"83 Kg - 86 Kg  ( 181 lbs - 190 lbs)"],
			['c',	"87 Kg - 91 Kg  ( 191 lbs - 200 lbs)"],
			['d',	"92 Kg - 95 Kg  ( 201 lbs - 210 lbs)"],
			['e',	"96 Kg - 100 Kg ( 211 lbs - 220 lbs)"],
			['f',	"Over 100 Kg    (over 221 lbs)"]		
		]

		HEIGHT = [
			['0',	"No Comment"],
			['1',	"Under 152 cm      (under 5')"],
			['2',	"152 cm - 158 cm   (5'    - 5'2\")"],
			['3',	"159 cm - 163 cm   (5'3\"  - 5'4\")"],
			['4',	"164 cm - 168 cm   (5'5\"  - 5'6\")"],
			['5',	"169 cm - 173 cm   (5'7\"  - 5'8\")"],
			['6',	"174 cm - 178 cm   (5'9\"  - 5'10\")"],
			['7',	"179 cm - 183 cm   (5'11\" - 6')"],
			['8',	"184 cm - 188 cm   (6'1\"  - 6'2\")"],
			['9',	"189 cm - 193 cm   (6'3\"  - 6'4\")"],
			['a',	"Over 194 cm   (over 6'5\")"]
		]

		SEXUAL_ORIENTATION = [
			['0',	"No Comment"],
			['1',	"Heterosexual"],
			['2',	"Homosexual"],
			['3',	"Bisexual/Open-Minded"]
		]

		DATING_SITUATION = [
			['0',	"No Comment"],
			['2',	"Single and not looking"],
			['6',	"Single"],
			['1',	"Single and looking"],
			['3',	"Dating"],
			['4',	"Long term"],
			['7', "Engaged"],
			['5',	"Married"]
		]

		LIVING_SITUATION = [
			['0',	"No Comment"],
			['1',	"Living alone"],
			['2',	"Living with spouse"],
			['3',	"Living with kid(s)"],
			['4',	"Living with roommate(s)"],
			['5',	"Living with parents/relatives"],
			['6',	"Living with significant other"]
		]

		DISPLAY_STRINGS = {
			:weight => WEIGHT,
			:height => HEIGHT,
			:orientation => SEXUAL_ORIENTATION,
			:dating => DATING_SITUATION,
			:living => LIVING_SITUATION
		}

		

		def owner
			return User.get_by_id(@userid);
		end

		if (site_module_loaded? :Observations)
			include Observations::Observable
			OBSERVABLE_NAME = "Profile"

			observable_event :create, proc{"#{owner.link} changed #{owner.possessive_pronoun} profile."}
		end

		def weight=(user_weight)
			stat_block = self.profile;
			stat_block[0] = user_weight[0];
			self.profile = stat_block;
		end

		def height=(user_height)
			stat_block = self.profile;
			stat_block[1] = user_height[0];
			self.profile = stat_block;
		end

		def orientation=(sexual_orientation)
			stat_block = self.profile;
			stat_block[2] = sexual_orientation[0];
			self.profile = stat_block;
		end

		def dating=(dating_situation)
			stat_block = self.profile;
			stat_block[3] = dating_situation[0];
			self.profile = stat_block;
		end

		def living=(living_situation)
			stat_block = self.profile;
			stat_block[4] = living_situation[0];
			self.profile = stat_block;
		end

		def weight
			stat_block = self.profile;
			return "" << stat_block[0];
		end
		
		def display_string(stat_type)
			key = self.send(stat_type)
			if (key == "0")
				return false
			else
				return get_profile_string(DISPLAY_STRINGS[stat_type], key)
			end
		end

		def height
			stat_block = self.profile;
			return "" << stat_block[1];
		end

		def orientation
			stat_block = self.profile;
			return "" << stat_block[2];
		end

		def dating
			stat_block = self.profile;
			return "" << stat_block[3];
		end

		def living
			stat_block = self.profile;
			return "" << stat_block[4];
		end

		def get_profile_string(haystack, needle)
			haystack.each {|key, value|
				if (needle == key)
					return value
				end
			}
		end
	end

	class Profiles
		def Profiles.get_age(profile_date)
			profile_day   = profile_date.day;
			profile_month = profile_date.month;
			profile_year  = profile_date.year;

			current_date  = Time.new();
			current_day   = current_date.day;
			current_month = current_date.month;
			current_year  = current_date.year;

			age  = current_year - profile_year;
			age += (current_month  > profile_month)? -1 : 0;
			age += (current_month == profile_month && current_day < profile_day)? -1 : 0;

			return age;
		end

	end

end


