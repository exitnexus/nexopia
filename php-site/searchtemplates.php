<?

	$forceserver = true;
	$login=1;
	$devutil = true;

	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");

	incHeader(false);

	$search = getPOSTval('search');
	$regex = getPOSTval('regex', 'bool');
	$highlight = getPOSTval('highlight', 'bool');

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header align=center colspan=2>Search:</td></tr>";
	echo "<tr><td class=body>Search for:<input class=body type=text name=search value='" . htmlentities($search) . "'></td></tr>";
	echo "<tr><td class=body>" . makeCheckBox('regex', 'Use Regex', $regex) . "</td></tr>";
	echo "<tr><td class=body>" . makeCheckBox('highlight', 'Use Syntax Highlighting', $highlight) . "</td></tr>";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Search></td></tr>";
	echo "</form>";
	echo "</table>\n";

	if($action){
		$basedir = "$sitebasedir/templates";

		$results = search("", $search, $regex);

		sortCols($results, SORT_ASC, SORT_NUMERIC, 'linenum', SORT_ASC, SORT_STRING, 'file');

		echo "<table>";
		echo "<tr><td class=header colspan=2>found " . count($results) . " results</td></tr>";

		$classes = array('body2','body');
		$i=0;

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
				echo "<td class=" . $classes[$i] . ">" . str_replace("$search", "<b>$search</b>", htmlentities($row['line'])) . "</td>";
			}
			echo "</tr>\n";
		}

		echo "</table>";
	}

	incFooter();

function search($directory, $search, $regex){
	global $basedir;

	$results = array();

	$dir = opendir($basedir . $directory);

	while($item = readdir($dir)) {
		if($item{0} == '.') // skip hidden files
			continue;

		if(is_dir($basedir . $directory . "/" . $item)){
			$results = array_merge($results,  search($directory . "/" . $item, $search, $regex));
		}elseif(strrchr($item, ".") == ".html"){
			$file = file($basedir . $directory . "/" . $item); //returns as an array of lines

			foreach($file as $i => $line){
				$i++;

				$line = trim($line);
				if(!$line)
					continue;

				if($regex)
					$found = (bool) preg_match($search, $line);
				else
					$found = (stripos($line, $search) !== false);

				if($found)
					$results[] = array('file' => ($directory ? $directory . "/" : '') . $item, 'linenum' => $i, 'line' => $line);
			}
			unset($file, $line);
		}
	}
	
	return $results;
}
