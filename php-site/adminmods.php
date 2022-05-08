<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"listmods"))
		die("Permission denied");

	$sortlist = array( 	'userid' => "",
						'username' => "username",
						'total' => "`right`+`wrong`",
						'right' => "right",
						'wrong' => "wrong",
						'strict' => "strict",
						'lenient' => "lenient",
						'level' => "level",
						'time' => "time",
						'creationtime' => "creationtime",
						'percent' => "IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`))",
						'online' => "'n'",
						'activetime' => "'0'"
						);

	isValidSortt($sortlist,$sortt);
	isValidSortd($sortd,'ASC');

	$isAdmin = $mods->isAdmin($userData['userid'],"editmods");

	if(empty($type) || !isset($mods->modtypes[$type])) // !in_array($type,array_keys($mods->modtypes)))
		$type = MOD_PICS;

	if($isAdmin){
		switch($action){
			case "add":			addMod(); 						break;
			case "Create":		insertMod($username,$level);	break;
			case "edit":		editMod($uid);					break;
			case "Update":		updateMod($uid,$level);			break;
			case "delete":		deleteMod($uid);				break;
		}
	}

	listMods($type);
	exit;


function listMods($type){
	global $fastdb, $db, $sortlist, $sortt, $sortd, $type, $isAdmin, $config, $mods;

	$mods->db->prepare_query("SELECT " . makeSortSelect($sortlist) . " FROM mods WHERE type = ?", $type);

	$rows = array();
	while($line = $mods->db->fetchrow())
		$rows[$line['userid']] = $line;

	$fastdb->prepare_query(array_keys($rows), "SELECT userid, online, activetime FROM useractivetime WHERE userid IN (?)", array_keys($rows));

	while($line = $fastdb->fetchrow()){
		$rows[$line['userid']]['online'] = $line['online'];
		$rows[$line['userid']]['activetime'] = $line['activetime'];
	}

	switch($sortt){
		case 'userid':
		case 'total':
		case 'right':
		case 'wrong':
		case 'strict':
		case 'lenient':
		case 'level':
		case 'time':
		case 'creationtime':
		case 'percent':
		case 'activetime':
			$sortcomp = SORT_NUMERIC;
			break;

		case "username":
		default:
			$sortcomp = SORT_CASESTR;
	}

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username', ($sortd == 'ASC' ? SORT_ASC : SORT_DESC), $sortcomp, $sortt);

	$mods->adminlog("list mods", "List $type mods");

	incHeader();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header colspan=12 align=center>Type: <select class=body name=type>" . make_select_list_key($mods->modtypes,$type) . "</select><input class=body type=submit value=Go></td></tr>";
	echo "</form>";

	echo "<tr>";
		makeSortTableHeader($sortlist,"Username","username");
		makeSortTableHeader($sortlist,"Level","level");
		makeSortTableHeader($sortlist,"Total","total");
		makeSortTableHeader($sortlist,"Right","right");
		makeSortTableHeader($sortlist,"Wrong","wrong");
		makeSortTableHeader($sortlist,"Strict","strict");
		makeSortTableHeader($sortlist,"Lenient","lenient");
		makeSortTableHeader($sortlist,"Percent","percent");
		makeSortTableHeader($sortlist,"Last Moded","time");
		makeSortTableHeader($sortlist,"Creation Time","creationtime");
		makeSortTableHeader($sortlist,"Active Time","activetime");
		if($isAdmin)
			echo "<td class=header></td>";
	echo "</tr>";

	$nummods = 0;
	$level = 0;
	$total = 0;
	$right = 0;
	$wrong = 0;
	$error = 0;
	$lenient = 0;
	$strict = 0;

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body>";
		if($isAdmin){
			$newlevel = $mods->suggestedModLevel($line['right'], $line['percent'], $line['level']);
			if($newlevel > $line['level'])
				echo "<b><a class=body href=/adminmods.php?type=$type&uid=$line[userid]&level=$newlevel&action=Update>$line[level] +++</a></b>";
			elseif($newlevel < $line['level'])
				echo "<b><a class=body href=/adminmods.php?type=$type&uid=$line[userid]&level=$newlevel&action=Update>$line[level] ---</a></b>";
			else
				echo "$line[level]";
		}else{
			echo "$line[level]";
		}
		echo "</td>";
		echo "<td class=body align=right>$line[total]</td>";
		echo "<td class=body align=right>$line[right]</td>";
		echo "<td class=body align=right>$line[wrong]</td>";
		echo "<td class=body align=right>$line[strict]</td>";
		echo "<td class=body align=right>$line[lenient]</td>";
		echo "<td class=body align=right>" . number_format($line['percent'],2) . "%</td>";
		echo "<td class=body>" . ($line['time'] == 0 ? "Never" : userDate("M j, Y G:i", $line['time']) ) . "</td>";
		echo "<td class=body>" . ($line['creationtime'] == 0 ? "Unknown" : userDate("M j, Y G:i", $line['creationtime']) ) . "</td>";
		echo "<td class=body>" . ($line['online'] == 'y' ? "<b>Online</b>" : ($line['activetime'] == 0 ? "Never" : userDate("M j, Y G:i", $line['activetime']) )) . "</td>";
		if($isAdmin){
			echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=edit&uid=$line[userid]&sortt=$sortt&sortd=$sortd&type=$type><img src=$config[imageloc]edit.gif border=0></a>";
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=delete&uid=$line[userid]&sortt=$sortt&sortd=$sortd&type=$type><img src=$config[imageloc]delete.gif border=0></a></td>";
		}
		echo "</tr>";
		$nummods++;
		$level += $line['level'];
		$total += $line['total'];
		$right += $line['right'];
		$wrong += $line['wrong'];
		$lenient += $line['lenient'];
		$strict += $line['strict'];
		$error += $line['percent'];
	}

	echo "<tr>";
	echo "<td class=header>$nummods Mods</td>";
	echo "<td class=header>" . ($nummods > 0 ? number_format($level/$nummods,2) : "0.00") . "</td>";
	echo "<td class=header align=right>$total</td>";
	echo "<td class=header align=right>$right</td>";
	echo "<td class=header align=right>$wrong</td>";
	echo "<td class=header align=right>$strict</td>";
	echo "<td class=header align=right>$lenient</td>";
	echo "<td class=header align=right>" . ($nummods > 0 ? number_format($error/$nummods,2) : "0.00") . "%</td>";
	echo "</td><td class=header colspan=4 align=right>";
	if($isAdmin)
		echo "<a class=header href=$_SERVER[PHP_SELF]?action=add&type=$type>Create Moderator</a>";
	echo "</td></tr>";

	echo "</table>";

	incFooter();
	exit;
}

