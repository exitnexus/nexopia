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
//	ignore_user_abort(true);

	$convertdb = & new sql_db("192.168.0.231", "root", 'pRlUvi$t', "nexopia2");

	$tables = array();

    $tableresult = $convertdb->listtables();
    while(list($name) = $convertdb->fetchrow($tableresult,DB_NUM))
		$tables[] = $name;


	$convertdb->query("SHOW TABLE STATUS");

	$tablestatus = array();
	while($line = $convertdb->fetchrow())
		$tablestatus[$line['Name']] = $line;

	$time = time();
	foreach($tables as $name){
		if($tablestatus[$name]['Rows'] > 0)
			continue;

		echo "Copying $name ... ";
		zipflush();

		$convertdb->query("INSERT INTO nexopia2.$name SELECT * FROM enternexus.$name");

		$time1 = time();
		echo "done " . ($time1 - $time) . " secs<br>";
		$time = $time1;
	}

	echo "\n<br>Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $startTime)/10000,4);
	echo "Run-time $dtime seconds<br>\n";

	outputQueries();

