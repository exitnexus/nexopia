lib_require :Core, 'users/user', 'users/locs'

sleep(1);

puts "userid,interestid";

UserInterests.db.get_split_dbs.each {|db|
	db.query("SELECT userid,interestid FROM userinterests") {|row|
		puts row.fetch_row.join(",");
	}
}
