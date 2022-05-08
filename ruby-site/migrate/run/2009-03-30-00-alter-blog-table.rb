# Pre-existing structures of blog table, as of March 19, 2009.
#
# CREATE TABLE `blog` (
#   `userid` int(10) unsigned NOT NULL default '0',
#   `id` int(10) unsigned NOT NULL default '0',
#   `time` int(11) NOT NULL default '0',
#   `year` smallint(5) unsigned NOT NULL default '0',
#   `month` tinyint(3) unsigned NOT NULL default '0',
#   `title` varchar(128) NOT NULL default '',
#   `allowcomments` enum('y','n') NOT NULL default 'y',
#   `msg` text NOT NULL,
#   `parse_bbcode` enum('y','n') NOT NULL default 'y',
#   `visibility` tinyint(4) NOT NULL default '4',
#   PRIMARY KEY  (`userid`,`id`),
#   KEY `bytime` (`userid`,`time`,`id`),
#   KEY `byyearmonth` (`userid`,`year`,`month`,`time`,`id`),
#   KEY `timeonly` (`time`)
# ) ENGINE=MyISAM DEFAULT CHARSET=latin1 CHECKSUM=1;

sql = "ALTER TABLE `blog` ADD `typeid` INT( 10 ) UNSIGNED DEFAULT '0' NOT NULL AFTER `visibility`"

$site.dbs[:anondb].query(sql)
$site.dbs[:usersdb].query(sql)
