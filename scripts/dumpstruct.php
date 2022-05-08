<?

	$forceserver = true;

	$_SERVER['DOCUMENT_ROOT'] = getcwd();

	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

	$tables = array();

	$names = array_keys($dbs);

	$include_dbname = true;

	foreach($names as $dbname){
		list($dbs[$dbname]) = $dbs[$dbname]->getSplitDBs();
		$tableresult = $dbs[$dbname]->listtables();

		while($table = $dbs[$dbname]->fetchrow($tableresult)){
			$tname = $table['Name'];
			$tableindex = ($include_dbname? "$dbname.$tname" : $tname);

			$engine = isset($table['Engine'])? $table['Engine'] : $table['Type'];
			$rowformat = $table['Row_format'];
			$options = $table['Create_options'];

			$tablestring = "table:$tableindex:$engine:$rowformat:$options\n";
			$columnstring = "";
			$columnresult = $dbs[$dbname]->query(null, "SHOW COLUMNS FROM `$tname`");
			while ($column = $dbs[$dbname]->fetchrow($columnresult))
			{
				$columnstring .= "column:$tableindex:$column[Field]:$column[Type] ";
				if (!$column['Null'])
					$columnstring .= "NOT NULL ";

				if ($column['Default'])
					$columnstring .= $dbs[$dbname]->prepare("DEFAULT ? ", $column['Default']);

				if ($column['Extra'])
					$columnstring .= $column['Extra'];

				$columnstring .= "\n";
			}

			$keyresult = $dbs[$dbname]->query(null, "SHOW INDEX FROM `$tname`");
			$keys = array();
			$keydetails = array();
			$pkey = array();
			$ukeys = array();
			$ikeys = array();
			while ($key = $dbs[$dbname]->fetchrow($keyresult))
			{
				$keyname = $key['Key_name'];
				if (!isset($keys[$keyname]))
					$keys[$keyname] = array();

				$keys[$keyname][ $key['Seq_in_index'] - 1 ] = "$key[Column_name] " . ($key['Collation'] == 'A'? 'ASC' : 'DESC');

				if (!isset($keydetails[$keyname]))
				{
					$keydetails[$keyname] = array(
						'unique' => !$key['Non_unique'],
						'type' => $key['Index_type']
					);
					if ($keyname == "PRIMARY")
						$pkey[] = $keyname;
					else if ($key['Non_unique'])
						$ikeys[] = $keyname;
					else
						$ukeys[] = $keyname;
				}
			}
			sort($ukeys);
			sort($ikeys);
			$keynames = array_merge($pkey, $ukeys, $ikeys);

			$keystring = "";
			foreach ($keynames as $keyname)
			{
				$columns = $keys[$keyname];

				$keystring .= "key:$tableindex:$keyname:";
				$keystring .= ($keydetails[$keyname]['unique']? "unique" : "nonunique");
				$keystring .= ":{$keydetails[$keyname]['type']}";
				$keystring .= ":" . implode(',', $columns) . "\n";
			}

			$tablestring .= $columnstring . $keystring;

			if (isset($tables[$tableindex]) && $tablestring != $tables[$tableindex])
			{
				echo "Duplicate but non-compatible table found: $dbname.$tname";
				exit(1);
			}
			$tables[$tableindex] = $tablestring;
		}
	}

	ksort($tables);

    echo implode("", $tables);
?>
