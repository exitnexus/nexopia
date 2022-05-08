lib_require :Streams, 'band_entry', 'entry_tag', 'music_news', 'stream_entry', 'stream_tag', 'music_feature', 'music_display_stream', 'music_helper', 'music_sidebar_feature';

module Music

	class MusicHandler < PageHandler
	
		include MusicHelper
		
		declare_handlers("music"){
			area :Public
			access_level :Any
			
			page 	:GetRequest, :Full, :music_front;
			
			page 	:GetRequest, :Full, :channel_display, "channel", input(Integer);
			
			page 	:GetRequest, :Full, :item_display, "item", "type", input(Integer), "id", input(Integer);
			page	:GetRequest, :Full, :item_display, "item", "type", input(Integer), "id", input(Integer), input(Integer);
			
			page	:GetRequest, :Full, :music_submissions, "submissions";
			#page	:GetRequest, :Full, :display_all_items, "item", "all";

			#access_level :Admin, StreamsModule, :edit
		};
		
		def music_front()
			t = Template.instance("streams", "music_front");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			#User band content is disabled due to incompleteness (June 6, 2007)
      		if(!request.session.anonymous?() && !request.session.user.anonymous?())
      			#user_band = BandEntry.find(:first, :conditions => ["userid = ?", request.session.user.userid]);

      			#if(user_band != nil)
      			#	t.user_band = user_band;
      			#end
      			if(request.session.has_priv?(StreamsModule, :edit))
      				t.show_admin_panel = true;
      			end
      		end
      		
      		t.feature = MusicFeature.current();
      		
			streams = Array.new();
			display_streams = MusicDisplayStream.find(:scan, :order => "priority ASC");

			t.primary_stream = compose_primary_stream(display_streams.first);
			
			temp = Array.new();
			if(t.primary_stream != nil)
				temp << flatten_music_stream(t.primary_stream);
			end
			
			
			if(!display_streams.empty?())
				display_streams.delete(display_streams.first);
			end
			display_streams.each{|stream|
				s_temp = compose_secondary_stream(stream);
				streams << s_temp;
				temp << flatten_music_stream(s_temp);
			};
			
			temp.flatten!();
			temp.delete(nil);
			
			stream_entry_id_array = Array.new();
			stream_entry_request_array = Hash.new();
			
			for entry in temp
				keys = entry.get_primary_key();
				if(keys.kind_of?(Array))
					for key in keys
						key_string = key_string + "-#{key}";
					end
				else
					key_string = "-#{keys}-0";
				end
				
				stream_entry_request_array["#{entry.class.typeid}#{key_string}"] = entry;
				if(keys.kind_of?(Array))
					stream_entry_id_array << [entry.class.typeid, keys[0], keys[1]];
				else
					stream_entry_id_array << [entry.class.typeid, keys, 0];
				end
				
			end
			
			stream_entries = StreamEntry.find(:typeid, *stream_entry_id_array);
			
			for entry in stream_entries
				entry_key_string = "#{entry.typeid}-#{entry.primaryid}-#{entry.secondaryid}";
				stream_entry_request_array[entry_key_string].stream_entry_object = entry;
			end
			
			#recent_band_entries = StreamEntry.find(:conditions => ["typeid = ?", BandEntry.typeid], :order=>"date DESC", :limit=>8);
			#recent_artists = recent_band_entries.map{|band| band.entry};
			
			spec_list = SidebarFeature.find(:conditions => "active = 'y'");
			t.sidebar_features = spec_list;
			
			if(!spec_list.empty?())
				t.show_sidebar_features = true;
			else
				t.show_sidebar_features = false;
			end
			
			pops = StreamEntry.find(:order=>"views DESC", :conditions=>["typeid <> ?", BandEntry.typeid], :limit=>10);
			popular_entries = pops.map{|entry| entry.entry();}
			
			t.popular_entries = popular_entries;
			
			t.music_streams = streams;
			#t.recent_artists = recent_artists;
			
			print t.display();
		end
		
		def item_display(type_id, primary_id, secondary_id = nil)
			storable_class = TypeID.get_class(type_id);
			
			if (storable_class.indexes[:PRIMARY].length > 1)
				item = storable_class.find(primary_id, secondary_id, :first)
			else
				item = storable_class.find(primary_id, :first)
			end
			
			item.stream_entry.increment_views();
			
			if(item.kind_of?(MusicNews))
				news_item_display(item);
			elsif(item.kind_of?(BandEntry))
				band_item_display(item);
			else
				
			end
		end
		
		def news_item_display(news_obj)
			t = Template.instance("streams", "music_news_item");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			
			t.back_link = request.headers["HTTP_REFERER"];
			
			if(!/^http(s)?:\/\/.*(nexopia.com)[\/](music)([\/]{1}|[\?]{1}|$)/.match(t.back_link))
				t.back_link = "#{$site.www_url}/music/";
			end
			
			t.news = news_obj;
			
			print t.display();
		end
		
		def band_item_display(band_obj)
			t = Template.instance("streams", "music_band_entry_large");
			
			inject_css(t);
			
			t.band = band_obj;
			
			t.back_link = request.headers["HTTP_REFERER"];
			
			print t.display();
		end
		
		def display_all_items()
			t = Template.instance("streams", "music_display_all_items")
			
			t.entry_list = MusicNews.find();
			
			t.band_list = BandEntry.find();
			
			print t.display();
		end
		
		
		
		def channel_display(channel_id)
			t = Template.instance("streams", "music_stream");
			
			inject_css(t);
			
			channel = MusicChannel.find(:first, channel_id);
			
			
