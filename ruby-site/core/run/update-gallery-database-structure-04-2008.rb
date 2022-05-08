
db_list = $site.dbs[:usersdb].get_split_dbs();
db_list << $site.dbs[:anondb];

db_list.each{|user_db|
	user_db.query("ALTER TABLE `gallerypics` ADD `md5` VARCHAR(32),
		ADD `userpicid` INT UNSIGNED,
		ADD `signpic` TINYINT UNSIGNED NOT NULL DEFAULT 0,
		ADD `userpic` TINYINT UNSIGNED NOT NULL DEFAULT 0,
		ADD `created` INT UNSIGNED NOT NULL");
		
	user_db.query("ALTER TABLE `gallerypics` ADD INDEX `userpic` ( `userid` , `userpicid` )")
	
	user_db.query("CREATE TABLE `gallerypiccrops` (
	  `userid` int(10) unsigned NOT NULL,
	  `gallerypicid` int(10) unsigned NOT NULL,
	  `x` float unsigned NOT NULL,
	  `y` float unsigned NOT NULL,
	  `w` float unsigned NOT NULL,
	  `h` float unsigned NOT NULL,
	  `time` int(11) unsigned NOT NULL,
	  PRIMARY KEY (`userid`,`gallerypicid`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1;");

	user_db.query("CREATE TABLE `galleryprofileblock` (
	  `userid` int(11) unsigned NOT NULL,
	  `id` int(11) unsigned NOT NULL,
	  `galleryid` int(11) unsigned NOT NULL,
	  PRIMARY KEY (`userid`,`id`),
		UNIQUE `usergallery`(`userid`, `galleryid`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1;");
	
	user_db.query("ALTER TABLE `gallery` ADD `created` INT UNSIGNED NOT NULL");
	
	user_db.query("ALTER TABLE `pics` ADD `gallerypicid` INT UNSIGNED NOT NULL ;");
	
	
	user_db.query("CREATE TABLE `gallerycomments` (
	  `userid` int(10) unsigned NOT NULL DEFAULT '0',
	  `id` int(10) unsigned NOT NULL DEFAULT '0',
	  `picid` int(10) unsigned NOT NULL,
	  `authorid` int(10) unsigned NOT NULL DEFAULT '0',
	  `authorip` int(11) NOT NULL,
	  `time` int(11) NOT NULL DEFAULT '0',
	  `nmsg` text NOT NULL,
	  `deleted` enum('n','y') NOT NULL DEFAULT 'n',
	  PRIMARY KEY (`userid`,`id`),
	  KEY `userid` (`userid`,`picid`,`time`))"); 
};