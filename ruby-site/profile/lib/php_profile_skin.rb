lib_require :Profile, 'profile_skin'
module Profile
	class PHPProfileSkin < Storable
		init_storable(:db, 'profileskins')
		
		SKIN_VARIABLES = [
			:header_background_color,
			:header_text_color,
			:header_link_color,
			:header_link_accent_color,
	
			:primary_background_color,
			:secondary_background_color,
			:primary_text_color,
			:link_color,
			:link_accent_color,
	
			:user_online_color,
			:user_offline_color
		]
		
		def variables()
			return SKIN_VARIABLES;
		end
		
		def convert!
			new_skin = ProfileSkin.new
			SKIN_VARIABLES.each_with_index {|variable, i|
				new_skin.send(:"#{variable}=", self.data[i*6,6])
			}
			new_skin.name = self.name
			new_skin.userid = self.userid
			new_skin.id = self.id
			new_skin.store
		end
		
		class << self
			def convert_all
				skins = self.find(:scan)
				skins.each {|skin|
					skin.convert!
				}
			end
		end
	end
end