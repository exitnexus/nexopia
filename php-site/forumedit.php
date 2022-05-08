<?

	$login=1;

	require_once("include/general.lib.php");

	if(!($id = getREQval('id')))
		die("Bad forumid");

	$perms = $forums->getForumPerms($id);	//checks it's a forum, not a realm


	if(!$perms['admin'])
		die("You don't have permission to edit this forum");

	switch($action){
		case "editforum":
			editForum($id);
			break;
		case "Update Forum":
			if($data = getPOSTVal('data', 'array'))
				updateForum($data,$id);
			break;
		case "Delete":
			$forums->deleteForum($id);
			header("location: /forums.php");
			exit;
	}

	editForum($id);	//exit


function editForum($id){
	global $childdata, $forums, $db, $sorttimes, $mods, $userData, $config;

	$data = $forums->getForums($id);

	$ownername = getUserName($data['ownerid']);

	$isForumAdmin = $mods->isAdmin($userData['userid'],'forums');

	$cats = $forums->getCategories();
	$outputcats = array(-1 => 'Select a category');
	foreach ($cats as $catid => $cat)
		if ($isForumAdmin || $cat['official'] != 'y')
			$outputcats[$catid] = $cat['name'];

	extract($data);

	$template = new template('forums/forumedit');
	
	$template->set('forumTrail', $forums->getForumTrail($data, "body"));
	$template->set('id', $id);
	$template->set('name', $name);
	$template->set('description', $description);
	$template->set('categoriesSelection', make_select_list_key($outputcats, $categoryid));
	$template->set('ownername', $ownername);
	$template->set('maxusernamelength', $config['maxusernamelength']);
	$template->set('autolock', $autolock/86400);
	$template->set('allowPostEditingButtons', make_radio_key("data[edit]", $forums->editlengths, $edit));
	$template->set('publicButtons', make_radio_key("data[public]", array('y'=>"Yes", 'n'=>"No"),$public));
	$template->set('officialButtons', make_radio_key("data[official]", array('y'=>"Yes", 'n'=>"No"),$official));
	$template->set('muteButtons', make_radio_key("data[mute]", array('y'=>"Yes", 'n'=>"No"),$mute));
	$template->set('isForumAdmin', $isForumAdmin);
	$template->set('lastThreadsSelection', make_select_list_key($forums->sorttimes,$sorttime));
	$template->set('rules', $rules);
	$template->display();
	
	exit;
}

function updateForum($data,$id){
	global $msgs,$userData,$forums,$sorttime,$sorttimes, $cache, $mods;

	$defaults = array(
		'name' => "",
		'description' => "",
		'catid' => -1,
		'autolock' => 0,
		'edit' => 'n',
		'countposts' => 'y',
		'public' => 'y',
		'official' => 'n',
		'mute' => 'n',
		'sorttime' => 14,
		'ownername' => "",
		'rules' => "",
		);
	
	extract(setDefaults($data, $defaults));

	$isForumAdmin = $mods->isAdmin($userData['userid'],'forums');

	$error=false;
	if($name==""){
		$msgs->addMsg("Forum needs a name");
		$error=true;
	}

	$res = $forums->db->prepare_query("SELECT id FROM forums WHERE name = ? && id != #", $name, $id);
	if($res->fetchrow()){
		$msgs->addMsg("A forum already exists with that name");
		$error=true;
	}

	if($description==""){
		$msgs->addMsg("Forum needs a description");
		$error=true;
	}

	$cats = $forums->getCategories();
	if(!isset($cats[$catid])){
		$msgs->addMsg("Forum needs an initial category");
		$error=true;
	}

	if (!$isForumAdmin && $catid != -1 && $cats[$catid]['official'] == 'y')
	{
		$catid = -1;
		$msgs->addMsg("You do not have permission to place a forum in '{$cats[$catid][name]}'");
		$error=true;
	}
	if ($catid != -1 && $cats[$catid]['official'] == 'y')
	{
		$official = 'y'; // force official forums in official categories.
	}
	if (!$isForumAdmin && $official == 'y')
	{
		$msgs->addMsg("You do not have permission to create an official forum.");
		$error = true;
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
	$commands[] = $forums->db->prepare("categoryid = #", $catid);
	$commands[] = $forums->db->prepare("autolock = ?", ($autolock*86400) );
	$commands[] = $forums->db->prepare("edit = ?", $edit);
	$commands[] = $forums->db->prepare("public = ?", $public);
	$commands[] = $forums->db->prepare("official = ?", $official);
	$commands[] = $forums->db->prepare("unofficial = ?", ($official=='y')? 'n' : 'y');
	$commands[] = $forums->db->prepare("mute = ?", $mute);
	$commands[] = $forums->db->prepare("ownerid = ?", $ownerid);
	if($isForumAdmin){
		if(!isset($forums->sorttimes[$sorttime]))
			$sorttime = 14;
		$commands[] = $forums->db->prepare("sorttime = #", $sorttime);
	}

	$commands[] = $forums->db->prepare("rules = ?", removeHTML(trim($rules)));

	$query = "UPDATE forums SET " . implode(", ",$commands) . $forums->db->prepare(" WHERE id = #", $id);
	$forums->db->query($query);

	$forums->invalidateForums($id);

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
    $res = $result = $forums->db->query($query);

	$data = array();
	while($line = $res->fetchrow($result))
		$data[$line['parent']][$line['id']]=$line;

	return $data;
}

function & getForumParentData(){	//table of type id,parent,name
	global $forums;
    $res = $forums->db->query("SELECT * FROM forums WHERE official='y' ORDER BY priority ASC");

	$data = array();
	while($line = $res->fetchrow())
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
