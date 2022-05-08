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

	user_db.query("CREATE TABLE `blogprofile` (
	`userid` INT( 10 ) UNSIGNED NOT NULL ,
	`views` INT UNSIGNED NOT NULL ,
	`showhits` ENUM( 'y', 'n' ) DEFAULT 'y' NOT NULL ,
	`allowcomments` ENUM( 'y', 'n' ) DEFAULT 'y' NOT NULL ,
	`defaultvisibility` TINYINT DEFAULT '4' NOT NULL ,
	PRIMARY KEY ( `userid` )
	) TYPE = MYISAM ;");
	
	user_db.query("ALTER TABLE `blog` ADD `visibility` TINYINT DEFAULT '4' NOT NULL;");
	
	user_db.query("UPDATE `blog` SET `visibility` = (CASE `scope`
		WHEN 1 THEN 4
		WHEN 2 THEN 3
		WHEN 3 THEN 1
		WHEN 4 THEN 0
	END);");
	
	user_db.query("ALTER TABLE `blog` DROP `scope`");
	
	user_db.query("INSERT INTO `blogprofile`(`userid`, `views`)
	SELECT `userid`, 0 as `views`
	FROM `users`");
	
	user_db.query("LOCK TABLES `blogcomments` WRITE")
	
	results = user_db.query("SELECT `bloguserid`, `id`, `deleted` FROM `blogcomments` WHERE `deleted` = 't'");
	
	user_db.query("ALTER TABLE `blogcomments` CHANGE `deleted` `deleted` ENUM('y', 'n') NOT NULL DEFAULT 'n'");
	
	user_db.query("UPDATE `blogcomments` SET `deleted`='n'");
	
	deleted_comments_list = [];
	
	results.each{|row|
		deleted_comments_list << [row['bloguserid'].to_i(), row['id'].to_i()];
	};
	
	deleted_comments_list.each{|comment_key|
		user_db.query("UPDATE `blogcomments` SET `deleted` = 'y' WHERE `bloguserid` = ? AND `id` = ?", comment_key[0], comment_key[1]);
	};
	
	user_db.query("UNLOCK TABLES");
	
	user_db.query("CREATE TABLE `blogviews` (
		`userid` int( 10 ) unsigned NOT NULL DEFAULT '0',
		`viewuserid` int( 10 ) unsigned NOT NULL DEFAULT '0',
		`time` int( 11 ) NOT NULL DEFAULT '0',
		`hits` tinyint( 3 ) unsigned NOT NULL DEFAULT '0',
		`anonymous` tinyint( 1 ) NOT NULL DEFAULT '0',
		`lastblogid` int( 11 ) NOT NULL ,
		PRIMARY KEY ( `userid` , `viewuserid` ) ,
		KEY `time` ( `time` ) ,
		KEY `userid` ( `userid` , `time` ) ,
		KEY `viewuserid` ( `viewuserid` , `time` )
		) ENGINE = MYISAM DEFAULT CHARSET = latin1;");

	user_db.query("CREATE TABLE `blognavigation` (
	`userid` INT( 10 ) UNSIGNED NOT NULL ,
	`bloguserid` INT( 10 ) UNSIGNED NOT NULL ,
	`postid` INT( 10 ) UNSIGNED NOT NULL ,
	PRIMARY KEY ( `userid` , `bloguserid` , `postid` )
	) TYPE = MYISAM;");
	
	user_db.query("INSERT IGNORE INTO `bloglastreadfriends`(`userid`) SELECT `userid` FROM `users`");
}
