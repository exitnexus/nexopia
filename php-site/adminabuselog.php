<?
	$login=1;

	require_once("include/general.lib.php");

	$abuseaction = getREQval('abuseaction', 'int');
	$reason = getREQval('reason', 'int');
	$type = getREQval('type', 'string', 'User');
	$uid = trim(getREQval('uid'));

	$adminLevel = 0;

	// this query now handled & cached by include/forums.php
	//	$sth = $forumdb->prepare_query('SELECT COUNT(*) AS modcnt FROM forummods, forums WHERE forummods.userid=# AND forummods.forumid=forums.id AND forums.official=?', $userData['userid'], 'y');

	if ($userData['loggedIn'] && $forums->isOfficialMod($userData['userid']))
		$adminLevel = ABUSE_ADMINLEVEL_FORUMMOD;

	if ($userData['loggedIn'] && $mods->isAdmin($userData['userid'], "abuselog"))
		$adminLevel = ABUSE_ADMINLEVEL_ADMIN;

	$allowedActions = $abuselog->getActions($adminLevel);
	if (! count($allowedActions) || ($abuseaction > 0 && ! isset($allowedActions[$abuseaction])) || ($adminLevel == ABUSE_ADMINLEVEL_FORUMMOD && $type != 'User'))
		die("no permission");

	$page = getREQval('page', 'int');

	switch($action){
		case "view":
			if($id = getREQval('id', 'int'))
				viewAbuse($id); //exit
			break;

		case "addabuse":
			addAbuse($uid, $abuseaction, $reason);	//exit

			break;

		case "Post Abuse":
			$uid 		= trim(getPOSTval('uid'));
			$reason 	= getPOSTval('reason', 'int');
			$subject 	= getPOSTval('subject');
			$msg 		= getPOSTval('msg');

			if(blank($uid, $abuseaction, $reason, $subject))
				addAbuse($uid, $abuseaction, $reason, $subject, $msg, true);

			$abuselog->addAbuse($uid, $abuseaction, $reason, $subject, $msg);

			$abuseaction = $reason = 0;
			$uid = "";
			break;

		case "Preview":
			$id = getREQval('id');
			$msg = getREQval('msg');

			addAbuseComment($id, $msg, true);

		case "Post":
			$id = getPOSTval('id', 'int');
			$msg = getPOSTval('msg');

			if(!empty($id) && !empty($msg))
				$abuselog->addAbuseComment($id, $msg);
			break;
	}

	listAbuse($abuseaction, $reason, $uid, $type); 	//exit

////////////////

