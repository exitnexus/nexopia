<?php

	if(!isset($basedomain))      $basedomain = "nexopia.com";

	if(!isset($wwwdomain))       $wwwdomain = "www.$basedomain";
	if(!isset($pluswwwdomain))   $pluswwwdomain = "www.$basedomain"; //needed only to check which db server to use, when plus only db servers are possible

	if(!isset($staticbasedomain)) $staticbasedomain = "static.$basedomain/$reporev";
	if(!isset($staticdomain))	 $staticdomain = "$staticbasedomain/files";
	if(!isset($staticimgdomain)) $staticimgdomain = "$staticdomain/Legacy";
	if(!isset($userimgdomain))   $userimgdomain = "images.$basedomain";
	if(!isset($userfilesdomain)) $userfilesdomain = "users.$basedomain";

	if(!isset($emaildomain))     $emaildomain = $basedomain;
	if(!isset($cookiedomain))    $cookiedomain = ".www.$basedomain";

	if(!isset($rubydomain))      $rubydomain = "ruby.$basedomain";

	if(!isset($sitebasedir))     $sitebasedir = "/var/nexopia/php-live";

	if(!isset($docRoot))         $docRoot = "$sitebasedir/public_html";

	$databaseprofile = 'live';

	$slowquerytime = 10*10000; // 10 secs

	$errorLogging = (isset($errorLogging) ? max($errorLogging, 1) : 1);

	$mogfs_domain = 'nexopia.com';
	$mogfs_hosts = array( // note: these all use slogile, as php site only does userfiles at this point, and they're entirely in the source class.
		'10.0.0.210:6001',
		'10.0.0.211:6001',
		'10.0.0.212:6001',
		'10.0.0.213:6001',
	);
	shuffle($mogfs_hosts);

	$rubysite = array('127.0.0.1:7080');

	$bannerservers = array('10.0.0.31', '10.0.0.32');

	$memcacheoptions = array(
		'name' => 'cache',
		'servers' => array(
			array ("10.0.7.1:11212", 1),
			array ("10.0.7.2:11212", 1),
			array ("10.0.7.3:11212", 1),
			array ("10.0.7.4:11212", 1),
			array ("10.0.7.5:11212", 1),
			array ("10.0.7.6:11212", 1),
			array ("10.0.7.7:11212", 1),
			array ("10.0.7.8:11212", 1)
			),
		'debug'   => false,
		'delete_only' => false,
		'compress_threshold' => 8000,
		'persistant' => true);

	$pagecacheoptions = array(
		'name' => 'pagecache',
		'servers' => array(
			array ("10.0.7.1:11213", 1),
			array ("10.0.7.2:11213", 1),
			array ("10.0.7.3:11213", 1),
			array ("10.0.7.4:11213", 1),
			array ("10.0.7.5:11213", 1),
			array ("10.0.7.6:11213", 1),
			array ("10.0.7.7:11213", 1),
			array ("10.0.7.8:11213", 1)
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


	$debuginfousers = array(1,21,997372,1745917,2309088,3233577,3495055);

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
	$systemAgeMin = "13";
	$systemAgeMax = "60";

	$lockSplitWriteOps = false;

	$config = array(
		'name' => 'live',
		'devutil' => false,

		'bannerlogserver' => '10.0.0.30:',
		'adblasterserver' => '10.0.0.30:5556',

		'templatefilesdir' => "$docRoot/include/templates/template_files/",
		'templateparsedir' => "$sitebasedir/cache/templates/",
		'templateusecached' => true,

		'cachedbs' => true,
		'cacheincludes' => false,

		'allowThreadUpdateEmails' => false, //send subscribed users an email if a thread is updated
		'defaultMessageAllowEmails' => true, // whether or not deliverMsg defaults to allowing emails to be sent

		'jsdir' => '/site_data/javascript/',
		'jsloc' => "http://$staticimgdomain/javascript/",

		'yuiloc' => "http://$staticdomain/Yui/",

		'skindir' => '/site_data/skins/',
		'skinloc' => "http://$staticimgdomain/skins/",

		'bannerloc' => "http://$userimgdomain/banners/", //directory relative to the $staticRoot to save banners.

		'timezone' => 317, //timezone the times should be aligned to

		'title' => 'Nexopia', //website name
		'metadescription' => ' Nexopia.com is one of the largest youth oriented social networking platforms and the number one place for teens to express themselves online. Members use Nexopia to keep in touch with friends, share pictures and videos, blog, and meet exciting new people.',
		'metakeywords' => 'Nexopia.com, nexopia, finding, friends, teens, kids, sharing, blogs, journals, pics, forums, music, Canadian, Canada, social, networking, boys, girls, men, women, high school, junior high, College, University, email, messaging, community, dating, pictures, profile, videos, rate, bands, rock, emo, goth, punk, hiphop, talk, opinions',


		'contactsubjectPrefix' => 'Nexopia Response:', //prefix for emails from the contact us page.
		'copyright' => '© Nexopia.com Inc. 2009 all rights reserved.',
		'email' => 'no-reply@nexopia.com',

		'enableCompression' => true,
		'errorLogging' => 1,
		'showsource' => true, //enables or disables source.php
		'memcached' => true, //Use Memcache?
		'gd2' => true, //which image resize function to use

		'voteHistLength' => 86400*21, //poll vote length

		'smtp_host' => '127.0.0.1', //outgoing email server
		'paypalemail' => 'timo@tzc.com',

		'minAge' => 13, //(current year- minAge) is the first year shown in the signup page.
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

		'forumBannerLoc' => "http://$userimgdomain/forumbanners/",
		'forumBannerHeight' => 130,
		'forumBannerWidth' => 500,
		'foruminactivetime' => 1209600, //time in seconds needed for a thread to be auto-locked. 0 to disable.
		'forumPic' => true, //show user pics in forum

		'basefileloc' => "http://$userfilesdomain/uploads/",
		'filesizelimit' => 204800,
		'filesMaxFileLength' => 25, //maximum length of any filename
		'filesMaxFileSize' => 3145728, //maximum size, in bytes, of any file
		'filesMaxFolderDepth' => 6, //maximum (sub)folder depth allowed
		'filesMaxFolderLength' => 15, //maximum length of a folder name
		'filesMaxFolders' => 100, //maximum number of total folders a user may create
		'filesRestrictExts' => 'php pif com scr bat vbs', //file extensions users may not create
		'quota' => 1048576,

		'imageloc' => "http://$staticimgdomain/images/",
		'smilydir' => '/site_data/smilies/',
		'smilyloc' => "http://$staticimgdomain/smilies/",

		'picText' => 'Nexopia.com',

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

		'picmodexamloc' => "http://$staticimgdomain/picmodexam/", //directory relative to the docroot to save pic mod exam imgs
		'picmodpluserrrate' => 2.5, //maximum error rate required during a week to earn plus
		'picmodpluspicrate' => 2500, //modded pics required during a week to get plus
		'picmodmonthlymin' => 10000, //modded pics required during a month to make top 5

		'passthrough_all_unrecognized' => false, // pass all unrecognized urls to the ruby site.
	);

	include_once("include/errorsyslog.php");
