lib_require :Core, 'data_structures/paged_result'
lib_require :Video, 'comment_mute'

class MutedUser

	attr_reader :user, :mutes;
	
	
	def MutedUser.get_page(page,limit_per_page)
		muted_users = PagedResult.new;
		mutes = CommentMute.find(:total_rows, :group => "userid", :limit => limit_per_page, :page => page);
		index = 0;
		mutes.each { |mute|
			muted_user = MutedUser.new(mute.userid);
			muted_users[index] = muted_user;
			index = index + 1;
		};
		

		muted_users.page = page;
		muted_users.total_rows = index;
		muted_users.page_length = limit_per_page;
		muted_users.calculated_total = index;
		
		return muted_users;
	end
	

	def initialize(userid)
		@user = User.find(:first, :promise, userid);
		@mutes = CommentMute.find(:all, :promise, :order => "unmutetime DESC", :conditions => ["userid = ?", userid]);
	end


	def username
		return user.username;
	end
	
	def total_rows
		return 1;
	end
	
end