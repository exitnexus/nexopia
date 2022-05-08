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
	begin
		user_db = db_hash[servid]
	
		$log.info( ("[%s] %i: Migrating users from serverid %i" % 
			[Time.now.strftime("%b %d %Y, %H:%M:%S"), Process.pid, servid]) );
	
		
		res = user_db.query("SELECT a.ownerid, a.id FROM gallery a, gallery b 
			WHERE a.ownerid = b.ownerid AND a.name = b.name AND a.id < b.id AND a.name = 'Imported Pictures'");
		
		res.each{|row|
			user_db.query("DELETE FROM gallerypics WHERE userid = ? AND galleryid = ?", row['ownerid'], row['id'])
			user_db.query("DELETE FROM gallery WHERE ownerid = ? AND id = ?", row['ownerid'], row['id'])
		}
	
		$log.info( ("[%s] %i: Done migrating users from serverid %i after %i seconds" % 
			[Time.now.strftime("%b %d %Y, %H:%M:%S"), Process.pid, servid, Time.now.to_i - starttime]) );
	rescue
		$log.info $!
		$log.info $!.backtrace.join("\n")
	end
}

