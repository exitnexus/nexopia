lib_require :Profile, "profile_display_block";

php_profile_block_type_id = 30;
db_list = Profile::ProfileDisplayBlock.db.get_split_dbs();

for user_db in db_list
	user_db.query("UPDATE usercounter SET area=? WHERE area=30", Profile::ProfileDisplayBlock.typeid);
end


