lib_require :Core, 'typeid';
lib_require :Core, 'template/template';
lib_require :Streams, 'band_entry', 'entry_tag', 'music_news', 'stream_entry', 'stream_tag', 'music_feature', 'music_display_stream', 'music_helper', 'stream_icon', 'stream_icon_type', 'music_sidebar_feature';


module Music

	StreamIconTypeDisplay = Struct.new("StreamIconTypeDisplay", :icon_id, :type_id, :type_name, :associated);

	class MusicHandler < PageHandler
	
		include MusicHelper
		
		declare_handlers("music/administration"){
			area :Public
			access_level :Admin, StreamsModule, :edit
			
			#MusicFeature related pagehandlers
			page	:GetRequest, :Full, :display_feature_administration, "features";
			page	:GetRequest, :Full, :new_feature, "feature", "new";
			page	:GetRequest, :Full, :edit_feature, "feature", "edit", input(Integer);
			handle	:GetRequest, :delete_feature, "feature", "delete", input(Integer);
			
			handle	:PostRequest, :create_feature, "feature", "new", "submit";
			handle	:PostRequest, :update_feature, "feature", "edit", input(Integer), "submit";
			
			
			#DisplayStream pagehandlers
			page	:GetRequest, :Full, :edit_display_streams, "display", "streams", "edit";
			
			handle	:PostRequest, :update_display_streams, "display", "streams", "edit", "submit";
			
			
			#StreamIcon related pagehandlers
			page	:GetRequest, :Full, :display_stream_icon_administration, "streamicons";
			page	:GetRequest, :Full, :new_stream_icon, "streamicon", "new";
			page	:GetRequest, :Full, :edit_stream_icon, "streamicon", "edit", input(Integer);
			handle	:GetRequest, :delete_stream_icon, "streamicon", "delete", input(Integer);
			
			handle	:PostRequest, :update_stream_icon, "streamicon", "edit", input(Integer), "submit";
			handle	:PostRequest, :create_stream_icon, "streamicon", "new", "submit";

			
			#StreamTag related pagehandlers
			page 	:GetRequest, :Full, :display_tag_administration, "tags";


			#MusicNews related pagehandlers
			page	:GetRequest, :Full, :display_news_administration, "news";
			page 	:GetRequest, :Full, :new_news_item, "item", "news", "new";
			page	:GetRequest, :Full, :edit_news_item, "item", "news", "edit", input(Integer);
			handle	:GetRequest, :delete_item, "delete", "type", input(Integer), "id", input(Integer);
			handle	:GetRequest, :delete_item, "delete", "type", input(Integer), "id", input(Integer), input(Integer);
			
			handle	:PostRequest, :create_news_item, "item", "news", "new", "submit";
			handle 	:PostRequest, :update_news_item, "item", "news", "edit", input(Integer), "submit";
			
			#SidebarFeature related pagehandlers
			page	:GetRequest, :Full, :display_sidebar_feature_administration, "sidebarfeatures";
			page	:GetRequest, :Full, :new_sidebar_feature, "sidebarfeature", "new";
			page	:GetRequest, :Full, :edit_sidebar_feature, "sidebarfeature", "edit", input(Integer);
			handle	:GetRequest, :delete_sidebar_feature, "sidebarfeature", "delete", input(Integer);
			
			handle	:PostRequest, :create_sidebar_feature, "sidebarfeature", "new", "submit";
			handle	:PostRequest, :update_sidebar_feature, "sidebarfeature", "edit", input(Integer), "submit";
			
		};
		
		def new_feature()
			t = Template.instance("streams", "music_new_feature");
			
			inject_css(t);
			
			t.feature = MusicFeature.new();
			t.submit_location = "new";
			
			print t.display();
		end
		
		def edit_feature(feature_id)
			t = Template.instance("streams", "music_new_feature");
			
			
			
			inject_css(t);
			
			t.feature = MusicFeature.find(:first, feature_id);
			t.submit_location = "edit/#{feature_id}";
			
			print t.display();
		end
		
		def create_feature()
			
			feature_body = params["feature_body", String];
			feature_date_string = params["feature_date", String];
			
			feature_startdate = Time.parse(feature_date_string).to_i();
			
			feature = Music::MusicFeature.new();
			
			feature.userid = request.session.user.userid;
			feature.date = Time.now.to_i();
			feature.body = feature_body;
			feature.startdate = feature_startdate;
			
			feature.store();
			
			site_redirect("/music/administration/features/");
		end
		
		def update_feature(feature_id)
			
			feature_body = params["feature_body", String];
			feature_date_string = params["feature_date", String];
			
			feature_startdate = Time.parse(feature_date_string).to_i();
			
			feature = MusicFeature.find(:first, feature_id);
			feature.body = feature_body;
			feature.startdate = feature_startdate;
			
			feature.store();
			
			site_redirect("/music/administration/features/");
		end
		
		def delete_feature(feature_id)
			
			feature = MusicFeature.find(feature_id, :first);
			
			feature.delete();
			
			site_redirect("/music/administration/features/");
		end
		
		def edit_display_streams()
			t = Template.instance("streams", "music_configure_display_streams");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			
			display_streams = MusicDisplayStream.find(:scan, :order => "priority ASC");
			
			streams = Array.new();
			
			if(display_streams != nil && !display_streams.empty?())
				display_streams.each{|stream| streams << stream; };
			end
			
			while streams.length < 4
				streams << MusicDisplayStream.new();
			end
			
			t.streams = streams;
			
			print t.display();
		end
		
		def update_display_streams()
		
			incoming_streams = Array.new();
			params.each{|param|
				if /stream_\d/.match(param)
					temp = MusicDisplayStream.new();
					temp.tagwords = params[param, String];
					temp.tagwords.strip!();
					stream_split = param.split("_");
					stream_number = stream_split[1];
					title = params["stream_title_#{stream_number}", String];

					if(title == nil || title.length <= 0)
						title = temp.tagwords;
					end
					
					temp.title = title;
					temp.priority = stream_number;
					incoming_streams << temp;
				end
			};
			
			existing_streams = MusicDisplayStream.find(:all);
			display_streams = Array.new();
			
			incoming_streams.each{|incoming_stream|
				existing_streams.each{|stream|
					if(stream == incoming_stream)
						stream.priority = incoming_stream.priority;
						stream.title = incoming_stream.title;
						incoming_streams.delete(incoming_stream);
						existing_streams.delete(stream);
						display_streams << stream;
					end
				};
			};
			
			if(!incoming_streams.empty?())
				display_streams << incoming_streams;
				display_streams.flatten!();
			end
			
			if(!existing_streams.empty?())
				existing_streams.each{|stream|
					stream.delete();
				};
			end
			
			#should check for priority uniqueness
			
			display_streams.each{|stream|
				stream.store();
			};
			
			site_redirect("/music/administration/display/streams/edit/");
		end
		
		def display_tag_administration()
			t = Template.instance("streams", "music_tag_administration");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			
			tag_list = StreamTag.find(:scan, :order => "tagid ASC");
			
			t.tag_list = tag_list;
			
			print t.display();
		end
		
		def new_stream_icon()
			t = Template.instance("streams", "music_new_stream_icon");
			
			t.submit_location = "new";
			
			t.types_list = build_type_obj_list();
			
			t.stream_icon = StreamIcon.new();
			
			print t.display();
		end
		
		def build_type_obj_list(icon_id = nil)
			types_list = TypeIDItem.find(:scan);
			
			if(icon_id != nil)
				icon_types_list = StreamIconType.find(icon_id, :iconid);
			end
			
			display_list = OrderedMap.new();
			
			for type_obj in types_list
				temp = StreamIconTypeDisplay.new();
				temp.type_id = type_obj.typeid;
				temp.type_name = type_obj.typename;
				temp.icon_id = icon_id;
				temp.associated = false;
				
				display_list[type_obj.typeid] = temp;
			end
			
			if(icon_types_list != nil && !icon_types_list.empty?())
				for icon_type in icon_types_list
					if(display_list[icon_type.typeid] != nil)
						display_list[icon_type.typeid].associated = "checked";
					end
				end
			end
			
			return display_list;
		end
		
		def edit_stream_icon(icon_id)
			t = Template.instance("streams", "music_new_stream_icon");
			
			stream_icon = StreamIcon.find(icon_id, :first);
			
			t.stream_icon = stream_icon;
			
			t.submit_location = "edit/#{icon_id}";
			
			t.types_list = build_type_obj_list(icon_id);
			
			print t.display();
		end
		
		def create_stream_icon()
			icon_image_location = params["stream_icon_image", String];
			icon_thumbnail_location = params["stream_icon_thumb", String];
			
			selected_types = Array.new();
			params.each{|param|
				if /icon_type_\d/.match(param)
					param_split = param.split("_");
					type_id = param_split[2];
					selected_types << type_id;
				end
			};
			
			stream_icon = StreamIcon.new();
			
			stream_icon.image = icon_image_location;
			stream_icon.thumbnail = icon_thumbnail_location;
			stream_icon.userid = request.session.user.userid;
			stream_icon.date = Time.now.to_i();
			
			stream_icon.store();
			
			for type in selected_types
				temp = StreamIconType.new();
				
				temp.iconid = stream_icon.iconid;
				temp.typeid = type;
				
				temp.store();
			end
			
			site_redirect("/music/administration/streamicons/");
		end
		
		def update_stream_icon(icon_id)
			icon_image_location = params["stream_icon_image", String];
			icon_thumbnail_location = params["stream_icon_thumb", String];
			
			selected_types = Array.new();
			params.each{|param|
				if /icon_type_\d/.match(param)
					param_split = param.split("_");
					type_id = param_split[2];
					selected_types << type_id;
				end
			};
			
			stream_icon = StreamIcon.find(icon_id, :first);
			stream_icon.thumbnail = icon_thumbnail_location;
			stream_icon.image = icon_image_location;
			
			stream_icon.store();
			
			stream_icon.icon_types.each{|type| 
				type.delete();
			};
			
			for type in selected_types
				temp = StreamIconType.new();
				
				temp.iconid = stream_icon.iconid;
				temp.typeid = type;
				
				temp.store();
			end
			
			site_redirect("/music/administration/streamicons/");
		end
		
		def delete_stream_icon(icon_id)
			stream_icon = StreamIcon.find(icon_id, :first);
			
			stream_icon.icon_types.each{|icon_type|
				icon_type.delete();
			};
			
			stream_icon.delete();
			
			site_redirect("/music/administration/streamicons/");
		end
		
		def display_stream_icon_administration()
			t = Template.instance("streams", "music_stream_icon_administration");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			inject_js(t);
			
			icon_list = StreamIcon.find(:scan, :order => "iconid ASC");
			
			t.icon_list = icon_list;
			
			print t.display();
		end
		
		def display_news_administration()
			t = Template.instance("streams", "music_news_administration");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			inject_js(t);
			
			#t.news_item_list = MusicNews.find(:total_rows, :page => requested_page, :order => "date DESC", :limit => 5);
			t.news_item_list = MusicNews.find(:scan, :order => "date DESC");
			print t.display();
		end
		
		def display_feature_administration()
			t = Template.instance("streams", "music_feature_administration");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			inject_js(t);
			
			t.feature_list = MusicFeature.find(:scan, :order => "startdate DESC");
			
			print t.display();
		end
		
		def new_news_item()
			icon_row_count = 5;
			
			t = Template.instance("streams", "music_new_news_entry");
		
			t.submit_location = "new"
			t.news = MusicNews.new();
			
			t.icon_row_list = build_icon_rows(MusicNews.typeid, icon_row_count);
			
			print t.display();
		end
		

		
		def edit_news_item(news_item_id)
			t = Template.instance("streams", "music_new_news_entry");
			
			icon_row_count = 5;
			
			news_item = MusicNews.find(news_item_id, :first);
			
			if(news_item == nil)
				site_redirect("/music/");
			end
			
			tag_word_string = "";
			i=0;
