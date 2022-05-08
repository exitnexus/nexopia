<?

	$forceserver = true;
	$login = 1;
	$devutil = true;

	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");


	incHeader(false);

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

					$found = (($pos = strpos($line, '$cache->')) !== false);

					if($found){
						$linepart = substr($line, $pos);
						$cmd = substr($linepart, 0, strpos($linepart, '('));
						$parts = explode(',', substr($linepart, strpos($linepart, '(')));
					
//					echo "$linepart .... $cmd .... " . implode(",,,", $parts) . "<br>\n";

						switch($cmd){
							case '$cache->put':
							case '$cache->remove':
							case '$cache->get':
							case '$cache->hdget':
							
								$prefix = $parts[0];
								break;
							
							case '$cache->get_multi':
								$prefix = $parts[1];
								break;
							
							default:
								$found = false;
						}

						if(!$found)
							continue;


						if($pos = strpos($prefix, '//'))
							$prefix = substr($prefix, 0, $pos);

						$prefix = trim($prefix, " 	();");
						$prefix = str_replace("'", '"', $prefix);
						
						$results[] = array('file' => ($directory ? $directory . "/" : '') . $item, 'linenum' => $i, 'line' => $line, 'prefix' => $prefix);
					}
				}
				unset($file, $line);
			}
		}
	}

	sortCols($results, SORT_ASC, SORT_NUMERIC, 'linenum', SORT_ASC, SORT_STRING, 'file', SORT_ASC, SORT_STRING, 'prefix');
//	sortCols($results, SORT_ASC, SORT_STRING, 'prefix');

	echo "<table>";

	$classes = array('body2','body');
	$i=0;

	echo "<tr><td class=header colspan=3>found " . count($results) . " results</td></tr>";

	$last = "";
	foreach($results as $row){

		echo "<tr>";
		echo "<td class=" . $classes[$i = !$i] . " nowrap>$row[file] : $row[linenum]</td>";
		echo "<td class=" . $classes[$i] . ">" . htmlentities($row['prefix']) . "</td>";
		if($highlight){
			echo "<td class=" . $classes[$i] . ">" . highlight_string($row['line'], true) . "</td>";
		}else{
			echo "<td class=" . $classes[$i] . ">" . htmlentities($row['line']) . "</td>";
		}
		echo "</tr>\n";
	}

	echo "</table>";

	incFooter();

