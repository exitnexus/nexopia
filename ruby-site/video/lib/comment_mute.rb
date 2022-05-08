lib_require :Core, 'storable/storable'
lib_require :Video, 'video'

class CommentMute < Storable
	init_storable(:videodb, 'commentmute');
	
	relation_singular :user, :userid, User;
	relation_singular :mod, :modid, User;
	relation_singular :video, :videoid, Video;
	
	attr_reader :reports, :display_mute_time, :display_unmute_time;
	

	
	def after_load()
		@display_mute_time = Time.at(mutetime);
		@display_unmute_time = Time.at(unmutetime);
	end


	def after_create()
		after_load();
	end
	

	def CommentMute.find_last(userid,videoid=nil)
		current_time = Time.now.to_i;
		
		conditions = Array.new;
		conditions[0] = "unmutetime > ? AND userid = ?";
		conditions << current_time;
		conditions << userid;
		
		if (!videoid.nil?)
			conditions[0] += " AND ((videoid = ?) OR (global = 'y'))";
			conditions << videoid;
		end
		
		mute = CommentMute.find(:first, :conditions => conditions);
	
		return mute;
	end
	
	
	def username
		return user.username;
	end
	
	
	def video_title
		return video.title;
	end
	
	
	def mod_name
		return mod.username;
	end

end