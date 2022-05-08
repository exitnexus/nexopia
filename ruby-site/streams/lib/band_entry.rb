lib_require :Core, 'storable/cacheable', 'users/user'

module Music
	class BandEntry < Cacheable
		attr_accessor :band_location, :user_name, :date_string, :formatted_band_name;
		
		init_storable(:streamsdb, 'bandentries');
		
		extend TypeID;
		
		relation_singular(:user, :userid, User);
		
		def after_load
			@band_location = self.user.location
			@user_name = self.user.username;
			@date_string = Time.at(date).strftime("%b %d, %Y");
			@formatted_band_name = "";
			
			format_band_name();
		end
		
		def format_band_name()
			if(self.name.length > 22)
				name_words = self.name.split(' ');
				
				name_words.each{|word| 
					if(word.length > 15)
						
						revised_word = word.slice(0, 13);
						revised_word << "...";
						
						word = revised_word;
					end
					@formatted_band_name << word << " ";
				};
			else
				@formatted_band_name = self.name;
			end
		end
		
		#TODO: Come back to this later. We need it to intelligently cut strings off at word boundaries, but there are more pressing issues.
=begin		
		def cut_string(in_string, desired_length)
			
			if(in_string.length <= desired_length)
				return in_string;
			end
			
			max_length = desired_length - 3;
			
			length_deviation = (max_length * 0.15).floor();
			
			word_list = in_string.split(' ');
			
			revised_string = "";
			for word in word_list
				revised_string << word << " ";
				
				if(revised_string.length)
			end
			
		end
=end		
		#TODO: Add link info for admin_bio to allow for AJAX loading of band bio for administration page
		def uri_info(mode = 'self')
			case mode
			when "self"
				return [self.formatted_band_name, "/music/item/type/#{BandEntry.typeid}/id/#{self.id}"];
			when "band_site"
				return ["Band Website", self.uri];
			when "admin_bio"
				return ["Show bio", "/music"];
			when "user_edit"
				return ["Edit #{self.name}", "/music/bandentry/edit/#{self.id}/"];
			when "user_delete"
				return ["Delete #{self.name}", "/music/bandentry/delete/#{self.id}/"];
			when "band_website"
				return [self.uri, self.uri];
			end
		end
		
		def stream_entry
			return StreamEntry.find(:first, :conditions => ["primaryid = ? AND typeid= ?", self.id, self.class.typeid]);
		end
		
		def stream_icon
			return StreamIcon.find(stream_entry.iconid, :first);
		end
	end
end