lib_require :Blogs, "blog_post";
lib_require :Blogs, "blog_date_map";
lib_require :Blogs, "blog_profile";
lib_require :Blogs, "blog_last_read_friends";
lib_require :Blogs, "blog_comment_unread_notification";

class User < Cacheable
	relation :paged, :blog_posts, [:userid], Blogs::BlogPost, {:order => "time DESC", :extra_columns => [:time, :visibility, :typeid]}
	relation :paged, :blog_posts_minimal, [:userid], Blogs::BlogPost, :order => "time DESC", :extra_columns => [:time, :visibility, :typeid], :selection => :minimal

	# filtered_post_count is the same as post_count.  It's here to get around a limitation in the relation caching system.
	# you can't look at the same relation with two different sets of options in the same page request since once it's done one
	# it will just give you the cached answer from then on.  NEX-1363
	relation :count, :filtered_post_count, [:userid], Blogs::BlogPost, :extra_columns => [:visibility, :typeid]
	relation :count, :post_count, [:userid], Blogs::BlogPost, :extra_columns => [:visibility, :typeid]
	
	relation :count, :freeform_post_count, [:userid], Blogs::BlogPost, :conditions => "typeid = 0", :extra_columns => [:typeid]
	relation :count, :photo_post_count, [:userid], Blogs::PhotoBlog
	relation :count, :battle_post_count, [:userid], Blogs::BattleBlog
	relation :count, :poll_post_count, [:userid], Blogs::PollBlog
	relation :count, :video_post_count, [:userid], Blogs::VideoBlog

	#This relation should be accessed on the user viewing a blog and should include 
	# :conditions => [":bloguserid => ?", bloguserid] as a runtime argument
	relation :paged, :collapsed_blog_posts, [:userid], Blogs::BlogNavigation, :extra_columns => [:bloguserid, :postid]

	relation :singular, :blog_profile, [:userid], Blogs::BlogProfile;
	relation :singular, :blog_friends_last_read, [:userid], Blogs::BlogLastReadFriends;
	relation :count, :blog_unread_replies_count, [:userid], Blogs::BlogCommentUnreadNotification;
	relation :multi, :blog_unread_replies, [:userid], Blogs::BlogCommentUnreadNotification, {:order => "time DESC", :extra_columns => :time};

	relation :multi, :freeform_blog_posts, [:userid], Blogs::BlogPost, {:order => "time DESC", :conditions => "typeid = 0", :extra_columns => [:typeid, :time]}	
	relation :multi, :photo_blog_posts, [:userid], Blogs::BlogPost, {:order => "time DESC", :conditions => "typeid = #{Blogs::PhotoBlog.typeid}", :extra_columns => [:typeid, :time]}
	relation :multi, :video_blog_posts, [:userid], Blogs::BlogPost, {:order => "time DESC", :conditions => "typeid = #{Blogs::VideoBlog.typeid}", :extra_columns => [:typeid, :time]}
	relation :multi, :poll_blog_posts, [:userid], Blogs::BlogPost, {:order => "time DESC", :conditions => "typeid = #{Blogs::PollBlog.typeid}", :extra_columns => [:typeid, :time]}
	relation :multi, :battle_blog_posts, [:userid], Blogs::BlogPost, {:order => "time DESC", :conditions => "typeid = #{Blogs::BattleBlog.typeid}", :extra_columns => [:typeid, :time]}
	
	self.postchain_method(:after_create, &lambda {
		blog_profile = Blogs::BlogProfile.new();
		
		blog_profile.userid = self.userid;
		blog_profile.views = 0;
		
		blog_profile.store();
		
		blog_last_read = Blogs::BlogLastReadFriends.new();
		
		blog_last_read.userid = self.userid;
		blog_last_read.readtime = Time.now.to_i();
		
		blog_last_read.store();
	});
	
	def show_hits()
	  if !plus? then
	    show_hits = true
    else
      show_hits = blog_profile.showhits
    end
  end
	
	# This makes working with some of the memcached data easier to understand, and will hopefully make the transition
	# back to minimalistic storable relations easier, once they are working. It adds a fairly minimal overhead to the
	# data that is stored in memcache (the raw array equivalent is around 70-75% of the size in the tests I did). The
	# reason this is not a Struct is that the Struct form basically doubled (or more) the memcache overhead, and I do
	# not want to introduce any huge performance killers in the name of code simplification.
	class RawBlogPostData	
		def initialize(userid,id,time,visibility)
			@data= [userid, id, time, visibility]
		end
		
		def userid() return @data[0] end
		def id() return @data[1] end
		def time() return @data[2] end
		def visibility() return @data[3] end
	end
	
	
	# Return a minimal data set for the blog posts of the current user.
	def raw_blog_post_data
		raw_posts = $site.memcache.get("user_blog_minimal_post_info-#{self.userid}");
		
		if(raw_posts.nil?())
			results = Blogs::BlogPost.db.query("SELECT userid, id, time, visibility, typeid FROM blog WHERE userid = # ORDER BY time DESC", self.userid);
			raw_posts = [];
			results.each{|row|
				raw_posts << RawBlogPostData.new(row['userid'].to_i(), row['id'].to_i(), row['time'].to_i(), row['visibility'].to_i());
			};
			$site.memcache.set("user_blog_minimal_post_info-#{self.userid}", raw_posts, 7*24*60*60);
		end

		return raw_posts
	end
	
	# Return a minimal blog data set for each post at or above the given visibility level.
	# Results are sorted by time newest to oldest.
	def filtered_raw_blog_post_data(viewer_visibility_level, filter_type)
		
		filtered_results = [];
		post_data = [];
		
		if( filter_type.nil? )
			post_data = self.raw_blog_post_data;
		else
			case filter_type
			when 0
				post_data = self.freeform_blog_posts
			when Blogs::PhotoBlog.typeid
				post_data = self.photo_blog_posts
			when Blogs::VideoBlog.typeid
				post_data = self.video_blog_posts
			when Blogs::PollBlog.typeid
				post_data = self.poll_blog_posts
			when Blogs::BattleBlog.typeid
				post_data = self.battle_blog_posts				
			end			
		end
		
		if(viewer_visibility_level.kind_of?(Symbol))
			viewer_visibility_level = BlogVisibility.list[viewer_visibility_level];
		end
		
		post_data.each{ |post|
			if(post.visibility >= viewer_visibility_level)
				filtered_results << RawBlogPostData.new(post.userid, post.id, post.time, post.visibility);
			end
		};
		
		filtered_results.sort{|x,y| y.time <=> x.time};
		
		return filtered_results;
	end
	
	
	def blog_post_date_map
		date_map = $site.memcache.get("blog-post-date-map-#{self.userid}")
		if (date_map.nil?)
			date_map = Blogs::DateMap.new(self.raw_blog_post_data)
			$site.memcache.set("blog-post-date-map-#{self.userid}", date_map, 86400)
		end

		return date_map
	end
	

	def friends_sorted_raw_blog_post_data
		sorted_list = $site.memcache.get("user_blog_friends_post_info-#{self.userid}");
		
		if(sorted_list.nil?())
			friend_id_list = [];
			self.friends_ids.each{|id|
				if(id[1] != self.userid)
					friend_id_list << id[1];
				end
			};
			
			sorted_list = [];
			if(!friend_id_list.empty?())
				min_time = Time.now.to_i() - 60*60*24*31;
				results = Blogs::BlogPost.db.query("SELECT userid, id, time, visibility FROM blog WHERE userid IN # AND time > ? AND visibility > 0", friend_id_list, min_time);
		
				raw_list = [];
				results.each{|row|
					raw_list << RawBlogPostData.new(row['userid'].to_i(), row['id'].to_i(), row['time'].to_i(), row['visibility'].to_i());
				};
				sorted_list = raw_list.sort{|x,y| y.time <=> x.time};
		
				#store in memcache
				$site.memcache.set("user_blog_friends_post_info-#{self.userid}", sorted_list, 60*60*24*7);
			end
		end
		
		return sorted_list
	end
	
end