-- articlesdb.articles
CREATE TABLE `articles` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `authorid` int(10) unsigned NOT NULL default '0',
  `author` varchar(12) NOT NULL default '',
  `submittime` int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `text` text NOT NULL,
  `category` int(10) unsigned NOT NULL default '0',
  `votes` int(10) unsigned NOT NULL default '0',
  `moded` enum('n','y') NOT NULL default 'n',
  `comments` smallint(5) unsigned NOT NULL default '0',
  `nextcomment` int(10) unsigned NOT NULL default '1',
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  `official` enum('y','n') NOT NULL default 'n',
  `avg_rating` int(10) unsigned default '0',
  `total_ratings` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `moded` (`moded`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- articlesdb.cats
CREATE TABLE `cats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- articlesdb.comments
CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `itemid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `itemid` (`itemid`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- articlesdb.commentstext
CREATE TABLE `commentstext` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `msg` text NOT NULL,
  `nmsg` text NOT NULL,
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- bannerdb.bannercampaigns
CREATE TABLE `bannercampaigns` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `clientid` int(10) unsigned NOT NULL default '0',
  `maxviews` int(11) NOT NULL default '0',
  `maxclicks` int(11) NOT NULL default '0',
  `viewsperday` int(11) NOT NULL default '0',
  `minviewsperday` int(10) unsigned NOT NULL default '0',
  `limitbyperiod` int(10) unsigned NOT NULL default '86400',
  `clicksperday` int(11) NOT NULL default '0',
  `viewsperuser` int(10) unsigned NOT NULL default '0',
  `limitbyhour` enum('y','n') NOT NULL default 'y',
  `startdate` int(11) NOT NULL default '0',
  `enddate` int(11) NOT NULL default '0',
  `payrate` smallint(5) unsigned NOT NULL default '0',
  `paytype` tinyint(3) unsigned NOT NULL default '0',
  `title` varchar(32) NOT NULL default '',
  `dateadded` int(11) NOT NULL default '0',
  `age` text NOT NULL,
  `sex` text NOT NULL,
  `loc` text NOT NULL,
  `page` text NOT NULL,
  `interests` text NOT NULL,
  `allowedtimes` text NOT NULL,
  `enabled` enum('y','n') NOT NULL default 'y',
  `refresh` smallint(6) NOT NULL default '30',
  `credits` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- bannerdb.bannerclients
CREATE TABLE `bannerclients` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `clientname` varchar(64) NOT NULL default '',
  `type` enum('agency','local','affiliate','payinadvance') NOT NULL default 'agency',
  `dateadded` int(11) NOT NULL default '0',
  `notes` text NOT NULL,
  `credits` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- bannerdb.banners
CREATE TABLE `banners` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `clientid` int(10) unsigned NOT NULL default '0',
  `bannersize` tinyint(3) unsigned NOT NULL default '0',
  `bannertype` tinyint(3) unsigned NOT NULL default '0',
  `views` int(10) unsigned NOT NULL default '0',
  `potentialviews` int(10) unsigned NOT NULL default '0',
  `clicks` int(10) unsigned NOT NULL default '0',
  `maxviews` int(11) NOT NULL default '0',
  `maxclicks` int(11) NOT NULL default '0',
  `viewsperday` int(11) NOT NULL default '0',
  `minviewsperday` int(10) unsigned NOT NULL default '0',
  `clicksperday` int(11) NOT NULL default '0',
  `viewsperuser` int(10) unsigned NOT NULL default '0',
  `limitbyperiod` int(10) unsigned NOT NULL default '86400',
  `limitbyhour` enum('y','n') NOT NULL default 'y',
  `startdate` int(11) NOT NULL default '0',
  `enddate` int(11) NOT NULL default '0',
  `payrate` smallint(6) NOT NULL default '-1',
  `paytype` tinyint(3) unsigned NOT NULL default '0',
  `age` text NOT NULL,
  `sex` text NOT NULL,
  `loc` text NOT NULL,
  `page` text NOT NULL,
  `interests` text NOT NULL,
  `allowedtimes` text NOT NULL,
  `moded` enum('denied','approved','unchecked') NOT NULL default 'unchecked',
  `enabled` enum('y','n') NOT NULL default 'y',
  `title` varchar(32) NOT NULL default '',
  `image` varchar(255) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  `alt` text NOT NULL,
  `dateadded` int(11) NOT NULL default '0',
  `lastupdatetime` int(11) NOT NULL default '0',
  `refresh` smallint(6) NOT NULL default '-1',
  `passbacks` int(10) unsigned NOT NULL default '0',
  `campaignid` int(10) unsigned NOT NULL default '0',
  `credits` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `clientid` (`clientid`,`bannersize`)
) TYPE=MyISAM

--------------------------------------------------------

-- bannerdb.bannersold
CREATE TABLE `bannersold` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `clientid` int(10) unsigned NOT NULL default '0',
  `maxviewsperday` int(10) unsigned NOT NULL default '0',
  `payrate` smallint(5) unsigned NOT NULL default '0',
  `maxviews` int(10) unsigned NOT NULL default '0',
  `startdate` int(11) NOT NULL default '0',
  `enddate` int(11) NOT NULL default '0',
  `bannersize` enum('468x60','120x60','120x240','120x600','300x250','728x90') NOT NULL default '468x60',
  `bannertype` enum('image','flash','iframe','html','text') NOT NULL default 'image',
  `views` int(10) unsigned NOT NULL default '0',
  `clicks` int(10) unsigned NOT NULL default '0',
  `title` varchar(32) NOT NULL default '',
  `image` varchar(255) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  `alt` text NOT NULL,
  `weight` double NOT NULL default '0',
  `statsid` int(10) unsigned NOT NULL default '0',
  `moded` enum('n','y') NOT NULL default 'n',
  `dateadded` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `bannersize` (`bannersize`,`weight`)
) TYPE=MyISAM

--------------------------------------------------------

