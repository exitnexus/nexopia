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
		handle :GetRequest, :recent, "recent"
	}

	def locations
		request.reply.headers['Content-type'] = 'text/plain';
		locs = Locs.get_children(0);

		puts "id,parent";
		locs.each {|loc|
			puts "#{loc.id},#{loc.parent}";
		}
	end

	def users
		request.reply.headers['Content-Type'] = 'text/plain';
		request.reply.headers['Content-Encoding'] = '';
		request.reply.out.buffer = false;

		puts "userid,age,sex,loc,active,pic,single,sexuality";

		User.db.get_split_dbs.each {|db|
			db.query("SELECT users.userid, users.age, IF(users.sex = 'Male', 0, 1) as sex, users.loc, ( (useractivetime.activetime > ?) + (useractivetime.activetime > ?) + (useractivetime.online = 'y' && useractivetime.activetime > ?) ) as active, ( (users.firstpic >= 1) + (users.signpic = 'y') ) as pic, (users.single = 'y') as single, users.sexuality FROM users INNER JOIN useractivetime ON users.userid = useractivetime.userid WHERE state = 'active'",
				Time.now.to_i - 86400*30, Time.now.to_i - 86400*7, Time.now.to_i - 600) {|row|
				puts row.fetch_row.join(",");
			}
		}
	end

	def interests
		request.reply.headers['Content-Type'] = 'text/plain';
		request.reply.headers['Content-Encoding'] = '';
		request.reply.out.buffer = false;

		puts "userid,interestid";

		UserInterests.db.get_split_dbs.each {|db|
			db.query("SELECT userid,interestid FROM userinterests") {|row|
				puts row.fetch_row.join(",");
			}
		}
	end

	def online
		request.reply.headers['Content-type'] = 'text/plain';
		puts "userid";
		User.db.get_split_dbs.each {|db|
			db.query("SELECT users.userid FROM users INNER JOIN useractivetime ON users.userid = useractivetime.userid WHERE useractivetime.online = 'y' && useractivetime.activetime > ? AND users.state = 'active'", Time.now.to_i - 600) {|row|
				puts row.fetch_row.join(",");
			}
		}
	end

	def recent
		request.reply.headers['Content-Type'] = 'text/plain';
		request.reply.headers['Content-Encoding'] = '';
		request.reply.out.buffer = false;

		puts "userid";

		User.db.get_split_dbs.each {|db|
			db.query("SELECT users.userid, ( (useractivetime.activetime > ?) + (useractivetime.activetime > ?) + (useractivetime.online = 'y' && useractivetime.activetime > ?) ) as active FROM users INNER JOIN useractivetime ON users.userid = useractivetime.userid WHERE users.state = 'active'", 
				Time.now.to_i - 86400*30, Time.now.to_i - 86400*7, Time.now.to_i - 600) {|row|
				puts row.fetch_row.join(",");
			}
		}
	end
end
end
