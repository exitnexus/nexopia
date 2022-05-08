<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"mirror"))
		die("Permission denied");

	$mirrortypes = array('www','image');

	switch($action){
		case "add":			addMirror($type);																			//exit
		case "Add Mirror":	insertMirror($type, (isset($plus) ? 'y' : 'n'), $domain, $control, $status, $weight);		break;
		case "edit":		editMirror($id);																			//exit
		case "Update":		updateMirror($type, (isset($plus) ? 'y' : 'n'), $domain, $control, $status, $weight, $id);	break;
		case "delete":		deleteMirror($id);																			break;
		case "Enable":		enableMirrors($checkID);	break;
		case "Disable":		disableMirrors($checkID);	break;
	}

	listMirrors(); //exit
//////////////////////

function editMirror($id){
	global $PHP_SELF, $db, $mirrortypes, $mods;

	$db->prepare_query("SELECT id, weight, type, domain, plus, control, status FROM mirrors WHERE id = ?", $id);
	$line = $db->fetchrow();

	$mods->adminlog('edit mirror',"edit mirror $line[domain]");

	incHeader();

	echo "<table align=center><form action=$PHP_SELF>";
	echo "<input type=hidden name=id value=$line[id]>";
	echo "<tr><td colspan=2 align=center class=header>Edit Mirror</td></tr>";
	echo "<tr><td class=body>Type</td><td class=body><select class=body name=type>" . make_select_list($mirrortypes, $line['type']) . "</select></td></tr>";
	echo "<tr><td class=body>Plus</td><td class=body><input type=checkbox name=plus" . ($line['plus']=='y' ? ' checked' : '') . "></td></tr>";
	echo "<tr><td class=body>Domain</td><td class=body><input class=body type=text size=30 name=domain value='$line[domain]'></td></tr>";
	echo "<tr><td class=body>Control</td><td class=body><input class=body type=text size=30 name=control value='$line[control]'> (where to send the image mirror updates)</td></tr>";
	echo "<tr><td class=body>Status</td><td class=body><input class=body type=text size=30 name=status value='$line[status]'></td></tr>";
	echo "<tr><td class=body>Weight</td><td class=body><input class=body type=text name=weight value='$line[weight]' size=3></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function addMirror($type){
	global $PHP_SELF, $mirrortypes;

	incHeader();

	echo "<table align=center><form action=$PHP_SELF>";
	echo "<tr><td colspan=2 align=center class=header>Add Mirror</td></tr>";
	echo "<tr><td class=body>Type</td><td class=body><select class=body name=type>" . make_select_list($mirrortypes, $type) . "</select></td></tr>";
	echo "<tr><td class=body>Plus</td><td class=body><input type=checkbox name=plus></td></tr>";
	echo "<tr><td class=body>Domain</td><td class=body><input class=body type=text size=30 name=domain></td></tr>";
	echo "<tr><td class=body>Control</td><td class=body><input class=body type=text size=30 name=control> (where to send the image mirror updates)</td></tr>";
	echo "<tr><td class=body>Status</td><td class=body><input class=body type=text size=30 name=status value='http://'></td></tr>";
	echo "<tr><td class=body>Weight</td><td class=body><input class=body type=text name=weight size=3></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Add Mirror'></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertMirror($type, $plus, $domain, $control, $status, $weight){
	global $db, $cache, $mods;

	$mods->adminlog('add mirror',"add mirror $domain");
	$db->prepare_query("INSERT INTO mirrors SET weight = ?, type = ?, domain = ?, control = ?, status = ?, plus = ?", $weight, $type, $domain, $control, $status, $plus);
//	$cache->resetflag('mirrors');
	$cache->hdput("mirrors", getMirrors());
}

function updateMirror($type, $plus, $domain, $control, $status, $weight, $id){
	global $db, $cache, $mods;

	$mods->adminlog('update mirror',"update mirror $domain");

	$db->prepare_query("UPDATE mirrors SET weight = ?, type = ?, domain = ?, control = ?, status = ?, plus = ? WHERE id = ?", $weight, $type, $domain, $control, $status, $plus, $id);
//	$cache->resetflag('mirrors');
	$cache->hdput("mirrors", getMirrors());
}

