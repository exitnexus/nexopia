<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"config"))
		die("Permission denied");

	switch($action){
		case "Add Variable":	add($name,$value,$comments); 		break;
		case "Delete":			delete($check);						break;
		case "edit":			edit($id);							//exit
		case "Update":			update($id,$name,$value,$comments);	break;
	}

	listConfig(); //exit
/////////////

function add($name,$value,$comments){
	global $msgs, $db, $cache, $mods;

	$db->prepare_query("INSERT INTO config SET name = ?, value = ?, comments = ?", $name, $value, $comments);

	$mods->adminlog('insert config',"insert config $name");

	$cache->resetflag('config');

	$msgs->addMsg("Variable $name added");
}

function delete($check){
	global $msgs, $db, $cache, $mods;
	if(!isset($check) || !is_array($check)) return false;

	$db->prepare_query("DELETE FROM config WHERE id IN (?)", $check);

	$mods->adminlog('delete config',"delete config " . implode(",", $check));

	$cache->resetflag('config');

	$msgs->addMsg("Variables deleted");
}

function edit($id){
	global $PHP_SELF, $db, $mods;

	if(!isset($id)) return false;

	$mods->adminlog('edit config',"edit config $id");

	$db->prepare_query("SELECT * FROM config WHERE id = ?", $id);
	$line = $db->fetchrow();

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=POST>\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<table>";
	echo "<tr><td class=body>Name: </td><td class=body><input class=body type=text size=50 name=\"name\" value=\"$line[name]\"></td></tr>\n";
	echo "<tr><td class=body>Value: </td><td class=body><input class=body type=text size=50 name=\"value\" value=\"$line[value]\"></td></tr>\n";
	echo "<tr><td class=body>Comments: </td><td class=body><input class=body type=text size=50 maxlength=255 name=\"comments\" value=\"$line[comments]\"></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"><input class=body type=submit value=Cancel></td></tr>\n";
	echo "</table></form>\n";


	incFooter(array('incAdminBlock'));
	exit;
}

function update($id,$name,$value,$comments){
	global $msgs, $db, $cache, $mods;
	if(!isset($id)) return false;

	$mods->adminlog('update config',"update config $id");

	$db->prepare_query("UPDATE config SET name = ?, value = ?, comments = ? WHERE id = ?", $name, $value, $comments, $id);

	$cache->resetflag('config');

	$msgs->addMsg("Update Complete");
}

function listConfig(){
	global $db, $PHP_SELF, $mods;

	$result = $db->query("SELECT * FROM config ORDER BY name");

	$mods->adminlog('list config',"list config");

	incHeader();

	echo "<table width=100%><form action=\"$PHP_SELF\" method=post>\n";
	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header>Name</td>";
	echo "<td class=header>Value</td>";
	echo "<td class=header>Comments</td>";
	echo "</tr>\n";
	while($line = $db->fetchrow($result)){
		echo "<tr>";
		echo "<td class=body><input type=checkbox name=check[] value=\"$line[id]\"></td>";
		echo "<td class=body><a class=body href=\"$PHP_SELF?id=$line[id]&action=edit\">$line[name]</a></td>";
		echo "<td class=body>$line[value]</td>";
		echo "<td class=body>$line[comments]</td>";
		echo "</tr>\n";
	}
	echo "<tr><td class=header colspan=4>";
	echo "<input class=body type=submit name=action value=Delete></td></tr>\n";
	echo "</form></table>\n";


	echo "<table>";
	echo "<form action=\"$PHP_SELF\" method=POST>\n";
	echo "<tr><td class=body>Name: </td><td class=body><input class=body size=50 type=text name=\"name\"></td></tr>\n";
	echo "<tr><td class=body>Value: </td><td class=body><input class=body size=50 type=text name=\"value\"></td></tr>\n";
	echo "<tr><td class=body>Comments: </td><td class=body><input class=body size=50 maxlength=255 type=text name=\"comments\"></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add Variable\"></td><td></td></tr>\n";
	echo "</form></table>\n";

	incFooter();
}

