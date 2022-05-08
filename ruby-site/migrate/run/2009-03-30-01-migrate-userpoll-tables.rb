# Pre-existing structures of userpollanswers, userpollquestions, and userpollvotes,
# as of March 19, 2009.
#
# CREATE TABLE `userpollanswers` (
#   `userid` int(11) NOT NULL,
#   `blockid` int(11) NOT NULL,
#   `answer` tinyint(4) NOT NULL,
#   `answertext` varchar(255) NOT NULL,
#   `votes` int(10) NOT NULL,
#   PRIMARY KEY  (`userid`,`blockid`,`answer`)
# ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
#
# CREATE TABLE `userpollquestions` (
#   `userid` int(11) NOT NULL,
#   `blockid` int(11) NOT NULL,
#   `deleted` enum('n','y') NOT NULL,
#   `question` varchar(255) NOT NULL,
#   `date` int(11) NOT NULL,
#   `tvotes` int(10) NOT NULL,
#   PRIMARY KEY  (`userid`,`blockid`),
#   KEY `deleted` (`deleted`)
# ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
#
# CREATE TABLE `userpollvotes` (
#   `userid` int(11) NOT NULL,
#   `blockid` int(11) NOT NULL,
#   `voterid` int(11) NOT NULL,
#   `answer` tinyint(4) default NULL,
#   `time` int(11) NOT NULL,
#   PRIMARY KEY  (`userid`,`blockid`,`voterid`)
# ) ENGINE=MyISAM DEFAULT CHARSET=latin1;

# Grab typeid
profile_display_block_typeid = nil
rows = $site.dbs[:db].query("SELECT typeid FROM typeid
 	WHERE typename = 'Profile::ProfileDisplayBlock'")
rows.each { |row|
	profile_display_block_typeid = row['typeid']
}
raise "Unable to determine typeid" if profile_display_block_typeid.nil?

[ :anondb, :usersdb ].each { |db_sym|
	[ 'userpollanswers', 'userpollquestions', 'userpollvotes'].each { |table|
		# Change column name, add typeid column
		$site.dbs[db_sym].query("ALTER TABLE #{table}
			CHANGE COLUMN blockid parentid INT(11),
			ADD COLUMN typeid INT(10) AFTER userid")

		# Set the typeid field appropriately
		$site.dbs[db_sym].query("UPDATE #{table}
			SET typeid = #{profile_display_block_typeid}
			WHERE ISNULL(typeid)")

		# And now, prevent further NULLs in typeid
		$site.dbs[db_sym].query("ALTER TABLE #{table}
			MODIFY COLUMN typeid INT(10) NOT NULL")
	}

	# Update primary keys
	$site.dbs[db_sym].query("ALTER TABLE userpollquestions DROP PRIMARY KEY")
	$site.dbs[db_sym].query("ALTER TABLE userpollquestions
		ADD PRIMARY KEY (userid, typeid, parentid)")

	$site.dbs[db_sym].query("ALTER TABLE userpollanswers DROP PRIMARY KEY")
	$site.dbs[db_sym].query("ALTER TABLE userpollanswers
		ADD PRIMARY KEY (userid, typeid, parentid, answer)")

	$site.dbs[db_sym].query("ALTER TABLE userpollvotes DROP PRIMARY KEY")
	$site.dbs[db_sym].query("ALTER TABLE userpollvotes
		ADD PRIMARY KEY (userid, typeid, parentid, voterid)")
}
