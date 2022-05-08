<?
/*

	$wwwdomain = 'www.nexopia.com';
	$pluswwwdomain = 'plus.www.nexopia.com';
	$emaildomain = 'nexopia.com';


	$sitebasedir = "/home/nexopia";
	$masterserver = "$sitebasedir/public_html";
	$docRoot = "$sitebasedir/public_html";

	$slowquerytime = 10*10000; // 10 secs

	$errorLogging = (isset($errorLogging) ? max($errorLogging, 1) : 1);

	$databases = array();

//users, pics, friends
	$databases['db1']["insert"] = array(	"host" => "10.0.2.1",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia" );
	$databases['db1']["select"][] = array(	"host" => "10.0.2.1",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia",	"weight" => 1,	"plus" => 'n', );
	$databases['db1']["select"][] = array(	"host" => "10.0.2.21",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia",	"weight" => 0,	"plus" => 'y', );
	$databases['db1']["backup"] = array(	"host" => "10.0.2.21",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia" );

//forums, blogs, mod
	$databases['db2']["insert"] = array(	"host" => "10.0.2.2",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia2" );
	$databases['db2']["select"][] = array(	"host" => "10.0.2.2",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia2",	"weight" => 1,	"plus" => 'n', );
	$databases['db2']["select"][] = array(	"host" => "10.0.2.22",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia2",	"weight" => 0,	"plus" => 'y', );
	$databases['db2']["backup"] = array(	"host" => "10.0.2.22",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia2" );

//messages
	$databases['db3']["insert"] = array(	"host" => "10.0.2.3",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia3" );
	$databases['db3']["select"][] = array(	"host" => "10.0.2.3",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia3",	"weight" => 1,	"plus" => 'n', );
	$databases['db3']["select"][] = array(	"host" => "10.0.2.23",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia3",	"weight" => 0,	"plus" => 'y', );
	$databases['db3']["backup"] = array(	"host" => "10.0.2.23",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia3" );

//iplog, loginlog, userhitlog
	$databases['db4']["insert"] = array(	"host" => "10.0.2.4",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia4" );
	$databases['db4']["select"][] = array(	"host" => "10.0.2.4",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia4",	"weight" => 1,	"plus" => 'n', );
	$databases['db4']["select"][] = array(	"host" => "10.0.2.24",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia4",	"weight" => 0,	"plus" => 'y', );
	$databases['db4']["backup"] = array(	"host" => "10.0.2.24",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia4" );

//sessions, stats, useractivetime
	$databases['db5']["insert"] = array(	"host" => "10.0.2.5",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia5" );
	$databases['db5']["select"][] = array(	"host" => "10.0.2.5",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia5",	"weight" => 1,	"plus" => 'n', );
	$databases['db5']["select"][] = array(	"host" => "10.0.2.25",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia5",	"weight" => 0,	"plus" => 'y', );
	$databases['db5']["backup"] = array(	"host" => "10.0.2.25",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia5" );

//comments
	$databases['db6']["insert"] = array(	"host" => "10.0.2.6",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia6" );
	$databases['db6']["select"][] = array(	"host" => "10.0.2.6",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia6",	"weight" => 1,	"plus" => 'n', );
	$databases['db6']["select"][] = array(	"host" => "10.0.2.26",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia6",	"weight" => 0,	"plus" => 'y', );
	$databases['db6']["backup"] = array(	"host" => "10.0.2.26",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia6" );

//profiles
	$databases['db7']["insert"] = array(	"host" => "10.0.2.7",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia7" );
	$databases['db7']["select"][] = array(	"host" => "10.0.2.7",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia7",	"weight" => 1,	"plus" => 'n', );
	$databases['db7']["select"][] = array(	"host" => "10.0.2.27",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia7",	"weight" => 0,	"plus" => 'y', );
	$databases['db7']["backup"] = array(	"host" => "10.0.2.27",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia7" );


//profileviews
	$databases['db8']["insert"] = array(	"host" => "10.0.2.8",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia8" );
	$databases['db8']["select"][] = array(	"host" => "10.0.2.8",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia8",	"weight" => 1,	"plus" => 'n', );
	$databases['db8']["select"][] = array(	"host" => "10.0.2.28",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia8",	"weight" => 0,	"plus" => 'y', );
	$databases['db8']["backup"] = array(	"host" => "10.0.2.28",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia8" );

//sessions, stats, useractivetime
	$databases['db9']["insert"] = array(	"host" => "10.0.2.9",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia9" );
	$databases['db9']["select"][] = array(	"host" => "10.0.2.9",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia9",	"weight" => 1,	"plus" => 'n', );
	$databases['db9']["select"][] = array(	"host" => "10.0.2.29",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopia9",	"weight" => 0,	"plus" => 'y', );
	$databases['db9']["backup"] = array(	"host" => "10.0.2.29",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopia9" );

//banners
	$databases['banner']["insert"] = array(	"host" => "10.0.0.16",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopiabanners",	"debug" => 0 );
	$databases['banner']["select"][]=array(	"host" => "10.0.0.16",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopiabanners",	"weight" => 1,	"plus" => 'n', );
	$databases['banner']["backup"] = array(	"host" => "10.0.0.16",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopiabanners" );

//message and comment archives
	$databases['archive']["insert"] = array("host" => "10.0.0.16",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopiaarchive" );
	$databases['archive']["select"][]=array("host" => "10.0.0.16",	"login" => "nexopia",	"passwd" => 'CierlU$I',	"db" => "nexopiaarchive",	"weight" => 1,	"plus" => 'n', );
	$databases['archive']["backup"] = array("host" => "10.0.0.16",	"login" => "root",		"passwd" => 'pRlUvi$t',	"db" => "nexopiaarchive" );


	$bannerservers = array('10.0.0.32', '10.0.0.1', '10.0.0.82');

	$memcacheoptions = array(
		"servers" => array(
							"10.0.3.1:11211",
							"10.0.3.2:11211",
							"10.0.3.3:11211",
							"10.0.3.4:11211",
							"10.0.3.5:11211",
							"10.0.3.6:11211",
							"10.0.3.7:11211",
							"10.0.3.8:11211",
							"10.0.3.9:11211",
							"10.0.3.10:11211",
							"10.0.3.11:11211",
							"10.0.3.12:11211",
							"10.0.3.13:11211",
							"10.0.3.14:11211",
							"10.0.3.15:11211",
							"10.0.3.16:11211",
							"10.0.3.17:11211",
							"10.0.3.18:11211",
							"10.0.3.19:11211",
							"10.0.3.20:11211",
							"10.0.3.21:11211",
							"10.0.3.22:11211",
							"10.0.3.23:11211",
							"10.0.3.24:11211",
							"10.0.3.25:11211",
							"10.0.3.26:11211",
							"10.0.3.27:11211",
							"10.0.3.28:11211",
							"10.0.3.29:11211",
							"10.0.3.30:11211",
							),
		'debug'   => false,
		'compress_threshold' => 0,
#		'compress_threshold' => 10240, //for native php version
		"compress" => 0, //for pecl version
		'persistant' => false);

	$pagecacheoptions = array(
		'servers' => array(
					'10.0.3.31:11211',
					'10.0.3.32:11211',
					'10.0.3.33:11211',
					'10.0.3.34:11211',
					'10.0.3.35:11211',
					'10.0.3.36:11211',
					 ),
		'debug'   => false,
		'compress_threshold' => 0,
#		'compress_threshold' => 10240, //for native php version
		"compress" => 0, //for pecl version
		'persistant' => true);

    $contactemails = array(
        "Information" => "info@nexopia.com",
        "Webmaster" => "webmaster@nexopia.com",
        "Admin" => "admin@nexopia.com",
        "Help" => "help@nexopia.com",
        "Plus" => "admin@nexopia.com",
        "Advertising" => "sales@nexopia.com",
        );

    $debuginfousers = array(1,21);

/*/

	$wwwdomain = 'www.nexopia.sytes.net';
	$pluswwwdomain = 'lighttpd.www.nexopia.sytes.net'; //needed only to check which db server to use, when plus only db servers are possible
	$emaildomain = 'nexopia.sytes.net';


	$sitebasedir = "/htdocs/nexopia";
	$masterserver = "$sitebasedir/master";
	$docRoot = "$sitebasedir/public_html";

	$slowquerytime = 1*10000; // 1 secs

	$errorLogging = (isset($errorLogging) ? max($errorLogging, 2) : 2);

	$databases = array();

	$dbserv = "localhost";
