lib_require :Profile, "profile_block", "profile_display_block";
lib_require :Core, 'array'

#depends on migrate-usercounter-profile-blocks

db_hash = $site.dbs[:usersdb].dbs
db_hash[0] = $site.dbs[:anondb]

db_list = db_hash.keys.sort {|a,b|
	if((a % 2) == (b % 2))
		(a <=> b)
	else
		(a % 2) <=> (b % 2)
	end
}


#get all of the needed type id's
profile_module_type_id = TypeID.get_typeid("ProfileModule");
comments_module_type_id = TypeID.get_typeid("CommentsModule");
user_pics_module_type_id = TypeID.get_typeid("UserpicsModule");
friends_module_type_id = TypeID.get_typeid("FriendsModule");
blogs_module_type_id = TypeID.get_typeid("BlogsModule");
gallery_module_type_id = TypeID.get_typeid("GalleryModule");


#Sanitize our profile block data. In case any users leveraged a rare race condition
# for duping blocks. If so, the duplicates above 3 are deleted.
#
#Some blocks have blockorders greater than 3 (with there only being
# 3 or less for the user) and this will reset their blockorder to be proper (1-3).

db_list.each_fork(14){|servid|
	$log.info "#{Process.pid} [#{Time.now}] Starting #{servid}"

	user_db = db_hash[servid]

	result = user_db.query("SELECT userid, blockorder, count(*) FROM profileblocks GROUP BY userid, blockorder HAVING count(*) > 1 OR blockorder > 3 ORDER BY userid");

	last_user = nil

	result.each{|row|
		next if(row['userid'] == last_user)
		last_user = row['userid'];
	
		res = user_db.query("SELECT userid, blockid, blockorder FROM profileblocks WHERE userid = ? ORDER BY blockorder", row['userid']);

		i = 1;
		res.each{|row|
			if(row['blockorder'] != i)
				user_db.query("UPDATE profileblocks SET blockorder = ? WHERE userid = ? AND blockid = ?", i, row['userid'], row['blockid']);
			elsif(i>3)
				user_db.query("DELETE FROM profileblocks WHERE userid = ? AND blockid = ?", row['userid'], row['blockid']);
			end
			i += 1;
		}
	}

#update profileblocks twice, once to make sure blockid is unique and won't conflict
# the second time is to set it to what we want it to be, which is also unique in general,
# but not between row changes
	user_db.query("UPDATE profileblocks SET blockid = blockorder + 136030")
	user_db.query("UPDATE profileblocks SET blockid = blockorder")

	#remove any ineligible blocks. Should not encounter any blocks due to our
	# previous sanitation, but it's best to be safe.
	user_db.query("DELETE FROM profileblocks WHERE blockorder > 3");

	user_db.query("TRUNCATE TABLE profiledisplayblocks");

	$log.info "#{Process.pid} [#{Time.now}] DB #{servid} sanitized"

	#create profiledisplayblock entries for the existing profile blocks
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, blockid, ? as typeid, 'freeform', CASE permission
									  WHEN 'anyone' THEN 4
									  WHEN 'loggedin' THEN 3
									  WHEN 'friends' THEN 1
									END, 1 as columnid, blockorder+5 as position
	FROM profileblocks", profile_module_type_id);

	#Create the mandatory new blocks and recommended blocks
	#
	#The mandatory blocks are: Control, Admin, Friends, Film Strip, Tag Line and Admin Info (which is hidden)
	#The recommended blocks are: Contact, Basics, Interests,
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 5 as blockid, ? as typeid, 'control' as path, 4 as visibility, 0 as columnid, 0 as position
	FROM users", profile_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 6 as blockid, ? as typeid, 'admin' as path, 5 as visibility, 0 as columnid, 1 as position
	FROM users", profile_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 7 as blockid, ? as typeid, 'list' as path, 4 as visibility, 0 as columnid, 2 as position
	FROM users", friends_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 8 as blockid, ? as typeid, 'film_strip' as path, 4 as visibility, 1 as columnid, 0 as position
	FROM users", user_pics_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 9 as blockid, ? as typeid, 'tagline' as path, 4 as visibility, 1 as columnid, 1 as position
	FROM users", profile_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 10 as blockid, ? as typeid, 'admin_info' as path, 5 as visibility, 1 as columnid, 2 as position
	FROM users", profile_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 11 as blockid, ? as typeid, 'contact' as path, 1 as visibility, 1 as columnid, 3 as position
	FROM users", profile_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 12 as blockid, ? as typeid, 'basics' as path, 4 as visibility, 1 as columnid, 4 as position
	FROM users", profile_module_type_id);
	
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 13 as blockid, ? as typeid, 'interests' as path, 4 as visibility, 1 as columnid, 5 as position
	FROM users", profile_module_type_id);
	
	#migrate the blog block if selected
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 14 as blockid, ? as typeid, 'blog' as path, 4 as visibility, 1 as columnid, 9 as position
	FROM profile
	WHERE profile.showlastblogentry = 'y'", blogs_module_type_id);
	
	#migrate the comments block if selected
	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 15 as blockid, ? as typeid, 'comments' as path, 3 as visibility, 1 as columnid, 10 as position
	FROM users
	WHERE enablecomments = 'y'", comments_module_type_id);

	user_db.query("INSERT INTO profiledisplayblocks(userid, blockid, moduleid, path, visibility, columnid, position)
	SELECT userid, 16 as blockid, ? as typeid, 'recent_galleries' as path, 4 as visibility, 0 as columnid, 3 as position
	FROM users", gallery_module_type_id);
	
	#insert or update the user counters for ProfileDisplayBlocks.
	user_db.query("INSERT INTO usercounter(id, area, max)
	SELECT userid as id, ? as area, 16 as max
	FROM users
	ON DUPLICATE KEY UPDATE max=16", Profile::ProfileDisplayBlock.typeid);

	$log.info "#{Process.pid} [#{Time.now}] DB #{servid} Done"
}
