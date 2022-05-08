<?

	$login = 1;

	require_once("include/general.lib.php");

	if($userData['userid'] != 1)
		die("Permission Denied");


	$db->prepare_query("SELECT users.username, admin.* FROM admin, users WHERE admin.userid = users.userid ORDER BY username");

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	incHeader();

	$cols = 0;

	echo "<table cellspacing=1 cellpadding=2>";

	$classes = array("body2","body");

	foreach($rows as $row){
		if(!$cols){
			echo "<tr>";
			foreach($row as $name => $val)
				if($name != 'id')
					echo "<td class=header>$name</td>";
			echo "</tr>";
			$cols = count($row)-1;
		}
		$i = !$i;
		echo "<tr>";
		foreach($row as $name => $val){
			switch($name){
				case "username":
				case "userid":
					echo "<td class=$classes[$i]>$val</td>";
				case "id":
					break;
				default:
					echo "<td class=$classes[$i]>" . ($val == 'y' ? 'y' : '') . "</td>";
			}
		}
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=$cols>" . count($rows) . " admins</td></tr>";
	echo "</table>";

	incFooter();