function listAbuse($action = 0, $reason = 0, $uid = "", $type = 'User'){
	global $abuselog, $page, $config, $adminLevel, $allowedActions;

	$types = array('User');
	if ($adminLevel == ABUSE_ADMINLEVEL_ADMIN)
		$types[] = 'Mod';

	$where = array();
	if($uid){
		$col = ($type == 'Mod' ? 'modid' : 'userid');
		$where[] = $abuselog->db->prepare("$col = ?", getUserID($uid));
	}

	if($action)
		$where[] = $abuselog->db->prepare("action = #", $action);
	elseif($adminLevel != ABUSE_ADMINLEVEL_ADMIN)
		$where[] = $abuselog->db->prepare_array("action IN (" . join(',', array_fill(0, count($allowedActions), '#')) . ")", array_keys($allowedActions));

	if($reason)
		$where[] = $abuselog->db->prepare("reason = ?", $reason);

	$res = $abuselog->db->query("SELECT SQL_CALC_FOUND_ROWS id, userid, modid, action, reason, time, subject, msg FROM abuselog" . (count($where) ? " WHERE " . implode(" && ", $where) : "" ) . " ORDER BY time DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]");

	$rows = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$rows[$line['id']] = $line;
		$uids[$line['userid']] = $line['userid'];
		$uids[$line['modid']] = $line['modid'];
	}

	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	$usernames = getUserName($uids);

	$comments = array();

	if(count($rows)){
		$res = $abuselog->db->prepare_query("SELECT abuseid, count(*) as count FROM abuselogcomments WHERE abuseid IN (#) GROUP BY abuseid", array_keys($rows));

		while($line = $res->fetchrow())
			$comments[$line['abuseid']] = $line['count'];
	}


	incHeader();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header colspan=6 align=center>";

	echo "<select name=abuseaction class=body><option value=0>Action" . make_select_list_key($allowedActions, $action) . "</select>";
	echo "<select name=reason class=body><option value=0>Reason" . make_select_list_key($abuselog->reasons, $reason) . "</select>";
	echo "<select name=type class=body>" . make_select_list($types, $type) . "</select>";
	echo "<input class=body type=text size=10 name=uid value='$uid'>";
	echo "<input class=body type=submit name=action value=Go>";

	echo "</td></tr>";
	echo "</form>";

	echo "<tr>";
	echo "<td class=header>User</td>";
	echo "<td class=header>Mod</td>";
	echo "<td class=header>Action</td>";
	echo "<td class=header>Reason</td>";
	echo "<td class=header>Subject</td>";
	echo "<td class=header>Time</td>";
	echo "</tr>";

	foreach($rows as $row){
		echo "<tr>";
		echo "<td class=body nowrap><a class=body href=/profile.php?uid=$row[userid]>" . $usernames[$row['userid']] . "</a></td>";
		echo "<td class=body nowrap><a class=body href=/profile.php?uid=$row[modid]>" . $usernames[$row['modid']] . "</a></td>";
		echo "<td class=body nowrap>" . (isset($abuselog->actions[$row['action']]) ? $abuselog->actions[$row['action']] : 'N/A') . "</td>";
		echo "<td class=body nowrap>" . (isset($abuselog->reasons[$row['reason']]) ? $abuselog->reasons[$row['reason']] : 'N/A') . "</td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=view&id=$row[id]>" . (isset($comments[$row['id']]) ? ($row['msg'] ? "<b><u>$row[subject]</u></b>" : "<u>$row[subject]</u>") : ($row['msg'] ? "<b>$row[subject]</b>" : (strlen($row['subject']) ? $row['subject'] : 'N/A'))) . "</a></td>";
		echo "<td class=body nowrap>" . userDate("M j, Y, g:i a", $row['time']) . "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=6>";

	echo "<table width=100% cellspacing=0 cellpadding=0><tr>";
	echo "<td class=header><a class=header href=$_SERVER[PHP_SELF]?action=addabuse&uid=" . urlencode($uid) . ">Add Abuse</a></td>";
	echo "<td class=header align=center><b>More Text</b> - <u>Comments</u></td>";
	echo "<td class=header align=right>Page: " . pageList("$_SERVER[PHP_SELF]?uid=" . urlencode($uid) . "&type=$type&abuseaction=$action&reason=$reason",$page,$numpages,'header') . "</td>";
	echo "</tr></table>";
	
	echo "</td></tr>";

	echo "</table>";

	incFooter();
	exit;
}

