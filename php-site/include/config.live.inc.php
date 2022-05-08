<?php

	if(!isset($basedomain))      $basedomain = "nexopia.com";

	if(!isset($wwwdomain))       $wwwdomain = "www.$basedomain";
	if(!isset($pluswwwdomain))   $pluswwwdomain = "plus.www.$basedomain"; //needed only to check which db server to use, when plus only db servers are possible

	if(!isset($staticimgdomain)) $staticimgdomain = "static.$basedomain";
	if(!isset($userimgdomain))   $userimgdomain = "images.$basedomain";
	if(!isset($userfilesdomain)) $userfilesdomain = "users.$basedomain";

	if(!isset($emaildomain))     $emaildomain = $basedomain;
	if(!isset($cookiedomain))    $cookiedomain = ".www.$basedomain";

	if(!isset($sitebasedir))     $sitebasedir = "/home/nexopia";

	$docRoot = "$sitebasedir/public_html";
	$staticRoot = "$sitebasedir/public_static";

	$slowquerytime = 10*10000; // 10 secs

	$errorLogging = (isset($errorLogging) ? max($errorLogging, 1) : 1);

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
#		'10.0.3.22', //not in from the beginning
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
			);

	$memcacheoptions = array(
		'servers' => array(
				array("10.0.2.43:11211", 8),
				array("10.0.2.7:11211",  5),
				array("10.0.2.8:11211",  5),
				array("10.0.2.9:11211",  5),
				array("10.0.2.10:11211", 5),
				array("10.0.2.11:11211", 5),
/*
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
#				"10.0.3.22:11211", //not in since reset
				"10.0.3.23:11211",
				"10.0.3.24:11211",
				"10.0.3.25:11211",
				"10.0.3.26:11211",
				"10.0.3.27:11211",
				"10.0.3.28:11211",
				"10.0.3.29:11211",
				"10.0.3.30:11211",
				"10.0.3.31:11211",
				"10.0.3.32:11211",
				"10.0.3.33:11211",
				"10.0.3.34:11211",
				"10.0.3.35:11211",
				"10.0.3.36:11211",
*/
			),
		'debug'   => false,
		'compress_threshold' => 8000,
		'persistant' => true);

	$pagecacheoptions = array(
		'servers' => array(
				'10.0.2.27:11211',
				'10.0.2.28:11211',
				'10.0.2.29:11211',
				'10.0.2.30:11211',
				'10.0.2.31:11211',
/*
				'10.0.3.37:11211',
				'10.0.3.38:11211',
				'10.0.3.39:11211',
				'10.0.3.40:11211',
				'10.0.3.41:11211',
				'10.0.3.42:11211',
				'10.0.3.43:11211',
				'10.0.3.44:11211',
*/
			),
		'debug'   => false,
		'compress_threshold' => 8000,
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

#	$databaseprofile = 'live';

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
		'templatefilesdir' => "$docRoot/include/templates/template_files/",
		'templateparsedir' => "$docRoot/include/templates/compiled_files/",
		'templateusecached' => true,
	
		'allowThreadUpdateEmails' => false, //send subscribed users an email if a thread is updated
		'defaultMessageAllowEmails' => true, // whether or not deliverMsg defaults to allowing emails to be sent

		'jsdir' => '/site_data/javascript/',
		'jsloc' => "http://$staticimgdomain/javascript/",

		'skindir' => '/site_data/skins/',
		'skinloc' => "http://$staticimgdomain/skins/",

		'bannerdir' => '/user_data/banners/', //directory relative to the $staticRoot to save banners.
		'bannerloc' => "http://$userimgdomain/banners/", //directory relative to the $staticRoot to save banners.		'timezone' => 317, //timezone the times should be aligned to

		'title' => 'Nexopia', //website name
		'metadescription' => 'Nexopia.com is the latest and fastest growing ranking site on the internet. We offer fair and fun user ranking, intriguing articles regarding all walks of life, and a friendly entertaining forum.',
		'metakeywords' => 'Nexopia.com, chat, nexopia, rank, guys, girls, boys, men, women, kids, teens, 20\'s, college, university, high school, junior high, clubs, jokes, community, forums, pictures, hot, babe, love, friends, email, e-mail, rate, vote, talk, date, dating, meet',


		'contactsubjectPrefix' => 'Nexopia Response:', //prefix for emails from the contact us page.
		'copyright' => '© Nexopia.com Inc. 2006 all rights reserved.',
		'email' => 'no-reply@nexopia.com',

		'enableCompression' => true,
		'errorLogging' => 1,
		'showsource' => true, //enables or disables source.php
		'memcached' => true, //Use Memcache?
		'gd2' => true, //which image resize function to use

		'voteHistLength' => 86400*21, //poll vote length

		'smtp_host' => '10.0.0.33', //outgoing email server
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
		'pagesInList' => 10, //number of pages to show in the page list
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
		'picloc' => "http://$userimgdomain/userpics/",
		'thumbdir' => '/user_data/userpicsthumb/', //directory relative to the docroot to save thumbs.
		'thumbloc' => "http://$userimgdomain/userpicsthumb/",
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
		'picmodpluspicrate'	=> 2500, //modded pics required during a week to get plus
		'picmodmonthlymin'	=> 10000, //modded pics required during a month to make top 5
	);

	include_once("include/errorsyslog.php");
