# Pre-existing structure of blogtype_poll
# as of March 19, 2009.
#
# CREATE TABLE `blogtype_poll` (
#   `userid` int(10) unsigned NOT NULL,
#   `blogid` int(10) unsigned NOT NULL,
#   `link` text NOT NULL,
#   PRIMARY KEY (`userid`,`blogid`)
# ) ENGINE=MyISAM DEFAULT CHARSET=latin1

[ :anondb, :usersdb ].each { |db_sym|
	# Add size, align columns
	$site.dbs[db_sym].query("ALTER TABLE blogtype_poll
		ADD COLUMN size TINYINT(4) AFTER link,
		ADD COLUMN align TINYINT(4) AFTER size")
}
