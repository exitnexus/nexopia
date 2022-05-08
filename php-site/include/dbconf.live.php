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
//		'login' => 'nexopia',
//		'passwd' => 'CierlU$I',

		'login' => 'root',
		'passwd' => 'pRlUvi$t',
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
		'host' => '10.0.4.100',
		'inherit' => 'devdbmulti',
	),

	/* new dbs */

	'masterdb' => array(
		'instance' => true,
		'db' => 'newmaster',
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
				'db' => 'newusersanon',
				'host' => '10.0.4.100',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
			),
			array( 'db' => 'userdb1_1', 'host' => '10.0.5.1',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb1_2', 'host' => '10.0.5.1',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb2_1', 'host' => '10.0.5.2',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb2_2', 'host' => '10.0.5.2',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb3_1', 'host' => '10.0.5.3',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb3_2', 'host' => '10.0.5.3',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb4_1', 'host' => '10.0.5.4',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb4_2', 'host' => '10.0.5.4',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb5_1', 'host' => '10.0.5.5',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb5_2', 'host' => '10.0.5.5',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb6_1', 'host' => '10.0.5.6',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb6_2', 'host' => '10.0.5.6',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb7_1', 'host' => '10.0.5.7',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb7_2', 'host' => '10.0.5.7',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb8_1', 'host' => '10.0.5.8',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb8_2', 'host' => '10.0.5.8',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb9_1', 'host' => '10.0.5.9',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb9_2', 'host' => '10.0.5.9',   'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb10_1', 'host' => '10.0.5.10', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb10_2', 'host' => '10.0.5.10', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb11_1', 'host' => '10.0.5.11', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb11_2', 'host' => '10.0.5.11', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb12_1', 'host' => '10.0.5.12', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb12_2', 'host' => '10.0.5.12', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb13_1', 'host' => '10.0.5.13', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb13_2', 'host' => '10.0.5.13', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),

			array( 'db' => 'userdb14_1', 'host' => '10.0.5.14', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'db' => 'userdb14_2', 'host' => '10.0.5.14', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
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
		'host' => '10.0.4.101',
		'inherit' => 'devdb',
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
/*
	'picmodexamdb' => array(
		'instance' => true,
		'db' => 'newpicmodexam',
		'inherit' => 'general',
	),
*/
);
