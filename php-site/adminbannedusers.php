<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"listbannedusers"))
		die("Permission denied");


	if($mods->isAdmin($userData['userid'],"banusers") && $action){
		$check = getPOSTval('check','array');
		if($check){
			$db->prepare_query("DELETE FROM bannedusers WHERE banned IN (?)", $check);

			$msgs->addMsg("Users unbanned");
		}
	}

	$res = $db->query("SELECT * FROM bannedusers ORDER BY date, banned");
	
	$uids = array();
	$rows = array();
	
	while($line = $res->fetchrow()){
		$rows[] = $line;
//		$uids[$line['userid']] = $line['userid'];
		$uids[$line['modid']] = $line['modid'];
	}
	
	$users = getUserName($uids);

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header>IP/Email</td>";
	echo "<td class=header>UserID</td>";
	echo "<td class=header>Mod</td>";
	echo "<td class=header>Time</td>";
	echo "</tr>";
	
	$classes = array('body','body2');
	$i = 1;
	
	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=" . $classes[$i = !$i] . "><input type=checkbox name=check[] value=\"$line[banned]\"></td>";
		echo "<td class=" . $classes[$i] . ">" . (is_numeric($line['banned']) ? long2ip($line['banned']) : $line['banned'] ) . "</a></td>";
		echo "<td class=" . $classes[$i] . " align=right>" . ($line['userid'] ? $line['userid'] : '' ) . "</a></td>";
		echo "<td class=" . $classes[$i] . " align=right>" . ($line['modid'] ? "<a class=body href=/profile.php?uid=$line[modid]>" . $users[$line['modid']] . "</a>" : '' ) . "</a></td>";
		echo "<td class=" . $classes[$i] . ">" . ($line['date'] ? userDate("M j, y, G:i", $line['date']) : '') . "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=5>";
	echo "<input class=body type=submit name=action value=Unban></td></tr>";
	echo "</form></table>\n";

	incFooter();
