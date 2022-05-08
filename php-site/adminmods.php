<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isAdmin($userData['userid'],"listmods"))
		die("Permission denied");

	$sortlist = array( 	'users.userid' => "",
						'username' => "username",
						'total' => "`right`+`wrong`",
						'right' => "right",
						'wrong' => "wrong",
						'strict' => "strict",
						'lenient' => "lenient",
						'level' => "level",
						'time' => "time",
						'creationtime' => "creationtime",
						'percent' => "IF(`right`+`wrong`=0,0,100.0*`wrong`/(`right` + `wrong`))"
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
	global $db, $sortlist, $sortt, $sortd, $type, $isAdmin, $PHP_SELF, $config, $mods;

	$mods->moddb->prepare_query("SELECT " . makeSortSelect($sortlist) . " FROM users, mods WHERE users.userid=mods.userid && type = ? ORDER BY $sortt $sortd,username", $type);

	$rows = array();
	while($line = $mods->moddb->fetchrow())
		$rows[] = $line;

	$mods->adminlog("list mods", "List $type mods");

	incHeader();

	echo "<table align=center>";

	echo "<form action=$PHP_SELF>";
	echo "<tr><td class=header colspan=11 align=center>Type: <select class=body name=type>" . make_select_list_key($mods->modtypes,$type) . "</select><input class=body type=submit value=Go></td></tr>";
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
		makeSortTableHeader($sortlist,"Last Activity","time");
		makeSortTableHeader($sortlist,"Creation Time","creationtime");
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
		echo "<td class=body>$line[level]</td>";
		echo "<td class=body align=right>$line[total]</td>";
		echo "<td class=body align=right>$line[right]</td>";
		echo "<td class=body align=right>$line[wrong]</td>";
		echo "<td class=body align=right>$line[strict]</td>";
		echo "<td class=body align=right>$line[lenient]</td>";
		echo "<td class=body align=right>" . number_format($line['percent'],2) . "%</td>";
		echo "<td class=body>" . ($line['time'] == 0 ? "Never" : userDate("D M j, Y G:i:s", $line['time']) ) . "</td>";
		echo "<td class=body>" . ($line['creationtime'] == 0 ? "Unknown" : userDate("D M j, Y G:i:s", $line['creationtime']) ) . "</td>";
		if($isAdmin){
			echo "<td class=body><a class=body href=$PHP_SELF?action=edit&uid=$line[userid]&sortt=$sortt&sortd=$sortd&type=$type><img src=/images/edit.gif border=0></a>";
			echo "<a class=body href=$PHP_SELF?action=delete&uid=$line[userid]&sortt=$sortt&sortd=$sortd&type=$type><img src=/images/delete.gif border=0></a></td>";
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
	echo "</td><td class=header colspan=3 align=right>";
	if($isAdmin)
		echo "<a class=header href=$PHP_SELF?action=add&type=$type>Create Moderator</a>";
	echo "</td></tr>";

	echo "</table>";

	incFooter();
	exit;
}

function addMod(){
	global $type, $db, $PHP_SELF;

	incHeader();

	echo "<table>";
	echo "<form action=$PHP_SELF>";
	echo "<input type=hidden name=type value=$type>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Level:</td><td class=body><select class=body name=level>" . make_select_list(range(0,5)) . "</select></td></tr>";
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

	if($level > 5)
		$level = 5;
	if($level < 0)
		$level = 0;

	$mods->adminlog("add mod", "Add $username as level $level $type mod");

	$mods->addMod($userid, $type, $level);

	$msgs->addMsg("$username added.");
}

function editMod($userid){
	global $type, $db, $PHP_SELF,$sortt,$sortd, $mods;

	$username = getUserName($userid);

	$level = $mods->getModLvl($userid, $type);

	if($level === false)
		die("Bad mod");

	incHeader();

	echo "<table>";
	echo "<form action=$PHP_SELF>";
	echo "<input type=hidden name=type value=$type>";
	echo "<input type=hidden name=uid value=$userid>";
	echo "<input type=hidden name=sortt value=$sortt>";
	echo "<input type=hidden name=sortd value=$sortd>";
	echo "<tr><td class=body>Username:</td><td class=body><a class=body href=profile.php?uid=$userid>$username</a></td></tr>";
	echo "<tr><td class=body>Level:</td><td class=body><select class=body name=level>" . make_select_list(range(0,5), $level) . "</select></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit name=action value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function updateMod($userid,$level){
	global $type, $msgs, $db, $mods;

	if($level > 5)
		$level = 5;
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


