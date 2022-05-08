<?php
	$forceserver = true;

	$_SERVER['DOCUMENT_ROOT'] = getcwd();

	require_once($_SERVER['DOCUMENT_ROOT'] . "/include/general.lib.php");

	if (count($_SERVER['argv']) < 3)
	{
		echo "Not enough arguments.";
	}

	list($php, $left, $right) = $_SERVER['argv'];

	$leftlines = file($left);
	$rightlines = file($right);

	// figure out which lines have been added
    $added = array_diff($rightlines, $leftlines);
	// figure out which lines have been removed
    $removed = array_diff($leftlines, $rightlines);

//	print_r(array('added' => $added, 'removed' => $removed));

	$newtables = array();
	$newcols = array();
	$newkeys = array();
	foreach ($added as $idx => $addline)
	{
		$addline = rtrim($addline);
		$addinfo = explode(':', $addline);
		$type = $addinfo[0];
		$tableindex = $addinfo[1];

		if ($type == 'table')
		{
			$newtables[$tableindex] = array(
				'type' => $addinfo[2],
				'format' => $addinfo[3],
				'extra' => $addinfo[4],
				'columns' => array(),
				'keys' => array(),
			);
			continue; // move on to the next row (which *should* be the columns and keys of the new table)
		}
		$itemname = $addinfo[2];
		$itemindex = "$tableindex:$itemname";
		if (isset($newtables[$tableindex]))
		{
			$newtables[$tableindex]["{$type}s"][$itemindex] = $idx;
			continue; // since this is (likely) a new table, we just move on.
		}

		if ($type == 'column')
			$newcols["$itemindex"] = $idx;
		else if ($type == 'key')
			$newkeys["$itemindex"] = $idx;
	}

	$altertables = array();
	$altercols = array();

	$deltables = array();
	$delcols = array();
	$delkeys = array();
	foreach ($removed as $idx => $delline)
	{
		$delline = rtrim($delline);
		$delinfo = explode(':', $delline);
		$type = $delinfo[0];
		$tableindex = $delinfo[1];

		if ($type == 'table')
		{
			if (isset($newtables[$tableindex]))
			{
				// if we get here, a table is actually being ALTERed
                $tableinfo = $newtables[$tableindex];
				$altertables[$tableindex] = $tableinfo;
				$newcols = array_merge($newcols, $tableinfo['columns']);
				$newkeys = array_merge($newkeys, $tableinfo['keys']);
				unset($newtables[$tableindex]);
			} else {
				$deltables[$tableindex] = $idx;
			}
			continue;
		}

		$itemname = $delinfo[2];
		$itemindex = "$tableindex:$itemname";
		if (!isset($deltables[$tableindex]))
		{
			if ($type == 'column')
			{
				if (isset($newcols[$itemindex]))
				{
					$altercols[$itemindex] = $newcols[$itemindex];
					unset($newcols[$itemindex]);
				} else {
					$delcols[$itemindex] = $idx;
				}
			} else if ($type == 'key') {
				$delkeys[$itemindex] = $idx;
			}
		}
	}

	// now actually DO stuff with it.
/*    print_r(array(
		'newtables' => $newtables,
		'newcols' => $newcols,
		'newkeys' => $newkeys,
		'deltables' => $deltables,
		'delcols' => $delcols,
		'delkeys' => $delkeys,
		'altertables' => $altertables,
		'altercols' => $altercols
	));*/

	foreach ($newtables as $tableindex => $newtable)
	{
		print("CREATE TABLE $tableindex (\n");
		$count = count($newtable['columns']) + count($newtable['keys']);
		$sofar = 0;

		foreach ($newtable['columns'] as $columnindex => $linenum)
		{
			$name = explode(':', $columnindex);
			$name = array_pop($name);
			$linestr = rtrim($rightlines[$linenum]);
			$line = explode(':', $linestr);
			print(" $name $line[3]");

			if (++$sofar < $count)
				print(",");
			print("\n");
		}
		foreach ($newtable['keys'] as $keyindex => $linenum)
		{
			$name = explode(':', $keyindex);
			$name = array_pop($name);
			$linestr = rtrim($rightlines[$linenum]);
			$line = explode(':', $linestr);

			if ($name == 'PRIMARY')
			{
				print(" PRIMARY KEY $line[4] ($line[5])");
			} else {
				print(" " . ($line[3] == 'unique'? 'UNIQUE' : 'INDEX') . " $name $line[4] ($line[5])");
			}
			if (++$sofar < $count)
				print(",");
			print("\n");
		}
		print(") TYPE = $newtable[type] ROW_FORMAT = $newtable[format] $newtable[extra];\n");
	}
	foreach ($newcols as $columnindex => $linenum)
	{
		$name = explode(':', $columnindex);
		$linestr = rtrim($rightlines[$linenum]);
		$line = explode(':', $linestr);
		$prevlinestr = rtrim($rightlines[$linenum-1]);
		$prevline = explode(':', $prevlinestr);
		$prevname = explode(':', $prevline[2]);
		$prevname = array_pop($prevname);

		print("ALTER TABLE $name[0] ADD COLUMN $name[1] $line[3] ");
		if ($prevline[0] == 'table')
			print(" FIRST;\n");
		else
			print(" AFTER $prevname;\n");
	}

	foreach ($deltables as $tableindex => $linenum)
	{
		print("DROP TABLE $tableindex;\n");
	}

	foreach ($delcols as $colindex => $linenum)
	{
		$name = explode(':', $colindex);
		print("ALTER TABLE $name[0] DROP COLUMN $name[1];\n");
	}

	foreach ($delkeys as $keyindex => $linenum)
	{
		$name = explode(':', $keyindex);

		print("ALTER TABLE $name[0] DROP ");
		if ($name[1] == 'PRIMARY')
			print("PRIMARY KEY;\n");
		else
			print("INDEX $name[1];\n");
	}

	foreach ($newkeys as $keyindex => $linenum)
	{
		$name = explode(':', $keyindex);
		$linestr = rtrim($rightlines[$linenum]);
		$line = explode(':', $linestr);

		print("ALTER TABLE $name[0] ADD ");
		if ($name[1] == 'PRIMARY')
			print("PRIMARY KEY /* $line[4] */ ($line[5]);\n");
		else
			print(($line[3] == 'unique'? 'UNIQUE' : 'INDEX') . " $name[1] /* $line[4] */ ($line[5]);\n");
	}

	foreach ($altertables as $tableindex => $newtable)
	{
		print("ALTER TABLE $tableindex TYPE = $newtable[type] ROW_FORMAT = $newtable[format] $newtable[extra];\n");
	}
	foreach ($altercols as $colindex => $linenum)
	{
		$name = explode(':', $colindex);
		$linestr = rtrim($rightlines[$linenum]);
		$line = explode(':', $linestr);

		print("ALTER TABLE $name[0] MODIFY COLUMN $name[1] $line[3];\n");
	}
?>
