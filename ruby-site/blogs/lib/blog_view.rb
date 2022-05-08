lib_require :Core, 'storable/storable'

module Blogs
	
	#
	# This class deals with how many times a blog has been viewed, not the displaying of said blog.
	#
	class BlogView < Storable
		init_storable(:usersdb, "blogviews")
	
		class << self
			def view(view_user, blog_user)
				if (view_user.nil? || blog_user.nil? || view_user.anonymous?() || view_user.userid == blog_user.userid)
					return nil;
				end
				
				#determine the viewing user's preferences regarding appearing on recent visitor lists
				if ((view_user.plus?() && view_user.anonymousviews == 'y') || (view_user.anonymousviews == 'f' && !blog_user.friend?(view_user)))
					anonymous = 1;
				else
					anonymous = 0;
				end
				
				view_limit = $site.memcache.get("blogviewslimit-#{blog_user.userid}");
				
				if(view_limit.nil?())
					increment_views(view_user, blog_user, anonymous);
					return nil;
				else
					user_view_limit = $site.memcache.get("blogviewsuserlimit-#{blog_user.userid}-#{view_user.userid}");
					if(user_view_limit.nil?())
						temp = Hash.new();
						temp["userid"] = blog_user.userid;
						temp["anon"] = anonymous;
						temp["time"] = Time.now.to_i();
						temp["key"] = make_key(blog_user.userid, anonymous, temp["time"]);
					end
					
					$site.memcache.set("blogviewslimit-#{blog_user.userid}", 1, 120);
					$site.memcache.set("blogviewsuserlimit-#{blog_user.userid}-#{view_user.userid}", 1, 120);
					return temp;
				end
			end
			
			def increment_views(view_user, blog_user, anonymous)
				time = Time.now.to_i();
				
				# This will pull the first post off the minimal list, but this list is sorted by time, so it doesn't guarantee to return the max id.
				
				# This is often causing a full query of all the information on the blog, so doing just a find on
				# one of the posts is actually more efficient.
				#blog_user.blog_posts_minimal.first;
				new_post = BlogPost.find(:first, blog_user.userid, :order => "time DESC", :limit => 1)
				
				if(new_post.nil?() || new_post.id.nil?())
					return;
				end
				
				new_post_id = new_post.id;
				
				result = db.query("INSERT INTO blogviews SET hits = 1, time = ?, anonymous = ?, userid = #, viewuserid = ?, lastblogid = ? ON DUPLICATE KEY UPDATE hits = hits + 1, time = ?, anonymous = ?",
					time, anonymous, blog_user.userid, view_user.userid, new_post_id, time, anonymous);
					
				if(result.affected_rows == 1) #inserted
					db.query("UPDATE blogprofile SET views = views + 1 WHERE userid = #", blog_user.userid);
					$site.memcache.incr("blogviews-#{blog_user.userid}");	
				elsif(result.affected_rows == 2) #updated
					view_result = BlogView.find(:first, [blog_user.userid, view_user.userid]);
					if(view_result.lastblogid < new_post_id)
						db.query("UPDATE blogviews SET lastblogid = ? WHERE userid = # AND viewuserid = ?", new_post_id, blog_user.userid, view_user.userid);
						db.query("UPDATE blogprofile SET views = views + 1 WHERE userid = #", blog_user.userid);
						$site.memcache.incr("blogviews-#{blog_user.userid}");
					end
				end
			end
			
			def views(blog_user, format=:none)
				views = $site.memcache.get("blogviews-#{blog_user.userid}");
				if(views.nil?())
					result = db.query("SELECT views FROM blogprofile WHERE userid = #", blog_user.userid);
					result.each{|row|
						views = row['views'];
					};
					
					$site.memcache.set("blogviews-#{blog_user.userid}", views, 60*60*24*7);
				end
				
				if(format == :display)
					return views.to_s().gsub(/(\d)(?=(\d\d\d)+(?!\d))/, "\\1,")
				else
					return views
				end
			end
			
			def make_key(user_id, anonymous, time)
				return Authorization.instance.make_key("#{user_id}:secret:#{anonymous}:#{time}");
			end
			
			def check_key(key, user_id, anonymous, time)
				return Authorization.instance.check_key("#{user_id}:secret:#{anonymous}:#{time}", key);
			end
		end
	end
end
