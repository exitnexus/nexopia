# Create the tables.

# This stores the poll question itself.
# tvotes is a denormalised column, the total number of votes cast.
# blockid refers to the profiledisplayblocks table.
$site.dbs[:usersdb].query("CREATE TABLE `userpollquestions` (
  `userid` INT(11) NOT NULL,
  `blockid` INT(11) NOT NULL,
  `deleted` ENUM('n', 'y') NOT NULL,
  `question` VARCHAR(255) NOT NULL,
  `date` INT(11) NOT NULL,
  `tvotes` INT(10) NOT NULL,
  PRIMARY KEY (`userid`, `blockid`),
  INDEX (`deleted`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

# This stores each answer for a given poll question.
# answer represents the order to display this answer.  It should range
# from 1 to 10.
# votes is a denormalised column, the total votes cast for this specific answer.
$site.dbs[:usersdb].query("CREATE TABLE `userpollanswers` (
  `userid` INT(11) NOT NULL,
  `blockid` INT(11) NOT NULL,
  `answer` TINYINT NOT NULL,
  `answertext` VARCHAR(255) NOT NULL,
  `votes` INT(10) NOT NULL,
  PRIMARY KEY (`userid`, `blockid`, `answer`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

# This stores votes from users and prevents the user from voting twice.
# `answer` is a foreign key to userpollans::answer.  If NULL, it means
# the user chose not to vote, just to skip ahead and see the results.
# We don't store the user's IP address (unlike the poll tables) because
# Nexopia doesn't really care if a specific IP address tries stuffing
# the ballot box.  We do track the user's answer and time of the vote
# for userdump purposes.
$site.dbs[:usersdb].query("CREATE TABLE `userpollvotes` (
  `userid` INT(11) NOT NULL,
  `blockid` INT(11) NOT NULL,
  `voterid` INT(11) NOT NULL,
  `answer` TINYINT,
  `time` INT(11) NOT NULL,
  PRIMARY KEY (`userid`, `blockid`, `voterid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1");

