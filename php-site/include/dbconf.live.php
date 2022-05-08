<?php

// three types of server:
/// - single: identifies a single, nonreplicated, server. Has only one level of options
/// - multi: a server with a secondary replication database. 'roles' subitem
///              indicates handlers for select and insert (and backup?). Those subitems
///              by default use any configuration item at the top level, and then
///              they can overload items selectively.
/// - pair: a multi-master replication server setup. 'roles' subitem
///              indicates handlers for host1 and host2, along with which is master and the
///              memcache key to use while swapping. Those subitems by default use any
///              configuration item at the top level, and then they can overload items selectively.
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
		'login' => 'php-site',
		'passwd' => 'xXAuVm2U',
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

	'general' => array(
		'host' => 'masterdb',
		'inherit' => 'devdbmulti',
	),

	'masterdb' => array(
		'instance' => true,
		'db' => 'master',
		'inherit' => 'general',
	),
	
	'userdb_template' => array(
		'login' => 'php-site',
		'passwd' => 'xXAuVm2U',
		'type' => 'single',
	),
	
	'usersdb' => array(
		'instance' => true,
		'type' => 'split',
		'splitfunc' => 'split_db_user',
		'sources' => array(
			array( // anonymous server
				'host' => 'masterdb',
				'db' => 'usersanon',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
			),
			array( 'host' => 'userdb1',  'db' => 'userdb1_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb1',  'db' => 'userdb1_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb2',  'db' => 'userdb2_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb2',  'db' => 'userdb2_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb3',  'db' => 'userdb3_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb3',  'db' => 'userdb3_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb4',  'db' => 'userdb4_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb4',  'db' => 'userdb4_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb5',  'db' => 'userdb5_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb5',  'db' => 'userdb5_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb6',  'db' => 'userdb6_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb6',  'db' => 'userdb6_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb7',  'db' => 'userdb7_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb7',  'db' => 'userdb7_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb8',  'db' => 'userdb8_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb8',  'db' => 'userdb8_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb9',  'db' => 'userdb9_1',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb9',  'db' => 'userdb9_2',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb10', 'db' => 'userdb10_1', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb10', 'db' => 'userdb10_2', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb11', 'db' => 'userdb11_1', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb11', 'db' => 'userdb11_2', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb12', 'db' => 'userdb12_1', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb12', 'db' => 'userdb12_2', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb13', 'db' => 'userdb13_1', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb13', 'db' => 'userdb13_2', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb14', 'db' => 'userdb14_1', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb14', 'db' => 'userdb14_2', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
		),
	),

	'configdb' => array(
		'instance' => true,
		'db' => 'config',
		'inherit' => 'general',
	),

	'db' => array(
		'db' => 'general',
		'inherit' => 'general',
		'instance' => true,
	),

	'moddb' => array(
		'instance' => true,
		'db' => 'mods',
		'inherit' => 'general'
	),

	/* old dbs */

	'polldb' => array(
		'instance' => true,
		'db' => 'polls',
		'inherit' => 'general',
	),
	'shopdb' => array(
		'instance' => true,
		'db' => 'shop',
		'inherit' => 'general',
	),
	'filesdb' => array(
		'instance' => true,
		'db' => 'fileupdates',
		'inherit' => 'general',
	),
	'bannerdb' => array(
		'instance' => true,
		'db' => 'banners',
		'inherit' => 'general',
		'debuglevel' => 0,
	),
	'contestdb' => array(
		'instance' => true,
		'db' => 'contest',
		'inherit' => 'general',
	),
	'forumdb' => array(
		'instance' => true,
		'db' => 'forum',
		'host' => 'forumdb',
		'inherit' => 'devdb',
	),
	'articlesdb' => array(
		'instance' => true,
		'db' => 'articles',
		'inherit' => 'general',
	),
	'wikidb' => array(
		'instance' => true,
		'db' => 'wiki',
		'inherit' => 'general',
	),
/*
	'picmodexamdb' => array(
		'instance' => true,
		'db' => 'newpicmodexam',
		'inherit' => 'general',
	),
*/
);
