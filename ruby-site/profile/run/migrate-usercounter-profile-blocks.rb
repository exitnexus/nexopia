lib_require :Profile, "profile_display_block";

php_profile_block_type_id = 30;

db_list = $site.dbs[:usersdb].get_split_dbs();
db_list << $site.dbs[:anondb];

db_list.each{|user_db|
	user_db.query("UPDATE usercounter SET area=? WHERE area=30", Profile::ProfileDisplayBlock.typeid);
}


