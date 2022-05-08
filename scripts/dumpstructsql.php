<?

	$forceserver = true;

	$_SERVER['DOCUMENT_ROOT'] = getcwd();

	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

	$tables = array();

	$names = array_keys($dbs);

	foreach($names as $dbname){
		$tableresult = $dbs[$dbname]->listtables();

		while(list($tname) = $dbs[$dbname]->fetchrow($tableresult, DB_NUM)){
			$result = $dbs[$dbname]->query(null, "SHOW CREATE TABLE `$tname`");
			$output = $result->fetchrow(DB_NUM);

			$tables["$dbname.$tname"] = "-- $dbname.$tname\n$output[1]";
		}
	}

	ksort($tables);

	echo implode("\n\n--------------------------------------------------------\n\n", $tables);
	echo "\n\n";
