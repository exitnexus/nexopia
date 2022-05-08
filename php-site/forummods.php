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
			if(!empty($_POST['data']))
				insertMod($_POST['data']);
			break;
		case "edit":
			if(!empty($_REQUEST['uid']))
				editMod($_REQUEST['uid']);
		case "Update":
			if(!empty($_POST['uid']) && !empty($_POST['data']))
				updateMod($_POST['uid'], $_POST['data']);
			break;
		case "delete":
			if(!empty($_REQUEST['uid']))
				deleteMod($_REQUEST['uid']);
			break;
	}

	listMods();

////////////////////////////////////////

function addMod($data = array()){
	global $fid,$possible;

	if(!isset($data['username']))
		$data['username']="";

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<tr><td class=header colspan=2>Create New Moderator</td></tr>";
	echo "<tr><td class=body>Username:<input class=body type=text name=data[username] value='$data[username]'></td></tr>";

	foreach($possible as $k => $n){
		if(!isset($data[$k]))
			$data[$k]="";
		echo "<tr><td class=body>" . makeCheckBox("data[$k]", $n, $data[$k] == 'y')  ."</td></tr>";
	}

	echo "<tr><td class=body>Mod powers never take away abilities.<br>If you don't want someone to post, mute them.</td></tr>";

	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Create><input class=body type=submit value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function insertMod($data){
	global $forums, $possible, $msgs, $fid, $cache;

	if(!isset($data['username']) || trim($data['username'])=="")
		addMod($data);

	$userid=getUserId(trim($data['username']));

	if(!$userid)
		addMod($data); //exit

	$forums->db->prepare_query("SELECT unmutetime FROM forummute WHERE userid = ? && forumid = 0", $userid);

	if($forums->db->numrows()){
		$unmutetime = $forums->db->fetchfield();
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

	$cache->remove(array($userid, "forummods-$userid-$fid"));

	$msgs->addMsg("Mod Created");
}

function editMod($uid){
	global $forums, $possible, $msgs, $fid;

	$forums->db->prepare_query("SELECT * FROM forummods WHERE userid = ? && forumid = ?", $uid, $fid);
	$data = $forums->db->fetchrow();

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<input type=hidden name=uid value=$uid>";
	echo "<tr><td class=header colspan=2>Edit Mod</td></tr>";

	echo "<tr><td class=body>Username: <a class=body href=profile.php?uid=$data[userid]>". getUserName($data['userid']) . "</a></td></tr>";

	foreach($possible as $k => $n)
		echo "<tr><td class=body>" . makeCheckBox("data[$k]", $n, $data[$k] == 'y')  ."</td></tr>";

	echo "<tr><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
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

	$cache->remove(array($uid, "forummods-$uid-$fid"));

	$msgs->addMsg("Mod Updated");
}

function deleteMod($uid){
	global $forums, $msgs, $fid, $cache;

	$forums->modLog('removemod', $fid, 0, $uid);

	$forums->db->prepare_query("DELETE FROM forummods WHERE userid = ? && forumid = ?", $uid, $fid);

	$cache->remove(array($uid, "forummods-$uid-$fid"));

	$msgs->addMsg("Mod Deleted");
}

function listMods(){
	global $forums, $db, $fid, $config, $possible;

	if($fid != 0){
		$forums->db->prepare_query("SELECT name, official, ownerid FROM forums WHERE id = ?", $fid);
		$forumdata = $forums->db->fetchrow();
	}


	$forums->db->prepare_query("SELECT forummods.*, '' as username FROM forummods WHERE forumid = ?", $fid);

	$rows = array();
	$uids = array();
	while($line = $forums->db->fetchrow()){
		$rows[$line['userid']] = $line;
		$uids[] = $line['userid'];
	}

	$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (?)", $uids);

	while($line = $db->fetchrow())
		$rows[$line['userid']]['username'] = $line['username'];

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username');

	incHeader();

	echo "<table>";

	echo "<tr><td class=body colspan=3>";
	if($fid != 0){
		if($forumdata['official']=='y')
			echo "<a class=body href=forums.php>Forums</a> > ";
		else
			echo "<a class=body href=forumsusercreated.php>User Created Forums</a> > ";

		echo "<a class=body href=forumthreads.php?fid=$fid>$forumdata[name]</a> > ";
	}

	echo "<a class=body href=$_SERVER[PHP_SELF]?fid=$fid>Edit Mods</a>";
	echo "</td></tr>";


	echo "<tr>";
	echo "<td class=header>Username</td>";
	echo "<td class=header>Mod Activity</td>";
	echo "<td class=header>Funcs</td>";
	echo "<td class=header>Powers</td>";
/*
	foreach($possible as $v){
		echo "<td class=header valign=bottom align=center>";

		for($i=0; $i < strlen($v); $i++)
			echo $v{$i} . "<br>";

		echo "</td>";
	}
*/
	echo "</tr>";

	foreach($rows as $line){
		echo "<tr>";
		echo "<td class=body nowrap><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body nowrap>" . ($line['activetime'] ? userDate("F j, Y \\a\\t g:i a", $line['activetime']) : 'Unknown') . "</td>";
		echo "<td class=body nowrap><a class=body href=$_SERVER[PHP_SELF]?action=edit&uid=$line[userid]&fid=$fid><img src=$config[imageloc]edit.gif border=0></a>";
		echo "<a class=body href=$_SERVER[PHP_SELF]?action=delete&uid=$line[userid]&fid=$fid><img src=$config[imageloc]delete.gif border=0></a></td>";
//*
		$vals = array();
		foreach($possible as $n => $v)
			if($line[$n] == 'y')
				$vals[] = $v;
		echo "<td class=body>" . implode(", ", $vals) . "</td>";
/*/
		foreach($possible as $n => $v)
			echo "<td class=body>" . ($line[$n] == 'y' ? "X" : '' . "</td>";
//*/

		echo "</tr>";
	}
	echo "<tr><td class=header colspan=4><a class=header href=$_SERVER[PHP_SELF]?action=add&fid=$fid>Create new moderator</a></td></tr>";
	echo "</table><br>";

	incFooter();
	exit;
}

