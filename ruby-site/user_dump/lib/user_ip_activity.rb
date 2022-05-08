lib_want :UserDump, "dumpable"

class UserIPActivity < Storable
	init_storable(:usersdb, "userhitlog");

	if (site_module_loaded?(:UserDump))
	  extend Dumpable
	  
		def self.user_dump(user_id, start_time=0, end_time=Time.now)
  		result = UserIPActivity.find(user_id, :conditions => ["activetime BETWEEN ? AND ?", start_time.to_i, end_time.to_i])

  		sortedresults = result.sort {|a,b|
  			a.activetime <=> b.activetime
  		}

  		out = %Q!"Username","User ID","IP Address","Last Activity","Total User Hits From IP"\n!
			user = UserName.find(:first, user_id)
			if user.nil?
				user = "Unknown User ID ##{user_id}"
			else
				user = user.username
			end
  		sortedresults.each {|row|
  			ip = Session.int_to_ip_addr(row.ip)
  			last_active = Time.at(row.activetime).gmtime.to_s
  			out += %Q!"#{user}","#{user_id}","#{ip}","#{last_active}","#{row.hits}"\n!
  		}
	  
		  return Dumpable.str_to_file("#{user_id}-ip_activity.csv", out)
	  end
  end
end