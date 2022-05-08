db_list = $site.dbs[:usersdb].get_split_dbs();
db_list << $site.dbs[:anondb];

for user_db in db_list
	user_db.query("UPDATE users, profile SET users.profileskin = profile.skin WHERE profile.userid = users.userid");
end


