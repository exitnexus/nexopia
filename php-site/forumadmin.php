<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"forums"))
		die("Permission denied");

	$childdata = getForumData();
//	$parentdata = getForumParentData();

	if(!isset($catid))		$catid=0;
	if(!isset($action))		$action="";

	switch($action){
		case "addforum":			addForum(); 				break;
		case "Create Forum":		insertForum($data); 		break;
		case "editforum":			editForum($id);				break;
		case "Update Forum":		updateForum($data,$id);		break;

		case "addrealm":			addRealm();					break;
		case "Create Realm":		insertRealm($data);			break;
		case "editrealm":			editRealm($id);				break;
		case "Update Realm":		updateRealm($data,$id);		break;

		case "delete":
			$mods->adminlog("delete forum", "Delete forum $id");
			$forums->deleteForum($id);
			break;
		case "moveup":
			$mods->adminlog("moveup forum", "Move up forum $id");
			$forums->db->prepare_query("SELECT parent FROM forums WHERE id = ?", $id);

			if($forums->db->numrows() > 0){
				$parent = $forums->db->fetchfield();
				increasepriority($forums->db, "forums", $id, $forums->db->prepare("parent = ? && official='y'", $parent));

				$childdata = getForumData();
//				$parentdata = getForumParentData();
			}
			break;
		case "movedown":
			$mods->adminlog("movedown forum", "Move down forum $id");
			$forums->db->prepare_query("SELECT parent FROM forums WHERE id = ?", $id);

			if($forums->db->numrows() > 0){
				$parent = $forums->db->fetchfield();
				decreasepriority($forums->db, "forums", $id, $forums->db->prepare("parent = ? && official='y'", $parent));

				$childdata = getForumData();
//				$parentdata = getForumParentData();
			}
			break;
	}

	listForums();	//exit


function addForum($data = array()){
	global $childdata, $forums;

	$name="";
	$description="";
	$parent=0;
	$autolock=0;
	$edit='n';
	$countposts='y';
	$public='y';
	$mute='n';
	$sorttime=14;

	extract($data);

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr><td class=header colspan=2 align=center>Add Forum</td></tr>";

	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[name] maxlength=32 value=\"$name\"></td></tr>";
	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=data[description] maxlength=250 size=30 value=\"$description\"></td></tr>";
	echo "<tr><td class=body>Parent:</td><td class=body><select class=body name=data[parent]><option value=0>Forums" . makeCatSelect(makeForumBranch($childdata,0,1),$parent) . "</select></td></tr>";
	echo "<tr><td class=header colspan=2 align=center>Options</td></tr>";

	echo "<tr><td class=body>Auto-lock time:</td><td class=body><input class=body type=text name=data[autolock] size=5 value=0> days. 0 to disable, otherwise time in days</td></tr>";
	echo "<tr><td class=body>Allow Post Editing:</td><td class=body>" . make_radio_key("data[edit]", array('y'=>"Yes", 'n'=>"No"),$edit) . "</td></tr>";
//	echo "<tr><td class=body>Public:</td><td class=body>" . make_radio_key("data[public]", array('y'=>"Yes", 'n'=>"No"),$public) . "&nbsp; Only invited users can enter a private forum.</td></tr>";
	echo "<tr><td class=body>Mute:</td><td class=body>" . make_radio_key("data[mute]", array('y'=>"Yes", 'n'=>"No"),$mute) . "&nbsp; Only mods can post in a mute forum.</td></tr>";
	echo "<tr><td class=body>Show threads from last</td><td class=body><select class=body name=sorttime>" . make_select_list_key($forums->sorttimes,$sorttime) . "</select></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Create Forum'><input class=body type=submit value=Cancel></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertForum($data){
	global $msgs, $userData, $forums, $sorttimes, $mods;

	$name="";
	$description="";
	$parent=0;
	$autolock=0;
	$edit='n';
	$countposts='y';
//	$public='y';
	$mute='n';
	$sorttime=14;

	extract($data);

	$error=false;
	if($name==""){
		$msgs->addMsg("Forum needs a name");
		$error=true;
	}
	if($description==""){
		$msgs->addMsg("Forum needs a description");
		$error=true;
	}

	$forums->db->prepare_query("SELECT id FROM forums WHERE name = ?", $name);
	if($forums->db->numrows() > 0){
		$msgs->addMsg("A forum already exists with that name");
		$error=true;
	}


	$forums->db->prepare_query("SELECT id,parent FROM forums WHERE id = ?", $parent);
	if($forums->db->numrows()==0){
		$msgs->addMsg("Must have a valid parent forum");
		$error=true;
	}else{
		$line = $forums->db->fetchrow();
		if($line['parent']!='0'){
			$msgs->addMsg("Parent must be a realm");
			$error=true;
		}
	}

	if($error)
		addForum($data); //exit

	$mods->adminlog('create forum',"Create Forum: $name");

	$priority = getMaxPriority($forums->db, "forums", $forums->db->prepare("parent = ?", $parent));

	$commands = array(); 				$params = array();
	$commands[] = "name = ?"; 			$params[] = $name;
	$commands[] = "description = ?";	$params[] = $description;
	$commands[] = "parent = ?"; 		$params[] = $parent;
	$commands[] = "autolock = ?";		$params[] = $autolock *86400;
	$commands[] = "edit = ?";			$params[] = $edit;
	$commands[] = "public='y'";
	$commands[] = "mute = ?";			$params[] = $mute;
	$commands[] = "official='y'";
	$commands[] = "priority = ?";		$params[] = $priority;
	if(!in_array($sorttime,$sorttimes))
		$sorttime = 14;
	$commands[] = "sorttime = ?";		$params[] = $sorttime;

	$query = "INSERT INTO forums SET " . implode(", ",$commands);
	$forums->db->prepare_array_query($query, $params);



	global $childdata,$parentdata; //update page

	$childdata = getForumData();
//	$parentdata = getForumParentData();

	$msgs->addMsg("Forum Created");
}

