<?php

	if(!isset($basedomain))      $basedomain = "dev";

	if(!isset($wwwdomain))       $wwwdomain = "www.$basedomain";
	if(!isset($pluswwwdomain))   $pluswwwdomain = $wwwdomain; //needed only to check which db server to use, when plus only db servers are possible

	if(!isset($staticdomain))	 $staticdomain = "$wwwdomain/static/$reporev/files";
	if(!isset($staticimgdomain)) $staticimgdomain = "$staticdomain/Legacy";
	if(!isset($userimgdomain))   $userimgdomain = "$wwwdomain/images";
	if(!isset($userfilesdomain)) $userfilesdomain = "users.$basedomain";

	if(!isset($emaildomain))     $emaildomain = $basedomain;
	if(!isset($cookiedomain))    $cookiedomain = ".www.$basedomain";

	if(!isset($rubydomain))      $rubydomain = "ruby.$basedomain";

	if(!isset($sitebasedir))     $sitebasedir = "/home/nexopia/php-site";

	if(!isset($docRoot))         $docRoot = "$sitebasedir/public_html";

	$staticRoot = "$sitebasedir/public_static";

	if (!isset($pendingdir))	 $pendingdir = "$staticRoot/user_data/pending";


	$databaseprofile = 'dev';

	$slowquerytime = 1*10000; // 1 secs

	$errorLogging = (isset($errorLogging) ? max($errorLogging, 2) : 2);

	$mogfs_domain = 'nexopia.com';
	$mogfs_hosts = array('192.168.10.5:6001');

	$rubysite = array($rubydomain); // in the dev server, there is usually only one of these and its domain is set in rubydomain

	$bannerservers = array('192.168.10.50');

	$memcacheoptions = 	array(
		'name' => 'cache',
		'servers' => array( '192.168.10.50:11211' ),
		'debug'   => false,
		'compress_threshold' => 8000,
		'persistant' => false,
		'delete_only' => false,
		);

	$pagecacheoptions = array(
		'name' => 'pagecache',
		'servers' => array( '192.168.10.50:11211' ),
		'debug'   => false,
		'compress_threshold' => 8000,
		'persistant' => false,
		);

    $contactemails = array(
        "Information" => "info@nexopia.com",
        "Webmaster" => "webmaster@nexopia.com",
        "Admin" => "admin@nexopia.com",
        "Help" => "help@nexopia.com",
        "Plus" => "plus@nexopia.com",
        "Advertising" => "sales@nexopia.com",
        );


	$debuginfousers = array(5, 7, 175, 192, 193, 199, 200, 203, 6638);

	$tidyoutput = false;

// below are options that need to be set to process interac payments through Moneris
	$INTERAC_PAYMENT['IDEBIT_postUrl'] = "http://192.168.0.42/eselectform.php";
	$INTERAC_PAYMENT['IDEBIT_merchNum'] = "0003MONMPGXXXX";
	$INTERAC_PAYMENT['IDEBIT_fundedUrl'] = "http://".$wwwdomain."/plus.php";
	$INTERAC_PAYMENT['IDEBIT_notFundedUrl'] = "http://".$wwwdomain."/plus.php";
	$INTERAC_PAYMENT['MONERIS_storeID'] = "store3";
	$INTERAC_PAYMENT['MONERIS_apiToken'] = "yesguy";