=begin			
			additional_tag_string = params['extratags', String];
			
			if((additional_tag_string != nil) || (additional_tag_string.length < 0))
				additional_tags = additional_tag_string.split(',');
			end
			
			tags = Array.new();
=end			
			primary_tag = StreamTag.find(tag_id, :first);
			
			if(primary_tag == nil)
				#display error
				#return;
			end
			
			t.title = primary_tag.tagname;
			
			t.entry_list = primary_tag.entries;
			
			print t.display();
=begin			
			tags << primary_tag;
			
			additional_tags.each{|tag|
				temp_tag = StreamTag.find(tag, :tagname, :first);
				if(temp_tag != nil)
					tags << temp_tag;
				end
			};
=end
		end
		
		def music_submissions()
			t = Template.instance("streams", "music_promotion_submissions");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			
			t.back_link = request.headers["HTTP_REFERER"];

			if(!/^http(s)?:\/\/.*(nexopia.com)[\/](music)([\/]{1}|[\?]{1}|$)/.match(t.back_link))
				t.back_link = "#{$site.www_url}/music/";
			end
			
			print t.display();
		end
		
		def compose_primary_stream(display_stream)
			music_stream = MusicStream.new();
			music_stream.display_smaller_entries = false;
			music_stream.large_display_entries = Array.new();
			music_stream.small_display_entries = MusicListPair.new();
			music_stream.small_display_entries.list_1 = Array.new();
			music_stream.small_display_entries.list_2 = Array.new();
			music_stream.micro_display_entries = MusicListPair.new();
			music_stream.micro_display_entries.list_1 = Array.new();
			music_stream.micro_display_entries.list_2 = Array.new();
			
			if(display_stream == nil)
				return music_stream;
			end
			
			music_stream.stream_tagwords = display_stream.tagwords;
			music_stream.stream_title = display_stream.title;
			
			#TODO: Needs to be changed to support multiple tagwords per display stream.
			stream_entries = StreamTag.find_items_by_name(display_stream.tagwords);
			
			result_set_empty = false;
			if(stream_entries == nil || stream_entries.empty?())
				result_set_empty = true;
			end
			
			if(stream_entries.length < 11)
				large_limit = stream_entries.length;
			else
				large_limit = 10;
			end
			
			i=0;
			while i < large_limit && !result_set_empty
				music_stream.large_display_entries << stream_entries[i];
				i= i + 1;
			end
			
			if(large_limit != 10)
				result_set_empty = true;
			else
				music_stream.display_smaller_entries = true;
			end
			
			if(stream_entries.length < 21)
				small_limit = stream_entries.length;
			else
				small_limit = 20;
			end
			
			i = 10;
			while i < small_limit && !result_set_empty
				music_stream.small_display_entries.list_1 << stream_entries[i];
				music_stream.small_display_entries.list_2 << stream_entries[i+1];
				i = i+2;
			end
			
			#Quick short circuit for logic. This should be changed. Probably put this
			#configuration stuff in the DB somewhere.
			result_set_empty = true;
			
			if(small_limit != 20)
				result_set_empty = true;
			end
			
			if(stream_entries.length < 36)
				micro_limit = stream_entries.length;
			else
				micro_limit = 36;
			end
			
			i= 20;
			while i < micro_limit && !result_set_empty
				music_stream.micro_display_entries.list_1 << stream_entries[i];
				if(stream_entries[i+1] != nil)
					music_stream.micro_display_entries.list_2 << stream_entries[i+1];
				end
				i = i+2;
			end
			
			return music_stream;
		end
		
		def compose_secondary_stream(display_stream)
			music_stream = MusicStream.new();
			music_stream.display_smaller_entries = false;
			music_stream.large_display_entries = Array.new();
			music_stream.small_display_entries = Array.new();
			music_stream.micro_display_entries = Array.new();
			
			if(display_stream == nil)
				return music_stream;
			end
			
			music_stream.stream_tagwords = display_stream.tagwords;
			music_stream.stream_title = display_stream.title;
			
			#TODO: Needs to be changed to support multiple tagwords per display stream.
			stream_entries = StreamTag.find_items_by_name(display_stream.tagwords);
			
			result_set_empty = false;
			if(stream_entries == nil || stream_entries.empty?())
				result_set_empty = true;
			end
			
			small_limit = 0;
			
			#if(stream_entries.length < 4)
			#	small_limit = stream_entries.length;
			#else
			#	small_limit = 3;
			#end
			
			i = 0;
			while i < small_limit && !result_set_empty
				music_stream.small_display_entries << stream_entries[i];
				i = i + 1;
			end
			
			#if(small_limit != 3)
			#	result_set_empty = true;
			#end
			
			if(stream_entries.length < 6)
				micro_limit = stream_entries.length;
			else
				micro_limit = 6;
			end
			i = 0;
			while i < micro_limit && !result_set_empty
				music_stream.micro_display_entries << stream_entries[i];
				i = i + 1;
			end
			
			return music_stream;
		end

	end
end