function editForum($id){
	global $childdata, $sorttimes, $forums;

	$forums->db->prepare_query("SELECT * FROM forums WHERE id = ? && official='y'", $id);
	$data = $forums->db->fetchrow();

	extract($data);

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<input type=hidden name=id value='$id'";
	echo "<tr><td class=header colspan=2 align=center>Add Forum</td></tr>";

	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[name] maxlength=32 value=\"$name\"></td></tr>";
	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=data[description] maxlength=250 size=30 value=\"$description\"></td></tr>";
	echo "<tr><td class=body>Parent:</td><td class=body><select class=body name=data[parent]><option value=0>Forums" . makeCatSelect(makeForumBranch($childdata,0,1),$parent) . "</select></td></tr>";
	echo "<tr><td class=header colspan=2 align=center>Options</td></tr>";

	echo "<tr><td class=body>Auto-lock time:</td><td class=body><input class=body type=text name=data[autolock] size=5 value=" . ($autolock/86400) . "> days. 0 to disable, otherwise time in days</td></tr>";
	echo "<tr><td class=body>Allow Post Editing:</td><td class=body>" . make_radio_key("data[edit]", array('y'=>"Yes", 'n'=>"No"),$edit) . "</td></tr>";
//	echo "<tr><td class=body>Public:</td><td class=body>" . make_radio_key("data[public]", array('y'=>"Yes", 'n'=>"No"),$public) . "&nbsp; Only invited users can enter a private forum.</td></tr>";
	echo "<tr><td class=body>Mute:</td><td class=body>" . make_radio_key("data[mute]", array('y'=>"Yes", 'n'=>"No"),$mute) . "&nbsp; Only mods can post in a mute forum.</td></tr>";
	echo "<tr><td class=body>Show threads from last</td><td class=body><select class=body name=sorttime>" . make_select_list_key($sorttimes,$sorttime) . "</select></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Update Forum'><input class=body type=submit value=Cancel></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function updateForum($data,$id){
	global $msgs, $userData, $sorttimes, $forums, $mods;

	$name="";
	$description="";
	$parent=0;
	$autolock=0;
	$edit='n';
	$countposts='y';
//	$public='y';
	$mute='n';
	$sorttime=14;

	extract($data);

	$error=false;
	if($name==""){
		$msgs->addMsg("Forum needs a name");
		$error=true;
	}

	$forums->db->prepare_query("SELECT id FROM forums WHERE name = ? && id != ?", $name, $id);
	if($forums->db->numrows() > 0){
		$msgs->addMsg("A forum already exists with that name");
		$error=true;
	}

	if($description==""){
		$msgs->addMsg("Forum needs a description");
		$error=true;
	}
	if($parent!=0){
		$forums->db->prepare_query("SELECT id, parent FROM forums WHERE id = ?", $parent);
		if($forums->db->numrows()==0){
			$msgs->addMsg("Must have a valid parent forum");
			$error=true;
		}elseif($forums->db->fetchfield(2,0)!=0){
			$msgs->addMsg("Parent must be a realm");
			$error=true;
		}
	}

	if($error)
		editForum($id); //exit

	$mods->adminlog('update forum',"Update Forum: $name");

	$commands = array(); 				$params = array();
	$commands[] = "name = ?"; 			$params[] = $name;
	$commands[] = "description = ?";	$params[] = $description;
	$commands[] = "parent = ?"; 		$params[] = $parent;
	$commands[] = "autolock = ?";		$params[] = $autolock *86400;
	$commands[] = "edit = ?";			$params[] = $edit;
	$commands[] = "public='y'";
	$commands[] = "mute = ?";			$params[] = $mute;
	$commands[] = "official='y'";
	if(!in_array($sorttime,$sorttimes))
		$sorttime = 14;
	$commands[] = "sorttime = ?";		$params[] = $sorttime;



	$params[] = $id;

	$forums->db->prepare_array_query("UPDATE forums SET " . implode(", ",$commands) . " WHERE id = ? && official='y'", $params);

	global $childdata,$parentdata; //update page

	$childdata = getForumData();
//	$parentdata = getForumParentData();

	$msgs->addMsg("Forum Updated");
}

