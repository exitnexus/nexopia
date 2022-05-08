lib_require :Core, 'storable/storable'

class VideoReport < Storable
	init_storable(:videodb, 'videoreport');
	relation_singular :repuser, :repuserid, User;
	
	def username
		if (repuser.nil?)
			return nil;
		end
		
		return repuser.username;
	end

end