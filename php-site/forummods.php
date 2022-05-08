<?

	$login=1;

	require_once("include/general.lib.php");

	$fid = getREQval('fid', 'int');

	$isAdmin = $mods->isAdmin($userData['userid'],'forums');

	//skip this check if it's the general case and is an admin
	if($fid != 0 || !$isAdmin){
		$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

		if(!$perms['editmods'])
			die("You don't have permission to edit the mods of this forum");
	}


	$possible = array();
	if($fid == 0)
		$possible['view']			= "Read private forums";
	$possible['post']			= "Post in Unlocked Threads";
	$possible['postlocked']		= "Post in Locked Threads";
	if($isAdmin)
		$possible['editposts'] 	= "Edit All Posts";
	$possible['editownposts'] 	= "Edit Own Posts";
	$possible['deleteposts']	= "Delete Posts";
	if($fid == 0 || $perms['cols']['official'] == 'y')
		$possible['move']		= "Move Threads";
	$possible['deletethreads']	= "Delete Threads";
	$possible['lock']			= "Lock Threads";
	$possible['stick']			= "Sticky Threads";
	$possible['announce']		= "Announce Threads";
	$possible['flag']			= "Flag Threads";
	$possible['mute']			= "Mute Users";
	if($fid == 0 || $perms['cols']['official'] == 'n')
		$possible['invite']		= "Invite Users";
	$possible['modlog']			= "View Modlog";
	$possible['editmods']		= "Edit Mods";

	switch($action){
		case "add":
			addMod();
		case "Create":
			if($data = getPOSTval('data', 'array'))
				insertMod($data);
			break;
		case "edit":
			if($uid = getREQval('uid', 'int'))
				editMod($uid);
		case "Update":
			if(($uid = getPOSTval('uid', 'int')) && ($data = getPOSTval('data', 'array')))
				updateMod($uid, $data);
			break;
		case "delete":
			if(($uid = getREQval('uid', 'int')) && ($k = getREQval('k')) && checkKey("$uid:$fid", $k))
				deleteMod($uid);
			break;
	}

	listMods();

////////////////////////////////////////

function addMod($data = array()){
	global $fid,$possible;

	if(!isset($data['username']))
		$data['username']="";

	foreach($possible as $k => $n){
		if(!isset($data[$k]))
			$data[$k]="";
		$checkBox[$k] = makeCheckBox("data[$k]", $n, $data[$k] == 'y');
	}

	$template = new template('forums/forummods/addMod');
	$template->set('fid', $fid);
	$template->set('possible', $possible);
	$template->set('checkBox', $checkBox);
	$template->set('data', $data);
	$template->display();
	exit;
}

function insertMod($data){
	global $forums, $possible, $msgs, $fid, $cache;

	if(!isset($data['username']) || trim($data['username'])=="")
		addMod($data);

	$userid=getUserId(trim($data['username']));

	if(!$userid)
		addMod($data); //exit

	$res = $forums->db->prepare_query("SELECT unmutetime FROM forummute WHERE userid = ? && forumid = 0", $userid);
	$mute = $res->fetchrow();

	if($mute){
		$unmutetime = $mute['unmutetime'];
		if($unmutetime == 0 || $unmutetime > time()){
			$msgs->addMsg("Sorry, this user has been globally banned and cannot become a mod");
			addMod($data); //exit
		}
	}

	$commands = array();
	foreach($possible as $k => $n){
		if(isset($data[$k]))
			$commands[] = "`$k` = 'y'";
		else
			$commands[] = "`$k` = 'n'";
	}

	$forums->modLog('addmod',$fid,0,$userid);

	$forums->db->prepare_query("INSERT INTO forummods SET forumid = ?, userid = ?, " . implode(", ", $commands), $fid, $userid);

	$cache->remove("forummodpowers-$userid-$fid");

	$msgs->addMsg("Mod Created");
}

function editMod($uid){
	global $forums, $possible, $msgs, $fid;

	$res = $forums->db->prepare_query("SELECT * FROM forummods WHERE userid = ? && forumid = ?", $uid, $fid);
	$data = $res->fetchrow();

	foreach($possible as $k => $n)
		$checkBox[$k] = makeCheckBox("data[$k]", $n, $data[$k] == 'y');

	$template = new template('forums/forummods/editMod');
	$template->set('fid', $fid);
	$template->set('data', $data);
	$template->set('uid', $uid);
	$template->set('username', getUserName($data['userid']));
	$template->set('checkBox', $checkBox);
	$template->set('possible', $possible);

	$template->display();
	exit;
}

function updateMod($uid, $data){
	global $forums, $possible, $msgs, $fid, $cache;

	$commands = array();
	foreach($possible as $k => $n){
		if(isset($data[$k]))
			$commands[] = "`$k` = 'y'";
		else
			$commands[] = "`$k` = 'n'";
	}

	$forums->modLog('editmod', $fid, 0, $uid);

	$forums->db->prepare_query("UPDATE forummods SET " . implode(", ", $commands) . " WHERE userid = ? && forumid = ?", $uid, $fid);

	$cache->remove("forummodpowers-$uid-$fid");

	$msgs->addMsg("Mod Updated");
}

function deleteMod($uid){
	global $forums, $msgs, $fid, $cache;

	$forums->modLog('removemod', $fid, 0, $uid);

	$forums->db->prepare_query("DELETE FROM forummods WHERE userid = ? && forumid = ?", $uid, $fid);

	$cache->remove("forummodpowers-$uid-$fid");

	$msgs->addMsg("Mod Deleted");
}

function listMods(){
	global $forums, $fid, $config, $possible;

	$forumdata = false;
	if($fid){
		$forumdata = $forums->getForums($fid);
	}


	$res = $forums->db->prepare_query("SELECT forummods.*, '' as username FROM forummods WHERE forumid = ?", $fid);

	$rows = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$rows[$line['userid']] = $line;
		$uids[] = $line['userid'];
	}

	if(count($uids)){
		$users = getUserInfo($uids);

		foreach($users as $line)
			$rows[$line['userid']]['username'] = $line['username'];
	}

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username');

	$powers = array();
	$key = array();

	foreach($rows as $line){
		$key[$line['userid']][$fid] = makeKey("$line[userid]:$fid");
		$vals = array();
		foreach($possible as $n => $v)
			if($line[$n] == 'y')
				$vals[] = $v;
		$powers[$line['userid']] = implode(", ", $vals);
	}

	$forumTrail = false;
	if ($fid)
		$forumTrail = $forums->getForumTrail($forumdata, "body");

	$template = new template('forums/forummods/listMods');
	$template->set('forumTrail', $forumTrail);
	$template->set('fid', $fid);
	$template->set('rows', $rows);
	$template->set('config', $config);
	$template->set('powers', $powers);
	$template->set('key', $key);
	$template->display();
	exit;
}

