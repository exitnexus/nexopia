lib_require :Streams, 'band_entry', 'entry_tag', 'music_news', 'stream_entry', 'stream_tag', 'music_feature', 'music_display_stream', 'stream_icon', 'stream_icon_type';

module MusicHelper
	
	
	MusicStream = Struct.new("MusicStream", :stream_tagwords, :stream_title, :large_display_entries, :small_display_entries, :micro_display_entries, :display_smaller_entries);
	MusicListPair = Struct.new("MusicListPair", :list_1, :list_2);
	MusicBandPair = Struct.new("MusicBandPair", :band_1, :band_2);
	
	def inject_css(t)
		return;
=begin
		file_path = "#{$site.config.site_base_dir}/streams/templates/music_css.html";
		file = File.open(file_path);
		css = file.read();
		file.close();
  		
		t.css = css;
=end
	end
	
	def inject_js(t)
		file_path = "#{$site.config.site_base_dir}/streams/templates/music_scripts.html";
		file = File.open(file_path);
		script = file.read();
		file.close();
  		
		t.script = script; 
	end
	
	def update_item_tags(stream_entry, tag_removal_list, new_tag_list)
		new_tags = Array.new();
		
		$log.info("I have for removal: #{tag_removal_list.inspect}");
		$log.info("I have for addition: #{new_tag_list}");
		
		current_tags = stream_entry.tags();
		
		new_tag_list.each{|tag| tag.strip!();};
		
		duplicate_tag_list = Array.new();
		for tag_name in new_tag_list
			for tag in current_tags
				if(tag.tagname == tag_name)
					duplicate_tag_list << tag_name;
				end
			end
		end
		
		for duplicate_tag in duplicate_tag_list
			new_tag_list.delete(duplicate_tag);
		end
		
		for new_tag in new_tag_list
			tag = StreamTag.find(new_tag, :tagname, :first);
			if(tag == nil)
				tag = add_new_tag(new_tag);
			end
			tag_item(stream_entry, tag.tagid);
		end
		
		for tag_id in tag_removal_list
			deassociate_tag_item(stream_entry, tag_id)
		end
	end
	
	def tag_item(stream_entry, tag_id)
		tag_item_pair = EntryTag.new();
		tag_item_pair.tagid = tag_id;
		tag_item_pair.entryid = stream_entry.entryid;
		
		tag_item_pair.store();
	end
	
	def deassociate_tag_item(stream_entry, tag_id)
		tag_item_pair = EntryTag.find(:first, [tag_id, stream_entry.entryid]);
		if(tag_item_pair == nil)
			return;
		end
		$log.info("Removing #{tag_item_pair.to_s()}");
		tag_item_pair.delete();
	end
	
	def add_new_tag(tag_name)
		tag = StreamTag.new();
		tag.tagname = tag_name;
		tag.store();
		
		return tag;
	end
	
	def request_user_login(referer = nil, message = nil)
		t = Template.instance("streams", "music_request_user_login");
		
		inject_css(t);
			
		t.refer_location = referer;
		
		if(message == nil || message.length <= 0)
			message = "view this page";
		end
		t.refer_message = message;
		
		print t.display();
	end
	
	def build_icon_rows(type_id, icons_per_row, selected_icon_id = nil)
		
		icon_type_list = StreamIconType.find(type_id, :typeid);
		raw_icon_list = StreamIcon.find(*(icon_type_list.map{|icon_type| icon_type.iconid;}));
		
		icon_list = Array.new();
		icon_found = false;
		
		if(raw_icon_list.empty?() || icons_per_row <= 0)
			return icon_list;
		end
		if(raw_icon_list.length > icons_per_row)
			row_count = (raw_icon_list.length / icons_per_row.to_f()).ceil();
			row_count = row_count.ceil();
		else
			row_count = 1;
		end
		
		i = 0;
		while i < row_count
			temp = Array.new();
			j = i * icons_per_row;
			
			if(raw_icon_list.length > ((i+1) * icons_per_row))
				row_limit = (i+1) * icons_per_row;
			else
				row_limit = raw_icon_list.length;
			end
			
			while j < row_limit
				if(raw_icon_list.at(j) != nil)
					if( (selected_icon_id == nil && icon_found == false) ||
						(raw_icon_list.at(j).iconid == selected_icon_id && icon_found == false) )
						raw_icon_list.at(j).selected = "checked";
						icon_found = true;
					end
					temp << raw_icon_list.at(j);
				else
					j = icons_per_row;
				end
				j = j + 1;
			end
			
			icon_list << temp;
			
			i = i + 1;
		end
		
		return icon_list;
	end
	
	def flatten_music_stream(music_stream)
		stream_articles = Array.new();
		stream_articles << music_stream.large_display_entries;
		
		if(music_stream.small_display_entries.kind_of?(MusicListPair))
			stream_articles << flatten_music_list_pair(music_stream.small_display_entries);
		elsif(music_stream.small_display_entries.kind_of?(Array))
			stream_articles << music_stream.small_display_entries;
		end
		
		if(music_stream.micro_display_entries.kind_of?(MusicListPair))
			stream_articles << flatten_music_list_pair(music_stream.micro_display_entries);
		elsif(music_stream.micro_display_entries.kind_of?(Array))
			stream_articles << music_stream.micro_display_entries;
		end
		
		return stream_articles.flatten();
	end
		
	def flatten_music_list_pair(music_list_pair)
		articles = Array.new();
		for item in music_list_pair.list_1
			articles << item;
		end
		
		for item in music_list_pair.list_2
			articles << item;
		end
		
		return articles;
	end
end