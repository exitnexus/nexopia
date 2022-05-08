lib_require :Core, 'array'

#From weblog.php the visibility levels for the blog are as follows:
#	1 -> all
#	2 -> logged in
#	3 -> friends
#	4 -> none (private)


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
	$log.info "#{Process.pid} [#{Time.now}] Starting #{servid}";
	
	user_db = db_hash[servid]

	results = user_db.query("SELECT DISTINCT userid, scope from blog WHERE scope < 4 order by userid, scope DESC");
	user_scope_list = [];
	
	results.each{|row|
		scope = 0;
		if(row['scope'].to_i() == 1)
			scope = 4;
		elsif(row['scope'].to_i() == 2)
			scope = 3;
		elsif(row['scope'].to_i() == 3)
			scope = 1;
		end
		
		temp = [row['userid'].to_i(), scope];
		if(!user_scope_list.last.nil?() && user_scope_list.last[0] == temp[0])
			user_scope_list.last[1] = temp[1];
		else
			user_scope_list << temp;
		end
	};
	
	results.free();
	results = nil;
	
	user_scope_list.each{|user_scope|
		user_db.query("UPDATE users SET blogsmenuaccess = ? WHERE userid = ?", user_scope[1], user_scope[0]);
	};
	
	$log.info "#{Process.pid} [#{Time.now}] DB #{servid} Done";
};
