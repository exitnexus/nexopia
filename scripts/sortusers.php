#!/usr/local/php/bin/php
<?

	$forceserver=true;
	$errorLogging=true;
	
	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = ".www.nexopia.com";
	chdir("public_html");
	
	require_once "include/general.lib.php";
	
	$tables = include("../tableinfo.php");
	
	$dbobjs = $usersdb->getSplitDBs();
	
	$timer = new timer();
	$timer->start("script start - " . gmdate("F j, g:i a T"));
	
	foreach($dbobjs as $dbname => & $sortdb){
		echo $timer->lap("Starting database $dbname");
	
		foreach($tables as $table => $uid){
			echo $timer->lap("$table");
			$sortdb->query("ALTER TABLE `$table` ORDER BY $uid");
		}
	
		echo "\n";
	}
	
	
	echo $timer->stop();

