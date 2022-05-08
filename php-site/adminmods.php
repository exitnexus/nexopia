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
						'activetime' => "'0'",
						'adminormod' => "'0'"
						);

	$sortt = getREQval('sortt');
	$sortd = getREQval('sortd');

	isValidSortt($sortlist,$sortt);
	isValidSortd($sortd,'ASC');

	$isAdmin = $mods->isAdmin($userData['userid'],"editmods");

	$type = getREQval('type', 'int');

	if(empty($type) || !isset($mods->modtypes[$type])) // !in_array($type,array_keys($mods->modtypes)))
		$type = MOD_PICS;

	if($isAdmin){
		switch($action){
			case "add":
				addMod();
				break;

			case "Create":
				$username = getPOSTval('username');
				$level = getPOSTval('level', 'int');

				if($username && $level)
					insertMod($username,$level);
				break;

			case "edit":
				$uid = getREQval('uid', 'int');
				if($uid && checkKey($uid, getREQval('k')))
					editMod($uid);
				break;

			case "Update":
				$uid = getPOSTval('uid', 'int');
				$level = getPOSTval('level', 'int');

				if($uid && $level)
					updateMod($uid,$level);
				break;

			case "delete":
				$uid = getREQval('uid', 'int');
				if($uid && checkKey($uid, getREQval('k')))
					deleteMod($uid);
				break;

			case "Reset":
				$uid = getPOSTval('uid', 'int');
				if($uid)
					resetMod($uid);
				break;
		}
	}

	listMods($type);
	exit;

/////////////////////////////

