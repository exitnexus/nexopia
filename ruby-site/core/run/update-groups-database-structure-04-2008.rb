
$site.dbs[:groupsdb].query("CREATE TABLE `groups` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`location` int(11) NOT NULL,
		`type` int(4) NOT NULL,
		`name` varchar(100) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `location` (`location`,`type`,`name`),
		KEY `search` (`location`,`name`(20))
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;");


user_db_list = $site.dbs[:usersdb].get_split_dbs();
user_db_list << $site.dbs[:anondb];

user_db_list.each{|user_db|
	user_db.query("CREATE TABLE `groupmembers` (
		`userid` int(11) NOT NULL,
		`groupid` int(11) NOT NULL,
		`frommonth` tinyint(2) NOT NULL,
		`fromyear` smallint(4) NOT NULL,
		`tomonth` tinyint(2) NOT NULL,
		`toyear` smallint(4) NOT NULL,
		`visibility` int(1) NOT NULL DEFAULT '2',
		PRIMARY KEY (`userid`,`groupid`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1;");
};