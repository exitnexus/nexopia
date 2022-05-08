<?php

	$forceserver=true;
	$errorLogging=true;
	$localforce=true;

	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = ".www.nexopia.com";

	chdir("/home/nexopia/public_html/");

	require_once "include/general.lib.php";


	set_time_limit(0);



	$commentarchives = array(
		'commentsarchive',
		'commentsarchivenew',
		);

	$msgarchives = array(
		'msgarchive',
		'msgarchive200502',
		'msgarchive200503',
		'msgarchive200504',
		'msgarchive200505',
		'msgarchive200506',
		'msgarchive200507',
		'msgarchive200508',
		'msgarchive200509',
		'msgarchivenew',
		);

	$uids = array(1073503, 1071737, 1241083);

	$dbase = new sql_db(array( 'host' => '10.0.0.16', 'login' => 'root', 'passwd' => 'pRlUvi$t', 'db' => 'nexopiaarchive'));

	foreach($commentarchives as $table)
		$dbase->prepare_query("INSERT INTO commentsdump SELECT * FROM $table WHERE `to` IN (#) || `from` IN (#)", $uids, $uids);

	foreach($msgarchives as $table)
		$dbase->prepare_query("INSERT INTO msgdump SELECT * FROM $table WHERE `to` IN (#) || `from` IN (#)", $uids, $uids);


