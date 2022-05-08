lib_want :Core, 'time_format'
lib_want	:Profile, "profile_block_query_info_module";
lib_require :Profile, "profile";
lib_require :Core, 'users/locs', 'validation/chain', 'validation/rules'


class BasicsBlock < PageHandler
	declare_handlers("profile_blocks/Profile/basics/") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :basics_block, input(Integer);

		area :Self
		access_level :IsUser, CoreModule, :editprofile
		
		page 	:GetRequest, :Full, :basics_block_edit, input(Integer), "edit";
		page	:GetRequest, :Full, :basics_block_edit, "new";
		
		
		handle	:PostRequest, :basics_block_save, input(Integer), "save";
		handle	:PostRequest, :basics_block_save, input(Integer), "create";
		
		handle	:PostRequest, :visibility_save, input(Integer), "visibility";
		
		handle	:PostRequest, :basics_block_remove, input(Integer), "remove";
	}
	
	Basic = Struct.new(:name, :value)
	
	def basics_block(block_id)
		edit_mode = params["profile_edit_mode", Boolean, false];
		
		if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
			print "<h1>Not visible</h1>";
			return;
		end
		
		t = Template::instance('profile', 'basics_block_view');
		t.user = request.user
		
		basics = []
		
		height = request.user.profile.display_string(:height)
		basics << Basic.new("Height:", height) if height
		
		weight = request.user.profile.display_string(:weight)
		basics << Basic.new("Weight:", weight) if weight
		
		basics << Basic.new("Birthday:", TimeFormat.date(Time.at(request.user.dob).getgm)) if (request.user.profile.showbday)

		orientation = request.user.profile.display_string(:orientation)
		basics << Basic.new("Sexual Orientation:", orientation) if orientation
	
		dating = request.user.profile.display_string(:dating)
		basics << Basic.new("Dating:", dating) if dating
		
		living = request.user.profile.display_string(:living)
		basics << Basic.new("Living Situation:", living) if living
		
		basics << Basic.new("Location:", Locs.get_by_id(Locs.get_id_by_name(request.user.location.to_s)).name_path)
				
		basics << Basic.new("Join Date:", TimeFormat.date_and_time(request.user.jointime)) if (request.user.profile.showjointime)
		basics << Basic.new("Profile Updated:", TimeFormat.pretty(request.user.profile.profileupdatetime)) if (request.user.profile.showprofileupdatetime)
		basics << Basic.new("Last Active:", TimeFormat.pretty(request.user.activetime(true).to_i)) if (request.user.profile.showactivetime)
		
		# If there are no basics to show, don't display the block
		if (basics.empty?)
			return;
		end		
		
		t.basics = basics
		
		print t.display();
	end
	
	def self.basics_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Basics";
			info.initial_position = 10;
			info.initial_column = 1;
			info.initial_block = true;
			info.form_factor = :wide;
			info.multiple = false;
			info.removable = true;
			info.max_number = 1;
			info.admin_editable = true
			
			# When real names goes in properly, this will need to not cache.
			info.content_cache_timeout = 120
		end
		
		return info;
	end
	
	def basics_block_edit(block_id = nil)
		t = Template::instance('profile', 'basics_edit_block')
		
		t.user = request.user
		t.showbday = request.user.profile.showbday
		t.showjointime = request.user.profile.showjointime
		t.showprofileupdatetime = request.user.profile.showprofileupdatetime
		t.showactivetime = request.user.profile.showactivetime
		
		puts t.display
	end
	
	def basics_block_save(block_id)

		if ( !(Profile::Profile::HEIGHT.assoc( params['height', String, nil] )).nil? );
			request.user.profile.height = params['height', String, nil];
		else
			request.user.profile.height = "0";
		end

		if ( !(Profile::Profile::WEIGHT.assoc( params['weight', String, nil] )).nil? );		
			request.user.profile.weight = params['weight', String, nil];
		else
			request.user.profile.weight = "0";
		end
		
		if ( !(Profile::Profile::SEXUAL_ORIENTATION.assoc( params['orientation', String, nil] )).nil? );		
			request.user.profile.orientation = params['orientation', String, nil];
		else
			request.user.profile.orientation = "0";
		end
		
		if ( !(Profile::Profile::LIVING_SITUATION.assoc( params['living', String, nil] )).nil? );				
			request.user.profile.living = params['living', String, nil];
		else
			request.user.profile.living = "0";
		end
		
		if ( !(Profile::Profile::DATING_SITUATION.assoc( params['dating', String, nil] )).nil? );						
			request.user.profile.dating = params['dating', String, nil];
		else
			request.user.profile.dating = "0";
		end
		
		if ( !(params['showbday', String, nil]).nil? );
			request.user.profile.showbday = true;
		else
			request.user.profile.showbday = false;
		end
		
		if ( !(params['showjointime', String, nil]).nil? );
			request.user.profile.showjointime = true;
		else
			request.user.profile.showjointime = false;
		end

		if ( !(params['showprofileupdatetime', String, nil]).nil? );
			request.user.profile.showprofileupdatetime = true;
		else
			request.user.profile.showprofileupdatetime = false;
		end
		
		if ( !(params['showactivetime', String, nil]).nil? );
			request.user.profile.showactivetime = true;
		else
			request.user.profile.showactivetime = false;
		end

		
		year = params['year', Integer, nil];
		month = params['month', Integer, nil];
		day = params['day', Integer, nil];
		
		dob_chain = Validation::Chain.new
		dob_chain.add(Validation::Rules::CheckDateOfBirth.new(
			Validation::ValueAccessor.new("year", year),
			Validation::ValueAccessor.new("month", month),
			Validation::ValueAccessor.new("day", day)));
		
		store_user = true;
		if (dob_chain.validate.state == :valid)
			request.user.dob = Time.utc(year, month, day).to_i
			request.user.age = request.user.calculate_age(request.user.dob)
		else
			store_user = false;
		end
		
		location = params["location", Integer]
		
		location_chain = Validation::Chain.new
		location_chain.add(Validation::Rules::CheckLocation.new(Validation::ValueAccessor.new("location", location)))
		
		if (location_chain.validate.state == :valid)
			request.user.loc = location
		else
			store_user = false;
		end

		request.user.store if store_user;
		
		request.user.profile.profileupdatetime = Time.now.to_i
		request.user.profile.store
	end
	
	def basics_block_remove(block_id)
		return;
	end
	
	def visibility_save(block_id)
		return;
	end
end
