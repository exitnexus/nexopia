<?

	$login=2;

	require_once("include/general.lib.php");


	if(!$mods->isAdmin($userData['userid'],'forums')){
		$forums->db->prepare_query("SELECT count(*) FROM forums WHERE ownerid = ?", $userData['userid']);

		if($forums->db->fetchfield() > 0)
			die("You can only create one forum");

		$forums->db->prepare_query("SELECT unmutetime FROM forummute WHERE userid = ? && forumid = 0", $userData['userid']);

		if($forums->db->numrows() > 0 && $forums->db->fetchfield() > time())
			die("You are not allowed to post in any forums, or create your own");
	}


	if($action == "Create Forum")
		insertForum($data);

	addForum();	//exit


function addForum($data = array()){
	global $childdata;

	$name="";
	$description="";
	$autolock=0;
	$edit='n';
	$countposts='y';
	$public='y';
	$mute='n';

	extract($data);

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr><td class=header colspan=2 align=center>Add Forum</td></tr>";

	echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=data[name] maxlength=32 value=\"$name\"></td></tr>";
	echo "<tr><td class=body>Description:</td><td class=body><input class=body type=text name=data[description] maxlength=250 size=30 value=\"$description\"></td></tr>";
	echo "<tr><td class=header colspan=2 align=center>Options</td></tr>";

	echo "<tr><td class=body>Auto-lock time:</td><td class=body><input class=body type=text name=data[autolock] size=5 value=0> days. 0 to disable, otherwise time in days</td></tr>";
	echo "<tr><td class=body>Allow Post Editing:</td><td class=body>" . make_radio_key("data[edit]", array('y'=>"Yes", 'n'=>"No"),$edit) . "</td></tr>";
	echo "<tr><td class=body>Public:</td><td class=body>" . make_radio_key("data[public]", array('y'=>"Yes", 'n'=>"No"),$public) . "&nbsp; Only invited users can enter a private forum.</td></tr>";
	echo "<tr><td class=body>Mute:</td><td class=body>" . make_radio_key("data[mute]", array('y'=>"Yes", 'n'=>"No"),$mute) . "&nbsp; Only mods can post in a mute forum.</td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Create Forum'><input class=body type=submit value=Cancel></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertForum($data){
	global $msgs, $userData, $forums;

	$name="";
	$description="";
	$parent=0;
	$autolock=0;
	$edit='n';
	$countposts='y';
	$public='y';
	$mute='n';

	extract($data);

	$error=false;
	if(trim($name)=="" || strlen(trim($name)) < 4){
		$msgs->addMsg("Forum needs a name");
		$error=true;
	}
	if($description==""){
		$msgs->addMsg("Forum needs a description");
		$error=true;
	}

	if($public != 'n')
		$public='y';

	$forums->db->prepare_query("SELECT id FROM forums WHERE name = ?", $name);
	if($forums->db->numrows() > 0){
		$msgs->addMsg("Forum name already taken");
		$error=true;
	}

	if($error)
		addForum($data); //exit

	$forums->db->prepare_query("INSERT INTO forums SET name = ?, description = ?, parent = ?, autolock = ?, edit = ?, public = ?, mute = ?, official = 'n', ownerid = ?",
							removehtml($name), removehtml($description), $parent, $autolock, $edit, $public, $mute, $userData['userid']);

	$fid = $forums->db->insertid();

	$forums->db->prepare_query("INSERT IGNORE INTO foruminvite SET userid = ?, forumid = ?", $userData['userid'], $fid);

	header("location: forumthreads.php?fid=$fid");
	exit;
}
