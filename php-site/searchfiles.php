<?

	$login=1;

	require_once("include/general.lib.php");

	if(!in_array($userData['userid'], $debuginfousers))
		die("error");

	if(!$mods->isAdmin($userData['userid'])){
		header("location: /");
		exit;
	}

	incHeader();

	if(!isset($search))
	$search="";

	if($search!=""){
		$found = searchCode($search,$docRoot);

		echo "<table>";
		echo "<tr><td class=header>File</td><td class=header>Line number</td></tr>";
		foreach($found as $item)
			echo "<tr><td class=body>$item[file]</td><td class=body>$item[linenum]</td></tr>";
		echo "</table>";
	}

	echo "<table><form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=body>Search for:</td><td class=body><input class=body type=text name=search value=\"$search\"></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Search></td></tr>";
	echo "</form></table>";

	incFooter();

