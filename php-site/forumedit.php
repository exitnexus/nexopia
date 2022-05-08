<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($id) || $id==0)
		die("Bad forumid");

	$perms = $forums->getForumPerms($id);	//checks it's a forum, not a realm


	if(!$perms['admin'])
		die("You don't have permission to edit this forum");

	switch($action){
		case "editforum":			editForum($id);				break;
		case "Update Forum":		updateForum($data,$id);		break;
		case "Delete":
			$forums->deleteForum($id);
			header("location: /forums.php");
			exit;
	}

	editForum($id);	//exit


function editForum($id){
	global $childdata, $forums, $db, $sorttimes;

	$forums->db->prepare_query("SELECT name, description, autolock, edit, public, mute, ownerid FROM forums WHERE forums.id = ?", $id);
	$data = $forums->db->fetchrow();

	$ownername = getUserName($data['ownerid']);

	extract($data);

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";


	echo "<tr><td class=body colspan=2>";
	echo "<a class=body href=forumsusercreated.php>User Created Forums</a> > ";

	echo "<a class=body href=forumthreads.php?fid=$id>$name</a> > ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?id=$id>Edit Forum</a>";
	echo "</td></tr>";

	echo "<input type=hidden name=id value='$id'";
	echo "<tr><td class=header colspan=2 align=center>Edit Forum</td></tr>";

	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[name] maxlength=32 value=\"$name\"></td></tr>";
	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=data[description] maxlength=250 size=30 value=\"$description\"></td></tr>";
	echo "<tr><td class=body>Owner:</td><td class=body><input class=body type=text name=data[ownername] maxlength=12 size=12 value=\"$ownername\"></td></tr>";
	echo "<tr><td class=header colspan=2 align=center>Options</td></tr>";

	echo "<tr><td class=body>Auto-lock time:</td><td class=body><input class=body type=text name=data[autolock] size=5 value=" . ($autolock/86400) . "> days. 0 to disable, otherwise time in days</td></tr>";
	echo "<tr><td class=body>Allow Post Editing:</td><td class=body>" . make_radio_key("data[edit]", array('y'=>"Yes", 'n'=>"No"),$edit) . "</td></tr>";
	echo "<tr><td class=body>Public:</td><td class=body>" . make_radio_key("data[public]", array('y'=>"Yes", 'n'=>"No"),$public) . "&nbsp; Only invited users can enter a private forum.</td></tr>";
	echo "<tr><td class=body>Mute:</td><td class=body>" . make_radio_key("data[mute]", array('y'=>"Yes", 'n'=>"No"),$mute) . "&nbsp; Only mods can post in a mute forum.</td></tr>";
//	echo "<tr><td class=body>Show threads from last</td><td class=body><select class=body name=sorttime>" . make_select_list_key($sorttimes,$sorttime) . "</select></td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Update Forum'></td></tr>";
	echo "</form></table>";

	echo "<br>";
	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=id value=$id>";
	echo "<tr><td class=header align=center>Delete Forum</td></tr>";
	echo "<tr><Td class=body>This will permanently delete the forum and all threads and posts associated with it</td></tr>";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Delete onClick=\"alert('This will permanently delete the forum and all threads and posts associated with it'); return confirm('Are you sure you want to delete this forum?');\"></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function updateForum($data,$id){
	global $msgs,$userData,$forums,$sorttime,$sorttimes, $cache;

	$name="";
	$description="";
	$parent=0;
	$autolock=0;
	$edit='n';
	$countposts='y';
	$public='y';
	$mute='n';
	$sorttime = 14;
	$ownername = "";

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

	$ownerid = getUserID($ownername);

	if(!$ownerid){
		$msgs->addMsg("Must specifiy a real user as the new owner");
		$error= true;
	}

	if($public != 'n')
		$public='y';

	if($error)
		editForum($id); //exit

	$commands = array();
	$commands[] = $forums->db->prepare("name = ?", removehtml($name));
	$commands[] = $forums->db->prepare("description = ?", removehtml($description));
	$commands[] = "parent='0'";
	$commands[] = $forums->db->prepare("autolock = ?", ($autolock*86400) );
	$commands[] = $forums->db->prepare("edit = ?", $edit);
	$commands[] = $forums->db->prepare("public = ?", $public);
	$commands[] = $forums->db->prepare("mute = ?", $mute);
	$commands[] = $forums->db->prepare("ownerid = ?", $ownerid);
	$commands[] = "official='n'";
	if(!in_array($sorttime, $forums->sorttimes))
		$sorttime = 14;
	$commands[] = "sorttime='$sorttime'";

	$query = "UPDATE forums SET " . implode(", ",$commands) . $forums->db->prepare(" WHERE id = ?", $id);
	$forums->db->query($query);

	$cache->remove(array($id, "forumdata-$id"));

	$msgs->addMsg("Forum Updated");
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
	$query = "SELECT * FROM forums WHERE official='y' ORDER BY priority ASC";
    $result = $forums->db->query($query);

	$data = array();
	while($line = $forums->db->fetchrow($result))
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
