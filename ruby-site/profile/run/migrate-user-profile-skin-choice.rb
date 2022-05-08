lib_require :Profile, "profile_display_block";

db_list = Profile::ProfileDisplayBlock.db.get_split_dbs();

for user_db in db_list
	user_db.query("UPDATE users SET users.profileskin=
										( SELECT profile.skin
										FROM profile
										WHERE profile.userid=users.userid)
					WHERE users.userid=
							(SELECT profile.userid
							FROM profile
							WHERE profile.userid = users.userid)");
end


