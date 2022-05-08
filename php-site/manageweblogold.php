<?

	$login=1;

	require_once("include/general.lib.php");

	$possibleScope = array('public'=>'Public','friends'=>'Friends only','private'=>'Private');


	$isAdmin = $mods->isAdmin($userData['userid'],'viewjournal');

	if(empty($uid) || !$isAdmin)
		$uid = $userData['userid'];

	switch($action){
		case "Delete":
		case "delete":
			if(empty($id))
				break;

			deleteJournalEntry($id);
			setJournalVisibility($uid);

			break;
		case "edit":
			if(!empty($id))
				addJournalEntry($id); //exit

			break;
		case "Post":
			if(empty($id))		$id = "";
			if(empty($title))	$title = "";
			if(empty($msg))		$msg = "";
			if(empty($scope))	$scope = "";
			if(count($_POST))
				insertJournalEntry($id, $title, $msg, $scope);

			setJournalVisibility($uid);

			break;
		case "Preview":
			if(empty($id))		$id = "";
			if(empty($title))	$title = "";
			if(empty($msg))		$msg = "";
			if(empty($scope))	$scope = "";
			addJournalEntry($id, $title, $msg, $scope, true);

			break;
	}

	listJournal(); //exit

/////////////////////////////////

function addJournalEntry($id = 0, $data, $preview = false){
	global $db, $possibleScope, $uid;

	if($id && !$title && !$msg && !$scope){
		$db->prepare_query("SELECT title, msg, scope FROM weblog WHERE userid = ? && id = ?", $uid, $id);
		$line = $db->fetchrow();
		extract($line);
	}

	incHeader();


	if($preview){
		$ntitle = trim($title);
		$ntitle = removeHTML($ntitle);

		$nmsg = trim($msg);
		$nmsg = removeHTML($nmsg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		echo "<table align=center>";
		echo "<tr><td class=body>";
		echo "Here is a preview of what the journal entry will look like:";
		echo "</td></tr>";


		echo "<tr><td class=header>Title:</td><td class=header>$ntitle</td></tr>";
		echo "<tr><td class=body colspan=2>" . nl2br($nmsg3) . "<hr></td></tr>";

		echo "</table>";

	}

	echo "<table align=center cellspacing=0>";

	echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
	echo "<input type=hidden name='id' value='$id'>\n";
	echo "<input type=hidden name='uid' value='$uid'>\n";
	echo "<tr><td class=header2>Title:<input class=body type=text size=50 name=title value=\"" . htmlentities($title) . "\" maxlength=250><select class=body name=scope>" . make_select_list_key($possibleScope, $scope) . "</select></td></tr>";
	echo "<tr><td class=body>";

	editBox($msg,true);

	echo "</td></tr>\n";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
	echo "</form>";

	echo "</table>";

	incFooter();
	exit;
}

function insertJournalEntry($id, $title, $msg, $scope){
	global $possibleScope,$uid, $db, $msgs;

	if(!isset($scope) || !isset($possibleScope[$scope]))
		$scope="public";

	$ntitle = trim($title);

	if($ntitle == ""){
		$msgs->addMsg("You must insert a title");
		addJournalEntry($id, $title, $msg, $scope, true); //exit
	}

	$ntitle = removeHTML($ntitle);


	$nmsg = trim($msg);

	if($nmsg == ""){
		$msgs->addMsg("You must input some text");
		addJournalEntry($id, $title, $msg, $scope, true); //exit
	}

	$nmsg = removeHTML($nmsg);
	$nmsg2 = parseHTML($nmsg);
	$nmsg3 = smilies($nmsg2);
	$nmsg3 = wrap($nmsg3);
	$nmsg3 = nl2br($nmsg3);



	if($id)
		$db->prepare_query("UPDATE weblog SET title = ?, msg = ?, nmsg = ?, time = ?, scope = ? WHERE userid = ? && id = ?", $ntitle, $nmsg, $nmsg3, time(), $scope, $uid, $id);
	else
		$db->prepare_query("INSERT INTO weblog SET userid = ?, title = ?, msg = ?, nmsg = ?, time = ?, scope = ?", $uid, $ntitle, $nmsg, $nmsg3, time(), $scope);
}

function deleteJournalEntry($id){
	global $uid, $db, $userData, $mods;

	$db->prepare_query("DELETE FROM weblog WHERE id = ?", $id);

	if($db->affectedrows() && $uid != $userData['userid'])
		$mods->adminlog("delete journal","Delete journal entry $id for userid $uid");
}

function listJournal(){
	global $page, $db, $action, $uid, $userData, $config, $possibleScope, $mods;

	if($uid != $userData['userid'])
		$mods->adminlog("view journal","View journal for userid $uid");

	if(empty($page)) $page=0;

	if($action) $db->begin();

	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS * FROM weblog WHERE userid = ? ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $uid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	$db->query("SELECT FOUND_ROWS()");
	$totalrows = $db->fetchfield();
	$numpages =  ceil($totalrows / $config['linesPerPage']);
	if($page>=$numpages) $page=0;

	if($action) $db->commit();


	incHeader(0);

	echo "<table width=100% cellpadding=3>";

	echo "<tr><td class=header>Title</td><td class=header>Date</td><td class=header>Scope</td><td class=header>Funcs</td></tr>";

	foreach($rows as $line){
		echo "<tr><td class=body><a class=body href=weblog.php?uid=$uid>$line[title]</a></td>";
		echo "<td class=body>" . userdate("M j, Y G:i:s",$line['time']) . "</td>";
		echo "<td class=body>" . $possibleScope[$line['scope']] . "</td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$line[id]&uid=$uid><img src=$config[imageloc]edit.gif border=0></a>";
		echo "<a class=body href=$_SERVER[PHP_SELF]?action=delete&id=$line[id]&uid=$uid><img src=$config[imageloc]delete.gif border=0></a></td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=4 align=right>";
	echo "Page:" . pageList("$_SERVER[PHP_SELF]?uid=$uid",$page,$numpages,'header');
	echo "</td></tr>";

	if($userData['userid'] == $uid){

		echo "<tr><td colspan=4>";
		echo "<table  cellspacing=0 align=center cellpadding=2>";
		echo "<tr><td class=header><a class=header name=reply>Post a new entry:</a></td></tr>\n";

		echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
		echo "<input type=hidden name=id value=0>";

		echo "<tr><td class=header2>Title:<input class=body type=text size=50 name=title maxlength=250><select class=body name=scope>" . make_select_list_key($possibleScope) . "</select></td></tr>";
		echo "<tr><td class=header2 align=center>";

		editBox("",true);

		echo "</td></tr>\n";
//		echo "<tr><td class=header2 align=center><select class=body name=action><option value=changed>Preview if changes made<option value=Post>Post without previewing<option value=Preview>Preview</select><input class=body type=submit value=Post accesskey='s' onClick='checksubmit()'></td></tr>\n";
		echo "<tr><td class=header2 align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
		echo "</form>";
		echo "</table>\n";
		echo "</td></tr>";
	}
	echo "</table>\n";

	incFooter();
	exit;
}


function setJournalVisibility($uid=0){
	global $db, $userData;

	$db->prepare_query("SELECT scope, count(*) as count FROM weblog WHERE userid = ? GROUP BY scope", $uid);

	$permission = "none";
	while($line = $db->fetchrow()){
		switch($line['scope']){
			case "public":
				$permission = "public";
				break 2;
			case "friends":
				$permission = "friends";
		}
	}

	$db->prepare_query("UPDATE users SET journalentries = ? WHERE userid = ?", $permission, $uid);
}

