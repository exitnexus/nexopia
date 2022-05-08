lib_require :Streams, 'band_entry', 'entry_tag', 'music_news', 'stream_entry', 'stream_tag', 'music_feature', 'music_display_stream', 'music_helper';

module Music
	class BandHandler < PageHandler
	
		include MusicHelper
	
		declare_handlers("music"){
			area :Public
			access_level :Any
			
			#page 	:GetRequest, :Full, :band_entries_display, "bandentries";
			#page 	:GetRequest, :Full, :band_entries_display, "bandentries", "page", input(Integer);
			
			#page 	:GetRequest, :Full, :new_band_entry, "bandentry", "new";
			#page	:GetRequest, :Full, :edit_band_entry, "bandentry", "edit", input(Integer);
			
			#page 	:GetRequest, :Full, :display_band_administration, "administration", "bandentries";
			
			#handle	:GetRequest, :delete_band_entry, "bandentry", "delete", input(Integer);
			
			#handle	:PostRequest, :create_band_entry, "bandentry", "new", "submit";
			#handle	:PostRequest, :update_band_entry, "bandentry", "edit", input(Integer), "submit";
		};
		
		#This is the pagehandler for the main band display list. It needs to be paged at some point.
		def band_entries_display(page = 1)
			t = Template.instance("streams", "music_list_band_entries");
			
			inject_css(t);
			
			if(!request.session.anonymous?() && !request.session.user.anonymous?())
      			user_band = BandEntry.find(:first, :conditions => ["userid = ?", request.session.user.userid]);

      			if(user_band != nil)
      				t.user_band = user_band;
      			end
      			
      			if(request.session.has_priv?(StreamsModule, :edit))
      				t.show_admin_panel = true;
      			end
      		end
			
			band_list = StreamTag.find_items_by_name("User Band");
			
			t.band_entries = compose_band_list(band_list);
			
			print t.display();
		end
		
		def new_band_entry()
			if(request.session.anonymous?() || request.session.user.anonymous?())
				request_user_login("/music/bandentry/new/", "register a band");
				return;
			end
			
			icon_row_count = 5;
			
			t = Template.instance("streams", "music_new_band_entry");
		
			t.band = BandEntry.new();
			t.submit_location = "new";
		
			t.icon_row_list = build_icon_rows(BandEntry.typeid, icon_row_count);
		
			print t.display();
		end
		
		def edit_band_entry(band_id)
		
			band_entry = BandEntry.find(band_id, :first);
		
			if(request.session.anonymous?() || request.session.user.anonymous?() || band_entry == nil || 
			(!request.session.has_priv?(StreamsModule, :edit) && request.session.user.userid != band_entry.userid))
				site_redirect("/music/");
			end
			
			t = Template.instance("streams", "music_new_band_entry");
			
			icon_row_count = 5;
			
			t.band = band_entry;
			
			t.submit_location = "edit/#{band_id}";
			
			t.icon_row_list = build_icon_rows(BandEntry.typeid, icon_row_count, band_entry.stream_entry.iconid);
			
			print t.display();
		end
		
		def create_band_entry()
		
			if(request.session.anonymous?() || request.session.user.anonymous?())
				site_redirect("/music/");
			end
			
			band_name = params["band_name", String];
			band_url = params["band_url", String];
			band_bio = params["band_bio", String];
			band_genre = params["band_genre", String];
			icon_selection = params['icon_choice', String];
			band_submission_user = request.session.user.userid;
			band_submission_date = Time.now.to_i();
			
			icon_selection_parts = icon_selection.split('_');
			
			if(icon_selection_parts.length == 2)
				band_icon_id = icon_selection_parts[1];
			end
			
			band_entry = BandEntry.new();
			
			band_entry.name = band_name;
			band_entry.uri = band_url;
			band_entry.bio = band_bio;
			band_entry.genre = band_genre;
			band_entry.userid = band_submission_user;
			band_entry.date = band_submission_date;
			
			band_entry.store();
			
			stream_entry = StreamEntry.new();
			stream_entry.userid = band_submission_user;
			stream_entry.date = band_submission_date;
			stream_entry.typeid = BandEntry.typeid;
			stream_entry.primaryid = band_entry.id;
			stream_entry.iconid = band_icon_id;
			
			stream_entry.store();
			
			tag = StreamTag.find("User band", :tagname, :first);
			if(tag == nil)
				tag = add_new_tag("User band");
			end
			
			tag_item(stream_entry, tag.tagid);
			
			site_redirect("/music/bandentries/");
		end
		
		def update_band_entry(band_id)
			band_entry = BandEntry.find(band_id, :first);
			
			if(request.session.anonymous?() || request.session.user.anonymous?() || band_entry == nil || 
			(!request.session.has_priv?(StreamsModule, :edit) && request.session.user.userid != band_entry.userid))
				site_redirect("/music/");
			end
			
			band_name = params["band_name", String];
			band_url = params["band_url", String];
			band_bio = params["band_bio", String];
			band_genre = params["band_genre", String];
			icon_selection = params["icon_choice", String];
			
			if(params.has_key?("band_cancel"))
				site_redirect("/music/");
			end
			
			if(band_entry == nil)
				site_redirect("/music/bandentries/");
			end
			
			icon_selection_parts = icon_selection.split('_');
			
			if(icon_selection_parts.length == 2)
				band_icon_id = icon_selection_parts[1];
			end
			
			band_entry.name = band_name;
			band_entry.uri = band_url;
			band_entry.bio = band_bio;
			band_entry.genre = band_genre;
			
			stream_entry = band_entry.stream_entry;
			stream_entry.iconid = band_icon_id;
			stream_entry.store();
			
			band_entry.store();
			
			if(request.session.has_priv?(StreamsModule, :edit))
				site_redirect("/music/administration/bandentries/");
			else
				site_redirect("/music/bandentries/");
			end
		end
		
		def compose_band_list(bands)
			band_lists = Array.new();
			
			if(bands.length < 10)
				limit = bands.length;
			else
				limit = 10;
			end

			i = 0;
			while i < limit
				temp = MusicBandPair.new();
				temp.band_1 = bands.at(i);
				if(bands.at(i + 1) != nil)
					temp.band_2 = bands.at(i + 1);
				end
				band_lists << temp;
				i = i + 2;
			end
			
			return band_lists;
		end
		
		def display_band_administration()
			t = Template.instance("streams", "music_band_administration");
			
			inject_css(t);
			
			t.user_band_list = BandEntry.find(:all);
			
			print t.display();
		end
		
		def delete_band_entry(band_id)
			
			band = BandEntry.find(band_id, :first);
			
			if(request.session.anonymous?() || request.session.user.anonymous?() || band_entry == nil || 
			(!request.session.has_priv?(StreamsModule, :edit) && request.session.user.userid != band_entry.userid))
				site_redirect("/music/");
			end
			
			if(band.stream_entry != nil)
				band.stream_entry.entry_tags.each{|entry_tag| entry_tag.delete();};
			
				band.stream_entry.delete();
			end
			band.delete();
			
			if(request.session.has_priv?(StreamsModule, :edit))
				site_redirect("/music/administration/bandentries/");
			else
				site_redirect("/music/");
			end
		end
	end
end