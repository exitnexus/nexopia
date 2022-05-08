<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"errorlog"))
		die("Permission denied");

	$mods->adminlog('error log',"error log");

	incHeader();

	$filename = "$sitebasedir/logs/site/errors.csv";

	if(file_exists($filename)){

		$errfile=fopen($filename,"r");

		$rows = array();
		while($line = fgetcsv($errfile,2048))
			$rows[] = $line;

		fclose($errfile);

		$total = count($rows);

		if(!isset($num))
			$num=100;

		if(!isset($offset) || $offset > $total)
			$offset = $total - $num;
		if($offset < 0)
			$offset = 0;

		$end = $offset + $num;
		if($end > $total)
			$end = $total;

		echo "<table width=100%>";
		echo "<tr><td class=header colspan=4>";
		echo "<table><form action=$_SERVER[PHP_SELF]><tr>";
		echo "<td class=header>";
		echo "<input class=body type=button onClick=\"location.href='$_SERVER[PHP_SELF]?num=$num&offset=0'\" value=First>";
		echo "<input class=body type=button onClick=\"location.href='$_SERVER[PHP_SELF]?num=$num&offset=" . ($offset-$num) . "'\" value=Prev>";
		echo "</td><td class=header>";
		echo "Show <input class=body type=text name=num value=$num size=5> rows starting at <input class=body type=text name=offset value=$offset size=5> of $total rows<input class=body type=submit value=Go>";
		echo "</td><td class=header>";
		echo "<input class=body type=button onClick=\"location.href='$_SERVER[PHP_SELF]?num=$num&offset=" . ($offset+$num) . "'\" value=Next>";
		echo "<input class=body type=button onClick=\"location.href='$_SERVER[PHP_SELF]?num=$num&offset=" . ($total-$num) . "'\" value=Last>";
		echo "</td>";
		echo "</tr></form></table>";
		echo "</td></tr>";
		echo "<tr><td class=header>Date</td><td class=header>File</td><td class=header>Error</td><td class=header>Page</td></tr>\n";

		for($i = $offset; $i < $end; $i++){
			list($time,$file,$err,$ip,$page) = $rows[$i];
			echo "<tr><td class=body nowrap>$time</td><td class=body nowrap>$file</td><td class=body nowrap>$err</td><td class=body nowrap>$page</td></tr>\n";
		}
		echo "</table>";
	}else
		echo "No error file";

	incFooter(array('incAdminBlock'));