//	$dbserv = "192.168.0.107";

//main db
	$databases['main']["insert"] = array(	"host" => "$dbserv",
											"login" => "root",
											"debug" => 2,
											"passwd" => "Hawaii",
											"db" => "nexopia" );

	$databases['main']["select"][] = array(	"weight" => 1,
											"plus" => 'n',
											"debug" => 2,
											"host" => "$dbserv",
											"login" => "root",
											"passwd" => "Hawaii",
											"db" => "nexopia" );

	$databases['main']["select"][] = array(	"weight" => 1,
											"plus" => 'y',
//											"debug" => 2,
											"host" => "$dbserv",
											"login" => "root",
											"passwd" => "Hawaii",
											"db" => "nexopia" );

	$databases['main']["backup"] = array(	"host" => "$dbserv",
											"login" => "root",
											"passwd" => "Hawaii",
											"db" => "nexopia" );

//fast db
	$databases['fast']["insert"] = array(	"host" => "$dbserv",
											"login" => "root",
											"passwd" => "Hawaii",
											"db" => "nexopiafast" );

	$databases['fast']["select"][] = array(	"weight" => 1,
											"plus" => 'n',
											"host" => "$dbserv",
											"login" => "root",
											"passwd" => "Hawaii",
											"db" => "nexopiafast" );

	$databases['fast']["select"][] = array(	"weight" => 1,
											"plus" => 'y',
											"host" => "$dbserv",
											"login" => "root",
											"passwd" => "Hawaii",
											"db" => "nexopiafast" );

	$databases['fast']["backup"] = array(	"host" => "$dbserv",
											"login" => "root",
											"passwd" => "Hawaii",
											"db" => "nexopiafast" );

	$databases["session"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiasession" );

	$databases["mods"] = array(		"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiamods" );

	$databases["archive"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiaarchive" );

	$databases["msgs"] = array(		"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiamsgs",
									"debug" => 2,
									 );

	$databases["comments"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiausercomments" );

	$databases["polls"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiapolls" );

	$databases["shop"] = array(		"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiashop" );

	$databases["files"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiafileupdates" );

	$databases["banner"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiabanners" );

	$databases["contest"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiacontest" );

	$databases["weblog"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiablog" );

	$databases["forums"] = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiaforum" );

	$databases["logs"]	 = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopialogs" );

	$databases["stats"]	 = array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiastats" );

	$databases["logsnew"]= array(
								array(	"host" => "$dbserv", "login" => "root", "passwd" => "Hawaii", "db" => "nexopialogs1" ),
								array(	"host" => "$dbserv", "login" => "root", "passwd" => "Hawaii", "db" => "nexopialogs2" ),
								);


	$databases["profviews"]= array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiaprofviews" );

	$databases["profile"]= array(	"host" => "$dbserv",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopiaprofile" );

	$bannerservers = array('192.168.0.8');

	$memcacheoptions = 	array(
		'servers' => array( '192.168.0.8:11211',
							'192.168.0.8:11212'
							),
		'debug'   => false,
		'compress_threshold' => 10240,
		"compress" => 0,
		'persistant' => false);

	$pagecacheoptions = array(
		'servers' => array( '192.168.0.8:11211'		),
		'debug'   => false,
		'compress_threshold' => 10240,
		"compress" => 0,
		'persistant' => false);

	$contactemails = array(
		"Information" => "timo@tzc.com",
		"Webmaster" => "timo@tzc.com",
		"Admin" => "timo@tzc.com",
		"Help" => "timo@tzc.com",
		"Plus" => "timo@tzc.com",
		"Advertising" => "timo@tzc.com"
		);

	$debuginfousers = array(5,7);

//*/

