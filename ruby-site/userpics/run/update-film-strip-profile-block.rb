# Does not need to be run!
# This was a patch script for the live server. The fix has not been merged into any migration scripts since the migrations have already
# taken place.
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

userpics_module_type_id = TypeID.get_typeid("UserpicsModule");

db_list.each_fork(14){|servid|
	user_db = db_hash[servid]

	user_db.query("UPDATE profiledisplayblocks SET path='classic_film_strip' WHERE moduleid=? AND path='film_strip'", userpics_module_type_id);
	$log.info("Completed update for DB #{servid}");
}