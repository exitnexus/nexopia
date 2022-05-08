<?php

	if(!isset($basedomain))      $basedomain = "nexopia.com";

	if(!isset($wwwdomain))       $wwwdomain = "www.$basedomain";
	if(!isset($pluswwwdomain))   $pluswwwdomain = "plus.www.$basedomain"; //needed only to check which db server to use, when plus only db servers are possible

	if(!isset($staticdomain))    $staticdomain = "$wwwdomain/static/$reporev/files";
	if(!isset($staticimgdomain)) $staticimgdomain = "$staticdomain/Legacy";
	if(!isset($userimgdomain))   $userimgdomain = "images.$basedomain";
	if(!isset($userfilesdomain)) $userfilesdomain = "users.$basedomain";

	if(!isset($emaildomain))     $emaildomain = $basedomain;
	if(!isset($cookiedomain))    $cookiedomain = ".www.$basedomain";

	if(!isset($rubydomain))      $rubydomain = "ruby.$basedomain";

	if(!isset($sitebasedir))     $sitebasedir = "/home/nexopia";

	if(!isset($docRoot))         $docRoot = "$sitebasedir/public_html_ram";

	$staticRoot = "$sitebasedir/public_static";

	$databaseprofile = 'live';

	$slowquerytime = 10*10000; // 10 secs

	$errorLogging = (isset($errorLogging) ? max($errorLogging, 1) : 1);

	$mogfs_domain = 'nexopia.com';
	$mogfs_hosts = array(
		"10.0.0.101:6001",
		"10.0.0.102:6001",
		"10.0.0.103:6001",
		"10.0.0.104:6001",
		);
	shuffle($mogfs_hosts);

	$rubysite = array('127.0.0.1:7080');

	$bannerservers = array(
		'10.0.3.1',
		'10.0.3.2',
		'10.0.3.3',
		'10.0.3.4',
		'10.0.3.5',
		'10.0.3.6',
		'10.0.3.7',
		'10.0.3.8',
		'10.0.3.9',
		'10.0.3.10',
		'10.0.3.11',
		'10.0.3.12',
		'10.0.3.13',
		'10.0.3.14',
		'10.0.3.15',
		'10.0.3.16',
		'10.0.3.17',
		'10.0.3.18',
		'10.0.3.19',
		'10.0.3.20',
		'10.0.3.21',
		'10.0.3.22',
		'10.0.3.23',
		'10.0.3.24',
		'10.0.3.25',
		'10.0.3.26',
		'10.0.3.27',
		'10.0.3.28',
		'10.0.3.29',
		'10.0.3.30',
		'10.0.3.31',
		'10.0.3.32',
		'10.0.3.33',
		'10.0.3.34',
		'10.0.3.35',
		'10.0.3.36',
		'10.0.3.37',
		'10.0.3.38',
		'10.0.3.39',
		'10.0.3.40',
		'10.0.3.41',
		'10.0.3.42',
		'10.0.3.43',
		'10.0.3.44',
		'10.0.3.45',
		'10.0.3.46',
		'10.0.3.47',
		'10.0.3.48',
		'10.0.3.49',
		'10.0.3.50',
		'10.0.3.51',
		'10.0.3.52',
		'10.0.3.53',
		'10.0.3.54',
		'10.0.3.55',
		'10.0.3.56',
		'10.0.3.57',
		'10.0.3.58',
#		'10.0.3.59', # dynamic59 is not configured/does not exist.
		'10.0.3.60',
		'10.0.3.61',
		'10.0.3.62',
#		'10.0.3.63', # dynamic63 is not configured/does not exist.
#		'10.0.3.64', # dynamic64 is of unknown stability.
		'10.0.3.65',
#		'10.0.3.66', # dynamic66 is of unknown stability (network problems?)
		'10.0.3.67',
		'10.0.3.68',
		'10.0.3.69',
#		'10.0.3.70', # dynamic70 is our testing/staging image.
			);

	$memcacheoptions = array(
		'name' => 'cache',
		'servers' => array(
			array ("10.0.7.1:11212", 1), // 3.1G
			array ("10.0.7.2:11212", 1), // 3.1G
			array ("10.0.7.3:11212", 1), // 3.1G
			array ("10.0.7.4:11212", 1), // 3.1G
			array ("10.0.7.5:11212", 1), // 3.1G
			array ("10.0.7.6:11212", 1), // 3.1G
			array ("10.0.7.7:11212", 1), // 3.1G
			array ("10.0.7.8:11212", 1), // 3.1G
			),
		'debug'   => false,
		'delete_only' => false,
		'compress_threshold' => 8000,
		'persistant' => true);

	$pagecacheoptions = array(
		'name' => 'pagecache',
		'servers' => array(
			array ("10.0.7.1:11213", 1), // 128M
			array ("10.0.7.2:11213", 1), // 128M
			array ("10.0.7.3:11213", 1), // 128M
			array ("10.0.7.4:11213", 1), // 128M
			array ("10.0.7.5:11213", 1), // 128M
			array ("10.0.7.6:11213", 1), // 128M
			array ("10.0.7.7:11213", 1), // 128M
			array ("10.0.7.8:11213", 1), // 128M
			),
		'debug'   => false,
		'compress_threshold' => 8000,
		'persistant' => true);

    $contactemails = array(
        "Information" => "info@nexopia.com",
        "Webmaster" => "webmaster@nexopia.com",
        "Admin" => "admin@nexopia.com",
        "Help" => "help@nexopia.com",
        "Plus" => "plus@nexopia.com",
        "Advertising" => "sales@nexopia.com",
        );


	$debuginfousers = array(1,21,997372,1745917,2309088);

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
		'devutil' => false,

		'bannerlogserver' => '10.0.0.85:6666',
		'adblasterserver' => "10.0.0.64:5556",

		'templatefilesdir' => "$docRoot/include/templates/template_files/",
		'templateparsedir' => "$sitebasedir/cache/templates/",
		'templateusecached' => true,

		'cachedbs' => true,
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

		'title' => 'Nexopia', //website name
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

		'smtp_host' => '10.0.0.8', //outgoing email server
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

		'lockmoditems' => true,

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

		'passthrough_all_unrecognized' => false, // pass all unrecognized urls to the ruby site.
	);

	include_once("include/errorsyslog.php");
