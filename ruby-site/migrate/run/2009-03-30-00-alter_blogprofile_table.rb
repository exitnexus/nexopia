# Pre-existing structures of blogprofile table, as of March 20, 2009.
#
# CREATE TABLE `blogprofile` (
#   `userid` int(10) unsigned NOT NULL,
#   `views` int(10) unsigned NOT NULL,
#   `showhits` enum('y','n') NOT NULL DEFAULT 'y',
#   `allowcomments` enum('y','n') NOT NULL DEFAULT 'y',
#   `defaultvisibility` tinyint(4) NOT NULL DEFAULT '4',
#   PRIMARY KEY (`userid`)
# ) ENGINE=MyISAM DEFAULT CHARSET=latin1;

sql = "ALTER TABLE `blogprofile` ADD `showquickpost` ENUM( 'y', 'n' ) DEFAULT 'y' NOT NULL"

$site.dbs[:anondb].query(sql)
$site.dbs[:usersdb].query(sql)