function viewAbuse($id){
	global $abuselog;

	$result = $abuselog->getAbuseID($id); //returns array('abuse' => $row, 'comments' => $comments)

	if(!$result)
		die("Bad id");

	extract($result);
	
	incHeader();

	echo "<table align=center>";

	echo "<tr><td class=body colspan=2><a class=body href=$_SERVER[PHP_SELF]>Abuse Log</a></td></tr>";
	echo "<tr><td class=header>User:</td><td class=header><a class=header href=/profile.php?uid=$abuse[userid]>$abuse[username]</a></td></tr>";
	echo "<tr><td class=header>Mod:</td><td class=header><a class=header href=/profile.php?uid=$abuse[modid]>$abuse[modname]</a></td></tr>";
	echo "<tr><td class=header>Action:</td><td class=header>" . (isset($abuselog->actions[$abuse['action']]) ? $abuselog->actions[$abuse['action']] : 'N/A') . "</td></tr>";
	echo "<tr><td class=header>Reason:</td><td class=header>" . (isset($abuselog->reasons[$abuse['reason']]) ? $abuselog->reasons[$abuse['reason']] : 'N/A') . "</td></tr>";
	echo "<tr><td class=header>Time:</td><td class=header>" . userDate("F j, Y, g:i a", $abuse['time']) . "</td></tr>";
	echo "<tr><td class=header>Subject:</td><td class=header>" . (strlen($abuse['subject']) ? $abuse['subject'] : 'N/A') . "</td></tr>";
	echo "<tr><td class=body colspan=2>$abuse[msg]<br><br><br></td></tr>";

	foreach($comments as $line){
		echo "<tr><td class=header>By: ";

		if($line['userid'])	echo "<a class=header href=/profile.php?uid=$line[userid]>$line[username]</a>";
		else				echo "$line[username]";

		echo "</td><td class=header>Date: " . userdate("F j, Y, g:i a",$line['time']) . "</td>";
		echo "</tr>";

		echo "<td class=body colspan=2>";

		echo $line['msg'] . "&nbsp;<br><br>";

		echo "</td></tr>";
	}

	echo "<form action=\"$_SERVER[PHP_SELF]\" method=post name=editbox>";
	echo "<input type=hidden name=id value=$id>";

	echo "<tr><td class=header align=center colspan=2>Add a Comment</td></tr>";
	echo "<tr><td class=body align=center colspan=2>";

	editBox("");

	echo "</td></tr>";
	echo "<tr><td class=body align=center colspan=2><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>";
	echo "</form>";

	echo "</table>";

	incFooter();
	exit;
}

function addAbuseComment($id, $msg, $preview){
	incHeader();

	echo "<table align=center cellspacing=0>";

	if($preview){
		$msg = trim($msg);
		$nmsg = html_sanitizer::sanitize($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		echo "<tr><td colspan=2 class=body>";

		echo "Here is a preview of what the post will look like:";

		echo "<blockquote>" . nl2br($nmsg3) . "</blockquote>";

		echo "<hr>";
		echo "</td></tr>";
	}


	echo "<form action=\"$_SERVER[PHP_SELF]\" method=post name=editbox>\n";
	echo "<input type=hidden name='id' value='$id'>\n";

	echo "<tr><td class=body>";

	editBox($msg);

	echo "</td></tr>\n";
	echo "<tr><td class=body align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=Post></td></tr>\n";
	echo "</form>";

	echo "</table>";

	incFooter();
	exit;
}

function addAbuse($uid = "", $action = 0, $reason = 0, $subject = "", $msg = "", $preview = false){
	global $abuselog, $allowedActions;

	incHeader();

	echo "<table align=center cellspacing=0>";

	if($preview){
		$msg = trim($msg);
		$nmsg = html_sanitizer::sanitize($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		echo "<tr><td class=body colspan=2>";

		echo "Here is a preview of what the entry will look like:";

		echo "<blockquote>" . nl2br($nmsg3) . "</blockquote>";

		echo "<hr>";
		echo "</td></tr>";
	}

	echo "<form action=\"$_SERVER[PHP_SELF]\" method=post name=editbox>\n";

	echo "<tr><td class=body>User: </td><td class=body><input class=body type=text name=uid value=\"$uid\" style=\"width:97\"> ";
	echo "<select class=body name=abuseaction style=\"width:100\"><option value=0>Action" . make_select_list_key($allowedActions, $action) . "</select>"; // $abuselog->manualactions
	echo "<select class=body name=reason style=\"width:100\"><option value=0>Reason" . make_select_list_key($abuselog->reasons, $reason) . "</select></td></tr>";

	echo "<tr><td class=body width=50>Subject: </td><td class=body><input class=body type=text name=\"subject\" value=\"" . htmlentities($subject) . "\" style=\"width:300\" maxlength=64></td></tr>\n";

	echo "<tr><td class=body colspan=2>";

	editBox($msg);

	echo "</td></tr>\n";
	echo "<tr><td class=body align=center colspan=2><input class=body type=submit name=action accesskey='s' value='Post Abuse'><input class=body type=submit name=action value='Cancel'></td></tr>\n";
	echo "</form>";

	echo "</table>";

	incFooter();
	exit;
}




