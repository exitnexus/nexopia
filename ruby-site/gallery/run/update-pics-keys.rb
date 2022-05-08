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
	user_db = db_hash[servid]

	#clean up all the pics from deleted users
	user_db.query("DELETE FROM pics WHERE gallerypicid = 0");

	user_db.query("ALTER TABLE `pics` DROP PRIMARY KEY ,
	ADD PRIMARY KEY ( `userid` , `gallerypicid` ),
	ADD INDEX `legacy` ( `userid` , `id` ),
	ADD INDEX `priority` ( `userid` , `priority` )");
};
