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
		'host' => '192.168.0.50',
		'login' => 'root',
		'passwd' => 'Hawaii',
		'debuglevel' => 2,
	),
	'devdb' => array(
		'inherit' => 'dbserv',
		'type' => 'single',
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

	/* new dbs */

	'masterdb' => array(
		'instance' => true,
		'db' => 'newmaster',
		'inherit' => 'devdbmulti',
	),

	'usersdb' => array(
		'instance' => true,
		'type' => 'split',
		'splitfunc' => 'split_db_user',
		'sources' => array(
			array( // anonymous server
				'db' => 'newusersanon',
				'inherit' => 'devdbmulti',
				'seqtable' => 'usercounter',
			),
			array(
				'db' => 'newusers',
				'inherit' => 'devdbmulti',
				'seqtable' => 'usercounter',
//				'needkey' => DB_KEY_REQUIRED,
			),
			array(
				'db' => 'newusers1',
				'inherit' => 'devdbmulti',
				'seqtable' => 'usercounter',
			),
		),
	),

	'configdb' => array(
		'instance' => true,
		'db' => 'newconfig',
		'inherit' => 'devdb',
	),

	'db' => array(
		'db' => 'newgeneral',
		'inherit' => 'devdbmulti',
		'instance' => true,
	),

	'moddb' => array(
		'instance' => true,
		'db' => 'newmods',
		'inherit' => 'devdb'
	),

	/* old dbs */

	'polldb' => array(
		'instance' => true,
		'db' => 'nexopiapolls',
		'inherit' => 'devdb',
	),
	'shopdb' => array(
		'instance' => true,
		'db' => 'nexopiashop',
		'inherit' => 'devdb',
	),
	'filesdb' => array(
		'instance' => true,
		'db' => 'nexopiafileupdates',
		'inherit' => 'devdb',
	),
	'bannerdb' => array(
		'instance' => true,
		'db' => 'nexopiabanners',
		'inherit' => 'devdb',
		/*/
		'instance' => true,
		'db' => 'banner',
		'type' => 'single',
		'host' => '192.168.0.50:3307',
		'login' => 'nathan',
		'passwd' => 'nathan',
		//*/
	),
	'contestdb' => array(
		'instance' => true,
		'db' => 'nexopiacontest',
		'inherit' => 'devdb',
	),
	'forumdb' => array(
		'instance' => true,
		'db' => 'nexopiaforum',
		'inherit' => 'devdb',
//		'needkey' => DB_KEY_FORBIDDEN
	),
	'articlesdb' => array(
		'instance' => true,
		'db' => 'nexopiaarticles',
		'inherit' => 'devdb',
	),
	'wikidb' => array(
		'instance' => true,
		'db' => 'nexopiawiki',
		'inherit' => 'devdb',
	),
	'picmodexamdb' => array(
		'instance' => true,
		'db' => 'nexopiapicmodexam',
		'inherit' => 'devdb',
	),
	'scrumdb' => array(
		'instance' => true,
		'db' => 'scrum',
		'inherit' => 'devdb',
	),
);
