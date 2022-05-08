-- banner.bannerclients
CREATE TABLE `bannerclients` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `clientname` varchar(64) NOT NULL default '',
  `type` enum('agency','local','affiliate') NOT NULL default 'agency',
  `dateadded` int(11) NOT NULL default '0',
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- banner.banners
CREATE TABLE `banners` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `clientid` int(10) unsigned NOT NULL default '0',
  `bannersize` tinyint(3) unsigned NOT NULL default '0',
  `bannertype` tinyint(3) unsigned NOT NULL default '0',
  `views` int(10) unsigned NOT NULL default '0',
  `clicks` int(10) unsigned NOT NULL default '0',
  `maxviews` int(11) NOT NULL default '0',
  `maxclicks` int(11) NOT NULL default '0',
  `viewsperday` int(11) NOT NULL default '0',
  `clicksperday` int(11) NOT NULL default '0',
  `viewsperuser` int(10) unsigned NOT NULL default '0',
  `limitbyhour` enum('y','n') NOT NULL default 'y',
  `startdate` int(11) NOT NULL default '0',
  `enddate` int(11) NOT NULL default '0',
  `payrate` smallint(5) unsigned NOT NULL default '0',
  `paytype` tinyint(3) unsigned NOT NULL default '0',
  `age` text NOT NULL,
  `sex` text NOT NULL,
  `loc` text NOT NULL,
  `page` text NOT NULL,
  `interests` text NOT NULL,
  `moded` enum('n','y') NOT NULL default 'n',
  `enabled` enum('y','n') NOT NULL default 'y',
  `title` varchar(32) NOT NULL default '',
  `image` varchar(255) NOT NULL default '',
  `link` varchar(255) NOT NULL default '',
  `alt` text NOT NULL,
  `dateadded` int(11) NOT NULL default '0',
  `lastupdatetime` int(11) NOT NULL default '0',
  `refresh` smallint(5) unsigned NOT NULL default '30',
  `passbacks` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `clientid` (`clientid`,`bannersize`)
) TYPE=MyISAM

--------------------------------------------------------

-- banner.bannersold
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

