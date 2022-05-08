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
//	ignore_user_abort(true);


	foreach($databases as $name => $testdb){

		if(!isset($databases[$name]['insert']))
			continue;

		$checkdb = new sql_db($databases[$name]['insert']);

		$res = $checkdb->query("SHOW TABLE STATUS");

		$inserttablestatus = array();
		while($line = $res->fetchrow())
			$inserttablestatus[$line['Name']] = $line['Rows'];


		$selecttablestatus = array();
		foreach($databases[$name]['select'] as $id => $testdb){
			$checkdb = new sql_db($databases[$name]['select'][$id]);

			$res = $checkdb->query("SHOW TABLE STATUS");

			while($line = $res->fetchrow())
				$selecttablestatus[$id][$line['Name']] = $line['Rows'];


			foreach($selecttablestatus[$id] as $tablename => $num){
				if($inserttablestatus[$tablename] != $num)
					echo "!!!!ERROR!!!! Insert: $inserttablestatus[$tablename], Select $id: $num<br>\n";
			}

		}

	echo "<br><br>";

		echo "insert$name<br>";
		print_r($inserttablestatus);
		echo "<br><br>";
		echo "select$name<br>";
		print_r($selecttablestatus);


	}




