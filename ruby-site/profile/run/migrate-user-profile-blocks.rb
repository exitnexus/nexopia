lib_require	:Profile, "profile_block", "profile_display_block";

db_list = Profile::ProfileBlock.db.get_split_dbs();

user_list = Array.new();
for user_db in db_list
	result = user_db.query("SELECT userid, blockorder, count(*) FROM profileblocks GROUP BY userid, blockorder HAVING count(*) > 1");
	
	result.each{|row|
		user_list << row['userid'];
	};
end

for user_id in user_list
	block_list = Profile::ProfileBlock.find(:all, user_id);
	i = 1;
	for block in block_list
		block.blockorder = i;
		user_db.query("UPDATE profileblocks SET blockorder=? WHERE userid=? AND blockid=?", i, block.userid, block.blockid);
		if(i>3)
			user_db.query("DELETE FROM profileblocks WHERE userid=? AND blockid=?", block.userid, block.blockid);
		end
		i = i + 1;
	end
end

for user_db in db_list
	#remove any ineligible blocks
	user_db.query("DELETE FROM profileblocks WHERE blockorder > 3");
	
	#create profiledisplayblock entries for the existing profile blocks and update their blockid's
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, blockorder, typeid, 'freeform', CASE permission
									  WHEN 'anyone' THEN 4
									  WHEN 'loggedin' THEN 3
									  WHEN 'friends' THEN 1
									END, 1 as columnid, blockorder+2 as position
	FROM profileblocks, newgeneral.typeid
	WHERE typename = 'ProfileModule'");
	
	user_db.query("UPDATE profileblocks SET blockid=blockorder")
	
	#migrate the comments block if selected
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 4 as blockid, typeid, 'comments' as path, 4 as visibility, 1 as columnid, 6 as position
	FROM users, newgeneral.typeid
	WHERE typename = 'CommentsModule'
	AND enablecomments = 'y'");
	
	#Create the mandatory new blocks.
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 5 as blockid, typeid, 'control' as path, 4 as visibility, 0 as columnid, 0 as position
	FROM users, newgeneral.typeid
	WHERE typename = 'ProfileModule'
	UNION ALL
	SELECT userid, 6 as blockid, typeid, 'film_strip' as path, 4 as visibility, 1 as columnid, 0 as position
	FROM users, newgeneral.typeid
	WHERE typename = 'UserpicsModule'
	UNION ALL
	SELECT userid, 7 as blockid, typeid, 'vitals' as path, 4 as visibility, 1 as columnid, 1 as position
	FROM users, newgeneral.typeid
	WHERE typename = 'ProfileModule'
	UNION ALL
	SELECT userid, 8 as blockid, typeid, 'list' as path, 4 as visibility, 0 as columnid, 1 as position
	FROM users, newgeneral.typeid
	WHERE typename = 'FriendsModule'
	");
	
	#insert or update the user counters
	user_db.query("INSERT INTO usercounter(id, area, max)
	SELECT userid as id, ? as area, 8 as max
	FROM users
	ON DUPLICATE KEY UPDATE max=8", Profile::ProfileDisplayBlock.typeid);
end