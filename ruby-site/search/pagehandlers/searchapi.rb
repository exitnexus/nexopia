lib_require :Core, 'users/user', 'users/locs'


module Search

# This is the api the user search daemon uses to get the data it needs to run.
class SearchAPI < PageHandler

	declare_handlers("search/api") {
		area :Public

#		handle :GetRequest, :config

		handle :GetRequest, :locations, "locations"
		handle :GetRequest, :users, "users"
		handle :GetRequest, :interests, "interests"
		handle :GetRequest, :online, "online"
		
	}

	def locations
		request.reply.headers['Content-type'] = 'text/text';
		locs = Locs.get_children(0);

		puts "id,parent";
		locs.each {|loc|
			puts "#{loc.id},#{loc.parent}";
		}
	end

	def users
		request.reply.headers['Content-type'] = 'text/text';
		request.reply.out.buffer = false;

		puts "userid,age,sex,loc,active,pic,single,sexuality";

		User.db.get_split_dbs.each {|db|
			db.query("SELECT userid, age, IF(sex = 'Male', 0, 1) as sex, loc, ( (activetime > ?) + (activetime > ?) + (online = 'y' && activetime > ?) ) as active, ( (firstpic >= 1) + (signpic = 'y') ) as pic, (single = 'y') as single, sexuality FROM users", 
				Time.now.to_i - 86400*30, Time.now.to_i - 86400*7, Time.now.to_i - 600) {|row|
				puts row.fetch_row.join(",");
			}
		}
	end

	def interests
		request.reply.headers['Content-type'] = 'text/text';
		request.reply.out.buffer = false;

		puts "userid,interestid";

		UserInterests.db.get_split_dbs.each {|db|
			db.query("SELECT userid,interestid FROM userinterests") {|row|
				puts row.fetch_row.join(",");
			}
		}
	end

	def online
		request.reply.headers['Content-type'] = 'text/text';
		puts "userid";
		User.db.get_split_dbs.each {|db|
			db.query("SELECT userid FROM users WHERE online = 'y' && activetime > ?", Time.now.to_i - 600) {|row|
				puts row.fetch_row.join(",");
			}
		}
	end
end
end
