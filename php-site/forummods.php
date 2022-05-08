<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($fid) || $fid=="" || !is_numeric($fid))
		die("Bad Forum id");

	//skip this check if it's the general case and is an admin
	if(!($fid == 0 && $mods->isAdmin($userData['userid'],'forums'))){
		$perms = getForumPerms($fid);	//checks it's a forum, not a realm

		if(!$perms['editmods'])
			die("You don't have permission to edit the mods of this forum");
	}


	$possible = array(	'editposts' 	=> "Edit Posts:",
						'editownposts' 	=> "Edit Own Posts:",
						'deleteposts'	=> "Delete Posts:",
						'move'			=> "Move Threads:",
						'deletethreads'	=> "Delete Threads:",
						'lock'			=> "Lock Threads:",
						'stick'			=> "Sticky Threads:",
						'announce'		=> "Announce Threads:",
						'mute'			=> "Mute Users:",
						'invite'		=> "Invite Users:",
						'modlog'		=> "View Modlog:",
						'editmods'		=> "Edit Mods:"
						);
	$list = array('y' => "Yes", 'n' => "No");

	switch($action){
		case "add":
			addMod();
		case "Create":
			insertMod($data);
			break;
		case "edit":
			editMod($id);
		case "Update":
			updateMod($id,$data);
			break;
		case "delete":
			deleteMod($id);
			break;
	}

	listMods();

////////////////////////////////////////

function addMod($data=array()){
	global $PHP_SELF,$fid,$possible,$list;

	if(!isset($data['username']))
		$data['username']="";

	incHeader();

	echo "<table><form action=$PHP_SELF>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<tr><td class=header colspan=2>Create New Moderator</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=data[username] value='$data[username]'></td></tr>";

	foreach($possible as $k => $n){
		if(!isset($data[$k]))
			$data[$k]="";
		echo "<tr><td class=body>$n</td><td class=body>" . make_radio_key("data[$k]", $list, $data[$k]) . "</td></tr>";
	}

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Create><input class=body type=submit value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function insertMod($data){
	global $db,$possible,$msgs,$fid;

	if(!isset($data['username']) || trim($data['username'])=="")
		addMod($data);

	$userid=getUserId(trim($data['username']));

	if(!$userid)
		addMod($data); //exit

	$db->prepare_query("SELECT unmutetime FROM forummute WHERE userid = ? && forumid = 0", $userid);

	if($db->numrows() && $db->fetchfield() > time()){
		$msgs->addMsg("Sorry, this user has been globally banned and cannot become a mod");
		addMod($data); //exit
	}

	$commands = array();
	foreach($possible as $k => $n){
		if(isset($data[$k]) && $data[$k]=='y')
			$commands[] = "`$k` = 'y'";
		else
			$commands[] = "`$k` = 'n'";
	}

	modLog('addmod',$fid,0,$userid);

	$db->prepare_query("INSERT INTO forummods SET forumid = ?, userid = ?, " . implode(", ", $commands), $fid, $userid);

	$msgs->addMsg("Mod Created");
}

function editMod($id){
	global $db,$possible,$msgs,$fid,$PHP_SELF,$list;

	$db->prepare_query("SELECT * FROM forummods WHERE id = ? && forumid = ?", $id, $fid);
	$data = $db->fetchrow();

	incHeader();

	echo "<table><form action=$PHP_SELF>";
	echo "<input type=hidden name=fid value=$fid>";
	echo "<input type=hidden name=id value=$id>";
	echo "<tr><td class=header colspan=2>Edit Mod</td></tr>";

	echo "<tr><td class=body>Username:</td><td class=body><a class=body href=profile.php?uid=$data[userid]>". getUserName($data['userid']) . "</a></td></tr>";

	foreach($possible as $k => $n)
		echo "<tr><td class=body>$n</td><td class=body>" . make_radio_key("data[$k]", $list, $data[$k]) . "</td></tr>";


	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Update><input class=body type=submit value=Cancel></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function updateMod($id,$data){
	global $db,$possible,$msgs,$fid;

	$commands = array();
	foreach($possible as $k => $n){
		if(isset($data[$k]) && $data[$k]=='y')
			$commands[] = "`$k` = 'y'";
		else
			$commands[] = "`$k` = 'n'";
	}

	$db->prepare_query("SELECT userid FROM forummods WHERE id = ? && forumid = ?", $id, $fid);
	$uid = $db->fetchfield();

	modLog('editmod',$fid,0,$uid);

	$db->prepare_query("UPDATE forummods SET " . implode(", ", $commands) . " WHERE forumid = ? && id = ?", $fid, $id);

	$msgs->addMsg("Mod Updated");
}

function deleteMod($id){
	global $db,$msgs,$fid;

	$db->prepare_query("SELECT userid FROM forummods WHERE id = ? && forumid = ?", $id, $fid);
	$uid = $db->fetchfield();

	modLog('removemod',$fid,0,$uid);

	$db->prepare_query("DELETE FROM forummods WHERE id = ? && forumid = ?", $id, $fid);

	$msgs->addMsg("Mod Deleted");
}

function listMods(){
	global $db, $PHP_SELF,$fid;

	if($fid != 0){
		$db->prepare_query("SELECT name,official,ownerid FROM forums WHERE id = ?", $fid);
		$forumdata = $db->fetchrow();
	}


	$db->prepare_query("SELECT forummods.*, users.username FROM forummods,users WHERE users.userid=forummods.userid && forumid = ? ORDER BY username", $fid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

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

	echo "<a class=body href=$PHP_SELF?fid=$fid>Edit Mods</a>";
	echo "</td></tr>";


	echo "<tr><td class=header>Username</td><td class=header>Funcs</td></tr>";

	foreach($rows as $line){
		echo "<tr><td class=body><a class=body href=profile.php?uid=$line[userid]>$line[username]</a></td>";
		echo "<td class=body><a class=body href=$PHP_SELF?action=edit&id=$line[id]&fid=$fid><img src=/images/edit.gif border=0></a>";
		echo "<a class=body href=$PHP_SELF?action=delete&id=$line[id]&fid=$fid><img src=/images/delete.gif border=0></a></td></tr>";
	}
	echo "<tr><td class=header colspan=2><a class=header href=$PHP_SELF?action=add&fid=$fid>Create new moderator</a></td></tr>";
	echo "</table><br>";

	incFooter();
	exit;
}