function deleteMirror($id){
	global $db, $cache,$mods;

	$mods->adminlog('delete mirror',"delete mirror $id");

	$db->prepare_query("DELETE FROM mirrors WHERE id = ?", $id);
//	$cache->resetflag('mirrors');
	$cache->hdput("mirrors", getMirrors());
}

function enableMirrors($checkID){
	global $db, $cache, $mods;

	$mods->adminlog('enable mirror',"enable mirrors");

	$db->prepare_query("UPDATE mirrors SET weight = abs(weight) WHERE id IN (?)", $checkID);

	$cache->hdput("mirrors", getMirrors());
}

function disableMirrors($checkID){
	global $db, $cache, $mods;

	$mods->adminlog('disable mirror',"disable mirrors");

	$db->prepare_query("UPDATE mirrors SET weight = 0 - abs(weight) WHERE id IN (?)", $checkID);

	$cache->hdput("mirrors", getMirrors());
}

function listMirrors(){
	global $PHP_SELF, $config, $db,$mods;

	$mods->adminlog('list mirror',"list mirrors");

	$db->prepare_query("SELECT id, weight, type, plus, status, domain FROM mirrors ORDER BY type, plus DESC, domain");

	$rows = array();
	while($line = $db->fetchrow())
		$rows[$line['type']][] = $line;

	incHeader();

	echo "<table align=center><form action=$PHP_SELF>";

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header>Domain</td>";
	echo "<td class=header>Weight</td>";
	echo "<td class=header>Plus</td>";
	echo "<td class=header>Uptime</td>";
	echo "<td class=header>Load Average</td>";
	echo "<td class=header></td>";
	echo "</tr>";


	foreach($rows as $type => $group){
		echo "<tr><td class=header colspan=7 align=center>$type</td></tr>";

		$total = 0;

		foreach($group as $server){
			$uptime = "";
			$loadaverage = "";
			if($type == 'www' && $server['weight'] > 0){
				$temp = file_get_contents("http://$server[domain]/uptime.php");
				$loc = strpos($temp, "up") + 3;
				$uptime = substr($temp, $loc, (strpos($temp, ",", $loc)-$loc));
				$loc = strpos($temp, "load average:")+13;
				$loadaverage = substr($temp, $loc, (strpos($temp, ",", $loc)-$loc));
			}
			echo "<tr>";
			echo "<td class=body><input type=checkbox name=checkID[] value=$server[id]></td>";
			echo "<td class=body>" . (empty($server['status']) ? $server['domain'] : "<a class=body href=$server[status]>$server[domain]</a>" ) . "</td>";
			echo "<td class=body align=right>$server[weight]</td>";
			echo "<td class=body align=right>" . ($server['plus'] == 'y' ? "Plus" : "") ."</td>";
			echo "<td class=body align=right>$uptime</td>";
			echo "<td class=body align=right>$loadaverage</td>";
			echo "<td class=body>";
			echo "<a class=body href=$PHP_SELF?action=edit&id=$server[id]><img src=$config[imageloc]edit.gif border=0></a>";
			echo "<a class=body href=$PHP_SELF?action=delete&id=$server[id]><img src=$config[imageloc]delete.gif border=0></a>";
			echo "</td>";
			echo "</tr>";
			if($server['weight'] > 0)
				$total += $server['weight'];
		}
		echo "<tr><td class=header colspan=2><a class=header href=$PHP_SELF?action=add&type=$type>Add Mirror</a></td><td class=header align=right>$total</td><td class=header colspan=4></td>";
		echo "<tr><td class=body colspan=6>&nbsp;</td>";
	}
	echo "<tr><td class=header colspan=7><input class=body type=submit name=action value=Enable><input class=body type=submit name=action value=Disable></td></tr>";

	echo "</table>";

	incFooter();
	exit;
}