// options relating to what age range is supported in the system (this is used to build the stats tables
// as well as in the userSearch object
	$systemAgeMin = "14";
	$systemAgeMax = "60";

	$lockSplitWriteOps = false;

	$config = array(
		'devutil' => true,

		'bannerlogserver' => '10.0.0.85:6666',
		'adblasterserver' => "10.0.0.64:5556",

		'queue_cluster' => '',

		'templatefilesdir' => "$sitebasedir/templates/template_files/",
		'templateparsedir' => "$sitebasedir/cache/templates/",
		'templateusecached' => false,

		'cachedbs' => false,
		'cacheincludes' => false,

		'allowThreadUpdateEmails' => false, //send subscribed users an email if a thread is updated
		'defaultMessageAllowEmails' => true, // whether or not deliverMsg defaults to allowing emails to be sent

		'jsdir' => '/site_data/javascript/',
		'jsloc' => "http://$staticimgdomain/javascript/",

		'skindir' => '/site_data/skins/',
		'skinloc' => "http://$staticimgdomain/skins/",

		'bannerdir' => '/user_data/banners/', //directory relative to the $staticRoot to save banners.
		'bannerloc' => "http://$userimgdomain/banners/", //directory relative to the $staticRoot to save banners.

		'timezone' => 317, //timezone the times should be aligned to

		'title' => 'DevNexus', //website name
		'metadescription' => 'Nexopia.com is the latest and fastest growing ranking site on the internet. We offer fair and fun user ranking, intriguing articles regarding all walks of life, and a friendly entertaining forum.',
		'metakeywords' => 'Nexopia.com, chat, nexopia, rank, guys, girls, boys, men, women, kids, teens, 20\'s, college, university, high school, junior high, clubs, jokes, community, forums, pictures, hot, babe, love, friends, email, e-mail, rate, vote, talk, date, dating, meet',


		'contactsubjectPrefix' => 'Nexopia Response:', //prefix for emails from the contact us page.
		'copyright' => '© Nexopia.com Inc. 2008 all rights reserved.',
		'email' => 'no-reply@nexopia.com',

		'enableCompression' => true,
		'errorLogging' => 1,
		'showsource' => true, //enables or disables source.php
		'memcached' => true, //Use Memcache?
		'gd2' => true, //which image resize function to use

		'voteHistLength' => 86400*21, //poll vote length

		'smtp_host' => '127.0.0.1', //outgoing email server
		'paypalemail' => 'timo@tzc.com',

		'minAge' => 14, //(current year- minAge) is the first year shown in the signup page.
		'maxAge' => 60,

		'maxfriends' => 250,
		'maxpics' => 8,
		'maxpicspremium' => 12,
		'maxgallerypics' => 20,
		'maxgallerypicspremium' => 1000, //Maximum gallery pictures for plus users

		'friendAwayTime' => 600, //inactive time for user to be considered offline
		'maxAwayTime' => 3600, //max away time before a user is logged out, only applies if not a cached login.

		'linesPerPage' => 25, //lines of text per page, used in forum, msgs, friends, etc.
		'pagesInList' => 5, //number of pages to show in the page list
		'picsPerPage' => 10, //pics per page in a list of users in a search
		'maxpollwidth' => 100,
		'minusernamelength' => 4,
		'maxusernamelength' => 15,

		'lockmoditems' => false,

		'forumBannerDir' => '/user_data/forumbanners/',
		'forumBannerLoc' => "http://$userimgdomain/forumbanners/",
		'forumBannerHeight' => 130,
		'forumBannerWidth' => 500,
		'foruminactivetime' => 1209600, //time in seconds needed for a thread to be auto-locked. 0 to disable.
		'forumPic' => true, //show user pics in forum

		'basefiledir' => '/user_files/uploads/',
		'basefileloc' => "http://$userfilesdomain/uploads/",
		'filesizelimit' => 204800,
		'filesMaxFileLength' => 25, //maximum length of any filename
		'filesMaxFileSize' => 3145728, //maximum size, in bytes, of any file
		'filesMaxFolderDepth' => 6, //maximum (sub)folder depth allowed
		'filesMaxFolderLength' => 15, //maximum length of a folder name
		'filesMaxFolders' => 100, //maximum number of total folders a user may create
		'filesRestrictExts' => 'php pif com scr bat vbs', //file extensions users may not create
		'quota' => 1048576,

		'imagedir' => '/site_data/images/',
		'imageloc' => "http://$staticimgdomain/images/",
		'smilydir' => '/site_data/smilies/',
		'smilyloc' => "http://$staticimgdomain/smilies/",

		'picText' => 'Nexopia.com',

		'sourcepicdir' => '/user_data/source/',
		'sourcepicloc' => "http://$userimgdomain/source/",
		'galleryfulldir' => '/user_data/galleryfull/', //Location below docroot of full size gallery pictures
		'galleryfullloc' => "http://$userimgdomain/galleryfull/",
		'gallerypicdir' => '/user_data/gallery/',
		'gallerypicloc' => "http://$userimgdomain/gallery/",
		'gallerythumbdir' => '/user_data/gallerythumb/',
		'gallerythumbloc' => "http://$userimgdomain/gallerythumb/",
		'maxFullPicHeight' => 1200,
		'maxFullPicWidth' => 1600,
		'maxGalleryPicHeight' => 640,
		'maxGalleryPicWidth' => 640,

		'picdir' => '/user_data/userpics/',
		'picloc' => "http://$userimgdomain/gallery/",
		'thumbdir' => '/user_data/userpicsthumb/', //directory relative to the docroot to save thumbs.
		'thumbloc' => "http://$userimgdomain/gallerythumb/",
		'thumbHeight' => 150,
		'thumbWidth' => 100,
		'minPicHeight' => 75,
		'minPicWidth' => 100,
		'maxPicHeight' => 280,
		'maxPicWidth' => 320,
		'maxTinyPicHeight' => 64,
		'maxTinyPicWidth' => 64,

		'picmodexamdir' => '/site_data/picmodexam/', //directory relative to the docroot to save pic mod exam imgs
		'picmodexamloc' => "http://$staticimgdomain/picmodexam/", //directory relative to the docroot to save pic mod exam imgs
		'picmodpluserrrate' => 2.5, //maximum error rate required during a week to earn plus
		'picmodpluspicrate' => 2500, //modded pics required during a week to get plus
		'picmodmonthlymin' => 10000, //modded pics required during a month to make top 5

		'passthrough_all_unrecognized' => true, // pass all unrecognized urls to the ruby site.
	);

/* 	include_once("include/errorlog.php"); */
