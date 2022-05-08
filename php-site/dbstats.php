<?

	$forceserver = true;
	$enableCompression = true;
//	$errorLogging = false;
	require_once("include/general.lib.php");



	$convertdb = new sql_db("192.168.0.234", "root", 'pRlUvi$t', "enternexus");

	$res = $convertdb->query("SHOW INNODB STATUS");

	echo "<pre>";

	echo $res->fetchfield();

	echo "</pre>";


