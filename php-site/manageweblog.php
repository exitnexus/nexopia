<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'editjournal');

	$uid = ($isAdmin ? getREQval('uid', 'int', $userData['userid']) : $userData['userid']);
	$scope = ($uid == $userData['userid'] ? WEBLOG_PRIVATE : WEBLOG_FRIENDS);


	switch($action){
		case "Delete":
		case "delete":
			if(!($id = getREQval('id', 'int')))
				break;

			if(!($k = getREQval('k')) || !checkKey($id, $k))
				break;

			$weblog->deleteEntry($uid, $id);
			if($uid != $userData['userid'])
				$mods->adminlog("delete blog","Delete blog entry $_REQUEST[id] for userid $uid");
			setBlogVisibility($uid, $weblog->getVisibility($uid));

			break;

		case "new":
			if($uid == $userData['userid'])
				addBlogEntry(); //exit

			break;

		case "edit":
			if($id = getREQval('id', 'int'))
				addBlogEntry($_REQUEST['id']); //exit

			break;
		case "Post":
			$id = getREQval('id', 'int');
			$data = getPOSTval('data', 'array');
			$data['msg'] = getPOSTval('msg');

			if($id || $uid == $userData['userid']) //can't post new entry in someone elses blog
				insertBlogEntry($id, $data);

			setBlogVisibility($uid, $weblog->getVisibility($uid));

			break;
		case "Preview":
			$id = getREQval('id', 'int');
			$data = getPOSTval('data', 'array');
			$data['msg'] = getPOSTval('msg');

			addBlogEntry($id, $data, true);

			break;
	}

	listMyBlog(); //exit

/////////////////////////////////

function addBlogEntry($id = 0, $data = array(), $preview = false){
	global $weblog, $uid, $userData;

	$title = "";
	$msg = "";
	$scope = 0;
	$category = 0;
	$allowcomments = 'n';

	if($id && !$preview){
		$line = $weblog->getEntry($id);
		extract($line);
	}else{
		extract($data);
	}

	if($scope == WEBLOG_PRIVATE && $uid != $userData['userid'])
		return;

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
		echo "<tr><td class=body colspan=2>";
		echo "Here is a preview of what the blog entry will look like:";
		echo "</td></tr>";

		echo "<tr><td class=header>Title:</td><td class=header>$ntitle</td></tr>";
		echo "<tr><td class=body colspan=2>" . nl2br($nmsg3) . "</td></tr>";

		echo "</table><br><br>";
	}

	echo "<table align=center cellspacing=0>";

	echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
	if($id)
		echo "<input type=hidden name='id' value='$id'>\n";
	if($uid != $userData['userid'])
		echo "<input type=hidden name='uid' value='$uid'>\n";

	echo "<tr><td class=header colspan=2 align=center>" . ($id ? "Edit" : "Add") . " Blog Entry</td></tr>";

	echo "<tr><td class=body>Title:</td><td class=body><input class=body type=text size=50 name=data[title] value=\"" . htmlentities($title) . "\" maxlength=250></td></tr>";
	echo "<tr><td class=body>Visibility:</td><td class=body><select class=body name=data[scope]>" . make_select_list_key($weblog->scopes, $scope) . "</select></td></tr>";
	if($id)
		echo "<tr><td class=body colspan=2>" . makeCheckBox('data[time]', 'Reset Time') . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('data[allowcomments]', 'Allow Comments', ($allowcomments != 'n')) . "</td></tr>";
	echo "<tr><td class=body colspan=2>";

	editBox($msg,true);

	echo "</td></tr>\n";
	echo "<tr><td class=body align=center colspan=2><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
	echo "</form>";

	echo "</table>";

	incFooter();
	exit;
}

function insertBlogEntry($id, $data){
	global $weblog, $uid, $msgs;

	$title = "";
	$msg = "";
	$scope = 0;
	$time = false;
	$allowcomments = false;

	extract($data);

	if(!isset($weblog->scopes[$scope]))
		$scope = WEBLOG_PUBLIC;

	$ntitle = trim($title);

	if($ntitle == ""){
		$msgs->addMsg("You must insert a title");
		addBlogEntry($id, $data, true); //exit
	}


	$nmsg = trim($msg);

	if($nmsg == ""){
		$msgs->addMsg("You must input some text");
		addBlogEntry($id, $data, true); //exit
	}

	if($id){
		$weblog->updateEntry($id, $uid, $ntitle, $nmsg, $scope, $time, $allowcomments);
	}else{
		$weblog->insertEntry($uid, $ntitle, $nmsg, $scope, $allowcomments);
	}
}

function listMyBlog(){
	global $weblog, $uid, $userData, $config, $mods;

	$scope = WEBLOG_PRIVATE;

	if($uid != $userData['userid']){
		$mods->adminlog("view blog","View blog for userid $uid");
		$scope = WEBLOG_FRIENDS;
	}

	$page = getREQval('page', 'int');

	$weblog->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id, title, time, scope, comments, allowcomments FROM blog WHERE userid = # && scope <= # ORDER BY time DESC LIMIT " . ($page * $weblog->entriesPerPage) . ", " . $weblog->entriesPerPage, $uid, $scope);

	$rows = array();
	while($line = $weblog->db->fetchrow())
		$rows[] = $line;

	$weblog->db->query("SELECT FOUND_ROWS()");
	$totalrows = $weblog->db->fetchfield();
	$numpages =  ceil($totalrows / $weblog->entriesPerPage);

	incHeader(0);

	echo "<table width=100% cellpadding=3>";

	echo "<tr>";
	echo "<td class=header>Title</td>";
	echo "<td class=header>Date</td>";
	echo "<td class=header>Scope</td>";
	echo "<td class=header>Comments</td>";
	echo "<td class=header></td>";
	echo "</tr>";

	$classes = array('body','body2');
	$i=1;

	foreach($rows as $line){
		$i = !$i;
		echo "<tr>";
		echo "<td class=$classes[$i]><a class=body href=weblog.php?uid=$uid&id=$line[id]>$line[title]</a></td>";
		echo "<td class=$classes[$i]>" . userdate("M j, Y G:i:s", $line['time']) . "</td>";
		echo "<td class=$classes[$i]>" . $weblog->scopes[$line['scope']] . "</td>";
		echo "<td class=$classes[$i]>" . ($line['allowcomments'] == 'y' ? $line['comments'] : 'N/A') . "</td>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=edit&id=$line[id]" . ($userData['userid'] == $uid ? "" : "&uid=$uid" ) . "><img src=$config[imageloc]edit.gif border=0></a>";
		echo "<a href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=delete&id=$line[id]&k=" . makeKey($line['id']) . ($userData['userid'] == $uid ? "" : "&uid=$uid" ) . "','delete this entry?')\"><img src=$config[imageloc]delete.gif border=0></a></td>";
		echo "</tr>\n";
	}
	echo "<tr><td class=header colspan=5>";
	echo "<table width=100%><tr>";
	if($uid == $userData['userid'])
		echo "<td class=header><a class=header href=$_SERVER[PHP_SELF]?action=new>Add Blog Entry</a></td>";

	echo "<td class=header align=right>";
	echo "Page:" . pageList("$_SERVER[PHP_SELF]?uid=$uid",$page,$numpages,'header');
	echo "</td>";
	echo "</tr></table>";

	echo "</td></tr>";
	echo "</table>\n";

	incFooter();
	exit;
}

function listSubscriptions(){


}

function setBlogVisibility($userid, $visibility){
	global $db;
	$db->prepare_query("UPDATE users SET journalentries = # WHERE userid = #", $visibility, $userid);
}

