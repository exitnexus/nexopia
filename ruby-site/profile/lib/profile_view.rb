lib_require :Core, 'storable/storable'
module Profile
	class ProfileView < Storable
		init_storable(:usersdb, "profileviews")
	
		class << self
			def view(view_user, profile_user)
				if (view_user.nil? || profile_user.nil? || view_user.anonymous?() || view_user.userid == profile_user.userid)
					return nil;
				end
				
				#determine the viewing user's preferences regarding appearing on recent visitor lists
				if ((view_user.plus?() && view_user.anonymousviews == 'y') || (view_user.anonymousviews == 'f' && !profile_user.friend?(view_user)))
					anonymous = 1;
				else
					anonymous = 0;
				end
				
				view_limit = $site.memcache.get("profileviewslimit-#{profile_user.userid}");
				
				if(view_limit.nil?())
					increment_views(view_user, profile_user, anonymous);
					return nil;
				else
					user_view_limit = $site.memcache.get("profileviewsuserlimit-#{profile_user.userid}-#{view_user.userid}");
					if(user_view_limit.nil?())
						temp = Hash.new();
						temp["userid"] = profile_user.userid;
						temp["anon"] = anonymous;
						temp["time"] = Time.now.to_i();
						temp["key"] = make_key(profile_user.userid, anonymous, temp["time"]);
					end
					
					$site.memcache.set("profileviewslimit-#{profile_user.userid}", 1, 120);
					$site.memcache.set("profileviewsuserlimit-#{profile_user.userid}-#{view_user.userid}", 1, 120);
					return temp;
				end
			end
			
			def increment_views(view_user, profile_user, anonymous)
				time = Time.now.to_i;

				result = db.query("INSERT INTO profileviews SET hits = 1, time = ?, anonymous = ?, userid = #, viewuserid = ? ON DUPLICATE KEY UPDATE hits = hits + 1, time = ?, anonymous = ?",
					time, anonymous, profile_user.userid, view_user.userid, time, anonymous);

				if(result.affected_rows == 1) #inserted
					db.query("UPDATE profile SET views = views + 1 WHERE userid = #", profile_user.userid);
					$site.memcache.incr("profileviews-#{profile_user.userid}");	
				#elsif(result.affected_rows == 2) #updated
				end
			end
			
			def views(profile_user)
				views = $site.memcache.get("profileviews-#{profile_user.userid}");
				if(views.nil?())
					result = db.query("SELECT views FROM profile WHERE userid = #", profile_user.userid);
					result.each{|row|
						views = row['views'];
					};
					
					$site.memcache.set("profileviews-#{profile_user.userid}", views, 60*60*24*7);
				end
				return views;
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
