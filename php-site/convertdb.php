<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
//	$errorLogging = false;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");

	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
	ignore_user_abort(true);

	$ignore = array();//'agegroups', 'picstop', 'picsvotable', 'spotlight', 'userclusters');

//	$convertdb = & new sql_db("192.168.0.8", "root", 'pRlUvi$t', "nexopia");
	$convertdb = & new sql_db(array("host" => "localhost",
									"login" => "root",
									"passwd" => "Hawaii",
									"db" => "nexopia" ));

	$tables = array();

    $tableresult = $convertdb->listtables();
    while(list($name) = $convertdb->fetchrow($tableresult,DB_NUM))
    	if(!in_array($name, $ignore))
			$tables[] = $name;

	$time = time();
	foreach($tables as $name){

		$result = $convertdb->query("SHOW CREATE TABLE `$name`");
		$create = $convertdb->fetchfield(1,0,$result);
		if(stristr($create, "myisam"))
			continue;

		echo "Converting $name ... ";
		zipflush();

		$convertdb->query("ALTER TABLE `$name` TYPE = MyISAM");

		$time1 = time();
		echo "done " . ($time1 - $time) . " secs<br>";
		$time = $time1;
	}

	echo "\n<br>Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $startTime)/10000,4);
	echo "Run-time $dtime seconds<br>\n";

	outputQueries();

