lib_require :Core, 'storable/storable'

class VideoNavigate < Storable

	set_enums(
		:view => {:default => 0, :feature => 1},
		:sort => {:recent => 0, :random =>1, :popular => 2, :best => 3, :worst => 4}
	);	

  
	init_storable(:usersdb, 'videonavigate');


	def VideoNavigate.get_for_user(userid)
		navigate = VideoNavigate.find(:first, userid);
		if (navigate.nil?)
			navigate = VideoNavigate.new;
			navigate.userid = userid;
		end
	
		return navigate;
	end

end