-- banner.bannerstats
CREATE TABLE `bannerstats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `bannerid` int(10) unsigned NOT NULL default '0',
  `clientid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `priority` smallint(5) unsigned NOT NULL default '0',
  `views` int(10) unsigned NOT NULL default '0',
  `clicks` int(10) unsigned NOT NULL default '0',
  `passbacks` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `bannerid` (`bannerid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- banner.bannertypestats
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

-- comments.usercomments
CREATE TABLE `usercomments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `itemid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `author` varchar(12) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `time` (`time`),
  KEY `itemid` (`itemid`,`time`),
  KEY `itemid2` (`itemid`,`time`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- comments.usercommentstext
CREATE TABLE `usercommentstext` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `time` int(11) NOT NULL default '0',
  `nmsg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `time` (`time`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- contest.contestentries
CREATE TABLE `contestentries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `contestid` int(10) unsigned NOT NULL default '0',
  `userid` int(11) NOT NULL default '0',
  `contact` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `contestid` (`contestid`,`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- contest.contests
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

-- fast.useractivetime
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

-- files.fileservers
CREATE TABLE `fileservers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `server` varchar(255) NOT NULL default '',
  `queueposition` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `server` (`server`)
) TYPE=MyISAM

--------------------------------------------------------

-- files.fileupdates
CREATE TABLE `fileupdates` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `action` enum('add','update','delete') NOT NULL default 'add',
  `file` varchar(255) NOT NULL default '',
  `server` varchar(255) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- forums.foruminvite
CREATE TABLE `foruminvite` (
  `userid` int(10) unsigned NOT NULL default '0',
  `forumid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`forumid`),
  KEY `forumid` (`forumid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forums.forummodlog
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

-- forums.forummods
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

-- forums.forummute
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

-- forums.forummutereason
CREATE TABLE `forummutereason` (
  `id` int(10) unsigned NOT NULL default '0',
  `modid` int(10) unsigned NOT NULL default '0',
  `reason` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- forums.forumposts
CREATE TABLE `forumposts` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `threadid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `author` varchar(12) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `edit` int(11) NOT NULL default '0',
  `msg` text NOT NULL,
  `nmsg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `time` (`time`),
  KEY `authorid` (`authorid`),
  KEY `threadid` (`threadid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- forums.forumpostsdel
CREATE TABLE `forumpostsdel` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `threadid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `author` varchar(12) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `edit` int(11) NOT NULL default '0',
  `msg` text NOT NULL,
  `nmsg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `threadid` (`threadid`),
  KEY `time` (`time`),
  KEY `authorid` (`authorid`)
) TYPE=MyISAM

--------------------------------------------------------

-- forums.forumranks
CREATE TABLE `forumranks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `postmax` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- forums.forumread
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

-- forums.forums
CREATE TABLE `forums` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `official` enum('y','n') NOT NULL default 'y',
  `name` varchar(32) NOT NULL default '',
  `description` varchar(255) NOT NULL default '',
  `threads` int(10) unsigned NOT NULL default '0',
  `posts` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `autolock` int(10) unsigned NOT NULL default '0',
  `edit` enum('n','y') NOT NULL default 'n',
  `ownerid` int(10) unsigned NOT NULL default '0',
  `public` enum('n','y') NOT NULL default 'n',
  `mute` enum('n','y') NOT NULL default 'n',
  `sorttime` int(10) unsigned NOT NULL default '14',
  `rules` text NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `time` (`time`),
  KEY `official` (`official`,`public`,`posts`)
) TYPE=MyISAM

--------------------------------------------------------

-- forums.forumthreads
CREATE TABLE `forumthreads` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `forumid` int(10) unsigned NOT NULL default '0',
  `moved` int(10) unsigned NOT NULL default '0',
  `title` varchar(64) NOT NULL default '',
  `authorid` int(10) unsigned NOT NULL default '0',
  `author` varchar(12) NOT NULL default '',
  `reads` int(10) unsigned NOT NULL default '0',
  `posts` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `lastauthorid` int(10) unsigned NOT NULL default '0',
  `lastauthor` varchar(12) NOT NULL default '',
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

-- forums.forumthreadsdel
CREATE TABLE `forumthreadsdel` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `forumid` int(10) unsigned NOT NULL default '0',
  `moved` int(10) unsigned NOT NULL default '0',
  `title` varchar(64) NOT NULL default '',
  `authorid` int(10) unsigned NOT NULL default '0',
  `author` varchar(12) NOT NULL default '',
  `reads` int(10) unsigned NOT NULL default '0',
  `posts` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `lastauthorid` int(10) unsigned NOT NULL default '0',
  `lastauthor` varchar(12) NOT NULL default '',
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

-- forums.forumupdated
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

-- logs.iplog
CREATE TABLE `iplog` (
  `ip` int(11) NOT NULL default '0',
  `hits` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ip`)
) TYPE=MyISAM

--------------------------------------------------------

-- logs.loginlog
CREATE TABLE `loginlog` (
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `result` enum('success','badpass','frozen','unactivated') NOT NULL default 'success',
  KEY `userid` (`userid`),
  KEY `ip` (`ip`)
) TYPE=MyISAM

--------------------------------------------------------

