lib_want	:Profile, "profile_block_query_info_module";
lib_require :Core, 'validation/chain', 'validation/rules'

class VitalsBlock < PageHandler
	declare_handlers("profile_blocks/Profile/vitals") {
		area :User
		access_level :Any
		page	:GetRequest, :Full, :vitals_block, input(Integer);
		
		area :Self
		page 	:GetRequest, :Full, :vitals_block_edit, input(Integer), "edit";
		handle	:PostRequest, :vitals_block_save, input(Integer), "save";
	}
	
	Vital = Struct.new(:name, :value)
	
	def vitals_block(block_id)
		t = Template::instance('profile', 'vitals_block_view');
		t.user = request.user
		
		vitals = []
		vitals << Vital.new("Username:", request.user.username)
		real_name = request.user.real_name(request.session.user)
		if (!real_name.empty?)
			vitals << Vital.new("Real Name:", real_name)
		end
		
		vitals << Vital.new("Age:", "#{request.user.age}")
		vitals << Vital.new("Birthday:",Time.at(request.user.dob).strftime("%B %d")) if (request.user.profile.showbday)
		vitals << Vital.new("Sex:", request.user.sex)
		vitals << Vital.new("Location:", request.user.location.to_s)
		
		t.vitals = vitals
		
		print t.display();
	end
	
	def self.vitals_block_query(info)
		if(site_module_loaded?(:Profile))
			info.extend(ProfileBlockQueryInfo);
			info.title = "Vitals";
			info.initial_position = 10;
			info.initial_column = 1;
			info.form_factor = :wide;
			info.multiple = false;
			info.removable = false;
		end
		
		return info;
	end
	
	def vitals_block_edit(block_id)
		t = Template::instance('profile', 'vitals_edit_block')
		t.user = request.user
		t.dob = Time.at(t.user.dob).strftime("%m/%d/%Y")
		puts t.display
	end
	
	def vitals_block_save(block_id)
		month = params['month', Integer]
		day = params['day', Integer]
		year = params['year', Integer]
		dob_chain = Validation::Chain.new
		dob_chain.add(Validation::Rules::CheckDateOfBirth.new(
			Validation::ValueAccessor.new("year", year),
			Validation::ValueAccessor.new("month", month),
			Validation::ValueAccessor.new("day", day)));
		
		if (dob_chain.validate.state == :valid)
			request.user.dob = Time.local(year, month, day).to_i
			request.user.age = ((Time.now.to_i-request.user.dob)/Constants::YEAR).to_i;
		end
		
		sex = params['sex', String]
		sex_chain = Validation::Chain.new
		sex_chain.add(Validation::Rules::CheckSex.new(Validation::ValueAccessor.new("sex", sex)))
		
		if (sex_chain.validate.state == :valid)
			request.user.sex = sex
		end
		
		location = params["location", Integer]
		location_chain = Validation::Chain.new
		location_chain.add(Validation::Rules::CheckLocation.new(Validation::ValueAccessor.new("location", location)))
		
		if (location_chain.validate.state == :valid)
			request.user.loc = location
		end
		
		request.user.store
	end
end