function addMod(){
	global $type, $db;

	incHeader();

	echo "<table>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<input type=hidden name=type value=$type>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Level:</td><td class=body><select class=body name=level>" . make_select_list(range(0,6)) . "</select></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Create><input class=body type=submit name=action value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function insertMod($username,$level){
	global $type, $msgs, $db, $mods;

	$userid = getUserid($username);
	if(!$userid){
		$msgs->addMsg("Usename does not exist");
		return;
	}

	if($level > 6)
		$level = 6;
	if($level < 0)
		$level = 0;

	$mods->adminlog("add mod", "Add $username as level $level $type mod");

	$mods->addMod($userid, $type, $level);

	$msgs->addMsg("$username added.");
}

function editMod($userid){
	global $type, $sortt, $sortd, $mods, $sortlist;

	$mods->db->prepare_query("SELECT " . makeSortSelect($sortlist) . " FROM mods WHERE type = ? && userid = ?", $type, $userid);

	if(!$mods->db->numrows())
		die("Bad mod");

	$line = $mods->db->fetchrow();

	incHeader();

	echo "<table>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<input type=hidden name=type value=$type>";
	echo "<input type=hidden name=uid value=$userid>";
	echo "<input type=hidden name=sortt value=$sortt>";
	echo "<input type=hidden name=sortd value=$sortd>";
	echo "<tr><td class=body>Username:</td><td class=body><a class=body href=profile.php?uid=$userid>$line[username]</a></td></tr>";
	echo "<tr><td class=body>Total:</td><td class=body>$line[total]</td></tr>";
	echo "<tr><td class=body>Right:</td><td class=body>$line[right]</td></tr>";
	echo "<tr><td class=body>Wrong:</td><td class=body>$line[wrong]</td></tr>";
	echo "<tr><td class=body>Strict:</td><td class=body>$line[strict]</td></tr>";
	echo "<tr><td class=body>Lenient:</td><td class=body>$line[lenient]</td></tr>";
	echo "<tr><td class=body>Error:</td><td class=body>" . number_format($line['percent'],2) . "%</td></tr>";
	echo "<tr><td class=body>Active Time:</td><td class=body>" . ($line['time'] == 0 ? "Never" : userDate("D M j, Y G:i:s", $line['time']) ) . "</td></tr>";
	echo "<tr><td class=body>Creation Time:</td><td class=body>" . ($line['creationtime'] == 0 ? "Unknown" : userDate("D M j, Y G:i:s", $line['creationtime']) ) . "</td></tr>";

	echo "<tr><td class=body>Suggested Level:</td><td class=body>" . $mods->suggestedModLevel($line['right'], $line['percent'], $line['level']) . "</td></tr>";
	echo "<tr><td class=body>Level:</td><td class=body><select class=body name=level>" . make_select_list(range(0,5), $line['level']) . "</select></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit name=action value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function updateMod($userid,$level){
	global $type, $msgs, $db, $mods;

	if($level > 6)
		$level = 6;
	if($level < 0)
		$level = 0;

	$username = getUserName($userid);

	$mods->adminlog("update mod", "Update $username to level $level $type mod");

	$mods->updateMod($userid, $type, $level);

	$msgs->addMsg("Updated");
}

function deleteMod($userid){
	global $type, $msgs, $db, $mods;

	$username = getUserName($userid);

	$mods->adminlog("delete mod", "Delete $type mod: $username");

	$mods->deleteMod($userid, $type);

	$msgs->addMsg("Deleted.");
}