function addRealm($data = array()){
	global $childdata;

	$name="";

	extract($data);

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr><td class=header colspan=2 align=center>Add Realm</td></tr>";

	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[name] maxlength=32 value=\"$name\"></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Create Realm'><input class=body type=submit value=Cancel></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertRealm($data){
	global $msgs, $forums, $mods;

	$name="";

	extract($data);

	$parent=0;


	$error=false;
	if($name==""){
		$msgs->addMsg("Realm needs a name");
		$error=true;
	}

	if($error)
		addRealm($data); //exit

	$mods->adminlog('create forum realm',"Create Forum Realm: $name");

	$priority = getMaxPriority($forums->db, "forums", $forums->db->prepare("parent = ?",$parent));

	$commands = array();			$params = array();
	$commands[] = "name = ?";		$params[] = $name;
	$commands[] = "parent = ?";		$params[] = $parent;
	$commands[] = "official = 'y'";
	$commands[] = "public = 'y'";
	$commands[] = "priority = ?";	$params[] = $priority;

	$query = "INSERT INTO forums SET " . implode(", ",$commands);
	$forums->db->prepare_array_query($query, $params);

	global $childdata, $parentdata; //update page

	$childdata = getForumData();
//	$parentdata = getForumParentData();

	$msgs->addMsg("Realm Created");
}

function editRealm($id){
	global $childdata;

	$forums->db->prepare_query("SELECT name FROM forums WHERE id = ? && official='y'", $id);
	$data = $forums->db->fetchrow();

	extract($data);

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<input type=hidden name=id value='$id'>";

	echo "<tr><td class=header colspan=2 align=center>Add Realm</td></tr>";

	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[name] maxlength=32 value=\"$name\"></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Update Realm'><input class=body type=submit value=Cancel></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function updateRealm($data,$id){
	global $msgs, $forums, $mods;

	$name="";

	extract($data);

	$parent=0;


	$error=false;
	if($name==""){
		$msgs->addMsg("Realm needs a name");
		$error=true;
	}
/*	if($parent!=0){
		$query = "SELECT id FROM forums WHERE id='$parent'";
		$result = $forums->db->query($query);
		if($forums->db->numrows($result)==0){
			$msgs->addMsg("Must have a valid parent forum");
			$error=true;
		}
	}
*/

	if($error)
		editRealm($id); //exit

	$mods->adminlog('update forum realm',"Create Forum Realm: $name");

	$commands = array();			$params = array();
	$commands[] = "name = ?";		$params[] = $name;
	$commands[] = "public = 'y'";
	$commands[] = "parent = ?";		$params[] = $parent;

	$params[] = $id;

	$query = "UPDATE forums SET " . implode(", ",$commands) . " WHERE id = ? && official='y'";
	$forums->db->prepare_array_query($query, $params);

	global $childdata,$parentdata; //update page

	$childdata = getForumData();
//	$parentdata = getForumParentData();

	$msgs->addMsg("Realm Updated");
}


