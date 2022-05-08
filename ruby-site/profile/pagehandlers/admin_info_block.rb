lib_want	:Profile, "profile_block_query_info_module";
lib_require :Core, 'validation/chain', 'validation/rules'

class AdminInfo < PageHandler
	declare_handlers("profile_blocks/Profile/admin_info") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :admin_info_block, input(Integer);

	}
	
	Vital = Struct.new(:name, :value)
	
	def admin_info_block(block_id)
		edit_mode = params["profile_edit_mode", Boolean, false];
		
		if(!Profile::ProfileDisplayBlock.verify_visibility(block_id, request.user, request.session.user, edit_mode))
			print "<h1>Not visible</h1>";
			return;
		end
		
		t = Template::instance('profile', 'admin_info_block_view');
		t.user = request.user
		
		admin_info = []
		admin_info << Vital.new("Username:", request.user.username)
		admin_info << Vital.new("Userid:", request.user.userid)

		real_name = request.user.real_name(request.session.user)
		if (!real_name.empty?)
			admin_info << Vital.new("Real Name:", real_name)
		end
				
		if(request.session.has_priv?(CoreModule, "showemail"))
			admin_info << Vital.new("Email:", request.user.email)
		end		

		admin_info << Vital.new("--", "--")
		
		admin_info << Vital.new("Age:", "#{request.user.age}")
		admin_info << Vital.new("Sex:", request.user.sex)
		admin_info << Vital.new("Location:", request.user.location.to_s)
		admin_info << Vital.new("Birthday:", TimeFormat.date(Time.at(request.user.dob).getgm))

		admin_info << Vital.new("--", "--")
		
		if(request.user.plus?() && request.session.has_priv?(CoreModule, "viewinvoice"))
			admin_info << Vital.new("Plus:", request.user.remaining_plus_days() + " Days")
			admin_info << Vital.new("Expiry:", request.user.plus_expiry_date());
		end
		admin_info << Vital.new("Join Date:", TimeFormat.date_and_time(request.user.jointime))
		admin_info << Vital.new("Profile Updated:", TimeFormat.date_and_time(request.user.profile.profileupdatetime))
		admin_info << Vital.new("Last Active:", TimeFormat.pretty(request.user.activetime(true).to_i))
		
		t.admin_info = admin_info
		
		print t.display();
	end
	
	def self.admin_info_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.default_visibility = :admin;
			info.title = "Admin Info";
			info.initial_position = 5;
			info.initial_column = 1;
			info.form_factor = :wide;
			info.editable = false;
			info.multiple = false;
			info.removable = false;
			info.moveable = false;

			info.content_cache_timeout = 120;
		end
		
		return info;
	end
end
