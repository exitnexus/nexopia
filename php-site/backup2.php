<?

	$forceserver = true;
	$login=1;

	require_once("include/general.lib.php");

	if($userData['userid'] != 1)
		die("bad permissions");

	echo str_repeat(" ",400). "\n";
	zipflush();
	set_time_limit(0);


	$backupdb = & new sql_db($databases['backup']['host'], $databases['backup']['login'], $databases['backup']['passwd'], $databases['backup']['db']);

	$tables = array();

	$tableresult = $backupdb->listtables();
	while (list($name) = $backupdb->fetchrow($tableresult,DB_NUM))
		$tables[] = "`$name` WRITE";

	$tables2 = array();

	$tableresult = $fastdb->listtables();
	while (list($name) = $fastdb->fetchrow($tableresult,DB_NUM))
		$tables2[] = "`$name` WRITE";


	$backupdb->query("SET wait_timeout = 1000");

	$backupdb->query("LOCK TABLES " . implode(", ", $tables));
	echo "backup locked<br>\n";
	zipflush();

	$fastdb->query("LOCK TABLES " . implode(", ", $tables2));
	echo "fastdb locked<br>\n";
	zipflush();

	$fastdb->backup("$sitebasedir/backup/good/fastdb",2);

	$fastdb->query("UNLOCK TABLES");

	$backupdb->backup("$sitebasedir/backup/good/db",2);

	$backupdb->query("UNLOCK TABLES");

	$fastdb->outputQueries("Fast");
	$backupdb->outputQueries("Backup");
