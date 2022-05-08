<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
//	$errorLogging = false;
	require_once("include/general.lib.php");

	if($userData['userid']!=5 && $userData['userid']!=1)
		die("error");


	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
	ignore_user_abort(true);

	$db2 = new sql_db("192.168.0.231", "root", 'pRlUvi$t', "nexopia2");
	$newdb = new sql_db("192.168.0.231", "root", 'pRlUvi$t', "nexopia");

	$tables = array();
	$skiptables = array();

    $tableresult = $db2->listtables();
    while(list($name) = $tableresult->fetchrow(DB_NUM))
		$skiptables[] = $name;

    $tableresult = $db->listtables();
    while(list($name) = $tableresult->fetchrow(DB_NUM))
		$tables[] = $name;


	$res = $newdb->query("SHOW TABLE STATUS");

	$tablestatus = array();
	while($line = $res->fetchrow())
		$tablestatus[$line['Name']] = $line;


	$time = time();
	foreach($tables as $name){
		if(in_array($name, $skiptables) || $tablestatus[$name]['Rows'] > 0)
			continue;

		$result = $db->query("SHOW CREATE TABLE `$name`");
		$create = $result->fetchfield(1,0);

		$create = str_replace("InnoDB", "MyISAM", $create);

		echo "Copying $name ... ";
		zipflush();

		$newdb->query($create);

		$newdb->query("INSERT INTO nexopia.$name SELECT * FROM enternexus.$name");

		$time1 = time();
		echo "done " . ($time1 - $time) . " secs<br>";
		$time = $time1;
	}

	echo "\n<br>Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $startTime)/10000,4);
	echo "Run-time $dtime seconds<br>\n";

	outputQueries();

