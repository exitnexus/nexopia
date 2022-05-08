<?

	$forceserver = true;

	$_SERVER['DOCUMENT_ROOT'] = "/home/nexopia/public_html";

	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

	$tables = array();

	$names = array_keys($dbs);

	foreach($names as $dbname){
		$tableresult = $dbs[$dbname]->listtables();

		while(list($tname) = $dbs[$dbname]->fetchrow($tableresult, DB_NUM)){
			$result = $dbs[$dbname]->query("SHOW CREATE TABLE `$tname`");
			$output = $dbs[$dbname]->fetchfield(1,0,$result);

			$tables["$dbname.$tname"] = "-- $dbname.$tname\n$output";
		}
	}

	ksort($tables);

	echo implode("\n\n--------------------------------------------------------\n\n", $tables);
	echo "\n\n";
