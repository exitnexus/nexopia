<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
	$errorLogging = false;
	require_once("include/general.lib.php");

	$debuginfousers[] = 2309088; // Add rbroemeling to the list of debuginfousers.
	
	if(!in_array($userData['userid'], $debuginfousers))
		die("error");

	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
//	ignore_user_abort(true);

	var_dump($mods->getModLvl(2350, 31));

	$queries = array(

	);

	foreach ($usersdb->getSplitDBs() as $userdb){
		foreach($queries as $query){
			$userdb->query($query);
		}
	}

//	$archive->createtable(time());
//	updateUserIndexes();
//	updateSpotlightList();

	echo "\n<br>\n<br>Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $times['start'])/10000,4);
	echo "Run-time $dtime seconds<br>\n";

	outputQueries();
