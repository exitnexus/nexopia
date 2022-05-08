lib_require :Core, 'storable/cacheable'
module Music
	class MusicNews < Cacheable
		attr_accessor :date_string, :compact_date_string, :stream_entry_object;
		
		init_storable(:streamsdb, 'musicnews');
		
		extend TypeID;

		relation_singular(:user, :userid, User);

		def after_load
			@date_string = Time.at(date).strftime("%b %d, %Y");
			@compact_date_string = Time.at(date).strftime("%d.%m.%y");
		end
		
		#TODO: Make the short brief smarter. It should search for the closest preceding white space from
		#the specified length limit instead of the hard cut off it currently has. 
		def short_brief()
			if(brief.length > 40)
				return brief.slice(0..39);
			else
				return brief;
			end
		end
		
		def stream_entry
			if(@stream_entry_object == nil)
				@stream_entry_object = StreamEntry.find(:first, :promise, :typeid, self.class.typeid, id);
			end
			return @stream_entry_object;
		end
		
		def stream_icon
			if(self.stream_entry != nil)
				return stream_entry.display_icon;
			end
			return nil;
			#return StreamIcon.find(stream_entry.iconid, :first);
		end
		
		def uri_info(mode = 'self')
	    	case mode
	    	when "self"
	    		return [self.title, "/music/item/type/#{MusicNews.typeid}/id/#{self.id}"];
	    	when "more"
	    		return ["MORE >", "/music/item/type/#{MusicNews.typeid}/id/#{self.id}"];
	    	when "micro"
				return [micro_title(), "/music/item/type/#{MusicNews.typeid}/id/#{self.id}"];
	    	when "share_anchor"
				return ["::Share with Friends", "/music/item/type/#{MusicNews.typeid}/id/#{self.id}#share"];
	    	when "share"
				return ["", "/messages.php?action=Preview&subject=#{self.share_message_subject}&msg=#{self.share_message_body}"];
	    	end
		end
		
		def tag_list
			if(self.stream_entry != nil)
				return self.stream_entry.tags;
			end
			return OrderedMap.new();
		end
		
		def delete_url
			return "/music/administration/delete/type/#{MusicNews.typeid}/id/#{self.id}";
		end
		
		def edit_url
			return "/music/administration/item/news/edit/#{self.id}";
		end
		
		def micro_title()
			if(title.length > 30)
				return "#{title.slice(0..26)}...";
			else
				return title;
			end
		end
		
		def share_message_subject()
			message_subject = "Nexopia Music - #{self.title}";
			
			return CGI::escape(message_subject);
		end
		
		def share_message_body()
			message_body = "Check this out...\n\n [url]#{$site.www_url}/music/item/type/#{MusicNews.typeid}/id/#{self.id}/[/url]";
			
			return CGI::escape(message_body);
		end
	end
end