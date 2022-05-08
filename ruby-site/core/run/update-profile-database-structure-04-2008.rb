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

	user_db.query("CREATE TABLE `profiledisplayblocks` (
		`userid` int(10) unsigned NOT NULL DEFAULT '0',
		`blockid` int(10) unsigned NOT NULL,
		`moduleid` int(10) unsigned NOT NULL DEFAULT '0',
		`path` varchar(255) NOT NULL,
		`visibility` tinyint(3) NOT NULL DEFAULT '4',
		`columnid` tinyint(3) NOT NULL DEFAULT '0',
		`position` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`userid`,`blockid`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

	user_db.query("CREATE TABLE `userskins` (
		`userid` int(10) unsigned NOT NULL,
		`skinid` int(10) unsigned NOT NULL,
		`name` varchar(64) NOT NULL,
		`revision` int(10) unsigned NOT NULL DEFAULT '0',
		`skindata` text NOT NULL,
		PRIMARY KEY (`userid`,`skinid`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

	user_db.query("ALTER TABLE `users` 
		ADD `profileskin` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `galleryskin` ,
		ADD `gallerymenuaccess` INT UNSIGNED DEFAULT '0' NOT NULL ,
		ADD `blogsmenuaccess` INT UNSIGNED DEFAULT '0' NOT NULL ,
		ADD `commentsmenuaccess` INT UNSIGNED DEFAULT '4' NOT NULL,
		ADD `lastnotification` INT UNSIGNED DEFAULT '0' NOT NULL,
		ADD `profilefriendslistthumbs` ENUM( 'y', 'n' ) DEFAULT 'y' NOT NULL,
		DROP `friendslistthumbs`,
		CHANGE `commentskin` `commentsskin` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0',
		CHANGE `friendskin` `friendsskin` INT( 10 ) UNSIGNED NOT NULL DEFAULT '0' ;");
	
	user_db.query("UPDATE `users` SET `profilefriendslistthumbs` = 'n'");
	
	user_db.query("ALTER TABLE `profile` 
		ADD `firstnamevisibility` TINYINT UNSIGNED DEFAULT '2' NOT NULL ,
		ADD `lastnamevisibility` TINYINT UNSIGNED DEFAULT '1' NOT NULL ,
		ADD `firstname` VARCHAR(64) NOT NULL,
		ADD `lastname` VARCHAR(64) NOT NULL");
	
	user_db.query("ALTER TABLE `blogcomments` ADD INDEX `ruby_index` ( `bloguserid` , `blogid` )");
	
	user_db.query("ALTER TABLE `usercomments` ADD `deleted` ENUM('n', 'y') NOT NULL DEFAULT 'n' AFTER `time`, ADD INDEX ( `authorid` )");
}