function listForums(){
	global $childdata, $mods, $config;

	$mods->adminlog('list forums',"List Forums");

	incHeader();

	echo "<table width=100%><form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr>\n";
	echo "  <td class=header>Category Name</td>";
	echo "  <td class=header>Threads</td>";
	echo "  <td class=header>Posts</td>";
	echo "  <td class=header width=16><img src=$config[imageloc]edit.gif border=0></td>";
	echo "  <td class=header width=16><img src=$config[imageloc]up.png border=0></td>";
	echo "  <td class=header width=16><img src=$config[imageloc]down.png border=0></td>";
	echo "  <td class=header width=16><img src=$config[imageloc]delete.gif border=0></td>";
	echo "</tr>\n";


	$branch = makeForumBranch($childdata);

	foreach($branch as $line){
		echo "<tr>";
		echo "<td class=body>";
		echo str_repeat("&nbsp;- ",$line['depth']);
		echo "$line[name] ($line[depth])";
		echo "</td>";
		echo "<td class=body>" . ($line['parent']==0 ? "N/A" : $line['info']['threads']) . "</td>";
		echo "<td class=body>" . ($line['parent']==0 ? "N/A" : $line['info']['posts']) . "</td>";

		echo "<td class=body>";
		echo "<a class=body href=\"$_SERVER[PHP_SELF]?action=edit" . ($line['parent']==0 ? "realm" : "forum" ) . "&id=$line[id]\"><img src=$config[imageloc]edit.gif border=0></a>";
		echo "</td>";

		echo "<td class=body>";
		if($line['info']['priority'] > 1)
			echo "<a class=body href=\"$_SERVER[PHP_SELF]?action=moveup&id=$line[id]\"><img src=$config[imageloc]up.png border=0></a>";
		echo "</td>";
		echo "<td class=body>";
		if($line['info']['priority'] < count($childdata[$line['parent']]) )
			echo "<a class=body href=\"$_SERVER[PHP_SELF]?action=movedown&id=$line[id]\"><img src=$config[imageloc]down.png border=0></a>";
		echo "</td>";
		echo "<td class=body>";
		if(!$line['isparent'])
			echo "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=delete&id=$line[id]','delete this " . ($line['parent']==0 ? "realm" : "forum" ) . "?')\"><img src=$config[imageloc]delete.gif border=0></a>";
		echo "</td>";
		echo "</tr>\n";
	}



	echo "<tr><td class=header colspan=7><a class=header href=$_SERVER[PHP_SELF]?action=addforum>Create Forum</a> | <a class=header href=$_SERVER[PHP_SELF]?action=addrealm>Create Realm</a></td></tr>";
	echo "</form></table>\n";

	incFooter();

}


///////////////////////////////////
////////// general funcs //////////
///////////////////////////////////

function dispBranch(&$data,$basedepth=0){
	global $table;
	foreach($data as $line){
		echo "<tr><td class=body>";
		echo "<input class=body type=checkbox name=checkID[] value=$line[id]>";
		for($i=0;$i<$line['depth']+$basedepth-1;$i++)
			echo "&nbsp;- ";
		echo "<a class=body href=$_SERVER[PHP_SELF]?catid=$line[id]&table=$table>" . $line['name'] . "</a> ($line[depth])";
		echo "</td></tr>\n";
	}
}

function dispRoot(&$data){
	global $table;

	$depth=0;
	$maxdepth=count($data)-1;
	foreach($data as $line){
		if($depth==$maxdepth)
			echo "<tr><td class=header>";
		else
			echo "<tr><td class=body>";
		echo "<input class=body type=checkbox name=checkID[] value=$line[id]>";
		for($i=0;$i<$depth;$i++)
			echo "&nbsp;- ";
		echo "<a class=body href=$_SERVER[PHP_SELF]?catid=$line[id]&table=$table>" . $line['name'] . "</a> ($line[depth])";
		echo "</td></tr>\n";
		$depth++;
	}
}


function & getForumData(){	//table of type id,parent,name
	global $forums;
	$forums->db->query("SELECT * FROM forums WHERE official='y' ORDER BY priority ASC");

	$data = array();
	while($line = $forums->db->fetchrow())
		$data[$line['parent']][$line['id']]=$line;

	return $data;
}

function & getForumParentData(){	//table of type id,parent,name
	global $forums;
	$forums->db->query("SELECT * FROM forums WHERE official='y' ORDER BY priority ASC");

	$data = array();
	while($line = $forums->db->fetchrow())
		$data[$line['id']][$line['parent']]=$line;
 	return $data;
}

function makeForumRoot(&$table,$catid,$defname="Home",$depth=0){
	if(!$catid){
		if($defname)
			return array(array('id' => 0, 'parent' => 0, 'depth'=>$depth,'name' => $defname, 'info'=>array()));
		else
			return array();
	}
	$list=$table[$catid];
	$key = key($list);
	$val = current($list);

	$ret = makeForumRoot($table,$key,$defname,$depth-1);
	$ret[] = array('id' => $catid, 'parent' => $key, 'depth'=>$depth,'name' => $val['name'], 'info'=>$val);
	return $ret;
}

function makeForumBranch(&$table,$parent=0,$maxdepth=0,$depth=1){
	if(!isset($table[$parent]))
		return array();
	$list=$table[$parent];

	$result=array();
	foreach($list as $key => $val){
		$result[] = array('id' => $key, 'depth' => $depth, 'name' => $val['name'], 'info'=>$val, 'parent' => $parent, 'isparent' => (isset($table[$key]) ? 1 : 0));
		if(isset($table[$key]) && (!$maxdepth || $maxdepth>$depth))
			$result= array_merge($result, makeForumBranch($table,$key,$maxdepth,$depth+1) );
	}

	return $result;
}
