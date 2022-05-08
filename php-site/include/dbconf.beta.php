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
		'login' => 'php-beta',
		'passwd' => 'ebHerdIt',
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
		'host' => 'masterdb-slave',
		'inherit' => 'devdbmulti',
	),

	'masterdb' => array(
		'instance' => true,
		'db' => 'master_beta',
		'inherit' => 'general',
	),
	
	'userdb_template' => array(
		'login' => 'php-beta',
		'passwd' => 'ebHerdIt',
		'type' => 'single',
	),
	
	'usersdb' => array(
		'instance' => true,
		'type' => 'split',
		'splitfunc' => 'split_db_user',
		'sources' => array(
			array( // anonymous server
				'host' => 'masterdb-slave',
				'db' => 'usersanon_beta',
				'inherit' => 'devdb',
				'seqtable' => 'usercounter',
			),                  
			array( 'host' => 'userdb1-slave',  'db' => 'userdb1_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb1-slave',  'db' => 'userdb1_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb2-slave',  'db' => 'userdb2_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb2-slave',  'db' => 'userdb2_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb3-slave',  'db' => 'userdb3_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb3-slave',  'db' => 'userdb3_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb4-slave',  'db' => 'userdb4_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb4-slave',  'db' => 'userdb4_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb5-slave',  'db' => 'userdb5_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb5-slave',  'db' => 'userdb5_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb6-slave',  'db' => 'userdb6_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb6-slave',  'db' => 'userdb6_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb7-slave',  'db' => 'userdb7_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb7-slave',  'db' => 'userdb7_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb8-slave',  'db' => 'userdb8_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb8-slave',  'db' => 'userdb8_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb9-slave',  'db' => 'userdb9_1_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb9-slave',  'db' => 'userdb9_2_beta',  'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb10-slave', 'db' => 'userdb10_1_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb10-slave', 'db' => 'userdb10_2_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb11-slave', 'db' => 'userdb11_1_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb11-slave', 'db' => 'userdb11_2_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb12-slave', 'db' => 'userdb12_1_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb12-slave', 'db' => 'userdb12_2_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb13-slave', 'db' => 'userdb13_1_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb13-slave', 'db' => 'userdb13_2_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb14-slave', 'db' => 'userdb14_1_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
			array( 'host' => 'userdb14-slave', 'db' => 'userdb14_2_beta', 'inherit' => 'userdb_template', 'seqtable' => 'usercounter' ),
		),
	),

	'configdb' => array(
		'instance' => true,
		'db' => 'config_beta',
		'inherit' => 'general',
	),

	'db' => array(
		'db' => 'general_beta',
		'inherit' => 'general',
		'instance' => true,
	),

	'moddb' => array(
		'instance' => true,
		'db' => 'mods_beta',
		'inherit' => 'general'
	),

	/* old dbs */

	'polldb' => array(
		'instance' => true,
		'db' => 'polls_beta',
		'inherit' => 'general',
	),
	'shopdb' => array(
		'instance' => true,
		'db' => 'shop_beta',
		'inherit' => 'general',
	),
	'filesdb' => array(
		'instance' => true,
		'db' => 'fileupdates_beta',
		'inherit' => 'general',
	),
	'bannerdb' => array(
		'instance' => true,
		'db' => 'banners_beta',
		'inherit' => 'general',
		'debuglevel' => 0,
	),
	'contestdb' => array(
		'instance' => true,
		'db' => 'contest_beta',
		'inherit' => 'general',
	),
	'forumdb' => array(
		'instance' => true,
		'db' => 'forum_beta',
		'host' => 'forumdb-slave',
		'inherit' => 'devdb',
	),
	'articlesdb' => array(
		'instance' => true,
		'db' => 'articles_beta',
		'inherit' => 'general',
	),
	'wikidb' => array(
		'instance' => true,
		'db' => 'wiki_beta',
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
