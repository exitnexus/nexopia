class AnonStats < Storable
	init_storable(:masterdb, "anonstats");
end

class Stats < Storable
	init_storable(:usersdb, "stats");

	def self.online_users
		res = UserSearch.db.query("SELECT count(*) as count FROM usersearch WHERE active = 2")
		total = 0;
		res.each{|r|
			total += r['count'].to_i
		}
		return total;
	end
	def self.online_guests
		res = UserSearch.db.query("SELECT count(*) as count FROM usersearch WHERE active = 2")
		total = 0;
		res.each{|r|
			total += r['count'].to_i
		}
		return total;
	end

	def self.update()
	
		$site.memcache.load('ruby_stats', nil, 20){
			logged_out_users = UserActiveTime.find(:conditions => ["online = 'y' && activetime <= #", Time.now.to_i - 600])
			logged_out_users.each{|user|
				user.logout!
			}
		}
	
		if PageRequest.current.session.user.anonymous?
			stats = AnonStats.find(:first)
			if (!stats)
				stats = AnonStats.new
			end
			stats.hitstotal += 1
			stats.hitsanon += 1
			stats.store
		else
			user.activetime.activetime = Time.now.to_i
			user.activetime.hits += 1
			user.activetime.ip = PageRequest.current.headers['IP']
			user.activetime.online = 'y'
			
			stats = Stats.find(:first, userid)
			if (!stats)
				stats = Stats.new()
				stats.userid = userid
			end
			if (user.plus?)
				hitsplus += 1;
			end
			
	
			stats.hitstotal += 1
			stats.hitsuser += 1
			if (user.sex.to_s === "Male")
				hitsMale += 1;
			else
				hitsFemale += 1;
			end
			stats.store

			hitlog = UserHitLog.find(:first, userid, ip)
			if (hitlog == nil)
				hitlog = UserHitLog.new
				hitlog.userid = userid;
				hitlog.ip = ip;
			end 
			hitlog.activetime = Time.now.to_i
			hitlog.hits += 1
			hitlog.store
		
		end
	end
end