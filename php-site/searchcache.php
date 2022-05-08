<?

	$forceserver = true;
	$login=1;
	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");


	incHeader(false);

	$search = getPOSTval('search');
	$highlight = getPOSTval('highlight', 'bool');

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header align=center colspan=2>Search for cache keys named:</td></tr>";
	echo "<tr><td class=body>Key:<input class=body type=text name=search value='$search'></td></tr>";
	echo "<tr><td class=body>" . makeCheckBox('highlight', 'Use Syntax Highlighting', $highlight) . "</td></tr>";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Search></td></tr>";
	echo "</form>";
	echo "</table>\n";

	if($action){
		$basedir = $docRoot;
		$directories = array(	"/",
								"/skins/",
								"/include/",
							);

		$searches = explode(',', $search);

		foreach($searches as $k => $v)
			$searches[$k] = trim($v);

		$results = array();
		foreach($directories as $directory){

			if(substr($directory,-1)=="/")
				$directory=substr($directory,0,-1);

			$dir = opendir($basedir . $directory);

			while($item = readdir($dir)) {
				if(!is_dir($basedir . $directory . "/" . $item) && strrchr($item, ".") == ".php") {


					$file = file($basedir . $directory . "/" . $item); //returns as an array of lines

					foreach($file as $i => $line){
						$i++;

						$line = trim($line);
						if(!$line)
							continue;

						$found = (strpos($line, '$cache->') !== false);

						if($found && count($searches))
							foreach($searches as $search)
								$found &= (strpos($line, $search) !== false);

						if($found)
							$results[] = array('file' => ($directory ? $directory . "/" : '') . $item, 'linenum' => $i, 'line' => $line);
					}
					unset($file, $line);
				}
			}
		}

		sortCols($results, SORT_ASC, SORT_NUMERIC, 'linenum', SORT_ASC, SORT_STRING, 'file');

		echo "<table>";

		$classes = array('body2','body');
		$i=0;

		echo "<tr><td class=header colspan=2>found " . count($results) . " results</td></tr>";

		$last = "";
		foreach($results as $row){

			if($row['file'] != $last){
				echo "<td colspan=2>&nbsp;</td>\n";
				echo "<tr><td class=header colspan=2>$row[file]</td></tr>\n";
				$last = $row['file'];
			}

			echo "<tr>";
			echo "<td class=" . $classes[$i = !$i] . ">$row[linenum]:&nbsp;</td>";
			if($highlight){
				echo "<td class=" . $classes[$i] . ">" . highlight_string($row['line'], true) . "</td>";
			}else{
				echo "<td class=" . $classes[$i] . ">$row[line]</td>";
			}
			echo "</tr>\n";
		}

		echo "</table>";
	}

	incFooter();

