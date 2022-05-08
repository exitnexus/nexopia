# Does not need to be run!
# This was a patch script for the beta server. The fix has been merged into the migration script (update-profile-database-structure-04-2008).
$log.info("Nothing done. This was a patch script.")
exit;


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
	
	user_db.query("ALTER TABLE `users` 
		CHANGE `friendssskin` `friendsskin` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' ;");
}