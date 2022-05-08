<?php

// this is a dbconf file JUST for pulling schema information out of the old database structure
// it probably doesn't work as a way to use the dev site. Massaged into existence from old svn checkins.

$databases = array(
	'dbserv' => array(
		'host' => '192.168.0.50',
		'login' => 'root',
		'passwd' => 'Hawaii',
	),
	'devdb' => array(
		'inherit' => 'dbserv',
		'type' => 'single',
		'debuglevel' => 2,
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

/*	'masterdb' => array(
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
	),*/

	/* old dbs */

	'db' => array(
		'instance' => true,
		'db' => 'nexopia',
		'inherit' => 'devdb',
	),
	'fastdb' => array(
		'instance' => true,
		'db' => 'nexopiafast',
		'inherit' => 'devdb',
	),

	'sessiondb' => array(
		'instance' => true,
		'db' => 'nexopiasession',
		'inherit' =>  'devdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'modsdb' => array(
		'instance' => true,
		'db' => 'nexopiamods',
		'inherit' =>  'devdb'
	),
	'modsdb' => array(
		'instance' => true,
		'db' => 'nexopiamods',
		'inherit' =>  'devdb',
	),
	'archivedb' => array(
		'instance' => true,
		'db' => 'nexopiaarchive',
		'inherit' =>  'devdb',
	),
	'msgsdb' => array(
		'instance' => true,
		'db' => 'nexopiamsgs',
		'inherit' =>  'devdb',
		'debug' => 2
	),
	'commentsdb' => array(
		'instance' => true,
		'db' => 'nexopiausercomments',
		'inherit' =>  'devdb',
	),
	'pollsdb' => array(
		'instance' => true,
		'db' => 'nexopiapolls',
		'inherit' =>  'devdb',
	),
	'shopdb' => array(
		'instance' => true,
		'db' => 'nexopiashop',
		'inherit' =>  'devdb',
	),
	'filesdb' => array(
		'instance' => true,
		'db' => 'nexopiafileupdates',
		'inherit' =>  'devdb',
	),
	'bannerdb' => array(
		'instance' => true,
		'db' => 'nexopiabanners',
		'inherit' =>  'devdb',
	),
	'contestdb' => array(
		'instance' => true,
		'db' => 'nexopiacontest',
		'inherit' =>  'devdb',
	),
	'weblogdb' => array(
		'instance' => true,
		'db' => 'nexopiablog',
		'inherit' =>  'devdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'forumsdb' => array(
		'instance' => true,
		'db' => 'nexopiaforum',
		'inherit' =>  'devdb',
		'needkey' => DB_KEY_FORBIDDEN
	),
	'statsdb' => array(
		'instance' => true,
		'db' => 'nexopiastats',
		'inherit' =>  'devdb',
	),
	'profviewsdb' => array(
		'instance' => true,
		'db' => 'nexopiaprofviews',
		'inherit' =>  'devdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'profiledbdb' => array(
		'instance' => true,
		'db' => 'nexopiaprofile',
		'inherit' =>  'devdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'articlesdb' => array(
		'instance' => true,
		'db' => 'nexopiaarticles',
		'inherit' =>  'devdb',
	),
	'gallerydb' => array(
		'instance' => true,
		'db' => 'nexopiagallery',
		'inherit' =>  'devdb',
		'needkey' => DB_KEY_REQUIRED
	),
	'friendsdb' => array(
		'instance' => true,
		'db' => 'nexopiafriends',
		'inherit' =>  'devdb',
	),
	'picsdb' => array(
		'instance' => true,
		'db' => 'nexopiapics',
		'inherit' =>  'devdb',
	),
	'wikidb' => array(
		'instance' => true,
		'db' => 'nexopiawiki',
		'inherit' =>  'devdb',
	),
	'logsdbdb' => array(
		'instance' => true,
		'db' => 'nexopialogs',
		'inherit' => 'devdb',
	),
	'shopdbdb' => array(
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
		'instance' => true,
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
/*	'picmodexamdb' => array(
		'instance' => true,
		'db' => 'nexopiapicmodexam',
		'inherit' => 'devdb',
	),
	'scrumdb' => array(
		'instance' => true,
		'db' => 'scrum',
		'inherit' => 'devdb',
	),*/
	'friendsdb' => array(
		'instance' => true,
		'db' => 'nexopiafriends',
		'inherit' => 'devdb',
	),
	'gallerydb' => array(
		'instance' => true,
		'db' => 'nexopiagallery',
		'inherit' => 'devdb',
	),
	'moddb' => array(
		'instance' => true,
		'db' => 'nexopiamods',
		'inherit' => 'devdb',
	),
	'usersdb' => array(
		'instance' => true,
		'db' => 'nexopiausers',
		'inherit' => 'devdb',
	),
);
