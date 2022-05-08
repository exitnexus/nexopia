<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"mirror"))
		die("Permission denied");

	$mirrortypes = array('www','image');

	switch($action){
		case "add":
			if(($type = getREQval('type')) && in_array($type, $mirrortypes))
				addMirror($type);	//exit
			break;

		case "Add Mirror":
			$type = getPOSTval('type');
			$plus = (getPOSTval('plus', 'bool') ? 'y' : 'n');
			$domain = getPOSTval('domain');
			$cookie = getPOSTval('cookie');
			$status = getPOSTval('status');
			$weight = getPOSTval('weight', 'int', 0);

			if(in_array($type, $mirrortypes) && !empty($domain))
				insertMirror($type, $plus, $domain, $cookie, $status, $weight);
			break;

		case "edit":
			if($id = getREQval('id', 'int'))
				editMirror($id);	//exit
			break;

		case "Update":
			$id = getPOSTval('id', 'int');
			$type = getPOSTval('type');
			$plus = (getPOSTval('plus') ? 'y' : 'n');
			$domain = getPOSTval('domain');
			$cookie = getPOSTval('cookie');
			$status = getPOSTval('status');
			$weight = getPOSTval('weight', 'int', 0);

			if($id && in_array($type, $mirrortypes) && !empty($domain))
				updateMirror($id, $type, $plus, $domain, $cookie, $status, $weight);
			break;

		case "Delete":
			if($id = getPOSTval('id', 'int'))
				deleteMirror($id);
			break;

		case "Enable":
			if(($checkID = getPOSTval('checkID', 'array')))
				enableMirrors($checkID);
			break;

		case "Disable":
			if(($checkID = getPOSTval('checkID', 'array')))
				disableMirrors($checkID);
			break;
	}

	listMirrors(); //exit
//////////////////////

function editMirror($id){
	global $db, $mirrortypes, $mods;

	$db->prepare_query("SELECT id, weight, type, domain, plus, cookie, status FROM mirrors WHERE id = ?", $id);
	$line = $db->fetchrow();

	$mods->adminlog('edit mirror',"edit mirror $line[domain]");

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=id value=$line[id]>";
	echo "<tr><td colspan=2 align=center class=header>Edit Mirror</td></tr>";
	echo "<tr><td class=body>Type</td><td class=body><select class=body name=type>" . make_select_list($mirrortypes, $line['type']) . "</select></td></tr>";
	echo "<tr><td class=body>Plus</td><td class=body><input type=checkbox name=plus" . ($line['plus']=='y' ? ' checked' : '') . "></td></tr>";
	echo "<tr><td class=body>Domain</td><td class=body><input class=body type=text size=30 name=domain value='$line[domain]'></td></tr>";
	echo "<tr><td class=body>Cookie</td><td class=body><input class=body type=text size=30 name=control value='$line[cookie]'></td></tr>";
	echo "<tr><td class=body>Status</td><td class=body><input class=body type=text size=30 name=status value='$line[status]'></td></tr>";
	echo "<tr><td class=body>Weight</td><td class=body><input class=body type=text name=weight value='$line[weight]' size=3></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit name=action value=Cancel><input class=body type=submit name=action value=Delete></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function addMirror($type){
	global $mirrortypes;

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td colspan=2 align=center class=header>Add Mirror</td></tr>";
	echo "<tr><td class=body>Type</td><td class=body><select class=body name=type>" . make_select_list($mirrortypes, $type) . "</select></td></tr>";
	echo "<tr><td class=body>Plus</td><td class=body><input type=checkbox name=plus></td></tr>";
	echo "<tr><td class=body>Domain</td><td class=body><input class=body type=text size=30 name=domain></td></tr>";
	echo "<tr><td class=body>Cookie</td><td class=body><input class=body type=text size=30 name=cookie></td></tr>";
	echo "<tr><td class=body>Status</td><td class=body><input class=body type=text size=30 name=status value='http://'></td></tr>";
	echo "<tr><td class=body>Weight</td><td class=body><input class=body type=text name=weight size=3></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Add Mirror'></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertMirror($type, $plus, $domain, $cookie, $status, $weight){
	global $db, $mods;

	$mods->adminlog('add mirror',"add mirror $domain");
	$db->prepare_query("INSERT INTO mirrors SET weight = ?, type = ?, domain = ?, cookie = ?, status = ?, plus = ?", $weight, $type, $domain, $cookie, $status, $plus);
}

function updateMirror($id, $type, $plus, $domain, $cookie, $status, $weight){
	global $db, $mods;

	$mods->adminlog('update mirror',"update mirror $domain");

	$db->prepare_query("UPDATE mirrors SET weight = ?, type = ?, domain = ?, cookie = ?, status = ?, plus = ? WHERE id = ?", $weight, $type, $domain, $cookie, $status, $plus, $id);
}

function deleteMirror($id){
	global $db, $mods;

	$mods->adminlog('delete mirror',"delete mirror $id");

	$db->prepare_query("DELETE FROM mirrors WHERE id = ?", $id);
}

function enableMirrors($checkID){
	global $db, $mods;

	$mods->adminlog('enable mirror',"enable mirrors");

	$db->prepare_query("UPDATE mirrors SET weight = abs(weight) WHERE id IN (?)", $checkID);
}

function disableMirrors($checkID){
	global $db, $mods;

	$mods->adminlog('disable mirror',"disable mirrors");

	$db->prepare_query("UPDATE mirrors SET weight = 0 - abs(weight) WHERE id IN (?)", $checkID);
}

function listMirrors(){
	global $config, $db, $mods;

	$mods->adminlog('list mirror',"list mirrors");

	$db->prepare_query("SELECT id, weight, type, plus, status, domain FROM mirrors");// ORDER BY plus DESC, domain");

	$rows = array();
	while($line = $db->fetchrow())
		$rows[$line['type']][] = $line;

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header>Domain</td>";
	echo "<td class=header>Weight</td>";
	echo "<td class=header>Plus</td>";
	echo "<td class=header></td>";
	echo "</tr>";


	foreach($rows as $type => $group){
		echo "<tr><td class=header colspan=7 align=center>$type</td></tr>";

		sortCols($group, SORT_DESC, 'plus', SORT_ASC, SORT_NATCASESTR, 'domain');


		$total = 0;

		foreach($group as $server){
			echo "<tr>";
			echo "<td class=body><input type=checkbox name=checkID[] value=$server[id]></td>";
			echo "<td class=body>" . (empty($server['status']) ? $server['domain'] : "<a class=body href=$server[status]>$server[domain]</a>" ) . "</td>";
			echo "<td class=body align=right>$server[weight]</td>";
			echo "<td class=body align=right>" . ($server['plus'] == 'y' ? "Plus" : "") ."</td>";
			echo "<td class=body>";
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$server[id]><img src=$config[imageloc]edit.gif border=0></a>";
			echo "</td>";
			echo "</tr>";
			if($server['weight'] > 0)
				$total += $server['weight'];
		}
		echo "<tr><td class=header colspan=2><a class=header href=$_SERVER[PHP_SELF]?action=add&type=$type>Add Mirror</a></td><td class=header align=right>$total</td><td class=header colspan=4></td>";
		echo "<tr><td class=body colspan=6>&nbsp;</td>";
	}
	echo "<tr><td class=header colspan=7><input class=body type=submit name=action value=Enable><input class=body type=submit name=action value=Disable></td></tr>";

	echo "</table>";

	incFooter();
	exit;
}

