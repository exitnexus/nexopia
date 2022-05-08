lib_want :Profile, "profile_block_query_info_module";

module Userpics
	class FilmStripProfileBlockHandler < PageHandler
		
		declare_handlers("profile_blocks/Userpics/film_strip/") {
			area :User
			access_level :Any
			page :GetRequest, :Full, :film_strip, input(Integer)
		}
		
		def film_strip(id)
			template = Template.instance("userpics", "film_strip");
			template.pics = request.user.pic_slots.collect {|pic|
				pic.kind_of?(EmptyPicSlot) ? nil : pic
			}.compact
			puts template.display
		end
		
		def self.film_strip_query(info)
			if(site_module_loaded?(:Profile))
				info.extend(ProfileBlockQueryInfo);
				info.title = "Film Strip";
				info.initial_position = 0;
				info.initial_column = 1;
				info.form_factor = :wide;
				info.moveable = false;
				info.editable = false;
				info.removable = false;
			end
			return info;
		end
	end
end