=begin
			while i < news_item.tag_list.length
				
				tag_word_string << news_item.tag_list.at(i).tagname;
				
				if(i < news_item.tag_list.length - 1)
					tag_word_string << ", ";
				end
				
				i = i + 1;
			end
=end	
			t.news = news_item;
			
			#t.tag_words = tag_word_string;
			
			t.submit_location = "edit/#{news_item_id}";
			
			t.icon_row_list = build_icon_rows(MusicNews.typeid, icon_row_count, news_item.stream_entry.iconid);
			
			print t.display();
		end
		
		def create_news_item()
		
			news_title = params['news_title', String];
			news_brief = params['news_brief', String];
			news_body = params['news_body', String];
			icon_selection = params['icon_choice', String];
			
			icon_selection_parts = icon_selection.split('_');
			
			if(icon_selection_parts.length == 2)
				news_icon_id = icon_selection_parts[1];
			end
			
			news_contributor = request.session.user.userid;
			tag_associations = params['tag_item_associate', String];
			tag_item_list = tag_associations.split(',');
			
			tag_item_list.each{|tag| tag.strip!();};
			
			news_entry = MusicNews.new();
			news_entry.title = news_title;
			news_entry.brief = news_brief;
			news_entry.body = news_body;
			news_entry.date = Time.now.to_i;
			news_entry.userid = news_contributor;
			
			news_entry.store();
			
			stream_entry = StreamEntry.new();
			stream_entry.primaryid = news_entry.id;
			stream_entry.typeid = MusicNews.typeid;
			stream_entry.userid = news_contributor;
			stream_entry.date = Time.now.to_i;
			stream_entry.iconid = news_icon_id;
			
			stream_entry.store();
			
			tag_id_list = Array.new();
			tag_item_list.each{|tag_string|
				tag = StreamTag.find(tag_string, :tagname, :first);
				
				if(tag == nil)
					tag = add_new_tag(tag_string);
				end
				
				tag_id_list << tag.tagid;
			};
			
			tag_id_list.each{|tag_id|
				tag_item(stream_entry, tag_id);
			};
			
			site_redirect("/music/administration/news/");
		end
		
		def update_news_item(news_item_id)
			news_item = MusicNews.find(news_item_id, :first);
			
			if(news_item == nil)
				#add code to indicate and error has occured
				site_redirect("/music/");
			end
			
			news_title = params['news_title', String];
			news_brief = params['news_brief', String];
			news_body = params['news_body', String];
			icon_selection = params['icon_choice', String];
			update_timestamp = params['news_update_date', String];
			
			icon_selection_parts = icon_selection.split('_');
			
			if(icon_selection_parts.length == 2)
				news_icon_id = icon_selection_parts[1];
			end
			
			removal_list = Array.new();
			
			for key in params.keys()
				if(/^tag_(\d+)$/.match(key))
					temp_removal_parts = key.split('_');
					removal_list << temp_removal_parts[1];
				end
			end
			
			news_item.title = news_title;
			news_item.brief = news_brief;
			news_item.body = news_body;
			
			stream_entry = news_item.stream_entry;
			stream_entry.iconid = news_icon_id;
			
			if(update_timestamp != nil)
				news_item.date = Time.now.to_i();
				stream_entry.date = Time.now.to_i();
			end
			
			stream_entry.store();
			
			news_item.store();

			tag_associations = params['tag_item_associate', String];
			new_tag_list = tag_associations.split(',');
			
			stream_entry = news_item.stream_entry();
			
			existing_tags = stream_entry.tags();
			
			update_item_tags(stream_entry, removal_list, new_tag_list);
			
			$log.info("I'm redirecting");
			
			site_redirect("/music/administration/news/")
		end
		
		def delete_item(type_id, obj_prim_id, obj_sec_id = nil)
			
			storable_class = TypeID.get_class(type_id);
			if (storable_class.indexes[:PRIMARY].length > 1 && obj_sec_id != nil)
				obj_del =  storable_class.find(obj_prim_id, obj_sec_id, :first);
			else
				obj_del =  storable_class.find(obj_prim_id, :first);
			end
			
			obj_del.stream_entry.entry_tags.each{|entry_tag| entry_tag.delete();};
			
			obj_del.stream_entry.delete();
			
			obj_del.delete();
			
			site_redirect("/music/administration/news");
		end
		
		def display_sidebar_feature_administration()
			t = Template.instance("streams", "music_sidebar_feature_administration");
			
			request.reply.headers['X-width'] = 0;
			
			inject_css(t);
			inject_js(t);
			
			t.sidebar_feature_list = SidebarFeature.find(:scan, :order => "active ASC");
			
			print t.display();
		end
		
		def new_sidebar_feature()
			t = Template.instance("streams", "music_new_sidebar_feature");
			
			inject_css(t);
			
			t.submit_location = "new";
			
			t.feature = SidebarFeature.new();
			
			print t.display();
		end
		
		def edit_sidebar_feature(spec_id)
			t = Template.instance("streams", "music_new_sidebar_feature");
			
			inject_css(t);
			
			t.submit_location = "edit/#{spec_id}";
			
			t.feature = SidebarFeature.find(spec_id, :first);
			
			print t.display();
		end
		
		def create_sidebar_feature()
			feature_content = params['feature_location', String];
			feature_link = params['feature_link', String];
			feature_active = params['feature_active', String];
			feature_width = params['feature_width', Integer, 0];
			feature_height = params['feature_height', Integer, 0];
			
			sidebar_feature = SidebarFeature.new();
			
			sidebar_feature.content = feature_content;
			sidebar_feature.link = feature_link;
			if(feature_active == "active")
				sidebar_feature.active = true;
			else
				sidebar_feature.active = false;
			end
			sidebar_feature.height = feature_height;
			sidebar_feature.width = feature_width;
			sidebar_feature.date = Time.now.to_i();
			
			sidebar_feature.store();
			
			site_redirect("/music/administration/sidebarfeatures/");
		end
		
		def update_sidebar_feature(spec_id)
			sidebar_feature = SidebarFeature.find(spec_id, :first);
			if(sidebar_feature == nil)
				site_redirect("/music/administration/sidebarfeatures/");
			end
			
			feature_content = params['feature_location', String];
			feature_link = params['feature_link', String];
			feature_active = params['feature_active', String];
			feature_width = params['feature_width', Integer];
			feature_height = params['feature_height', Integer];
			
			sidebar_feature.content = feature_content;
			sidebar_feature.link = feature_link;
			if(feature_active == "active")
				sidebar_feature.active = true;
			else
				sidebar_feature.active = false;
			end
			sidebar_feature.height = feature_height;
			sidebar_feature.width = feature_width;
			
			sidebar_feature.store();
			
			site_redirect("/music/administration/sidebarfeatures/");
		end
		
		def delete_sidebar_feature(spec_id)
			sidebar_feature = SidebarFeature.find(spec_id, :first);
			if(sidebar_feature == nil)
				site_redirect("/music/administration/sidebarfeatures/");
			end
			
			sidebar_feature.delete();
			
			site_redirect("/music/administration/sidebarfeatures/");
		end
	end
end
