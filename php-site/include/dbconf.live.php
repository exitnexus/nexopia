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

$databases = array(
	'dbserv' => array(
		'login' => 'nexopia',
		'passwd' => 'CierlU$I',
	),
	'devdb' => array(
		'inherit' => 'dbserv',
		'type' => 'single'
	),
	'devdbmulti' => array(
		'inherit' => 'dbserv',
		'type' => 'multi',
		'roles' => array(
			'insert' => array(),
			'select' => array(
				array('weight' => 1, 'plus' => 'n'),
				array('weight' => 1, 'plus' => 'y'),
			),
			'backup' => array()
		)
	),

	'general' => array(
		'host' => '10.0.2.3',
		'inherit' => 'devdbmulti',
	),

	/* new dbs */

	'masterdb' => array(
		'instance' => true,
		'db' => 'newmaster',
		'inherit' => 'general',
	),

	'usersdb' => array(
		'instance' => true,
		'type' => 'split',
		'splitfunc' => 'split_db_user',
		'sources' => array(
			array( // anonymous server
				'db' => 'newusersanon',
				'host' => '10.0.2.3',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
			),
			array(
				'db' => 'newusers1',
				'host' => '10.0.2.1',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
//				'needkey' => DB_KEY_REQUIRED,
			),
			array(
				'db' => 'newusers2',
				'host' => '10.0.2.2',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
//				'needkey' => DB_KEY_REQUIRED,
			),
			array(
				'db' => 'newusers3',
				'host' => '10.0.2.41',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
//				'needkey' => DB_KEY_REQUIRED,
			),
			array(
				'db' => 'newusers4',
				'host' => '10.0.2.42',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
//				'needkey' => DB_KEY_REQUIRED,
			),
		),
	),

	'configdb' => array(
		'instance' => true,
		'db' => 'newconfig',
		'inherit' => 'general',
	),

	'db' => array(
		'db' => 'newgeneral',
		'inherit' => 'general',
		'instance' => true,
	),

	'moddb' => array(
		'instance' => true,
		'db' => 'newmods',
		'inherit' => 'general'
	),

	/* old dbs */

	'polldb' => array(
		'instance' => true,
		'db' => 'newpolls',
		'inherit' => 'general',
	),
	'shopdb' => array(
		'instance' => true,
		'db' => 'newshop',
		'inherit' => 'general',
	),
	'filesdb' => array(
		'instance' => true,
		'db' => 'newfileupdates',
		'inherit' => 'general',
	),
	'bannerdb' => array(
		'instance' => true,
		'db' => 'newbanners',
		'inherit' => 'general',
		'debuglevel' => 0,
	),
	'contestdb' => array(
		'instance' => true,
		'db' => 'newcontest',
		'inherit' => 'general',
	),
	'forumdb' => array(
		'instance' => true,
		'db' => 'newforum',
		'host' => '10.0.2.6',
		'inherit' => 'devdb',
//		'needkey' => DB_KEY_FORBIDDEN
	),
	'articlesdb' => array(
		'instance' => true,
		'db' => 'newarticles',
		'inherit' => 'general',
	),
	'wikidb' => array(
		'instance' => true,
		'db' => 'newwiki',
		'inherit' => 'general',
	),
	'picmodexamdb' => array(
		'instance' => true,
		'db' => 'newpicmodexam',
		'inherit' => 'general',
	),
);