-- bannerdb.bannerstats
CREATE TABLE `bannerstats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `bannerid` int(10) unsigned NOT NULL default '0',
  `clientid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `priority` smallint(5) unsigned NOT NULL default '0',
  `views` int(10) unsigned NOT NULL default '0',
  `potentialviews` int(10) unsigned NOT NULL default '0',
  `clicks` int(10) unsigned NOT NULL default '0',
  `passbacks` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `bannerid` (`bannerid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- bannerdb.bannertypestats
CREATE TABLE `bannertypestats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `size` tinyint(3) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `views` int(11) NOT NULL default '0',
  `clicks` int(11) NOT NULL default '0',
  `viewsdump` text NOT NULL,
  `clicksdump` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- bannerdb.bannertypestatsold
CREATE TABLE `bannertypestatsold` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `size` tinyint(3) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `views` int(11) NOT NULL default '0',
  `clicks` int(11) NOT NULL default '0',
  `viewsdump` text NOT NULL,
  `clicksdump` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- configdb.interests
CREATE TABLE `interests` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- configdb.locs
CREATE TABLE `locs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- contestdb.contestentries
CREATE TABLE `contestentries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `contestid` int(10) unsigned NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `contact` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `contestid` (`contestid`,`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- contestdb.contests
CREATE TABLE `contests` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `end` int(11) NOT NULL default '0',
  `anonymous` enum('n','y') NOT NULL default 'n',
  `content` text NOT NULL,
  `entries` int(10) unsigned NOT NULL default '0',
  `final` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.abuse
CREATE TABLE `abuse` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` tinyint(3) unsigned NOT NULL default '0',
  `itemid` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `reason` text NOT NULL,
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `picid` (`itemid`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.agegroups
CREATE TABLE `agegroups` (
  `age` tinyint(3) unsigned NOT NULL default '0',
  `Male` int(10) unsigned NOT NULL default '0',
  `Female` int(10) unsigned NOT NULL default '0',
  `singleMale` int(10) unsigned NOT NULL default '0',
  `singleFemale` int(10) unsigned NOT NULL default '0',
  `picsMale` int(10) unsigned NOT NULL default '0',
  `picsFemale` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`age`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.bannedusers
CREATE TABLE `bannedusers` (
  `banned` varchar(255) NOT NULL default '',
  `userid` int(10) unsigned NOT NULL default '0',
  `modid` int(10) unsigned NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  UNIQUE KEY `banned` (`banned`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.bannedwords
CREATE TABLE `bannedwords` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `word` char(32) NOT NULL default '',
  `type` enum('word','part','name') NOT NULL default 'word',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `type` (`type`,`word`(8))
) TYPE=MyISAM

--------------------------------------------------------

-- db.bday
CREATE TABLE `bday` (
  `userid` int(10) unsigned NOT NULL default '0',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  PRIMARY KEY  (`sex`,`age`,`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.blocks
CREATE TABLE `blocks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `priority` int(10) unsigned NOT NULL default '0',
  `side` enum('l','r') NOT NULL default 'l',
  `funcname` char(32) NOT NULL default '',
  `enabled` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.config
CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `comments` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.deletedusers
CREATE TABLE `deletedusers` (
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(18) NOT NULL default '',
  `jointime` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `reason` text NOT NULL,
  `deleteid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`),
  KEY `email` (`email`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.faq
CREATE TABLE `faq` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `text` mediumtext NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `priority` (`priority`),
  KEY `parent` (`parent`,`priority`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.faqcats
CREATE TABLE `faqcats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.files
CREATE TABLE `files` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `location` varchar(255) NOT NULL default '',
  `list` enum('n','y') NOT NULL default 'n',
  `read` enum('n','y') NOT NULL default 'n',
  `readphp` enum('n','y') NOT NULL default 'n',
  `write` enum('n','y') NOT NULL default 'n',
  `writephp` enum('n','y') NOT NULL default 'n',
  `recursive` enum('n','y') NOT NULL default 'n',
  `filesizelimit` int(10) unsigned NOT NULL default '0',
  `quota` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.inviteoptout
CREATE TABLE `inviteoptout` (
  `email` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`email`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.invites
CREATE TABLE `invites` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `email` (`email`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.mirrors
CREATE TABLE `mirrors` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `weight` smallint(6) NOT NULL default '0',
  `type` enum('image','www') NOT NULL default 'image',
  `plus` enum('n','y') NOT NULL default 'n',
  `cookie` varchar(64) NOT NULL default '',
  `domain` varchar(64) NOT NULL default '',
  `status` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.newestprofile
CREATE TABLE `newestprofile` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(18) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `age` tinyint(4) NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`),
  UNIQUE KEY `age` (`sex`,`age`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.newestusers
CREATE TABLE `newestusers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(18) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `age` tinyint(4) NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`),
  UNIQUE KEY `age` (`sex`,`age`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.news
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` enum('both','inside','outside') NOT NULL default 'both',
  `userid` int(10) unsigned default NULL,
  `title` varchar(255) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `text` text NOT NULL,
  `ntext` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `typedate` (`type`,`date`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.pixels
CREATE TABLE `pixels` (
  `x` tinyint(4) NOT NULL default '0',
  `y` tinyint(4) NOT NULL default '0',
  `color` varchar(6) NOT NULL default '',
  PRIMARY KEY  (`x`,`y`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.pluslog
CREATE TABLE `pluslog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `from` int(10) unsigned NOT NULL default '0',
  `to` int(10) unsigned NOT NULL default '0',
  `admin` int(10) unsigned NOT NULL default '0',
  `duration` int(11) NOT NULL default '0',
  `trackid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.police
CREATE TABLE `police` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `fileid` varchar(32) NOT NULL default '',
  `officer` varchar(64) NOT NULL default '',
  `location` varchar(128) NOT NULL default '',
  `phone` varchar(128) NOT NULL default '',
  `fax` varchar(32) NOT NULL default '',
  `email` varchar(255) NOT NULL default '',
  `dumpuserid` int(10) unsigned NOT NULL default '0',
  `reason` text NOT NULL,
  `dump` longtext NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.premiumlog
CREATE TABLE `premiumlog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `duration` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.profileskins
CREATE TABLE `profileskins` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `data` varchar(66) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`name`(2))
) TYPE=MyISAM

--------------------------------------------------------

-- db.profiletext
CREATE TABLE `profiletext` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `priority` tinyint(3) unsigned NOT NULL default '0',
  `scope` tinyint(3) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `text` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.searchqueries
CREATE TABLE `searchqueries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `time` int(11) NOT NULL default '0',
  `sort` text NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.skins
CREATE TABLE `skins` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `name` varchar(20) NOT NULL default '',
  `cssloc` varchar(255) NOT NULL default '',
  `menudata` text NOT NULL,
  `active` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.spotlight
CREATE TABLE `spotlight` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.spotlighthist
CREATE TABLE `spotlighthist` (
  `userid` int(10) unsigned NOT NULL default '0',
  `pic` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  KEY `time` (`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.staticpages
CREATE TABLE `staticpages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL default '',
  `restricted` enum('n','y') NOT NULL default 'n',
  `html` enum('y','n') NOT NULL default 'y',
  `autonewlines` enum('n','y') NOT NULL default 'n',
  `pagewidth` smallint(5) unsigned NOT NULL default '0',
  `content` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.todo
CREATE TABLE `todo` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(64) NOT NULL default '',
  `description` text NOT NULL,
  `priority` tinyint(3) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `assignee` int(10) unsigned NOT NULL default '0',
  `timereq` int(10) unsigned NOT NULL default '0',
  `section` int(10) unsigned NOT NULL default '0',
  `status` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- db.usernotify
CREATE TABLE `usernotify` (
  `usernotifyid` int(10) unsigned NOT NULL auto_increment,
  `creatorid` int(10) unsigned NOT NULL default '0',
  `createtime` int(11) NOT NULL default '0',
  `targetid` int(10) unsigned NOT NULL default '0',
  `triggertime` int(11) NOT NULL default '0',
  `subject` varchar(250) NOT NULL default '',
  `message` text NOT NULL,
  PRIMARY KEY  (`usernotifyid`),
  KEY `targetid` (`targetid`,`triggertime`)
) TYPE=MyISAM

--------------------------------------------------------

-- filesdb.fileservers
CREATE TABLE `fileservers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `server` varchar(255) NOT NULL default '',
  `queueposition` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `server` (`server`)
) TYPE=MyISAM

--------------------------------------------------------

-- filesdb.fileupdates
CREATE TABLE `fileupdates` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `action` enum('add','update','delete') NOT NULL default 'add',
  `file` varchar(255) NOT NULL default '',
  `server` varchar(255) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumcatcollapse
CREATE TABLE `forumcatcollapse` (
  `userid` int(10) unsigned NOT NULL default '0',
  `categoryid` int(10) unsigned NOT NULL default '0',
  KEY `users` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumcats
CREATE TABLE `forumcats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `ownerid` int(10) unsigned NOT NULL default '0',
  `priority` tinyint(4) NOT NULL default '0',
  `official` enum('y','n') NOT NULL default 'n',
  `name` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `categoryowner` (`ownerid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.foruminvite
CREATE TABLE `foruminvite` (
  `userid` int(10) unsigned NOT NULL default '0',
  `forumid` int(10) unsigned NOT NULL default '0',
  `categoryid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`forumid`),
  KEY `forumid` (`forumid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forummodlog
CREATE TABLE `forummodlog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `forumid` int(10) unsigned NOT NULL default '0',
  `action` enum('deletethread','deletepost','lock','unlock','stick','unstick','announce','unannounce','move','mute','unmute','invite','uninvite','editpost','addmod','removemod','editmod','flag','unflag') NOT NULL default 'deletethread',
  `threadid` int(10) unsigned NOT NULL default '0',
  `var1` int(10) unsigned NOT NULL default '0',
  `var2` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `forumid` (`forumid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forummods
CREATE TABLE `forummods` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `forumid` int(10) unsigned NOT NULL default '0',
  `activetime` int(11) NOT NULL default '0',
  `view` enum('n','y') NOT NULL default 'y',
  `post` enum('n','y') NOT NULL default 'y',
  `postlocked` enum('n','y') NOT NULL default 'n',
  `move` enum('n','y') NOT NULL default 'n',
  `editposts` enum('n','y') NOT NULL default 'n',
  `editownposts` enum('n','y') NOT NULL default 'n',
  `deleteposts` enum('n','y') NOT NULL default 'n',
  `deletethreads` enum('n','y') NOT NULL default 'n',
  `lock` enum('n','y') NOT NULL default 'n',
  `stick` enum('n','y') NOT NULL default 'n',
  `mute` enum('n','y') NOT NULL default 'n',
  `invite` enum('n','y') NOT NULL default 'n',
  `announce` enum('n','y') NOT NULL default 'n',
  `flag` enum('n','y') NOT NULL default 'n',
  `modlog` enum('n','y') NOT NULL default 'n',
  `listmods` enum('n','y') NOT NULL default 'n',
  `editmods` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`forumid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forummute
CREATE TABLE `forummute` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `forumid` int(10) unsigned NOT NULL default '0',
  `mutetime` int(11) NOT NULL default '0',
  `unmutetime` int(11) NOT NULL default '0',
  `reasonid` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`,`forumid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forummutereason
CREATE TABLE `forummutereason` (
  `id` int(10) unsigned NOT NULL default '0',
  `modid` int(10) unsigned NOT NULL default '0',
  `reason` varchar(255) NOT NULL default '',
  `threadid` int(10) unsigned NOT NULL default '0',
  `globalreq` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumposts
CREATE TABLE `forumposts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `threadid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `edit` int(11) NOT NULL default '0',
  `msg` text NOT NULL,
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`),
  KEY `time` (`time`),
  KEY `authorid` (`authorid`),
  KEY `threadid` (`threadid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumpostsdel
CREATE TABLE `forumpostsdel` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `threadid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `edit` int(11) NOT NULL default '0',
  `msg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `threadid` (`threadid`),
  KEY `time` (`time`),
  KEY `authorid` (`authorid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumranks
CREATE TABLE `forumranks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `postmax` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumrankspending
CREATE TABLE `forumrankspending` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `forumrank` varchar(18) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumread
CREATE TABLE `forumread` (
  `userid` int(10) unsigned NOT NULL default '0',
  `threadid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `readtime` int(11) NOT NULL default '0',
  `subscribe` enum('n','y') NOT NULL default 'n',
  `posts` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`threadid`,`userid`),
  KEY `readtime` (`readtime`),
  KEY `userid` (`userid`,`subscribe`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forums
CREATE TABLE `forums` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `categoryid` int(10) unsigned NOT NULL default '0',
  `official` enum('y','n') NOT NULL default 'y',
  `name` varchar(32) NOT NULL default '',
  `description` varchar(128) NOT NULL default '',
  `threads` int(10) unsigned NOT NULL default '0',
  `posts` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `autolock` int(10) unsigned NOT NULL default '0',
  `edit` enum('n','y','5','15','60') NOT NULL default 'n',
  `ownerid` int(10) unsigned NOT NULL default '0',
  `public` enum('n','y') NOT NULL default 'n',
  `mute` enum('n','y') NOT NULL default 'n',
  `sorttime` int(10) unsigned NOT NULL default '14',
  `rules` text NOT NULL,
  `unofficial` enum('y','n') NOT NULL default 'y',
  `has_banner` enum('y','n') NOT NULL default 'n',
  `banner_modded` enum('y','n') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `time` (`time`),
  KEY `official` (`official`,`public`,`posts`),
  KEY `bytime` (`public`,`categoryid`,`unofficial`,`time`,`id`),
  KEY `byname` (`public`,`categoryid`,`official`,`name`,`id`),
  KEY `byposts` (`public`,`categoryid`,`unofficial`,`posts`,`id`)
) TYPE=MyISAM ROW_FORMAT=FIXED

--------------------------------------------------------

-- forumdb.forumthreads
CREATE TABLE `forumthreads` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `forumid` int(10) unsigned NOT NULL default '0',
  `moved` int(10) unsigned NOT NULL default '0',
  `title` varchar(64) NOT NULL default '',
  `authorid` int(10) unsigned NOT NULL default '0',
  `reads` int(10) unsigned NOT NULL default '0',
  `posts` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `lastauthorid` int(10) unsigned NOT NULL default '0',
  `sticky` enum('n','y') NOT NULL default 'n',
  `locked` enum('n','y') NOT NULL default 'n',
  `announcement` enum('n','y') NOT NULL default 'n',
  `flag` enum('n','y') NOT NULL default 'n',
  `pollid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `time` (`time`),
  KEY `forumid` (`forumid`,`sticky`,`time`),
  KEY `announcement` (`announcement`,`forumid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumthreadsdel
CREATE TABLE `forumthreadsdel` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `forumid` int(10) unsigned NOT NULL default '0',
  `moved` int(10) unsigned NOT NULL default '0',
  `title` varchar(64) NOT NULL default '',
  `authorid` int(10) unsigned NOT NULL default '0',
  `reads` int(10) unsigned NOT NULL default '0',
  `posts` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `lastauthorid` int(10) unsigned NOT NULL default '0',
  `sticky` enum('n','y') NOT NULL default 'n',
  `locked` enum('n','y') NOT NULL default 'n',
  `announcement` enum('n','y') NOT NULL default 'n',
  `flag` enum('n','y') NOT NULL default 'n',
  `pollid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `forumid` (`forumid`),
  KEY `time` (`time`),
  KEY `announcement` (`announcement`)
) TYPE=MyISAM

--------------------------------------------------------

-- forumdb.forumupdated
CREATE TABLE `forumupdated` (
  `userid` int(10) unsigned NOT NULL default '0',
  `forumid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `readalltime` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`forumid`),
  KEY `time` (`time`),
  KEY `forumid` (`forumid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.accountmap
CREATE TABLE `accountmap` (
  `relid` int(10) unsigned NOT NULL auto_increment,
  `primaryid` int(10) unsigned NOT NULL default '0',
  `accountid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`relid`),
  UNIQUE KEY `primaryid` (`primaryid`,`accountid`),
  KEY `accountid` (`accountid`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.accounts
CREATE TABLE `accounts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` tinyint(3) unsigned NOT NULL default '0',
  `serverid` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `users` (`type`)
) TYPE=InnoDB

--------------------------------------------------------

-- masterdb.anonstats
CREATE TABLE `anonstats` (
  `hitsanon` bigint(20) unsigned NOT NULL default '0',
  `hitstotal` bigint(20) unsigned NOT NULL default '0'
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.masteragesexgroups
CREATE TABLE `masteragesexgroups` (
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `total` int(10) unsigned NOT NULL default '0',
  `active` int(10) unsigned NOT NULL default '0',
  `pics` int(10) unsigned NOT NULL default '0',
  `signpics` int(10) unsigned NOT NULL default '0',
  `activepics` int(10) unsigned NOT NULL default '0',
  `activesignpics` int(10) unsigned NOT NULL default '0',
  `single` int(10) unsigned NOT NULL default '0',
  `sexuality1` int(10) unsigned NOT NULL default '0',
  `sexuality2` int(10) unsigned NOT NULL default '0',
  `sexuality3` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`age`,`sex`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.masterintereststats
CREATE TABLE `masterintereststats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `users` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.masterlocstats
CREATE TABLE `masterlocstats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `users` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.serverbalance
CREATE TABLE `serverbalance` (
  `serverid` tinyint(3) unsigned NOT NULL auto_increment,
  `weight` tinyint(3) unsigned NOT NULL default '1',
  `count` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`serverid`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.statshist
CREATE TABLE `statshist` (
  `time` int(11) NOT NULL default '0',
  `hitsFemale` bigint(20) unsigned NOT NULL default '0',
  `hitsMale` bigint(20) unsigned NOT NULL default '0',
  `hitsuser` bigint(20) unsigned NOT NULL default '0',
  `hitsplus` bigint(20) unsigned NOT NULL default '0',
  `hitsanon` bigint(20) unsigned NOT NULL default '0',
  `hitstotal` bigint(20) unsigned NOT NULL default '0',
  `onlineusers` int(10) unsigned NOT NULL default '0',
  `onlineguests` int(10) unsigned NOT NULL default '0',
  `userstotal` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.useremails
CREATE TABLE `useremails` (
  `userid` int(10) unsigned NOT NULL default '0',
  `active` enum('n','y') NOT NULL default 'n',
  `email` varchar(100) NOT NULL default '',
  `key` varchar(32) NOT NULL default '',
  `time` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`active`),
  UNIQUE KEY `email` (`email`)
) TYPE=MyISAM

--------------------------------------------------------

-- masterdb.usernames
CREATE TABLE `usernames` (
  `userid` int(10) unsigned NOT NULL default '0',
  `username` char(18) NOT NULL default '',
  `live` enum('y') default NULL,
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `username` (`username`,`live`)
) TYPE=MyISAM ROW_FORMAT=FIXED

--------------------------------------------------------

-- masterdb.usersearchidrange
CREATE TABLE `usersearchidrange` (
  `serverid` int(10) unsigned NOT NULL default '0',
  `startid` int(10) unsigned NOT NULL default '0',
  `endid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`serverid`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.abuselog
CREATE TABLE `abuselog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `modid` int(10) unsigned NOT NULL default '0',
  `modname` varchar(12) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `action` smallint(5) unsigned NOT NULL default '0',
  `reason` smallint(5) unsigned NOT NULL default '0',
  `subject` varchar(128) NOT NULL default '',
  `msg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`),
  KEY `time` (`time`),
  KEY `modid` (`modid`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.abuselogcomments
CREATE TABLE `abuselogcomments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `abuseid` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `msg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `abuseid` (`abuseid`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.admin
CREATE TABLE `admin` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `visible` enum('y','n') NOT NULL default 'y',
  `title` varchar(32) NOT NULL default 'Administrator',
  `listusers` enum('n','y') NOT NULL default 'n',
  `showemail` enum('n','y') NOT NULL default 'n',
  `listdeletedusers` enum('n','y') NOT NULL default 'n',
  `listbannedusers` enum('n','y') NOT NULL default 'n',
  `deleteusers` enum('n','y') NOT NULL default 'n',
  `banusers` enum('n','y') NOT NULL default 'n',
  `activateusers` enum('n','y') NOT NULL default 'n',
  `abuselog` enum('n','y') NOT NULL default 'n',
  `editprofile` enum('n','y') NOT NULL default 'n',
  `editpictures` enum('n','y') NOT NULL default 'n',
  `editfiles` enum('n','y') NOT NULL default 'n',
  `editfriends` enum('n','y') NOT NULL default 'n',
  `editgallery` enum('n','y') NOT NULL default 'n',
  `viewmessages` enum('n','y') NOT NULL default 'n',
  `deletecomments` enum('n','y') NOT NULL default 'n',
  `ignoreusers` enum('n','y') NOT NULL default 'n',
  `editjournal` enum('n','y') NOT NULL default 'n',
  `viewrecentvisitors` enum('n','y') NOT NULL default 'n',
  `viewsubscriptions` enum('n','y') NOT NULL default 'n',
  `editpreferences` enum('n','y') NOT NULL default 'n',
  `editemail` enum('n','y') NOT NULL default 'n',
  `editpassword` enum('n','y') NOT NULL default 'n',
  `adminlog` enum('n','y') NOT NULL default 'n',
  `loginlog` enum('n','y') NOT NULL default 'n',
  `listmods` enum('n','y') NOT NULL default 'n',
  `editmods` enum('n','y') NOT NULL default 'n',
  `moderator` enum('n','y') NOT NULL default 'n',
  `mirror` enum('n','y') NOT NULL default 'n',
  `news` enum('n','y') NOT NULL default 'n',
  `polls` enum('n','y') NOT NULL default 'n',
  `stats` enum('n','y') NOT NULL default 'n',
  `articles` enum('n','y') NOT NULL default 'n',
  `faq` enum('n','y') NOT NULL default 'n',
  `forums` enum('n','y') NOT NULL default 'n',
  `forummods` enum('n','y') NOT NULL default 'n',
  `smilies` enum('n','y') NOT NULL default 'n',
  `categories` enum('n','y') NOT NULL default 'n',
  `config` enum('n','y') NOT NULL default 'n',
  `wordfilter` enum('n','y') NOT NULL default 'n',
  `errorlog` enum('n','y') NOT NULL default 'n',
  `todo` enum('n','y') NOT NULL default 'n',
  `listbannerclients` enum('n','y') NOT NULL default 'n',
  `listbanners` enum('n','y') NOT NULL default 'n',
  `createbannerclients` enum('n','y') NOT NULL default 'n',
  `createbanners` enum('n','y') NOT NULL default 'n',
  `listinvoices` enum('n','y') NOT NULL default 'n',
  `viewinvoice` enum('n','y') NOT NULL default 'n',
  `editinvoice` enum('n','y') NOT NULL default 'n',
  `contests` enum('n','y') NOT NULL default 'n',
  `staticpages` enum('n','y') NOT NULL default 'n',
  `wiki` enum('n','y') NOT NULL default 'n',
  `editskins` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.adminlog
CREATE TABLE `adminlog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `page` varchar(64) NOT NULL default '',
  `action` varchar(32) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.modhist
CREATE TABLE `modhist` (
  `dumptime` int(11) NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `right` int(10) unsigned NOT NULL default '0',
  `wrong` int(10) unsigned NOT NULL default '0',
  `lenient` int(10) unsigned NOT NULL default '0',
  `strict` int(10) unsigned NOT NULL default '0',
  `level` tinyint(3) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `creationtime` int(11) NOT NULL default '0',
  KEY `type` (`type`,`dumptime`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.moditems
CREATE TABLE `moditems` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` tinyint(3) unsigned NOT NULL default '0',
  `splitid` int(10) unsigned NOT NULL default '0',
  `itemid` int(10) unsigned NOT NULL default '0',
  `priority` enum('n','y') NOT NULL default 'n',
  `points` tinyint(4) NOT NULL default '0',
  `lock` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `item` (`type`,`itemid`,`splitid`),
  KEY `type` (`type`,`lock`,`itemid`,`splitid`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.mods
CREATE TABLE `mods` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `right` int(10) unsigned NOT NULL default '0',
  `wrong` int(10) unsigned NOT NULL default '0',
  `lenient` int(10) unsigned NOT NULL default '0',
  `strict` int(10) unsigned NOT NULL default '0',
  `level` tinyint(3) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `creationtime` int(11) NOT NULL default '0',
  `autoscroll` enum('y','n') NOT NULL default 'y',
  `picsperpage` tinyint(3) unsigned NOT NULL default '35',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`,`type`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.modvotes
CREATE TABLE `modvotes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `moditemid` int(10) unsigned NOT NULL default '0',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `modid` int(10) unsigned NOT NULL default '0',
  `vote` enum('y','n') NOT NULL default 'y',
  `points` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `moditemid` (`moditemid`),
  KEY `modid` (`modid`,`type`)
) TYPE=MyISAM

--------------------------------------------------------

-- moddb.modvoteslog
CREATE TABLE `modvoteslog` (
  `modid` int(10) unsigned NOT NULL default '0',
  `picid` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `vote` enum('y','n') NOT NULL default 'y',
  `time` int(11) NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  `points` tinyint(3) unsigned NOT NULL default '0',
  KEY `modid` (`modid`),
  KEY `picid` (`picid`),
  KEY `time` (`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- picmodexamdb.examconfig
CREATE TABLE `examconfig` (
  `var` varchar(20) NOT NULL default '',
  `data` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`var`)
) TYPE=MyISAM

--------------------------------------------------------

-- picmodexamdb.exampiccategories
CREATE TABLE `exampiccategories` (
  `catid` tinyint(3) unsigned NOT NULL auto_increment,
  `catlabel` varchar(40) NOT NULL default '',
  `catcnt` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`catid`)
) TYPE=MyISAM

--------------------------------------------------------

-- picmodexamdb.exampics
CREATE TABLE `exampics` (
  `picid` int(10) unsigned NOT NULL auto_increment,
  `picfilename` varchar(48) NOT NULL default '',
  `addedtime` int(10) unsigned NOT NULL default '0',
  `forceinclude` enum('y','n') NOT NULL default 'n',
  `modanswer` enum('unknown','accept','deny','instant fail') NOT NULL default 'unknown',
  `isretired` enum('y','n') NOT NULL default 'n',
  `modcategory` tinyint(3) unsigned NOT NULL default '1',
  `modreason` varchar(255) NOT NULL default '',
  `picgender` enum('male','female') NOT NULL default 'male',
  `picage` tinyint(3) unsigned NOT NULL default '0',
  `piccomment` varchar(255) NOT NULL default '',
  `acceptcnt` int(10) unsigned NOT NULL default '0',
  `denycnt` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`picid`),
  KEY `picfilename` (`picfilename`(10)),
  KEY `modcategory` (`modcategory`,`modanswer`,`isretired`,`picgender`),
  KEY `isretired` (`isretired`),
  KEY `modanswer` (`modanswer`)
) TYPE=MyISAM

--------------------------------------------------------

-- picmodexamdb.examresults
CREATE TABLE `examresults` (
  `examid` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `examtimestart` int(10) unsigned NOT NULL default '0',
  `examtimeend` int(10) unsigned NOT NULL default '0',
  `examscore` float unsigned NOT NULL default '0',
  `examstatus` enum('waiting','invite sent','invite declined','invite revoked','exam started','passed','failed') NOT NULL default 'waiting',
  `isarchived` enum('y','n') NOT NULL default 'n',
  `approvegood` tinyint(3) unsigned NOT NULL default '0',
  `denybad` tinyint(3) unsigned NOT NULL default '0',
  `approvebad` tinyint(3) unsigned NOT NULL default '0',
  `denygood` tinyint(3) unsigned NOT NULL default '0',
  `frozenexam` text NOT NULL,
  `comments` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`examid`),
  KEY `userid` (`userid`),
  KEY `isarchived` (`isarchived`)
) TYPE=MyISAM

--------------------------------------------------------

-- picmodexamdb.examusers
CREATE TABLE `examusers` (
  `userid` int(10) unsigned NOT NULL default '0',
  `posid` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- polldb.pollans
CREATE TABLE `pollans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pollid` int(10) unsigned NOT NULL default '0',
  `answer` varchar(255) NOT NULL default '',
  `votes` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `pollid` (`pollid`)
) TYPE=MyISAM

--------------------------------------------------------

-- polldb.pollcomments
CREATE TABLE `pollcomments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `itemid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `time` int(11) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- polldb.pollcommentstext
CREATE TABLE `pollcommentstext` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `msg` text NOT NULL,
  `nmsg` text NOT NULL,
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- polldb.polls
CREATE TABLE `polls` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `question` varchar(255) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `tvotes` int(10) unsigned NOT NULL default '0',
  `comments` smallint(5) unsigned NOT NULL default '0',
  `official` enum('y','n') NOT NULL default 'y',
  `moded` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  KEY `official` (`official`,`date`)
) TYPE=MyISAM

--------------------------------------------------------

-- polldb.pollvotes
CREATE TABLE `pollvotes` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `pollid` int(10) unsigned NOT NULL default '0',
  `vote` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `pollid` (`pollid`,`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- scrumdb.acl_flags
CREATE TABLE `acl_flags` (
  `flag_id` int(11) NOT NULL auto_increment,
  `flag_descr` varchar(200) NOT NULL default '',
  PRIMARY KEY  (`flag_id`)
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.product_backlog
CREATE TABLE `product_backlog` (
  `prod_bklg_id` int(11) NOT NULL auto_increment,
  `task_headline` longtext NOT NULL,
  `est_hours` float NOT NULL default '0',
  `priority` int(11) NOT NULL default '0',
  `status` varchar(50) NOT NULL default '',
  `detailed_description` longtext,
  PRIMARY KEY  (`prod_bklg_id`)
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.product_backlog_dep
CREATE TABLE `product_backlog_dep` (
  `prod_bklg_id` int(11) NOT NULL default '0',
  `dep_prod_bklg_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`prod_bklg_id`,`dep_prod_bklg_id`),
  KEY `dep_prod_bklg_id` (`dep_prod_bklg_id`),
  CONSTRAINT `product_backlog_dep_ibfk_4` FOREIGN KEY (`dep_prod_bklg_id`) REFERENCES `product_backlog` (`prod_bklg_id`) ON DELETE CASCADE,
  CONSTRAINT `product_backlog_dep_ibfk_3` FOREIGN KEY (`prod_bklg_id`) REFERENCES `product_backlog` (`prod_bklg_id`) ON DELETE CASCADE
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.sprint_backlog
CREATE TABLE `sprint_backlog` (
  `sp_bklg_id` int(11) NOT NULL auto_increment,
  `prod_bklg_id` int(11) NOT NULL default '0',
  `sp_id` int(11) NOT NULL default '0',
  `status` varchar(20) NOT NULL default 'OPEN',
  PRIMARY KEY  (`sp_bklg_id`),
  UNIQUE KEY `prod_bklg_id` (`prod_bklg_id`),
  KEY `sp_id` (`sp_id`),
  CONSTRAINT `sprint_backlog_ibfk_2` FOREIGN KEY (`prod_bklg_id`) REFERENCES `product_backlog` (`prod_bklg_id`),
  CONSTRAINT `sprint_backlog_ibfk_3` FOREIGN KEY (`sp_id`) REFERENCES `sprints` (`sp_id`)
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.sprint_backlog_subtasks
CREATE TABLE `sprint_backlog_subtasks` (
  `sp_bklg_stsk_id` int(11) NOT NULL auto_increment,
  `sp_bklg_id` int(11) NOT NULL default '0',
  `subtask_headline` longtext NOT NULL,
  `detailed_description` longtext,
  `initial_est_hours` float NOT NULL default '0',
  `user_id` int(11) default NULL,
  `status` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`sp_bklg_stsk_id`),
  KEY `sp_bklg_id` (`sp_bklg_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `sprint_backlog_subtasks_ibfk_3` FOREIGN KEY (`sp_bklg_id`) REFERENCES `sprint_backlog` (`sp_bklg_id`),
  CONSTRAINT `sprint_backlog_subtasks_ibfk_4` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.sprints
CREATE TABLE `sprints` (
  `sp_id` int(11) NOT NULL auto_increment,
  `start` date NOT NULL default '0000-00-00',
  `end` date NOT NULL default '0000-00-00',
  `comment` longtext,
  PRIMARY KEY  (`sp_id`)
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.subtask_daily_est
CREATE TABLE `subtask_daily_est` (
  `est_id` int(11) NOT NULL auto_increment,
  `sp_bklg_stsk_id` int(11) NOT NULL default '0',
  `day_number` int(11) NOT NULL default '0',
  `day_est_hours` float NOT NULL default '0',
  `comments` longtext,
  PRIMARY KEY  (`est_id`),
  KEY `sp_bklg_stsk_id` (`sp_bklg_stsk_id`),
  CONSTRAINT `subtask_daily_est_ibfk_1` FOREIGN KEY (`sp_bklg_stsk_id`) REFERENCES `sprint_backlog_subtasks` (`sp_bklg_stsk_id`)
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.user_acl
CREATE TABLE `user_acl` (
  `user_id` int(11) NOT NULL default '0',
  `flag_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`user_id`,`flag_id`),
  KEY `flag_id` (`flag_id`),
  CONSTRAINT `user_acl_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_acl_ibfk_2` FOREIGN KEY (`flag_id`) REFERENCES `user_acl` (`flag_id`) ON DELETE CASCADE
) TYPE=InnoDB

--------------------------------------------------------

-- scrumdb.users
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL auto_increment,
  `login` varchar(50) NOT NULL default '',
  `password` varchar(41) NOT NULL default '',
  `full_name` varchar(250) default NULL,
  `email` varchar(250) default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `login` (`login`)
) TYPE=InnoDB

--------------------------------------------------------

-- shopdb.billingpeople
CREATE TABLE `billingpeople` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `invoiceid` int(10) unsigned NOT NULL default '0',
  `action` varchar(32) NOT NULL default '',
  `transactionid` int(10) unsigned NOT NULL default '0',
  `maid` varchar(32) NOT NULL default '',
  `amount` varchar(10) NOT NULL default '',
  `customerid` varchar(32) NOT NULL default '',
  `date` varchar(10) NOT NULL default '',
  `time` varchar(8) NOT NULL default '',
  `mid` varchar(10) NOT NULL default '',
  `status` varchar(32) NOT NULL default '',
  `fullname` varchar(100) NOT NULL default '',
  `address` varchar(100) NOT NULL default '',
  `city` varchar(100) NOT NULL default '',
  `state` varchar(100) NOT NULL default '',
  `zip` varchar(100) NOT NULL default '',
  `country` varchar(100) NOT NULL default '',
  `email` varchar(200) NOT NULL default '',
  `custom` varchar(100) NOT NULL default '',
  `ip` varchar(15) NOT NULL default '',
  `paymenttype` varchar(32) NOT NULL default '',
  `score` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `transactionid` (`transactionid`),
  KEY `invoiceid` (`invoiceid`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.invoice
CREATE TABLE `invoice` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `creationdate` int(11) NOT NULL default '0',
  `total` decimal(6,2) unsigned NOT NULL default '0.00',
  `paymentmethod` enum('cash','cheque','paypal','moneyorder','debit','emailmoneytransfer','payg','visa','mc') NOT NULL default 'cash',
  `paymentcontact` varchar(32) NOT NULL default '',
  `paymentdate` int(11) NOT NULL default '0',
  `amountpaid` decimal(6,2) unsigned NOT NULL default '0.00',
  `completed` enum('n','y') NOT NULL default 'n',
  `txnid` varchar(255) NOT NULL default '',
  `valid` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.invoiceitems
CREATE TABLE `invoiceitems` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `invoiceid` int(10) unsigned NOT NULL default '0',
  `productid` int(10) unsigned NOT NULL default '0',
  `quantity` smallint(5) unsigned NOT NULL default '0',
  `price` decimal(8,4) unsigned NOT NULL default '0.0000',
  `input` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `invoiceid` (`invoiceid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.moneris
CREATE TABLE `moneris` (
  `invoiceid` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `response_order_id` varchar(99) NOT NULL default '',
  `date_stamp` varchar(10) NOT NULL default '',
  `time_stamp` varchar(8) NOT NULL default '',
  `bank_transaction_id` varchar(18) NOT NULL default '',
  `charge_total` varchar(10) NOT NULL default '',
  `bank_approval_code` varchar(8) NOT NULL default '',
  `response_code` varchar(4) NOT NULL default '',
  `iso_code` char(2) NOT NULL default '',
  `message` varchar(100) NOT NULL default '',
  `trans_name` varchar(30) NOT NULL default '',
  `cardholder` varchar(40) NOT NULL default '',
  `f4l4` varchar(16) NOT NULL default '',
  `card` char(2) NOT NULL default '',
  `expiry_date` varchar(4) NOT NULL default '',
  `result` char(1) NOT NULL default '',
  `transactionKey` varchar(100) NOT NULL default '',
  `ISSNAME` varchar(30) NOT NULL default '',
  `INVOICE` varchar(20) NOT NULL default '',
  `ISSCONF` varchar(15) NOT NULL default '',
  `status` varchar(100) NOT NULL default '',
  PRIMARY KEY  (`invoiceid`),
  UNIQUE KEY `bank_transaction_id` (`bank_transaction_id`),
  KEY `userid` (`userid`),
  KEY `f4l4` (`f4l4`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.paygbatches
CREATE TABLE `paygbatches` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `storeid` int(10) unsigned NOT NULL default '0',
  `gendate` int(11) NOT NULL default '0',
  `assigndate` int(11) NOT NULL default '0',
  `activatedate` int(11) NOT NULL default '0',
  `value` decimal(6,2) NOT NULL default '0.00',
  `num` int(10) unsigned NOT NULL default '0',
  `numused` int(10) unsigned NOT NULL default '0',
  `storeinvoiceid` varchar(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `storeid` (`storeid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.paygcards
CREATE TABLE `paygcards` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `secret` varchar(32) NOT NULL default '',
  `batchid` int(10) unsigned NOT NULL default '0',
  `usedate` int(11) NOT NULL default '0',
  `value` decimal(6,2) NOT NULL default '0.00',
  `invoiceid` int(10) unsigned NOT NULL default '0',
  `useuserid` int(10) unsigned NOT NULL default '0',
  `valid` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secret` (`secret`),
  KEY `batchid` (`batchid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.paygstores
CREATE TABLE `paygstores` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `address` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.productcats
CREATE TABLE `productcats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `name` varchar(24) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.productinputchoices
CREATE TABLE `productinputchoices` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `productid` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `productid` (`productid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.productpics
CREATE TABLE `productpics` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `productid` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `description` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `productid` (`productid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.productprices
CREATE TABLE `productprices` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `productid` int(10) unsigned NOT NULL default '0',
  `minimum` tinyint(3) unsigned NOT NULL default '0',
  `price` decimal(8,4) unsigned NOT NULL default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `productid` (`productid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.products
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `category` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `firstpicture` int(10) unsigned NOT NULL default '0',
  `unitprice` decimal(8,4) NOT NULL default '0.0000',
  `bulkpricing` enum('n','y') NOT NULL default 'n',
  `shipping` enum('n','y') NOT NULL default 'n',
  `input` enum('none','mc','text') NOT NULL default 'none',
  `inputname` varchar(32) NOT NULL default '',
  `validinput` varchar(32) NOT NULL default '',
  `callback` varchar(32) NOT NULL default '',
  `stock` varchar(32) NOT NULL default '',
  `active` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`),
  KEY `category` (`category`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.producttext
CREATE TABLE `producttext` (
  `id` int(10) unsigned NOT NULL default '0',
  `summary` text NOT NULL,
  `description` text NOT NULL,
  `ndescription` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- shopdb.shoppingcart
CREATE TABLE `shoppingcart` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `productid` int(10) unsigned NOT NULL default '0',
  `quantity` smallint(5) unsigned NOT NULL default '0',
  `price` decimal(8,4) unsigned NOT NULL default '0.0000',
  `input` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.agesexgroups
CREATE TABLE `agesexgroups` (
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `total` int(10) unsigned NOT NULL default '0',
  `active` int(10) unsigned NOT NULL default '0',
  `pics` int(10) unsigned NOT NULL default '0',
  `signpics` int(10) unsigned NOT NULL default '0',
  `activepics` int(10) unsigned NOT NULL default '0',
  `activesignpics` int(10) unsigned NOT NULL default '0',
  `single` int(10) unsigned NOT NULL default '0',
  `sexuality1` int(10) unsigned NOT NULL default '0',
  `sexuality2` int(10) unsigned NOT NULL default '0',
  `sexuality3` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`age`,`sex`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.blog
CREATE TABLE `blog` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `year` smallint(5) unsigned NOT NULL default '0',
  `month` tinyint(3) unsigned NOT NULL default '0',
  `title` varchar(128) NOT NULL default '',
  `scope` tinyint(3) unsigned NOT NULL default '1',
  `allowcomments` enum('y','n') NOT NULL default 'y',
  `msg` text NOT NULL,
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`userid`,`id`),
  KEY `bytime` (`userid`,`time`,`scope`,`id`),
  KEY `byyearmonth` (`userid`,`year`,`scope`,`month`,`time`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.blogcomments
CREATE TABLE `blogcomments` (
  `bloguserid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `blogid` int(10) unsigned NOT NULL default '0',
  `rootid` int(10) unsigned NOT NULL default '0',
  `parentid` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `deleted` enum('t','f') NOT NULL default 'f',
  `msg` text NOT NULL,
  PRIMARY KEY  (`bloguserid`,`id`),
  KEY `commenttree` (`blogid`,`rootid`,`time`,`id`,`parentid`),
  KEY `blogid` (`blogid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.blogcommentsunread
CREATE TABLE `blogcommentsunread` (
  `userid` int(10) unsigned NOT NULL default '0',
  `bloguserid` int(10) unsigned NOT NULL default '0',
  `blogid` int(10) unsigned NOT NULL default '0',
  `replytoid` int(10) unsigned NOT NULL default '0',
  `commentid` int(10) unsigned NOT NULL default '0',
  `time` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`blogid`,`time`,`replytoid`,`commentid`),
  KEY `time` (`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.bloglastreadfriends
CREATE TABLE `bloglastreadfriends` (
  `userid` int(10) unsigned NOT NULL default '0',
  `readtime` int(10) unsigned NOT NULL default '0',
  `postcount` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.friends
CREATE TABLE `friends` (
  `userid` int(10) unsigned NOT NULL default '0',
  `friendid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`friendid`),
  KEY `friendid` (`friendid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.friendscomments
CREATE TABLE `friendscomments` (
  `userid` int(10) unsigned NOT NULL default '0',
  `friendid` int(10) unsigned NOT NULL default '0',
  `comment` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`userid`,`friendid`),
  KEY `friendid` (`friendid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.gallery
CREATE TABLE `gallery` (
  `ownerid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `permission` enum('anyone','loggedin','friends') NOT NULL default 'anyone',
  `previewpicture` int(10) unsigned NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`ownerid`,`id`),
  KEY `name` (`name`,`ownerid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.gallerypending
CREATE TABLE `gallerypending` (
  `userid` int(10) unsigned NOT NULL default '0',
  `sourceid` int(10) unsigned NOT NULL default '0',
  `description` text NOT NULL,
  `uploadtime` int(10) unsigned NOT NULL default '0',
  `md5` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`userid`,`sourceid`),
  UNIQUE KEY `md5` (`userid`,`md5`),
  KEY `uploadtime` (`uploadtime`,`userid`,`sourceid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.gallerypics
CREATE TABLE `gallerypics` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `sourceid` int(11) NOT NULL default '0',
  `galleryid` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `description` text NOT NULL,
  PRIMARY KEY  (`userid`,`id`),
  KEY `usercat` (`userid`,`galleryid`,`priority`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.ignore
CREATE TABLE `ignore` (
  `userid` int(10) unsigned NOT NULL default '0',
  `ignoreid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`ignoreid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.intereststats
CREATE TABLE `intereststats` (
  `id` int(10) unsigned NOT NULL default '0',
  `users` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.locstats
CREATE TABLE `locstats` (
  `id` int(10) unsigned NOT NULL default '0',
  `users` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.loginlog
CREATE TABLE `loginlog` (
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `result` enum('success','badpass','frozen','unactivated','deleted') NOT NULL default 'success',
  KEY `userid` (`userid`),
  KEY `ip` (`ip`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.msgarchive
CREATE TABLE `msgarchive` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `to` int(10) unsigned NOT NULL default '0',
  `toname` varchar(18) NOT NULL default '',
  `from` int(10) unsigned NOT NULL default '0',
  `fromname` varchar(18) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `subject` varchar(64) NOT NULL default '',
  `msg` text NOT NULL,
  PRIMARY KEY  (`userid`,`id`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- usersdb.msgfolder
CREATE TABLE `msgfolder` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`userid`,`id`),
  KEY `userid` (`name`(2))
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.msgs
CREATE TABLE `msgs` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `otheruserid` int(10) unsigned NOT NULL default '0',
  `folder` int(10) unsigned NOT NULL default '0',
  `to` int(10) unsigned NOT NULL default '0',
  `toname` varchar(18) NOT NULL default '',
  `from` int(10) unsigned NOT NULL default '0',
  `fromname` varchar(18) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `mark` enum('n','y') NOT NULL default 'n',
  `status` enum('new','read','replied') NOT NULL default 'new',
  `othermsgid` int(10) unsigned NOT NULL default '0',
  `replyto` int(10) unsigned NOT NULL default '0',
  `subject` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`userid`,`id`),
  KEY `userid` (`userid`,`folder`,`date`),
  KEY `date` (`date`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- usersdb.msgtext
CREATE TABLE `msgtext` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `date` int(11) NOT NULL default '0',
  `html` enum('n','y') NOT NULL default 'n',
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  `msg` text NOT NULL,
  PRIMARY KEY  (`userid`,`id`),
  KEY `date` (`date`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- usersdb.picbans
CREATE TABLE `picbans` (
  `userid` int(10) unsigned NOT NULL default '0',
  `md5` char(32) NOT NULL default '',
  `times` tinyint(3) unsigned NOT NULL default '0',
  KEY `bans` (`md5`(8),`userid`)
) TYPE=MyISAM ROW_FORMAT=FIXED

--------------------------------------------------------

-- usersdb.pics
CREATE TABLE `pics` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `priority` tinyint(3) unsigned NOT NULL default '1',
  `description` varchar(64) NOT NULL default '',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `signpic` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`userid`,`id`),
  UNIQUE KEY `sexage` (`sex`,`age`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.picspending
CREATE TABLE `picspending` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `description` varchar(64) NOT NULL default '',
  `md5` varchar(32) NOT NULL default '',
  `signpic` enum('n','y') NOT NULL default 'n',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`id`),
  KEY `userid` (`userid`,`md5`(4))
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.profile
CREATE TABLE `profile` (
  `userid` int(10) unsigned NOT NULL default '0',
  `icq` bigint(20) unsigned NOT NULL default '0',
  `yahoo` varchar(200) NOT NULL default '',
  `msn` varchar(200) NOT NULL default '',
  `aim` varchar(200) NOT NULL default '',
  `tagline` text NOT NULL,
  `ntagline` text NOT NULL,
  `likes` text NOT NULL,
  `dislikes` text NOT NULL,
  `about` text NOT NULL,
  `enablesignature` enum('y','n') NOT NULL default 'y',
  `signiture` text NOT NULL,
  `nsigniture` text NOT NULL,
  `skin` int(10) unsigned NOT NULL default '0',
  `profile` varchar(5) NOT NULL default '00000',
  `profileupdatetime` int(11) NOT NULL default '0',
  `showprofileupdatetime` enum('y','n') NOT NULL default 'y',
  `showbday` enum('y','n') NOT NULL default 'y',
  `showjointime` enum('y','n') NOT NULL default 'y',
  `showactivetime` enum('y','n') NOT NULL default 'y',
  `showpremium` enum('y','n') NOT NULL default 'y',
  `showlastblogentry` enum('y','n') NOT NULL default 'y',
  `views` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.profileblocks
CREATE TABLE `profileblocks` (
  `userid` int(10) unsigned NOT NULL default '0',
  `blockid` int(10) unsigned NOT NULL default '0',
  `blocktitle` varchar(32) NOT NULL default '',
  `blockcontent` text NOT NULL,
  `blockorder` tinyint(3) unsigned NOT NULL default '0',
  `permission` enum('anyone','loggedin','friends') NOT NULL default 'anyone',
  PRIMARY KEY  (`userid`,`blockid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.profileviews
CREATE TABLE `profileviews` (
  `userid` int(10) unsigned NOT NULL default '0',
  `viewuserid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `hits` tinyint(3) unsigned NOT NULL default '0',
  `anonymous` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`userid`,`viewuserid`),
  KEY `time` (`time`),
  KEY `userid` (`userid`,`time`),
  KEY `viewuserid` (`viewuserid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.sessions
CREATE TABLE `sessions` (
  `userid` int(10) unsigned default '0',
  `activetime` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `sessionid` char(32) NOT NULL default '',
  `cachedlogin` enum('n','y') NOT NULL default 'n',
  `lockip` enum('n','y') NOT NULL default 'n',
  `jstimezone` smallint(6) default NULL,
  KEY `userid` (`userid`,`sessionid`(2)),
  KEY `ip` (`ip`),
  KEY `activetime` (`activetime`)
) TYPE=MyISAM ROW_FORMAT=FIXED

--------------------------------------------------------

-- usersdb.sourcepics
CREATE TABLE `sourcepics` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `uploadtime` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`id`),
  KEY `uploadtime` (`uploadtime`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.stats
CREATE TABLE `stats` (
  `hitsFemale` bigint(20) unsigned NOT NULL default '0',
  `hitsMale` bigint(20) unsigned NOT NULL default '0',
  `hitsuser` bigint(20) unsigned NOT NULL default '0',
  `hitsplus` bigint(20) unsigned NOT NULL default '0',
  `hitstotal` bigint(20) unsigned NOT NULL default '0',
  `userstotal` int(10) unsigned NOT NULL default '0'
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.useractivetime
CREATE TABLE `useractivetime` (
  `userid` int(10) unsigned NOT NULL default '0',
  `activetime` int(11) NOT NULL default '0',
  `hits` int(10) unsigned NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `online` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`userid`),
  KEY `online` (`online`,`activetime`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.usercomments
CREATE TABLE `usercomments` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  `nmsg` text NOT NULL,
  PRIMARY KEY  (`userid`,`id`),
  KEY `time` (`time`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- usersdb.usercommentsarchive
CREATE TABLE `usercommentsarchive` (
  `userid` int(10) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `msg` text NOT NULL,
  KEY `userid` (`userid`),
  KEY `authorid` (`authorid`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- usersdb.usercounter
CREATE TABLE `usercounter` (
  `id` int(10) unsigned NOT NULL default '0',
  `area` tinyint(3) unsigned NOT NULL default '0',
  `max` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`,`area`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.userhitlog
CREATE TABLE `userhitlog` (
  `userid` int(10) unsigned NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `activetime` int(11) NOT NULL default '0',
  `hits` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`ip`),
  KEY `ip` (`ip`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.userinterests
CREATE TABLE `userinterests` (
  `userid` int(10) unsigned NOT NULL default '0',
  `interestid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`interestid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.usernames
CREATE TABLE `usernames` (
  `userid` int(10) unsigned NOT NULL default '0',
  `username` char(12) NOT NULL default '',
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `username` (`username`)
) TYPE=MyISAM ROW_FORMAT=FIXED

--------------------------------------------------------

-- usersdb.userpasswords
CREATE TABLE `userpasswords` (
  `userid` int(10) unsigned NOT NULL default '0',
  `password` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- usersdb.users
CREATE TABLE `users` (
  `userid` int(10) unsigned NOT NULL default '0',
  `state` enum('new','active','frozen','deleted') NOT NULL default 'new',
  `frozentime` int(11) NOT NULL default '0',
  `jointime` int(11) NOT NULL default '0',
  `activetime` int(11) NOT NULL default '0',
  `loginnum` int(10) unsigned NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `timeonline` int(10) unsigned NOT NULL default '0',
  `online` enum('n','y') NOT NULL default 'n',
  `premiumexpiry` int(11) NOT NULL default '0',
  `dob` int(11) NOT NULL default '0',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `loc` int(10) unsigned NOT NULL default '0',
  `fwmsgs` enum('y','n') NOT NULL default 'n',
  `enablecomments` enum('y','n') NOT NULL default 'y',
  `friendslistthumbs` enum('y','n') NOT NULL default 'y',
  `recentvisitlistthumbs` enum('y','n') NOT NULL default 'y',
  `recentvisitlistanon` enum('y','n') NOT NULL default 'y',
  `onlyfriends` enum('neither','msgs','comments','both') NOT NULL default 'neither',
  `ignorebyage` enum('neither','msgs','comments','both') NOT NULL default 'neither',
  `timeoffset` smallint(5) unsigned NOT NULL default '4',
  `trustjstimezone` enum('y','n') NOT NULL default 'y',
  `limitads` enum('y','n') NOT NULL default 'y',
  `single` enum('n','y') NOT NULL default 'n',
  `sexuality` tinyint(3) unsigned NOT NULL default '0',
  `spotlight` enum('y','n') NOT NULL default 'y',
  `firstpic` int(10) unsigned NOT NULL default '0',
  `signpic` enum('n','y') NOT NULL default 'n',
  `newmsgs` tinyint(3) unsigned NOT NULL default '0',
  `newcomments` tinyint(3) unsigned NOT NULL default '0',
  `showrightblocks` enum('n','y') NOT NULL default 'n',
  `threadupdates` enum('n','y') NOT NULL default 'n',
  `posts` int(10) unsigned NOT NULL default '0',
  `forumrank` varchar(24) NOT NULL default '',
  `showpostcount` enum('y','n') NOT NULL default 'y',
  `forumpostsperpage` tinyint(3) unsigned NOT NULL default '25',
  `forumsort` enum('post','thread') NOT NULL default 'post',
  `replyjump` enum('forum','thread') NOT NULL default 'forum',
  `onlysubscribedforums` enum('n','y') NOT NULL default 'n',
  `orderforumsby` enum('mostactive','mostrecent','alphabetic') NOT NULL default 'mostactive',
  `autosubscribe` enum('n','y') NOT NULL default 'n',
  `showsigs` enum('y','n') NOT NULL default 'y',
  `anonymousviews` enum('n','y','f') NOT NULL default 'n',
  `friendsauthorization` enum('n','y') NOT NULL default 'y',
  `hideprofile` enum('n','y') NOT NULL default 'n',
  `defaultminage` tinyint(3) unsigned NOT NULL default '14',
  `defaultmaxage` tinyint(3) unsigned NOT NULL default '30',
  `defaultsex` enum('Male','Female') NOT NULL default 'Male',
  `defaultloc` int(10) unsigned NOT NULL default '1',
  `gallery` enum('none','friends','loggedin','anyone') NOT NULL default 'none',
  `skin` varchar(24) NOT NULL default '',
  `commentskin` int(10) unsigned NOT NULL default '0',
  `blogskin` int(10) unsigned NOT NULL default '0',
  `friendskin` int(10) unsigned NOT NULL default '0',
  `galleryskin` int(10) unsigned NOT NULL default '0',
  `abuses` tinyint(3) unsigned NOT NULL default '0',
  `forumjumplastpost` enum('n','y') NOT NULL default 'n',
  `fileslisting` enum('private','public','loggedin','friends') NOT NULL default 'public',
  `parse_bbcode` enum('y','n') NOT NULL default 'y',
  `bbcode_editor` enum('y','n') NOT NULL default 'n',
  `filestoolbar` tinyint(1) NOT NULL default '1',
  `filesquota` int(10) unsigned NOT NULL default '10485760',
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `activetime` (`online`,`userid`),
  KEY `ip` (`ip`)
) TYPE=MyISAM ROW_FORMAT=DYNAMIC

--------------------------------------------------------

-- usersdb.usersearch
CREATE TABLE `usersearch` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `loc` int(10) unsigned NOT NULL default '0',
  `active` tinyint(3) unsigned NOT NULL default '0',
  `pic` tinyint(3) unsigned NOT NULL default '0',
  `single` tinyint(3) unsigned NOT NULL default '0',
  `sexuality` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`),
  KEY `pic` (`pic`,`sex`,`age`,`id`),
  KEY `activesexage` (`active`,`sex`,`age`,`id`),
  KEY `activepicsexage` (`active`,`pic`,`sex`,`age`,`id`),
  KEY `locsexage` (`loc`,`sex`,`age`,`id`),
  KEY `singlesexage` (`single`,`sex`,`age`,`id`),
  KEY `sexuality` (`sex`,`age`,`sexuality`,`id`),
  KEY `sexage` (`sex`,`age`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- wikidb.wikipagedata
CREATE TABLE `wikipagedata` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pageid` int(10) unsigned NOT NULL default '0',
  `revision` smallint(5) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `parent` int(10) unsigned NOT NULL default '0',
  `permdisplay` tinyint(3) unsigned NOT NULL default '0',
  `permedit` tinyint(3) unsigned NOT NULL default '0',
  `permhtml` tinyint(3) unsigned NOT NULL default '0',
  `permactive` tinyint(3) unsigned NOT NULL default '0',
  `permdelete` tinyint(3) unsigned NOT NULL default '0',
  `permcreate` tinyint(3) unsigned NOT NULL default '0',
  `allowhtml` enum('n','y') NOT NULL default 'n',
  `parsebbcode` enum('y','n') NOT NULL default 'y',
  `autonewlines` enum('y','n') NOT NULL default 'y',
  `pagewidth` smallint(5) unsigned NOT NULL default '0',
  `changedesc` varchar(255) NOT NULL default '',
  `content` text NOT NULL,
  `comment` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `pageid` (`pageid`,`revision`)
) TYPE=MyISAM

--------------------------------------------------------

-- wikidb.wikipages
CREATE TABLE `wikipages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL default '',
  `parent` int(10) unsigned NOT NULL default '0',
  `maxrev` smallint(5) unsigned NOT NULL default '0',
  `activerev` smallint(5) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `category` (`parent`,`name`),
  KEY `name` (`name`)
) TYPE=MyISAM

