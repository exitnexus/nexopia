lib_require :Core, 'users/user', 'users/locs'

sleep(1);

puts "userid,age,sex,loc,active,pic,single,sexuality";

User.db.get_split_dbs.each {|db|
	db.query("SELECT userid, age, IF(sex = 'Male', 0, 1) as sex, loc, ( (activetime > ?) + (activetime > ?) + (online = 'y' && activetime > ?) ) as active, ( (firstpic >= 1) + (signpic = 'y') ) as pic, (single = 'y') as single, sexuality FROM users", 
		Time.now.to_i - 86400*30, Time.now.to_i - 86400*7, Time.now.to_i - 600) {|row|
		puts row.fetch_row.join(",");
	}
}

