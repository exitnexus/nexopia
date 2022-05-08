# Pre-existing structures of gallery table, as of March 25, 2009.
#
# CREATE TABLE `gallery` (
#   `ownerid` int(10) unsigned NOT NULL DEFAULT '0',
#   `id` int(10) unsigned NOT NULL DEFAULT '0',
#   `name` varchar(32) NOT NULL DEFAULT '',
#   `permission` enum('anyone','loggedin','friends') NOT NULL DEFAULT 'anyone',
#   `previewpicture` int(10) unsigned NOT NULL DEFAULT '0',
#   `description` varchar(255) NOT NULL DEFAULT '',
#   `allowcomments` enum('n','y') NOT NULL DEFAULT 'y',
#   `created` int(10) unsigned NOT NULL,
#   PRIMARY KEY (`ownerid`,`id`),
#   KEY `name` (`name`,`ownerid`)
# ) ENGINE=MyISAM DEFAULT CHARSET=latin1

sqls = [
	"ALTER TABLE gallery CHANGE COLUMN `permission` `permission` enum('anyone','loggedin','friends', 'none') NOT NULL DEFAULT 'anyone'",
	"UPDATE blog SET visibility = 1 WHERE visibility = 2",
	"UPDATE profiledisplayblocks SET visibility = 1 WHERE visibility = 2"
]

sqls.each { |sql|
	$site.dbs[:anondb].query(sql)
	$site.dbs[:usersdb].query(sql)
}
