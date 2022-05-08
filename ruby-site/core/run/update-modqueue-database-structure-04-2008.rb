
$site.dbs[:moddb].query("
CREATE TABLE `newmoditem` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` blob NOT NULL,
  `priority` enum('n','y') NOT NULL DEFAULT 'n',
  `points` tinyint(4) NOT NULL DEFAULT '0',
  `lock` int(11) NOT NULL DEFAULT '0',
  `typeid` smallint(5) NOT NULL DEFAULT '0',
  `lockid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;
");
$site.dbs[:moddb].query("
CREATE TABLE `newmodqueue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `typeid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `right` int(10) unsigned NOT NULL DEFAULT '0',
  `wrong` int(10) unsigned NOT NULL DEFAULT '0',
  `lenient` int(10) unsigned NOT NULL DEFAULT '0',
  `strict` int(10) unsigned NOT NULL DEFAULT '0',
  `level` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `time` int(11) NOT NULL DEFAULT '0',
  `creationtime` int(11) NOT NULL DEFAULT '0',
  `autoscroll` enum('y','n') NOT NULL DEFAULT 'y',
  `picsperpage` tinyint(3) unsigned NOT NULL DEFAULT '35',
  `errorrate` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid` (`userid`,`typeid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 PACK_KEYS=0 ;
");

$site.dbs[:moddb].query("
CREATE TABLE `newmodvotes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `moditemid` int(10) unsigned NOT NULL DEFAULT '0',
  `typeid` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `modid` int(10) unsigned NOT NULL DEFAULT '0',
  `vote` enum('y','n') NOT NULL DEFAULT 'y',
  `points` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `moditemid` (`moditemid`),
  KEY `modid` (`modid`,`typeid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;
");

$site.dbs[:moddb].query("CREATE TABLE `randomstring` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `str` varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
)")
