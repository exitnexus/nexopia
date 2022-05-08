lib_require :Core, "array"

db_hash = $site.dbs[:usersdb].dbs
db_hash[0] = $site.dbs[:anondb]

db_list = db_hash.keys.sort {|a,b|
	if((a % 2) == (b % 2))
		(a <=> b)
	else
		(a % 2) <=> (b % 2)
	end
}

begin
	db_list.each_fork(14){|servid|
		db = db_hash[servid]

		starttime = Time.now.to_i

		$log.info( ("[%s] %i: Updating users firstpic from serverid %i" % 
			[Time.now.strftime("%b %d %Y, %H:%M:%S"), Process.pid, servid]) );

		db.query("UPDATE `users`, `pics` SET `users`.`firstpic` = `pics`.`gallerypicid` WHERE `users`.`userid` = `pics`.`userid` AND `pics`.`priority` = 1");

		$log.info( ("[%s] %i: Done updating users firstpic from serverid %i after %i seconds" % 
			[Time.now.strftime("%b %d %Y, %H:%M:%S"), Process.pid, servid, Time.now.to_i - starttime]) );
	}
rescue
	$log.info $!
	$log.info $!.backtrace.join("\n")
end