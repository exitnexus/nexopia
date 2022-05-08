<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"listbannedusers"))
		die("Permission denied");


	if($mods->isAdmin($userData['userid'],"banusers")){
		switch($action){
			case "Add":				add($val,$type); 			break;
			case "Delete":			delete($check);				break;
			case "edit":			edit($id);					break;
			case "Update":			update($id,$val,$type);		break;
		}
	}

function add($val,$type){
	global $msgs, $db;

	$ip = "";
	$email = "";

	if($type=='ip')
		$ip = ip2int($val);
	else
		$email = $val;

	$db->prepare_query("INSERT INTO bannedusers SET ip = ?, email = ?, type = ?", $ip, $email, $type);

	$msgs->addMsg("User $val banned");
}

function delete($check){
	global $msgs, $db;

	if(!isset($check) || !is_array($check))
		return false;

	$db->prepare_query("DELETE FROM bannedusers WHERE id IN (?)", $check);

	$msgs->addMsg("Users unbanned");
}

function edit($id){
	global $db;
	if(!isset($id)) return false;

	$db->prepare_query("SELECT * FROM bannedusers WHERE id = ?", $id);
	$line = $db->fetchrow();

	if($line['type']=='ip')
		$line['ip']=long2ip($line['ip']);

	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=POST>\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<table>";
	echo "<tr><td class=body>User: </td><td class=body><input class=body type=text name=\"val\" value=\"" . ($line['type']=='ip' ? $line['ip'] : $line['email'] ) . "\"></td></tr>\n";
	echo "<tr><td class=body>Type: </td><td class=body><select name=type class=body>" . make_select_list(getEnumValues($db, "bannedusers","type"),$line['type']) . "</select></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"><input class=body type=submit value=Cancel></td></tr>\n";
	echo "</table></form>\n";


	incFooter(array('incAdminBlock'));
	exit;
}

function update($id,$val,$type){
	global $msgs, $db;
	if(!isset($id)) return false;

	$ip="";
	$email = "";

	if($type=='ip')
		$ip = ip2int($val);
	else
		$email = $val;

	$db->prepare_query("UPDATE bannedusers SET ip = ?, email = ?, type = ? WHERE id = ?", $ip, $email, $type, $id);

	$msgs->addMsg("Update Complete");
}


	$result = $db->query("SELECT * FROM bannedusers");

	incHeader();

	echo "<table width=100%><form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr>\n";
	echo "  <td class=header></td>\n";
	echo "  <td class=header>User</td>\n";
	echo "  <td class=header>Type</td>\n";
	echo "</tr>\n";
	while($line = $db->fetchrow($result))
		echo "<tr><td class=body><input type=checkbox name=check[] value=\"$line[id]\"></td><td class=body><a class=body href=\"$_SERVER[PHP_SELF]?id=$line[id]&action=edit\">" . ($line['type']=='ip' ? long2ip($line['ip']) : $line['email'] ) . "</a></td><td class=body>$line[type]</td></tr>\n";
	echo "<tr><td class=header colspan=3>";
	echo "<input class=body type=submit name=action value=Delete></td></tr>\n";
	echo "</form></table>\n";


	echo "<form action=$_SERVER[PHP_SELF] method=POST>\n";
	echo "<table>";
	echo "<tr><td class=body>User: </td><td class=body><input class=body type=text name=\"val\"></td></tr>\n";
	echo "<tr><td class=body>Type: </td><td class=body><select name=type class=body>" . make_select_list(array("ip","email"),$line['type']) . "</select></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add\"></td><td></td></tr>\n";
	echo "</table></form>\n";

	incFooter(array('incAdminBlock'));