function listMods($type){
	global $usersdb, $db, $sortlist, $sortt, $sortd, $type, $isAdmin, $config, $mods, $forumdb;

	$res = $mods->db->prepare_query("SELECT userid, `right`+`wrong` AS `total`, `right`, `wrong`, strict, lenient, level, time, creationtime, IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`)) AS percent, 'n' AS online, '0' AS activetime FROM mods WHERE type = #", $type);

	$rows = array();
	while($line = $res->fetchrow()) {
		$rows[$line['userid']] = $line;
		$rows[$line['userid']]['adminormod'] = 'pic mod';
		$rows[$line['userid']]['username'] = getUserName($line['userid']);

		$allowdel = true;
		if ($type == MOD_PICS) { // only strip deletion for pic mod type
			if (!$mods->isAdmin($line['userid'])) {
				$res = $forumdb->prepare_query('SELECT COUNT(*) AS modcnt FROM forummods, forums WHERE forummods.userid=# AND forummods.forumid=forums.id AND forums.official=?', $line['userid'], 'y');
				$isforummod = $res->fetchfield();

				if (!is_null($isforummod) and $isforummod > 0) {
					$rows[$line['userid']]['adminormod'] = 'forum mod';
					$allowdel = false;
				}
			}
			else {
				$rows[$line['userid']]['adminormod'] = 'admin';
				$allowdel = false;
			}
		}
	}

	$res = $usersdb->prepare_query("SELECT userid, online, activetime FROM useractivetime WHERE userid IN (%)", array_keys($rows));

	while($line = $res->fetchrow()){
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
		case "adminormod":
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
		echo makeSortTableHeader("Username","username");
		echo makeSortTableHeader("Level","level");
		echo makeSortTableHeader("Total","total");
		echo makeSortTableHeader("Right","right");
		echo makeSortTableHeader("Wrong","wrong");
		echo makeSortTableHeader("Strict","strict");
		echo makeSortTableHeader("Lenient","lenient");
		echo makeSortTableHeader("Percent","percent");
		echo makeSortTableHeader("Last Moded","time");
		echo makeSortTableHeader("Creation Time","creationtime");
		echo makeSortTableHeader("Active Time","activetime");
		if($isAdmin)
			echo makeSortTableHeader("Action/Type","adminormod",$sortlist);
	echo "</tr>";

	$nummods = 0;
	$level = 0;
	$total = 0;
	$right = 0;
	$wrong = 0;
	$error = 0;
	$lenient = 0;
	$strict = 0;

	$classes = array('body','body2');
	$i = 1;

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=" . $classes[$i = !$i] . "><a class=body href=/profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=" . $classes[$i] . ">";
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
		echo "<td class=" . $classes[$i] . " align=right>$line[total]</td>";
		echo "<td class=" . $classes[$i] . " align=right>$line[right]</td>";
		echo "<td class=" . $classes[$i] . " align=right>$line[wrong]</td>";
		echo "<td class=" . $classes[$i] . " align=right>$line[strict]</td>";
		echo "<td class=" . $classes[$i] . " align=right>$line[lenient]</td>";
		echo "<td class=" . $classes[$i] . " align=right>" . number_format($line['percent'],2) . "%</td>";
		echo "<td class=" . $classes[$i] . ">" . ($line['time'] == 0 ? "Never" : userDate("M j, Y G:i", $line['time']) ) . "</td>";
		echo "<td class=" . $classes[$i] . ">" . ($line['creationtime'] == 0 ? "Unknown" : userDate("M j, Y G:i", $line['creationtime']) ) . "</td>";
		echo "<td class=" . $classes[$i] . ">" . ($line['online'] == 'y' ? "<b>Online</b>" : ($line['activetime'] == 0 ? "Never" : userDate("M j, Y G:i", $line['activetime']) )) . "</td>";

		if($isAdmin){
			$k = makekey($line['userid']);
			echo "<td class=" . $classes[$i] . ">";
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=edit&uid=$line[userid]&sortt=$sortt&sortd=$sortd&type=$type&k=$k><img src=$config[imageloc]edit.gif border=0></a>";

			if ($line['adminormod'] == 'forum mod')
				echo "Forum Mod";
			elseif ($line['adminormod'] == 'admin')
				echo "Admin";

			// user has no admin privileges and is not a moderator for any official forum
			if ($line['adminormod'] == 'pic mod') echo "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=delete&uid=$line[userid]&sortt=$sortt&sortd=$sortd&type=$type&k=$k','delete this mod?')\"><img src=$config[imageloc]delete.gif border=0></a>";

			echo "</td>";
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
	if($isAdmin and $type != MOD_PICS)
		echo "<a class=header href=$_SERVER[PHP_SELF]?action=add&type=$type>Create Moderator</a>";
	elseif ($isAdmin and $type == MOD_PICS)
		echo "<a class=header href=/adminaddpicmods.php>Create Moderator</a>";
	echo "</td></tr>";

	echo "</table>";

	incFooter();
	exit;
}

function addMod(){
	global $type;

	incHeader();

	echo "<table>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=type value=$type>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Level:</td><td class=body><select class=body name=level>" . make_select_list(range(0,6)) . "</select></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Create><input class=body type=submit name=action value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function insertMod($username,$level){
	global $type, $msgs, $mods;

	$userid = getUserID($username);
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

	$res = $mods->db->prepare_query("SELECT userid, `right`+`wrong` AS `total`, `right`, `wrong`, strict, lenient, level, time, creationtime, IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`)) AS percent, 'n' AS online, '0' AS activetime FROM mods WHERE type = # && userid = #", $type, $userid);

	$line = $res->fetchrow();

	if(!$line)
		die("Bad mod");

	$line['username'] = getUserName($userid);

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=type value=$type>";
	echo "<input type=hidden name=uid value=$userid>";
	echo "<input type=hidden name=sortt value=$sortt>";
	echo "<input type=hidden name=sortd value=$sortd>";

	echo "<tr><td class=header colspan=2 align=center>Edit Mod</td></tr>";

	echo "<tr><td class=body>Username:</td><td class=body><a class=body href=/profile.php?uid=$userid>$line[username]</a></td></tr>";
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
	echo "<tr><td class=body colspan=2 align=center>";
	echo "<input class=body type=submit name=action value=Update>";
	echo "<input class=body type=submit name=action value=Cancel>";
	echo "<input class=body type=submit name=action value=Reset>";
	echo "</td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function updateMod($userid,$level){
	global $type, $msgs, $mods;

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
	global $type, $msgs,$mods, $abuselog;

	$username = getUserName($userid);

	$mods->adminlog("delete mod", "Delete $type mod: $username");
	if ($type == MOD_PICS) $abuselog->addAbuse($userid, ABUSE_ACTION_NOTE, ABUSE_REASON_OTHER, 'Removed as pic mod', 'User stripped of pic mod privileges.');

	$mods->deleteMod($userid, $type);
	$msgs->addMsg("Deleted.");
}

function resetMod($userid){
	global $type, $msgs, $mods;

	$username = getUserName($userid);

	$mods->adminlog("reset mod", "Reset $type mod: $username");

	$mods->resetMod($userid, $type);

	$msgs->addMsg("Stats Reset");
}

