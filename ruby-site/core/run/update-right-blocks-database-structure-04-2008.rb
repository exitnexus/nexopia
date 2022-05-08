lib_require :Core, 'array'

db_hash = $site.dbs[:usersdb].dbs
db_hash[0] = $site.dbs[:anondb]

db_list = db_hash.keys.sort {|a,b|
	if((a % 2) == (b % 2))
		(a <=> b)
	else
		(a % 2) <=> (b % 2)
	end
}

db_list.each_fork(14){|servid|
	user_db = db_hash[servid]

	user_db.query("ALTER TABLE `users` CHANGE `showrightblocks` `showrightblocks` ENUM( 'n', 'y' ) NOT NULL DEFAULT 'y'");
	user_db.query("UPDATE `users` SET `showrightblocks`= 'y'");
}