-- logs.userhitlog
CREATE TABLE `userhitlog` (
  `userid` int(10) unsigned NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `activetime` int(11) NOT NULL default '0',
  `hits` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`ip`),
  KEY `ip` (`ip`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.abuse
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

-- main.agegroups
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

-- main.agesexgroups
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
  `picsvotable` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`age`,`sex`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.articles
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
  PRIMARY KEY  (`id`),
  KEY `moded` (`moded`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.bannedusers
CREATE TABLE `bannedusers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `ip` int(11) NOT NULL default '0',
  `type` enum('ip','email') NOT NULL default 'ip',
  PRIMARY KEY  (`id`),
  KEY `val` (`email`),
  KEY `ip` (`ip`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.bannedwords
CREATE TABLE `bannedwords` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `word` char(32) NOT NULL default '',
  `type` enum('word','part','name') NOT NULL default 'word',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `type` (`type`,`word`(8))
) TYPE=MyISAM

--------------------------------------------------------

-- main.bday
CREATE TABLE `bday` (
  `userid` int(10) unsigned NOT NULL default '0',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  PRIMARY KEY  (`sex`,`age`,`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.blocks
CREATE TABLE `blocks` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `priority` int(10) unsigned NOT NULL default '0',
  `side` enum('l','r') NOT NULL default 'l',
  `funcname` char(32) NOT NULL default '',
  `enabled` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.cats
CREATE TABLE `cats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.comments
CREATE TABLE `comments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `itemid` int(10) unsigned NOT NULL default '0',
  `authorid` int(10) unsigned NOT NULL default '0',
  `author` varchar(12) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `itemid` (`itemid`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.commentstext
CREATE TABLE `commentstext` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `msg` text NOT NULL,
  `nmsg` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.config
CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL default '',
  `value` varchar(255) NOT NULL default '',
  `comments` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.deletedusers
CREATE TABLE `deletedusers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `jointime` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `email` varchar(255) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `reason` text NOT NULL,
  `deleteid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `email` (`email`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.faq
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

-- main.faqcats
CREATE TABLE `faqcats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.files
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

-- main.forumrankspending
CREATE TABLE `forumrankspending` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `forumrank` varchar(18) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.friends
CREATE TABLE `friends` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `friendid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`,`friendid`),
  KEY `friendid` (`friendid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.friendscomments
CREATE TABLE `friendscomments` (
  `id` int(10) unsigned NOT NULL default '0',
  `comment` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.gallery
CREATE TABLE `gallery` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `category` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `description` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `usercat` (`userid`,`category`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.gallerycats
CREATE TABLE `gallerycats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `permission` enum('anyone','loggedin','friends') NOT NULL default 'anyone',
  `firstpicture` int(10) unsigned NOT NULL default '0',
  `description` varchar(255) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.hithist
CREATE TABLE `hithist` (
  `time` int(11) NOT NULL default '0',
  `hits` int(10) unsigned NOT NULL default '0',
  `total` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.ignore
CREATE TABLE `ignore` (
  `userid` int(10) unsigned NOT NULL default '0',
  `ignoreid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`ignoreid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.interests
CREATE TABLE `interests` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(64) NOT NULL default '',
  `users` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.locs
CREATE TABLE `locs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `users` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.mirrors
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

-- main.modvoteslog
CREATE TABLE `modvoteslog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `modid` int(10) unsigned NOT NULL default '0',
  `picid` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `vote` enum('y','n') NOT NULL default 'y',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `modid` (`modid`),
  KEY `picid` (`picid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.newestprofile
CREATE TABLE `newestprofile` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `age` tinyint(4) NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`),
  UNIQUE KEY `age` (`sex`,`age`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.newestusers
CREATE TABLE `newestusers` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `age` tinyint(4) NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`),
  UNIQUE KEY `age` (`sex`,`age`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.news
CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` enum('both','inside','outside') NOT NULL default 'both',
  `userid` int(10) unsigned default NULL,
  `title` varchar(255) binary NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `text` text NOT NULL,
  `ntext` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `typedate` (`type`,`date`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.picbans
CREATE TABLE `picbans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `md5` char(32) NOT NULL default '',
  `times` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `bans` (`md5`(8),`userid`)
) TYPE=MyISAM ROW_FORMAT=FIXED

--------------------------------------------------------

-- main.pics
CREATE TABLE `pics` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `itemid` int(10) unsigned NOT NULL default '0',
  `priority` tinyint(3) unsigned NOT NULL default '1',
  `vote` enum('y','n') NOT NULL default 'y',
  `description` varchar(64) NOT NULL default '',
  `score` double NOT NULL default '0',
  `votes` smallint(5) unsigned NOT NULL default '0',
  `v1` smallint(5) unsigned NOT NULL default '0',
  `v2` smallint(5) unsigned NOT NULL default '0',
  `v3` smallint(5) unsigned NOT NULL default '0',
  `v4` smallint(5) unsigned NOT NULL default '0',
  `v5` smallint(5) unsigned NOT NULL default '0',
  `v6` smallint(5) unsigned NOT NULL default '0',
  `v7` smallint(5) unsigned NOT NULL default '0',
  `v8` smallint(5) unsigned NOT NULL default '0',
  `v9` smallint(5) unsigned NOT NULL default '0',
  `v10` smallint(5) unsigned NOT NULL default '0',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `signpic` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `sexage` (`sex`,`age`,`id`),
  KEY `itemid` (`itemid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.picspending
CREATE TABLE `picspending` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `itemid` int(10) unsigned NOT NULL default '0',
  `vote` enum('y','n') NOT NULL default 'y',
  `description` varchar(64) NOT NULL default '',
  `md5` varchar(32) NOT NULL default '',
  `signpic` enum('n','y') NOT NULL default 'n',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`itemid`,`md5`(4))
) TYPE=MyISAM

--------------------------------------------------------

-- main.picstop
CREATE TABLE `picstop` (
  `id` int(10) unsigned NOT NULL default '0',
  `itemid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `score` decimal(2,1) NOT NULL default '0.0',
  PRIMARY KEY  (`id`),
  KEY `sex` (`sex`,`age`,`score`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.picsvotable
CREATE TABLE `picsvotable` (
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `id` int(10) unsigned NOT NULL auto_increment,
  `picid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`sex`,`age`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.police
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

-- main.premiumlog
CREATE TABLE `premiumlog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `duration` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.profileskins
CREATE TABLE `profileskins` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  `data` varchar(66) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`name`(2))
) TYPE=MyISAM

--------------------------------------------------------

-- main.profiletext
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

-- main.searchqueries
CREATE TABLE `searchqueries` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `time` int(11) NOT NULL default '0',
  `sort` text NOT NULL,
  `query` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.smilies
CREATE TABLE `smilies` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `code` varchar(16) NOT NULL default '',
  `pic` varchar(16) NOT NULL default '',
  `uses` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.spotlight
CREATE TABLE `spotlight` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `username` varchar(12) NOT NULL default '',
  `pic` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.staticpages
CREATE TABLE `staticpages` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(16) NOT NULL default '',
  `title` varchar(64) NOT NULL default '',
  `restricted` enum('n','y') NOT NULL default 'n',
  `content` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.todo
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

-- main.userclusters
CREATE TABLE `userclusters` (
  `id` int(10) unsigned NOT NULL default '0',
  `start` int(10) unsigned NOT NULL default '0',
  `end` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.userinterests
CREATE TABLE `userinterests` (
  `userid` int(10) unsigned NOT NULL default '0',
  `interestid` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`interestid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.usernotify
CREATE TABLE `usernotify` (
  `usernotifyid` int(10) unsigned NOT NULL auto_increment,
  `creatorid` int(10) unsigned NOT NULL default '0',
  `creatorname` varchar(12) NOT NULL default '',
  `createtime` int(11) NOT NULL default '0',
  `targetid` int(10) unsigned NOT NULL default '0',
  `triggertime` int(11) NOT NULL default '0',
  `subject` varchar(250) NOT NULL default '',
  `message` text NOT NULL,
  PRIMARY KEY  (`usernotifyid`),
  KEY `targetid` (`targetid`,`triggertime`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.users
CREATE TABLE `users` (
  `userid` int(10) unsigned NOT NULL auto_increment,
  `username` varchar(12) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `frozen` enum('n','y') NOT NULL default 'n',
  `activated` enum('n','y') NOT NULL default 'n',
  `activatekey` varchar(32) NOT NULL default '',
  `jointime` int(11) NOT NULL default '0',
  `activetime` int(11) NOT NULL default '0',
  `loginnum` int(10) unsigned NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `hits` int(10) unsigned NOT NULL default '0',
  `timeonline` int(10) unsigned NOT NULL default '0',
  `online` enum('n','y') NOT NULL default 'n',
  `premiumexpiry` int(11) NOT NULL default '0',
  `dob` int(11) NOT NULL default '0',
  `age` tinyint(3) unsigned NOT NULL default '0',
  `showbday` enum('y','n') NOT NULL default 'y',
  `sex` enum('Male','Female') NOT NULL default 'Male',
  `loc` int(10) unsigned NOT NULL default '0',
  `email` varchar(100) NOT NULL default '',
  `showemail` enum('y','n') NOT NULL default 'n',
  `fwmsgs` enum('y','n') NOT NULL default 'n',
  `enablecomments` enum('y','n') NOT NULL default 'y',
  `showactivetime` enum('y','n') NOT NULL default 'y',
  `showjointime` enum('y','n') NOT NULL default 'y',
  `friendslistthumbs` enum('y','n') NOT NULL default 'y',
  `onlyfriends` enum('neither','msgs','comments','both') NOT NULL default 'neither',
  `ignorebyage` enum('neither','msgs','comments','both') NOT NULL default 'neither',
  `timeoffset` smallint(5) unsigned NOT NULL default '4',
  `limitads` enum('y','n') NOT NULL default 'y',
  `profile` varchar(5) NOT NULL default '00000',
  `single` enum('n','y') NOT NULL default 'n',
  `sexuality` tinyint(3) unsigned NOT NULL default '0',
  `showpremium` enum('y','n') NOT NULL default 'y',
  `spotlight` enum('y','n') NOT NULL default 'y',
  `firstpic` int(10) unsigned NOT NULL default '0',
  `signpic` enum('n','y') NOT NULL default 'n',
  `profileupdatetime` int(11) NOT NULL default '0',
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
  `autosubscribe` enum('n','y') NOT NULL default 'n',
  `showsigs` enum('y','n') NOT NULL default 'y',
  `anonymousviews` enum('n','y') NOT NULL default 'n',
  `friendsauthorization` enum('n','y') NOT NULL default 'y',
  `hideprofile` enum('n','y') NOT NULL default 'n',
  `views` mediumint(8) unsigned NOT NULL default '0',
  `defaultminage` tinyint(3) unsigned NOT NULL default '14',
  `defaultmaxage` tinyint(3) unsigned NOT NULL default '30',
  `defaultsex` enum('Male','Female') NOT NULL default 'Male',
  `journalentries` tinyint(3) unsigned NOT NULL default '0',
  `gallery` enum('none','friends','loggedin','anyone') NOT NULL default 'none',
  `skin` varchar(12) NOT NULL default '',
  `abuses` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `loc` (`loc`,`userid`),
  UNIQUE KEY `activetime` (`online`,`userid`),
  UNIQUE KEY `sexage` (`sex`,`age`,`single`,`sexuality`,`signpic`,`userid`)
) TYPE=MyISAM ROW_FORMAT=DYNAMIC

--------------------------------------------------------

-- main.usersearch
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
  KEY `sexage` (`sex`,`age`,`id`),
  KEY `pic` (`pic`,`sex`,`age`,`id`),
  KEY `activesexage` (`active`,`sex`,`age`,`id`),
  KEY `activepicsexage` (`active`,`pic`,`sex`,`age`,`id`),
  KEY `locsexage` (`loc`,`sex`,`age`,`id`),
  KEY `singlesexage` (`single`,`sex`,`age`,`id`),
  KEY `sexuality` (`sex`,`age`,`sexuality`,`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.votehist
CREATE TABLE `votehist` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `picid` int(10) unsigned NOT NULL default '0',
  `vote` tinyint(3) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `blocked` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`,`picid`),
  KEY `votetime` (`userid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.wordmatch
CREATE TABLE `wordmatch` (
  `wordid` int(10) unsigned NOT NULL default '0',
  `postid` int(10) unsigned NOT NULL default '0',
  `title` enum('n','y') NOT NULL default 'n',
  KEY `wordid` (`wordid`)
) TYPE=MyISAM

--------------------------------------------------------

-- main.words
CREATE TABLE `words` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `word` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `word` (`word`)
) TYPE=MyISAM ROW_FORMAT=DYNAMIC

--------------------------------------------------------

-- mods.abuselog
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

-- mods.abuselogcomments
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

-- mods.admin
CREATE TABLE `admin` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `visible` enum('y','n') NOT NULL default 'y',
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
  PRIMARY KEY  (`id`),
  UNIQUE KEY `userid` (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- mods.adminlog
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

-- mods.modhist
CREATE TABLE `modhist` (
  `dumptime` int(11) NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
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

-- mods.moditems
CREATE TABLE `moditems` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `type` tinyint(3) unsigned NOT NULL default '0',
  `itemid` int(10) unsigned NOT NULL default '0',
  `priority` enum('n','y') NOT NULL default 'n',
  `points` tinyint(4) NOT NULL default '0',
  `lock` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `item` (`type`,`itemid`),
  KEY `type` (`type`,`lock`,`itemid`)
) TYPE=MyISAM

--------------------------------------------------------

-- mods.mods
CREATE TABLE `mods` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `type` tinyint(3) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
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

-- mods.modvotes
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

-- mods.underage
CREATE TABLE `underage` (
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `lastchecked` int(11) NOT NULL default '0',
  `numtimes` int(10) unsigned NOT NULL default '0',
  `confirmed` enum('n','y') NOT NULL default 'n',
  `deleted` int(11) NOT NULL default '0',
  PRIMARY KEY  (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- msgs.msgarchive
CREATE TABLE `msgarchive` (
  `id` int(10) unsigned NOT NULL default '0',
  `to` int(10) unsigned NOT NULL default '0',
  `toname` varchar(12) NOT NULL default '',
  `from` int(10) unsigned NOT NULL default '0',
  `fromname` varchar(12) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `subject` varchar(64) NOT NULL default '',
  `msg` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- msgs.msgfolder
CREATE TABLE `msgfolder` (
  `id` int(11) NOT NULL auto_increment,
  `userid` int(11) NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`name`(2))
) TYPE=MyISAM

--------------------------------------------------------

-- msgs.msgs
CREATE TABLE `msgs` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `otheruserid` int(10) unsigned NOT NULL default '0',
  `folder` int(10) unsigned NOT NULL default '0',
  `to` int(10) unsigned NOT NULL default '0',
  `toname` char(12) NOT NULL default '',
  `from` int(10) unsigned NOT NULL default '0',
  `fromname` char(12) NOT NULL default '',
  `date` int(11) NOT NULL default '0',
  `mark` enum('n','y') NOT NULL default 'n',
  `status` enum('new','read','replied') NOT NULL default 'new',
  `othermsgid` int(10) unsigned NOT NULL default '0',
  `replyto` int(10) unsigned NOT NULL default '0',
  `msgtextid` int(10) unsigned NOT NULL default '0',
  `subject` char(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`folder`,`date`),
  KEY `date` (`date`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50 ROW_FORMAT=FIXED

--------------------------------------------------------

-- msgs.msgtext
CREATE TABLE `msgtext` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `date` int(11) NOT NULL default '0',
  `compressed` enum('n','y') NOT NULL default 'n',
  `html` enum('n','y') NOT NULL default 'n',
  `hash` varchar(16) binary NOT NULL default '',
  `msg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `hash` (`hash`(4)),
  KEY `date` (`date`)
) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50

--------------------------------------------------------

-- poll.pollans
CREATE TABLE `pollans` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `pollid` int(10) unsigned NOT NULL default '0',
  `answer` varchar(255) NOT NULL default '',
  `votes` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `pollid` (`pollid`)
) TYPE=MyISAM

--------------------------------------------------------

-- poll.polls
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

-- poll.pollvotes
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

-- profile.profile
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
  PRIMARY KEY  (`userid`)
) TYPE=MyISAM

--------------------------------------------------------

-- profviews.profileviews
CREATE TABLE `profileviews` (
  `userid` int(10) unsigned NOT NULL default '0',
  `viewuserid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `hits` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`,`viewuserid`),
  KEY `time` (`time`),
  KEY `userid` (`userid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- session.sessions
CREATE TABLE `sessions` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned default '0',
  `activetime` int(11) NOT NULL default '0',
  `ip` int(11) NOT NULL default '0',
  `sessionid` char(32) NOT NULL default '',
  `cachedlogin` enum('n','y') NOT NULL default 'n',
  `lockip` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`sessionid`(2)),
  KEY `ip` (`ip`),
  KEY `activetime` (`activetime`)
) TYPE=MyISAM ROW_FORMAT=FIXED

--------------------------------------------------------

-- shop.invoice
CREATE TABLE `invoice` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
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

-- shop.invoiceitems
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

-- shop.paygbatches
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

-- shop.paygcards
CREATE TABLE `paygcards` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `secret` varchar(32) NOT NULL default '',
  `batchid` int(10) unsigned NOT NULL default '0',
  `usedate` int(11) NOT NULL default '0',
  `value` decimal(6,2) NOT NULL default '0.00',
  `invoiceid` int(10) unsigned NOT NULL default '0',
  `valid` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `secret` (`secret`)
) TYPE=MyISAM

--------------------------------------------------------

-- shop.paygstores
CREATE TABLE `paygstores` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) NOT NULL default '',
  `address` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- shop.productcats
CREATE TABLE `productcats` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `parent` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `name` varchar(24) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- shop.productinputchoices
CREATE TABLE `productinputchoices` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `productid` int(10) unsigned NOT NULL default '0',
  `name` varchar(32) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `productid` (`productid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shop.productpics
CREATE TABLE `productpics` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `productid` int(10) unsigned NOT NULL default '0',
  `priority` int(10) unsigned NOT NULL default '0',
  `description` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `productid` (`productid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shop.productprices
CREATE TABLE `productprices` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `productid` int(10) unsigned NOT NULL default '0',
  `minimum` tinyint(3) unsigned NOT NULL default '0',
  `price` decimal(8,4) unsigned NOT NULL default '0.0000',
  PRIMARY KEY  (`id`),
  KEY `productid` (`productid`)
) TYPE=MyISAM

--------------------------------------------------------

-- shop.products
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

-- shop.producttext
CREATE TABLE `producttext` (
  `id` int(10) unsigned NOT NULL default '0',
  `summary` text NOT NULL,
  `description` text NOT NULL,
  `ndescription` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

--------------------------------------------------------

-- shop.shoppingcart
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

-- stats.stats
CREATE TABLE `stats` (
  `hitsFemale` int(10) unsigned NOT NULL default '0',
  `hitsMale` int(10) unsigned NOT NULL default '0',
  `hitsuser` int(10) unsigned NOT NULL default '0',
  `hitsanon` int(10) unsigned NOT NULL default '0',
  `hitstotal` int(10) unsigned NOT NULL default '0',
  `hitsmaxday` int(10) unsigned NOT NULL default '0',
  `hitsmaxhour` int(10) unsigned NOT NULL default '0',
  `ipsmaxday` int(10) unsigned NOT NULL default '0',
  `ipsmaxhour` int(10) unsigned NOT NULL default '0',
  `onlineusers` int(10) unsigned NOT NULL default '0',
  `onlineguests` int(10) unsigned NOT NULL default '0',
  `onlineusersmax` int(10) unsigned NOT NULL default '0',
  `onlineguestsmax` int(10) unsigned NOT NULL default '0',
  `userstotal` int(10) unsigned NOT NULL default '0',
  `userswithpics` int(10) unsigned NOT NULL default '0',
  `userswithsignpics` int(10) unsigned NOT NULL default '0'
) TYPE=MyISAM

--------------------------------------------------------

-- stats.statshist
CREATE TABLE `statshist` (
  `time` int(11) NOT NULL default '0',
  `hitsFemale` int(10) unsigned NOT NULL default '0',
  `hitsMale` int(10) unsigned NOT NULL default '0',
  `hitsuser` int(10) unsigned NOT NULL default '0',
  `hitsanon` int(10) unsigned NOT NULL default '0',
  `hitstotal` int(10) unsigned NOT NULL default '0',
  `hitsmaxday` int(10) unsigned NOT NULL default '0',
  `hitsmaxhour` int(10) unsigned NOT NULL default '0',
  `ipsmaxday` int(10) unsigned NOT NULL default '0',
  `ipsmaxhour` int(10) unsigned NOT NULL default '0',
  `onlineusers` int(10) unsigned NOT NULL default '0',
  `onlineguests` int(10) unsigned NOT NULL default '0',
  `onlineusersmax` int(10) unsigned NOT NULL default '0',
  `onlineguestsmax` int(10) unsigned NOT NULL default '0',
  `userstotal` int(10) unsigned NOT NULL default '0',
  `userswithpics` int(10) unsigned NOT NULL default '0',
  `userswithsignpics` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- weblog.blog
CREATE TABLE `blog` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `userid` int(10) unsigned NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `title` varchar(255) NOT NULL default '',
  `scope` tinyint(3) unsigned NOT NULL default '1',
  `comments` smallint(5) unsigned NOT NULL default '0',
  `allowcomments` enum('y','n') NOT NULL default 'y',
  PRIMARY KEY  (`id`),
  KEY `userid` (`userid`,`time`)
) TYPE=MyISAM

--------------------------------------------------------

-- weblog.blogcomments
CREATE TABLE `blogcomments` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `blogid` int(10) unsigned NOT NULL default '0',
  `userid` int(10) unsigned NOT NULL default '0',
  `username` varchar(12) NOT NULL default '',
  `time` int(11) NOT NULL default '0',
  `msg` text NOT NULL,
  `nmsg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `blogid` (`blogid`)
) TYPE=MyISAM

--------------------------------------------------------

-- weblog.blogsubscriptions
CREATE TABLE `blogsubscriptions` (
  `userid` int(10) unsigned NOT NULL default '0',
  `blogid` int(10) unsigned NOT NULL default '0',
  `new` enum('n','y') NOT NULL default 'n',
  PRIMARY KEY  (`userid`,`blogid`),
  KEY `blogid` (`blogid`)
) TYPE=MyISAM

--------------------------------------------------------

-- weblog.blogtext
CREATE TABLE `blogtext` (
  `id` int(10) unsigned NOT NULL default '0',
  `msg` text NOT NULL,
  `nmsg` text NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM

