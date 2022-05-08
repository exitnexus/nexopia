table_creation_statements = [

	"CREATE TABLE IF NOT EXISTS `blogtype_battle` (
	  `userid` int(10) unsigned NOT NULL,
	  `blogid` int(10) unsigned NOT NULL,
	  `battletype` enum('photo','video') NOT NULL,
	  `caption1` varchar(128) NOT NULL,
	  `link1` text NOT NULL,
	  `caption2` varchar(128) NOT NULL,
	  `link2` text NOT NULL,
	  PRIMARY KEY (`userid`,`blogid`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1",

	"CREATE TABLE IF NOT EXISTS `blogtype_photo` (
		`userid` int(10) unsigned NOT NULL,
		`blogid` int(10) unsigned NOT NULL,
		`link` text NOT NULL,
		`size` tinyint(4) NOT NULL,
		`align` tinyint(4) NOT NULL,
		PRIMARY KEY (`userid`,`blogid`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1",

	"CREATE TABLE IF NOT EXISTS `blogtype_poll` (
		`userid` int(10) unsigned NOT NULL,
		`blogid` int(10) unsigned NOT NULL,
		`link` text NOT NULL,
		PRIMARY KEY (`userid`,`blogid`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1",

	"CREATE TABLE IF NOT EXISTS `blogtype_video` (
		`userid` int(10) unsigned NOT NULL,
		`blogid` int(10) unsigned NOT NULL,
		`embed` text NOT NULL,
		`size` tinyint(4) NOT NULL,
		`align` tinyint(4) NOT NULL,
		PRIMARY KEY (`userid`,`blogid`)
	) ENGINE=MyISAM DEFAULT CHARSET=latin1"

]

table_creation_statements.each {|sql|
	$site.dbs[:anondb].query(sql)
	$site.dbs[:usersdb].query(sql)
}
