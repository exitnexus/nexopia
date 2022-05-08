# Create the tables.

# This is used to keep track of which user notifications we have sent
# out.  We don't want to notify a user every single day, for example,
# that they have not been active in a while.
$site.dbs[:usersdb].query("CREATE TABLE IF NOT EXISTS `notifications_sent` (
  `userid` INT(11) NOT NULL,
  `moduleid` INT(11) NOT NULL,
  `date` INT(11) NOT NULL,
  PRIMARY KEY (`userid`, `moduleid`, `date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

