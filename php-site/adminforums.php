<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isadmin($userData['userid'],"forums"))
		die("Permission denied");


	switch($action){
		case "Add":			add($name,$isrealm,$description);	break;
		case "delete":		delete($id);						break;
		case "moveup":		increasepriority($id,"forums");		break;
		case "movedown":	decreasepriority($id,"forums");		break;
		case "edit":		edit($id);							break;
		case "Update":		update($id,$name,$description);		break;
	}


function add($name,$isrealm,$description){
	global $msgs;

	$priority = getMaxPriority("forums","official='y'");

	if(isset($isrealm)){
		$isrealm='y';
		$description="";
	}else
		$isrealm='n';

	$db->prepare_query("INSERT INTO forums SET priority = ?, isrealm = ?, name = ?, description = ?", $priority, $isrealm, $name, $description);

	$msgs->addMsg("Forum Added");
}
function delete($id){
	global $msgs;
	if(!isset($id)) return false;

	set_time_limit(0);

	setMaxPriority($id,"forums");

	$query = "SELECT id FROM forumthreads WHERE forumid='$id'";
	$result = $db->query($query);
	while($line = $db->fetchrow($result)){
		$query="DELETE FROM forumposts WHERE threadid='$line[id]'";
		$db->query($query);
		$query="DELETE FROM forumread WHERE threadid='$line[id]'";
		$db->query($query);
	}

	$query="DELETE FROM forumthreads WHERE forumid='$id'";
	$db->query($query);

	$query="DELETE FROM forums WHERE id = '$id'";
	$db->query($query);

	set_time_limit(30);

	$msgs->addMsg("Forum Deleted");
}

function edit($id){
	global $PHP_SELF;
	if(!isset($id)) return false;

	$query = "SELECT isrealm,name,description,adminonly FROM forums WHERE id = '$id'";
    $result = $db->query($query);
	$line = $db->fetchrow($result);

	incHeader();

	echo "<table><form method=POST action=\"$PHP_SELF\">\n";
	echo "<input type=hidden name=id value=\"$id\">";
	echo "<tr><td class=body>Name</td><td class=body><input class=body type=text name=name value=\"$line[name]\"></td></tr>\n";
	if($line['isrealm']=='n')
		echo "<tr><td class=body>Description</td><td class=body><input class=body type=text name=description value=\"$line[description]\"></td></tr>\n";
	else
		echo "<input type=hidden name=description value=''>";
	echo "<tr><td class=body>Admin only?</td><td class=body><input type=checkbox name=admin value=y" . ($line['adminonly']=='y' ? ' checked' : '') . "></td></tr>";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Update\"><input class=body type=submit name=action value=Cancel></td></tr>\n";
	echo "</form></table>";

	incFooter(array('incAdminBlock'));
	exit();
}

function update($id,$name,$description,$admin){
	global $msgs;
	if(!isset($id)) return false;
	$query = "UPDATE forums SET name='$name', description='$description', adminonly='" . ($admin ? 'y' : 'n' ) . "' WHERE id='$id'";
	$db->query($query);
	$msgs->addMsg("Update Complete");
}



	$query = "SELECT * FROM forums ORDER BY priority ASC";
	$result = $db->query($query);


	incHeader();


	echo "<table width=100%>\n";
	echo "<tr><td class=header></td><td class=header>Forum Name</td><td class=header>Threads</td><td class=header>Posts</td></tr>\n";
	while($line = $db->fetchrow($result)){
		if($line['isrealm']=="y"){
			echo "<tr><td class=header>";

			echo "<a class=body href=\"javascript:confirmLink('$PHP_SELF?action=delete&id=$line[id]','delete this forum')\"><img src=/images/delete.gif border=0></a>";
			echo "<a class=body href=\"$PHP_SELF?action=moveup&id=$line[id]\"><img src=/images/up.png border=0></a>";
			echo "<a class=body href=\"$PHP_SELF?action=movedown&id=$line[id]\"><img src=/images/down.png border=0></a>";
			echo "<a class=body href=\"$PHP_SELF?action=edit&id=$line[id]\"><img src=/images/edit.gif border=0></a>";

			echo "</td><td class=header colspan=3>$line[name]</td></tr>";
		}else{
			echo "<tr><td class=body>";
			echo "<a class=body href=\"javascript:confirmLink('$PHP_SELF?action=delete&id=$line[id]','delete this forum')\"><img src=/images/delete.gif border=0></a>";
			echo "<a class=body href=\"$PHP_SELF?action=moveup&id=$line[id]\"><img src=/images/up.png border=0></a>";
			echo "<a class=body href=\"$PHP_SELF?action=movedown&id=$line[id]\"><img src=/images/down.png border=0></a>";
			echo "<a class=body href=\"$PHP_SELF?action=edit&id=$line[id]\"><img src=/images/edit.gif border=0></a></td>";
			echo "<td class=body>$line[name]</td>";
			echo "<td class=body>$line[threads]</td>";
			echo "<td class=body>$line[posts]</td></tr>\n";
		}
	}
	echo "</table><br>\n";


	echo "<table><form method=POST action=\"$PHP_SELF\">\n";
	echo "<tr><td class=header colspan=2>Add Forum</td></tr>";
	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=name></td></tr>\n";
	echo "<tr><td class=body>Is a Realm?</td><td class=body><input class=body type=checkbox name=isrealm></td></tr>\n";
	echo "<tr><td class=body>Admin only?</td><td class=body><input class=body type=checkbox name=admin></td></tr>\n";
	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=description></td></tr>\n";
	echo "<tr><td class=body></td><td class=body><INPUT class=body TYPE=submit name=action VALUE=\"Add\"></td></tr>\n";
	echo "</form></table>";


	incFooter(array('incAdminBlock'));
