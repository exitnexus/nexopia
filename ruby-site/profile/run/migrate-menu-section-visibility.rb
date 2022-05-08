lib_require :Core, 'array'

#From weblog.php the visibility levels for the blog are as follows:
#	1 -> all
#	2 -> logged in
#	3 -> friends
#	4 -> none (private)


blog_entry_limit = Time.now.to_i() - 2*7*24*60*60;


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

	user_db.query("UPDATE users SET gallerymenuaccess = CASE gallery
									  WHEN 'anyone' THEN 4
									  WHEN 'loggedin' THEN 3
									  WHEN 'friends' THEN 1
									END WHERE gallery <> 'none'");
	
	user_db.query("UPDATE users SET blogsmenuaccess = (SELECT CASE scope
				  WHEN 1 THEN 4
				  WHEN 2 THEN 3
				  WHEN 3 THEN 1
				  END as 'newscope'
				  FROM blog
				  WHERE time > ?
				  AND users.userid = blog.userid
				  AND scope < 4
				  ORDER BY scope ASC LIMIT 1)", blog_entry_limit);
	
	user_db.query("UPDATE users SET commentsmenuaccess = 0 WHERE enablecomments = 'n'");
	
	user_db.query("ALTER TABLE `users` DROP `gallery`");
};
