<?php

// three types of server:
/// - single: identifies a single, nonreplicated, server. Has only one level of options
/// - multi: a server with a secondary replication database. 'roles' subitem
///              indicates handlers for select and insert (and backup?). Those subitems
///              by default use any configuration item at the top level, and then
///              they can overload items selectively.
/// - split: multiple servers load balanced based on a split function identified by the
///         'splitfunc' key. 'sources' top level item is a list of configurations that are as if
///         they were top level server configs.
// inheritance:
/// An 'inherit' option at any level brings all config options of the named server
/// config to the current level of the current server config, recursively. It's no more
/// clever than that.
// instances:
/// A server config with 'instance' => true is one that should be constructed into an object
/// for the php code to use.

$databaselib = 'pdo';

$databases = array(
	'pgdbserv' => array(
		'host' => '192.168.0.50',
		'login' => 'graham',
		'passwd' => 'Hawaii',
		'engine' => 'pgsql',
	),
	'pgdevdb' => array(
		'inherit' => 'pgdbserv',
		'type' => 'single'
	),
	'pgdevdbmulti' => array(
		'inherit' => 'pgdbserv',
		'type' => 'multi',
		'roles' => array(
			'insert' => array('debug' => 2),
			'select' => array(
				array('debug' => 2, 'weight' => 1, 'plus' => 'n'),
				array('weight' => 1, 'plus' => 'y'),
			),
			'backup' => array('debug' => 2)
		)
	),
	'dbserv' => array(
		'host' => '192.168.0.50',
		'login' => 'root',
		'passwd' => 'Hawaii'
	),
	'devdb' => array(
		'inherit' => 'dbserv',
		'type' => 'single'
	),
	'devdbmulti' => array(
		'inherit' => 'dbserv',
		'type' => 'multi',
		'roles' => array(
			'insert' => array('debug' => 2),
			'select' => array(
				array('debug' => 2, 'weight' => 1, 'plus' => 'n'),
				array('weight' => 1, 'plus' => 'y'),
			),
			'backup' => array('debug' => 2)
		)
	),

	'usersdb' => array(
		'instance' => true,
		'db' => 'nexopiausers',
		'inherit' => 'pgdevdbmulti',
	),

	'db' => array(
		'db' => 'nexopia',
		'inherit' => 'pgdevdbmulti', // police table breaks converter
		'instance' => true,
	),

	'fastdb' => array(
		'db' => 'nexopiafast',
		'inherit' => 'pgdevdbmulti',
		'instance' => true,
	),

	'hashlogdb' => array(
		'type' => 'split',
		'instance' => true,
		'splitfunc' => 'split_db_hash',
		'sources' => array(
			array('type' => 'single', 'inherit' => 'pgdevdb', 'db' => 'nexopialogs1'),
			array('type' => 'single', 'inherit' => 'pgdevdb', 'db' => 'nexopialogs2'),
		)
	),
	'logdb' => array(
		'instance' => true,
		'db' => 'nexopialogs',
		'inherit' => 'pgdevdb',
	),
	'sessiondb' => array(
		'instance' => true,
		'db' => 'nexopiasession',
		'inherit' => 'pgdevdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'moddb' => array(
		'instance' => true,
		'db' => 'nexopiamods',
		'inherit' => 'pgdevdb'
	),
	'archivedb' => array(
		'instance' => true,
		'db' => 'nexopiaarchive',
		'inherit' => 'pgdevdb',
	),
	'msgsdb' => array(
		'instance' => true,
		'db' => 'nexopiamsgs',
		'inherit' => 'pgdevdb',
		'debug' => 2
	),
	'msgbenchdb' => array(
		'instance' => true,
		'db' => 'nexopiamsgbench',
		'inherit' => 'pgdevdb',
		'debug' => 2,
		'seqtable' => 'usercounter',
	),
	'commentsdb' => array(
		'instance' => true,
		'db' => 'nexopiausercomments',
		'inherit' => 'pgdevdb',
		'debug' => 2,
	),
	'polldb' => array(
		'instance' => true,
		'db' => 'nexopiapolls',
		'inherit' => 'pgdevdb',
	),
	'shopdb' => array(
		'instance' => true,
		'db' => 'nexopiashop',
		'inherit' => 'pgdevdb',
	),
	'filesdb' => array(
		'instance' => true,
		'db' => 'nexopiafileupdates',
		'inherit' => 'pgdevdb',
	),
	'bannerdb' => array(
		'instance' => true,
		'db' => 'nexopiabanners',
		'inherit' => 'pgdevdb',
	),
	'contestdb' => array(
		'instance' => true,
		'db' => 'nexopiacontest',
		'inherit' => 'pgdevdb',
	),
	'weblogdb' => array(
		'instance' => true,
		'db' => 'nexopiablog',
		'inherit' => 'pgdevdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'forumdb' => array(
		'instance' => true,
		'db' => 'nexopiaforum',
		'inherit' => 'pgdevdb', // warning: autolock column may have legitimate reason to be huge?
		'needkey' => DB_KEY_FORBIDDEN
	),
	'statsdb' => array(
		'instance' => true,
		'db' => 'nexopiastats',
		'inherit' => 'pgdevdb',
	),
	'profviewsdb' => array(
		'instance' => true,
		'db' => 'nexopiaprofviews',
		'inherit' => 'pgdevdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'profiledb' => array(
		'instance' => true,
		'db' => 'nexopiaprofile',
		'inherit' => 'pgdevdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'articlesdb' => array(
		'instance' => true,
		'db' => 'nexopiaarticles',
		'inherit' => 'pgdevdb',
	),
	'gallerydb' => array(
		'instance' => true,
		'db' => 'nexopiagallery',
		'inherit' => 'pgdevdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'friendsdb' => array(
		'instance' => true,
		'db' => 'nexopiafriends',
		'inherit' => 'pgdevdb',
	),
	'picsdb' => array(
		'instance' => true,
		'db' => 'nexopiapics',
		'inherit' => 'pgdevdb',
	),
	'wikidb' => array(
		'instance' => true,
		'db' => 'nexopiawiki',
		'inherit' => 'pgdevdb',
	),
	'picmodexamdb' => array(
		'instance' => true,
		'db' => 'nexopiapicmodexam',
		'inherit' => 'pgdevdb',
	),
);
