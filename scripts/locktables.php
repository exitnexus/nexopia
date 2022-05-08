#!/usr/bin/php
<?

	$forceserver=true;
	$errorLogging=true;

	$_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'] = ".www.nexopia.com";
	chdir("public_html");
	
	require_once("/include/general.lib.php");

set_time_limit(0);

	$locktime = 50;
	$waittime = 0;

	$time1 = time();

	$i = 0;

	while(time() - $time1 < 60){

		$db->query("LOCK TABLES users WRITE");

		usleep($locktime*1000);

		$db->query("UNLOCK TABLES");

		usleep($waittime*1000);

		$i++;
	}

	echo "blocked $i times\n";


