<?

	$login=1;

	require_once("include/general.lib.php");

//	if(!isadmin($userData['userid'],"sales"))
//		die("Permission denied");
	

	sqlSafe(&$name,&$check,&$id);
	
	if(!isset($action))
		$action="";
	
	switch($action){
		case "Add Manufacturer":	add($name);					 		break;
		case "Delete":				delete($check);						break;
		case "edit":				edit($id);							break;
		case "Update":				update($id,$name);					break;
	}
	
function add($name){
	global $msgs;
	$query = "INSERT INTO manufacturers SET name='$name'";
	mysql_query ($query) or die ("Query failed");
	writeConfig();
	
	$msgs->addMsg("Manufacturer $name added");
}
function delete($check){
	global $msgs;
	if(!isset($check)) return false;
	foreach($check as $deleteId){
		$query="DELETE FROM manufacturers WHERE `id` = '$deleteId'";
		mysql_query ($query) or die ("Query failed");
		$msgs->addMsg("Manufacturer deleted");
	}	
	writeConfig();
}
function edit($id){
	global $PHP_SELF;
	if(!isset($id)) return false;
	$query = "SELECT * FROM manufacturers WHERE id = '$id'";
    $result = mysql_query ($query)
        or die ("Query failed");
	$line = mysql_fetch_assoc($result);

	incHeader();

	echo "<form action=\"$PHP_SELF\" method=POST>\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<table>";
	echo "<tr><td class=body>Name: </td><td class=body><input class=body type=text size=50 name=\"name\" value=\"$line[name]\"></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"><input class=body type=submit value=Cancel></td></tr>\n";
	echo "</table></form>\n";


	incFooter(array('incAdminBlock'));
	exit();
}
function update($id,$name){
	global $msgs;
	if(!isset($id)) return false;
	$query = "UPDATE manufacturers SET name='$name' WHERE id='$id'";
	mysql_query ($query) or die ("Query failed");
	writeConfig();
	$msgs->addMsg("Update Complete");
}


	$query = "SELECT * FROM manufacturers";
    $result = mysql_query ($query)
        or die ("Query failed");


	incHeader();

	echo "<table width=100%><form action=\"$PHP_SELF\" method=post>\n";
	echo "<tr>\n";
	echo "  <td class=header></td>\n";
	echo "  <td class=header>Name</td>\n";
	echo "</tr>\n";
	while($line = mysql_fetch_assoc($result))
		echo "<tr><td class=body><input type=checkbox name=check[] value=\"$line[id]\"></td><td class=body><a class=body href=\"$PHP_SELF?id=$line[id]&action=edit\">$line[name]</a></td></tr>\n";
	echo "<tr><td class=header colspan=6>";
	echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
	echo "<input class=body type=submit name=action value=Delete></td></tr>\n";
	echo "</form></table>\n";


	echo "<form action=\"$PHP_SELF\" method=POST>\n";
	echo "<table>";
	echo "<tr><td class=body>Name: </td><td class=body><input class=body size=50 type=text name=\"name\"></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add Manufacturer\"></td><td></td></tr>\n";
	echo "</table></form>\n";


	incFooter(array('incAdminBlock'));
