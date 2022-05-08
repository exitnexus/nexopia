<?

	$forceserver = true;
	$login=1;
	$devutil = true;

	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");


	incHeader(false);

	$linetypes = array( 'parsed' => "Full Parsed (slow, accurate)",
						'multiline' => "Joined Lines (faster, lots of crap)" ,
						'line' => "Single Lines (fastest, no multi-line queries)",
					);

	$searchtable = getPOSTval('searchtable');
	$search = getPOSTval('search');
	$highlight = getPOSTval('highlight', 'bool');
	$cached = getPOSTval('cached', 'bool', false);
	$linetype = getPOSTval('linetype');

	if(!isset($linetypes[$linetype]))
		$linetype = current($linetypes);

?>
<style>
td.bodyselected			{ background-color: #FFCC99; color: #000000; font-family: arial; font-size: 8pt}
td.body2selected		{ background-color: #FFCC99; color: #000000; font-family: arial; font-size: 8pt}

td.bodyhighlight		{ background-color: #CCFFCC; color: #000000; font-family: arial; font-size: 8pt}
td.body2highlight		{ background-color: #CCFFCC; color: #000000; font-family: arial; font-size: 8pt}

.selected		{ background-color: #FFCC99 }
.highlight		{ background-color: #CCFFCC }

</style>
<script>

var stripe = function() {

	var table = document.getElementById("output");

	var tbodies = table.getElementsByTagName("tbody");

	for (var h = 0; h < tbodies.length; h++) {
		var trs = tbodies[h].getElementsByTagName("tr");

		for (var i = 0; i < trs.length; i++) {

			var tds = trs[i].getElementsByTagName("td");

			for (var j = 0; j < tds.length; j++)
				if(tds[j].className != "" && tds[j].className != "header")
					tds[j].classNameOld = tds[j].className;


			trs[i].onmouseover=function(){
				var tds = this.getElementsByTagName("td");

				for (var j = 0; j < tds.length; j++)
					if(tds[j].className != "" && tds[j].className != "header"  && tds[j].className != tds[j].classNameOld + "selected")
						tds[j].className += "highlight";

				return false
			}

			trs[i].onmouseout=function(){
				var tds = this.getElementsByTagName("td");

				for (var j = 0; j < tds.length; j++)
					if(tds[j].className != "" && tds[j].className != "header" && tds[j].className != tds[j].classNameOld + "selected")
						tds[j].className = tds[j].classNameOld;

				return false
			}

			trs[i].onclick=function(){
				var tds = this.getElementsByTagName("td");

				for (var j = 0; j < tds.length; j++){
					if(tds[j].className != "" && tds[j].className != "header"){
						if(tds[j].className == tds[j].classNameOld + "selected"){
							tds[j].className = tds[j].classNameOld;
						}else{
							tds[j].className = tds[j].classNameOld + "selected";
						}
					}
				}

				return false
			}
		}
	}
}

window.onload = stripe;

</script>
<?

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header align=center colspan=2>Search for queries with tables:</td></tr>";
	echo "<tr><td class=body>Tables:</td><td class=body><input class=body type=text name=searchtable value='" . htmlentities($searchtable) . "'> (comma separated for and, pipe for or)</td></tr>";
	echo "<tr><td class=body>Search:</td><td class=body><input class=body type=text name=search value='" . htmlentities($search) . "'></td></tr>";
	echo "<tr><td class=body>Line Definiton:</td><td class=body><select class=body name=linetype>" . make_select_list_key($linetypes, $linetype) . "</select></td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('highlight', 'Use Syntax Highlighting', $highlight) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('cached', 'Use Cached Version', $cached) . "</td></tr>";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit value=Search></td></tr>";
	echo "</form>";
	echo "</table>\n";

	if($searchtable){
		$basedir = $docRoot;
		$directories = array(	"/",
								"/skins/",
								"/include/",
							);

		$tables = explode(',',$searchtable);

		foreach($tables as $k => $v)
			$tables[$k] = trim($v);

		sort($tables);


		$results = array();

		$searchtablekey = "searchtables-$linetype-" . implode(',',$tables);

		if($cached){
			$results = $cache->get($searchtablekey);

			if(!$results)
				$results = array();
		}

		if(!count($results)){


			foreach($directories as $directory){

				if(substr($directory,-1)=="/")
					$directory=substr($directory,0,-1);

				$dir = opendir($basedir . $directory);

				while($item = readdir($dir)) {
					if(!is_dir($basedir . $directory . "/" . $item) && strrchr($item, ".") == ".php") {

						switch($linetype){
							case 'parsed':
								//ignores comments, includes if/while/foreach/etc if it doesn't open a new block

								$str = file_get_contents($basedir . $directory . "/" . $item); //returns as one big string

								$len = strlen($str);
								$line = "";
								$linenum = 0;
								$i = -1;
								$startlinenum = $linenum;

								$quotes = 0;
								$comment = 0;
								$php = 0;

								$file = array();

								while(++$i < $len){
									$chr = $str{$i};

								//count lines
									if($chr == "\n"){
										$linenum++;
										if($comment == 1)
											$comment = 0;
										continue;
									}

								//ignore whitespace
								//	if($chr == ' ' || $chr == "\t" || $chr == "\r")
								//		continue;

								//start php
									if(!$php && $chr == '<' && $str{$i+1} == '?'){
										$i++;
										$php = true;
										continue;
									}

									if(!$php)
										continue;

								//ignore stuff up to the end of a comment
									if($comment){
										if($comment == 2 && $chr == '*' && $str{$i+1} == '/'){ //end comment
											$comment = 0;
											$i++;
										}

										continue;
									}

								//find single line comments
									if(!$quotes && !$comment && ($chr == '#' || ($chr == '/' && $str{$i+1} == '/'))){
										$comment = 1;
										continue;
									}

								//find multi-line comments
									if(!$quotes && $chr == '/' && $str{$i+1} == '*'){
										$comment = 2;
										$i++;
										continue;
									}

								//if not in a comment, but in php, it's worth recording, line starts here
									if(trim($line) == '')
										$startlinenum = $linenum;

									$line .= $chr;

								//start quotes
									if(!$quotes){
										if($chr == "'"){
											$quotes = 1;
											continue;
										}
										if($chr == '"'){
											$quotes = 2;
											continue;
										}
									}

								//end quotes
									if($quotes){
										if($quotes == 1 && $chr == "'" && $str{$i-1} != '\\'){
											$quotes = 0;
											continue;
										}
										if($quotes == 2 && $chr == '"' && $str{$i-1} != '\\'){
											$quotes = 0;
											continue;
										}
									}

								//end lines
									if($chr == ';' || $chr == '{' || $chr == '}'){
										if(!isset($file[$startlinenum]))
											$file[$startlinenum] = "";

										$file[$startlinenum] .= " $line";
										$line = "";
									}
								}

								break;

							case 'multiline':
								//includes alot of crap when there is a comment at the end of a line

								$contents = file($basedir . $directory . "/" . $item); //returns as an array of lines

								$starti = 0;
								$str = "";
								$file = array();

								foreach($contents as $i => $line){

									$line = trim($line);

									if(!$str)
										$starti = $i;

									$str .= " $line";

									$end = substr($line, -1);

									if($end == ';' || $end == '{' || $end == '}'){
										$file[$starti] = $str;
										$str = "";
									}
								}
								$file[$starti] = $str;

								break;

							case 'line':
								//misses multi-line queries
								$file = file($basedir . $directory . "/" . $item);
						}

						foreach($file as $i => $line){
							$i++;

							$line = trim($line);
							if(!$line)
								continue;

							$found = true;

							foreach($tables as $table){
								if(!$found)
									break;

								$patterns = array(
										"/['\"]SELECT[^;]+FROM[^;]*[,\.\s`](" . $table. ")([`\s]?|[`,\s][^;]*)['\"]/", // SELECT ... FROM ..., need to deal with joins, multi-line queries
										"/['\"]INSERT[^;]*[,\.\s`](" . $table. ")[`,\s][^;]*['\"]/", //INSERT ...
										"/['\"]UPDATE[^;]*[,\.\s`](" . $table. ")[`,\s][^;]*SET[^;]*['\"]/", // UPDATE ... SET ..., deal with joins
										"/['\"]DELETE[^;]*FROM[^;]*[,\.\s`](" . $table. ")([`\s]?|[`,\s][^;]*)['\"]/", // DELETE ... FROM ..., need to deal with joins
										"/['\"]TRUNCATE[^;]*[\.\s`](" . $table. ")[`\s]['\"]/", // TRUNCATE TABLE ...
									);

								$foundlocal = false;

								foreach($patterns as $pattern){
									$foundlocal |= preg_match($pattern, $line);
									if($foundlocal)
										break;
								}
								unset($patterns);

								$found &= $foundlocal;
							}
							if($found)
								$results[] = array('file' => ($directory ? $directory . "/" : '') . $item, 'linenum' => $i, 'line' => $line);
						}
						unset($file, $line);
					}
				}

				$cache->put($searchtablekey, $results, 3600*6);
			}
		}

		sortCols($results, SORT_ASC, SORT_NUMERIC, 'linenum', SORT_ASC, SORT_STRING, 'file');

		echo "<table id=output>";

		$classes = array('body2','body');
		$i=0;

		echo "<tr><td class=header colspan=2>found " . count($results) . " results</td></tr>";

		$last = "";
		foreach($results as $row){

			if($search && strpos($row['line'], $search) === false)
				continue;

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
				foreach($tables as $table)
					$row['line'] = preg_replace("/([,\.\s`])($table)(([`\s]?|[`,\s][^;]*)['\"])/", "\\1<b>\\2</b>\\3", $row['line']);
				echo "<td class=" . $classes[$i] . ">$row[line]</td>";
			}
			echo "</tr>\n";
		}

		echo "</table>";
	}

	incFooter();


