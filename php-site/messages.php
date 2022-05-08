<?

	$login=1;

	require_once("include/general.lib.php");
	require_once("include/template.php");
	$folder = getREQval('folder', 'int', MSG_INBOX);

	$page = getREQval('page', 'int');

	$uid = getREQval('uid', 'int');

	$isAdmin = $mods->isAdmin($userData['userid'], 'viewmessages');
	if(!$uid || !$isAdmin)
		$uid = $userData['userid'];

	switch($action){
		case "viewnew":
			//if(checkKey('newmsg', getREQval('k')))
			viewNew(); 		//exit

		case "next":
			if($id = getREQval('id', 'int'))
				viewNext($id, getREQval('fid', 'int', MSG_INBOX));	//exit
			break;
		case "prev":
			if($id = getREQval('id', 'int'))
				viewPrev($id, getREQval('fid', 'int', MSG_INBOX));	//exit
			break;
		case "view":
			$parse_bbcode = $userData['parse_bbcode'];
			if($id = getREQval('id', 'int'))
				viewMsg($id, $parse_bbcode);	//exit
			break;
		case "reply":
			if($id = getREQval('id', 'int'))
				reply($id);		//exit
			break;
		case "forward":
			if($id = getREQval('id', 'int'))
				forward($id);	//exit
			break;
		case "Delete":
		case "delete":
			if(($checkID = getREQval('checkID', 'array')))
				$messaging->moveMsg($checkID, MSG_TRASH, $uid);
			break;

		case "Write Message":
		case "write":
			$parse_bbcode = $userData['parse_bbcode'];
		case "Preview":
			$to 	 = getREQval('to');
			$subject = getREQval('subject');
			$msg 	 = getPOSTval('msg');
			$replyto = getPOSTval('replyto', 'int');

			if(!isset($parse_bbcode))
				$parse_bbcode = getPOSTval('parse_bbcode', 'bool');

			writeMsg($to, $subject, $msg, $replyto, ($action == "Preview"),  $parse_bbcode);	//exit
			break;

		case "Send Message":
		case "send":
			$to 	 = getPOSTval('to');
			$subject = getPOSTval('subject');
			$msg 	 = getPOSTval('msg');
			$replyto = getPOSTval('replyto', 'int');
			$parse_bbcode = getPOSTval('parse_bbcode', 'bool');

			sendMesg($to, $subject, $msg, $replyto,  $parse_bbcode);
			header("location: $_SERVER[PHP_SELF]");
			exit;

		case "Mark":
		case "mark":
			if(($checkID = getREQval('checkID', 'array')))
				$messaging->markMsg($checkID);

			if(getREQval('noreload', 'bool'))
				exit;

			break;
		case "UnMark":
		case "unmark":
			if(($checkID = getREQval('checkID', 'array')))
				$messaging->unMarkMsg($checkID);

			if(getREQval('noreload', 'bool'))
				exit;

			break;

		case "Move":
		case "move":
			if(($checkID = getPOSTval('checkID', 'array')) && ($moveto = getPOSTval('moveto', 'int')))
				$messaging->moveMsg($checkID, $moveto);
			break;

		case "Manage Folders":
		case "folders":
			listFolders();	//exit
			break;
		case "Create Folder":
			if($name = getREQval('name'))
				$messaging->createMsgFolder($name);
			break;
		case "deletefolder":
			if($id = getREQval('id', 'int'))
				$messaging->deleteMsgFolder($id);
			break;
		case "renamefolder":
			if(($id = getREQval('id', 'int')) && ($name = getREQval('name')))
				$messaging->renameMsgFolder($id, $name);
			break;

		case "Ignore List":
		case "ignorelist":
			ignorelist(); //exit;
		case "ignore": //from a message or comment
			if(($id = getREQval('id')) && ($k = getREQval('k')) && checkKey($id, $k))
				ignore($id);
			break;
		case "Ignore": //straight from the ignore list
			if($id = getPOSTval('id'))
				ignore($id);
			break;
		case "unignore":
		case "UnIgnore":
			if(($id = getREQval('id')) && ($k = getREQval('k')) && checkKey($id, $k))
				unignore($id);
			break;

		case "archive":
		case "Archive":
			if(($checkID = getPOSTval('checkID', 'array')))
				archive($checkID);
	}
	listMsgs();		//exit

/////////////////////////////////////////


function listFolders(){
	global $userData, $config, $uid, $messaging, $usersdb;
	$template = new template("messages/listfolders");
	$folders = $messaging->getMsgFolders($uid);

	$folderdata = array();
	foreach($folders as $id => $name)
		$folderdata[$id] = array('count' => 0, 'name' => $name);

	unset($folders);

	$res = $usersdb->prepare_query("SELECT folder, count(*) as count FROM msgs WHERE userid = % GROUP BY folder", $uid);

	while($line = $res->fetchrow())
		$folderdata[$line['folder']]['count'] = $line['count'];

	$template->set("is_current_user", ($userData['userid'] == $uid));

	$total=0;
	$template->set("rename_img_src", "$config[imageloc]rename.gif");
	$template->set("delete_img_src", "$config[imageloc]delete.gif");
	foreach($folderdata as $id => &$folder)
	{

		$folder['folder_options'] = ($userData['userid'] == $uid && $id != MSG_INBOX && $id != MSG_SENT && $id != MSG_TRASH);
		$total += $folder['count'];
	}
	$template->set("folderdata", $folderdata);
	$template->set("total", $total);
	$template->display();
	exit;
}

function sendMesg($to, $subject, $msg, $replyto,  $parse_bbcode){
	global $msgs, $userData, $sortt, $sortd, $messaging;

	if(!is_numeric($to)){
		$touser=$to;
		$to=getUserID($to);
		if(!$to){
			$msgs->addMsg("Invalid User");
			writeMsg($to,$subject,$msg, $replyto,"Preview", $parse_bbcode);
		}
	}

	$spam = spamfilter(trim($msg));

	if(!$spam || !$messaging->deliverMsg($to, $subject, $msg, $replyto, false, false, true, false,  $parse_bbcode ))
		writeMsg($to, $subject, $msg, $replyto, null, $parse_bbcode);
}

function writeMsg($to="",$subject="",$msg="",$replyto=0, $preview = false, $parse_bbcode = true ){
	global $msgs, $userData, $sortt, $sortd, $config, $usersdb, $messaging;
	$template = new Template("messages/writemsg");
	if(is_numeric($to)){
		$to = getUserName($to);
		if(!$to){
			$msgs->addMsg("Invalid User");
			$to="";
		}
	}

	$friends = getFriendsList($userData['userid']);
    $template->set("preview", $preview);

/*    if(!isset($parse_bbcode))
        $template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', true));
    else
        $template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));*/
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');




    $nmsg = html_sanitizer::sanitize(trim($msg));


	if($preview){
		$nsubject = trim(removeHTML($subject));

		if($parse_bbcode)
		{
			$nmsg2 = parseHTML($nmsg);
			$nmsg3 = smilies($nmsg2);

		}
		else
			$nmsg3	= $nmsg;
		$template->set("nsubject", $nsubject);
		$template->set("msg", nl2br($nmsg3));
	}
	$template->set("replyto", $replyto);
	$template->set("to", $to);
	$template->set("select_list_friends", make_select_list($friends));
	$template->set("subject", $subject);

	ob_start();
	editbox($msg);
	$template->set("editbox",ob_get_contents() );
	ob_end_clean();

	$template->display();
	exit();
}

function forward($id){
	global $messaging, $uid, $userData;

	$msg = $messaging->getMsg($uid, $id);

	if(!$msg)
		die("query failed, likely trying to access message not to the user");

	$subject = "Fw: $msg[subject]";
	$message = "\n\n";

	if(fckeditor::IsCompatible() && !$userData['bbcode_editor'])
	{

		$message .= str_replace("\n","<br>",	"\n----- Original Message -----\n" .
										"To: <a href='/profile.php?uid=".$msg['toname']."'>" . $msg['toname'] . "</a>\n" .
										"From: <a href='/profile.php?uid=" . $msg['fromname']."'>"  .$msg['fromname']. "</a>\n" .
										"Sent: " . userdate("D M j, Y g:i a", $msg['date']) . "\n" .
										"Subject: $msg[subject]\n\n" .
										"$msg[msg]");

		$message = "<br><div class=editboxquote>".$message."</div><br>";
	}
	else
	{
		$message .= str_replace("\n","\n>",	"\n----- Original Message -----\n" .
										"To: [user]" . $msg['toname'] . "[/user]\n" .
										"From: [user]" . $msg['fromname'] . "[/user]\n" .
										"Sent: " . userdate("D M j, Y g:i a", $msg['date']) . "\n" .
										"Subject: $msg[subject]\n\n" .
										"$msg[msg]");

	}

	if($msg['replyto']){

		$rmsg = $messaging->getMsg($uid, $msg['replyto']);
		if(fckeditor::IsCompatible() && !$userData['bbcode_editor'])
		{

			$message .= "<br>" . str_replace("\n","<br>",	"\n----- Original Message -----\n" .
														"To: <a href='/profile.php?uid=".$rmsg['toname']."'>".$rmsg['toname'] . "</a>\n" .
														"From: <a href='/profile.php?uid=" . $rmsg['fromname'] ."'>".$rmsg['fromname'] . "</a>\n" .
														"Sent: " . userdate("D M j, Y g:i a", $rmsg['date']) . "\n" .
														"Subject: $rmsg[subject]\n\n" .	"$rmsg[msg]");
			$message = "<br><div class=editboxquote>".$message."</div><br>";
		}
		else
		{
			$message .= "\n>" . str_replace("\n","\n>>",	"\n----- Original Message -----\n" .
														"To: [user]" . $rmsg['toname'] . "[/user]\n" .
														"From: [user]" . $rmsg['fromname'] . "[/user]\n" .
														"Sent: " . userdate("D M j, Y g:i a", $rmsg['date']) . "\n" .
														"Subject: $rmsg[subject]\n\n" .	"$rmsg[msg]");
		}
	}

	writeMsg("", $subject, $message);
}

function viewNext($id, $fid){
	global $messaging, $uid, $usersdb;

	$res = $usersdb->prepare_query("SELECT id FROM msgs WHERE userid = % && folder = # && id > # ORDER BY id ASC LIMIT 1", $uid, $fid, $id);

	if($msg = $res->fetchrow()){
		viewMsg($msg['id']);
	}else{
		listMsgs();
	}
}

function viewPrev($id, $fid){
	global $messaging, $uid, $usersdb;
	$res = $usersdb->prepare_query("SELECT id FROM msgs WHERE userid = % && folder = # && id < # ORDER BY id DESC LIMIT 1", $uid, $fid, $id);

	if($msg = $res->fetchrow()){
		viewMsg($msg['id']);
	}else{
		listMsgs();
	}
}

function viewNew(){
	global $messaging, $userData, $usersdb, $cache, $config;

	$res = $messaging->db->prepare_query("SELECT id FROM msgs WHERE userid = % && folder = # && `to` = # && status='new' ORDER BY id ASC LIMIT 1", $userData['userid'], MSG_INBOX, $userData['userid']);

	if($msg = $res->fetchrow()){
		viewMsg($msg['id']);
	}else{
		if($userData['newmsgs']){
			$usersdb->prepare_query("UPDATE users SET newmsgs = 0 WHERE userid = %", $userData['userid']);
			$userData['newmsgs'] = 0;
//			$cache->put("newmsgs-$userData[userid]", 0, $config['maxAwayTime']);
			$cache->remove("newmsglist-$userData[userid]");
		}

		listMsgs();
	}
}

function viewMsg($id, $parse_bbcode = true){
	viewMsg1($id, $parse_bbcode); //original
//	viewMsg2($id); //threaded below, email style
//	viewMsg3($id); //collapsing above
}

function viewMsg1($id, $parse_bbcode = true){
	global $msgs, $userData, $usersdb, $messaging, $uid, $config, $mods, $cache;
	$template = new Template("messages/viewMsg1");
	if(empty($id))
		return false;

	$msg = $messaging->getMsg($uid, $id);


	if(!$msg)
		listMsgs();

	if($msg['replyto'])
		$rmsg = $messaging->getMsg($uid, $msg['replyto'], true);

	if($msg['status']=='new' && $msg['to'] == $userData['userid']){
		$usersdb->prepare_query("UPDATE msgs SET status='read' WHERE (userid = % && id = #) || (userid = % && id = #)", $msg['to'], $msg['id'], $msg['from'], $msg['othermsgid']);
		$usersdb->prepare_query("UPDATE users SET newmsgs = newmsgs - 1 WHERE userid = %", $userData['userid']);
		$userData['newmsgs']--;
//		$cache->decr(array($userData['userid'], "newmsgs-$userData[userid]"));
		$cache->remove("newmsglist-$userData[userid]");
	}

	if($uid != $userData['userid']){
		$mods->adminlog("view message","View Userid $uid message $id");
	}

	$template->set("id", $id);


	$links = array();

	if($uid == $userData['userid']){
		$template->set("is_curr_user", true);
		if($msg['from'] && $msg['from'] != $uid)
		{
		  $template->set("from_key", makeKey($msg['from']));
		  $template->set("from", true);
		}
		else
		   $template->set("from", false);

	}else{
		$template->set("uid", $uid);
	}

	$msg['msg'] = html_sanitizer::sanitize($msg['msg']);
	if($msg['parse_bbcode'] != false)
		$msg['msg'] = nl2br(parseHTML(smilies($msg['msg'])));


	if($msg['replyto'] && $rmsg)
	{
		$msg['replyto'] = $msg['replyto'] && $rmsg;
		$rmsg['msg'] = html_sanitizer::sanitize($rmsg['msg']);
		if($rmsg['parse_bbcode'] != false)
			$rmsg['msg'] = nl2br(parseHTML(smilies($rmsg['msg'])));
		$template->set("rmsg", $rmsg);
	}
	else
	  $template->set("rmsg", null);
	$template->set("replyto", $msg['replyto']) ;
	if($msg['from']){
		if(substr($msg['subject'],0,4)=='Re: '){
			$subject = "Re (2): " . substr($msg['subject'],4);
		}elseif(substr($msg['subject'],0,4)=='Re ('){
			$loc = strpos($msg['subject'],")");
			$subject = "Re (" . (substr($msg['subject'],4,$loc-4)+1)  . "): " . substr($msg['subject'],$loc+3);
		}else{
			$subject = "Re: $msg[subject]";
		}
		$template->set("subject", $subject);
		ob_start();
		editbox("");
		$template->set("editbox", ob_get_contents() );
		ob_end_clean();
	}

/*	if(!isset($parse_bbcode))
        $template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $userData['parse_bbcode']));
    else
        $template->set("checkbox_parsebbcode", makeCheckBox('parse_bbcode', 'Parse BBcode', $parse_bbcode));*/
	$template->set("checkbox_parsebbcode", '<input type="hidden" name="parse_bbcode" value="y"/>');

	$template->set("msg", $msg);
	$template->display();
	exit;
}

function viewMsg2($id){
	global $msgs, $userData, $usersdb, $messaging, $uid, $config, $mods, $cache;

die("This function doesn't work. It needs to be ported to the new db layout");


	if(empty($id))
		return false;

	$msg = $messaging->getMsg($uid, $id);

$maxdepth = 5;

	if(!$msg)
		listMsgs();

	if($msg['status']=='new' && $msg['to'] == $userData['userid']){
		$ids = array($id, $msg['othermsgid']);

		//ERROR!
		$messaging->db->squery(array($msg['to'], $msg['from']), $messaging->db->prepare("UPDATE msgs SET status = 'read' WHERE id IN (#)", $ids));
		$usersdb->prepare_query("UPDATE users SET newmsgs = newmsgs - 1 WHERE userid = %", $userData['userid']);
		$userData['newmsgs']--;
//		$cache->decr(array($userData['userid'], "newmsgs-$userData[userid]"));
		$cache->remove("newmsglist-$userData[userid]");
	}

	$reply = $msg['replyto'];

	$depth = 0;
	$rmsgs = array();

	while($depth < $maxdepth && $reply){
		$rmsgs[$depth] = $messaging->getMsg($uid, $reply, true);
		$reply = $rmsgs[$depth]['replyto'];
		$depth++;
	}

	if($uid != $userData['userid']){
		$mods->adminlog("view message","View Userid $uid message $id");
	}

	incHeader();

	echo "<table width=100%>\n";

	$links = array();

	if($uid == $userData['userid']){
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]\">Message list</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=forward&id=$id\">Forward</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=delete&checkID[]=$id\">Delete</a>";
		if($msg['from'] && $msg['from'] != $uid)
			$links[] = "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=ignore&id=$msg[from]&k=" . makeKey($msg['from']) . "','ignore this user')\">Ignore User</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&fid=$msg[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&fid=$msg[folder]&id=$id\">Previous</a>";
	}else{
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?uid=$uid\">Message list</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&uid=$uid&fid=$msg[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&uid=$uid&fid=$msg[folder]&id=$id\">Previous</a>";
	}

	echo "<tr><td class=body colspan=2>" . implode(" | ", $links) . "</td></tr>";


	echo "<tr><td class=header>To:</td><td class=header>" . ($msg['to'] ? "<a class=header href=/profile.php?uid=$msg[to]>$msg[toname]</a>" : "$msg[toname]" ) . "</td></tr>";
	echo "<tr><td class=header>From:</td><td class=header>" . ($msg['from'] ? "<a class=header href=/profile.php?uid=$msg[from]>$msg[fromname]</a>" : "$msg[fromname]" ) . "</td></tr>";
	echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a", $msg['date']) . "</td></tr>";
	echo "<tr><td class=header>Subject:</td><td class=header>$msg[subject]</td></tr>";
	echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($msg['msg']))) . "<br><br></td></tr>";

	if($msg['from']){
		echo "<tr><td class=body colspan=2>";

		if(substr($msg['subject'],0,4)=='Re: '){
			$subject = "Re (2): " . substr($msg['subject'],4);
		}elseif(substr($msg['subject'],0,4)=='Re ('){
			$loc = strpos($msg['subject'],")");
			$subject = "Re (" . (substr($msg['subject'],4,$loc-4)+1)  . "): " . substr($msg['subject'],$loc+3);
		}else{
			$subject = "Re: $msg[subject]";
		}

		echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
		echo "<input type=hidden name=replyto value=$msg[id]>";
		echo "<input type=hidden name=to value=$msg[from]>";

	//	echo "<tr><td class=body colspan=2 align=center><b>Please do not share personal or financial information while using Nexopia.com.<br>All information passed through the use of this site, is at the risk of the user.<br>Nexopia.com will assume no liability for any users' actions.</b></td></tr>";

		echo "<tr><td class=header colspan=2 align=center>Reply</td></tr>";

		echo "<tr><td class=body>Subject: </td><td class=body><input class=body type=text name=\"subject\" value=\"" . htmlentities($subject) . "\" style=\"width:300\" maxlength=64></td></tr>\n";
		echo "<tr><td class=body colspan=2>";

		editbox("");

		echo "</td></tr>\n";
		echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=\"Send Message\"></td></tr>\n";

		echo "</form></table>";
		echo "</td></tr>";
	}

	$classes = array('body','body2');
	$i=0;

	$indent = 0;

	foreach($rmsgs as $depth => $rmsg){

		echo "<tr><td class=body colspan=2 align=right><table width=" . (100 - $indent*($depth+1)) . "%>";

		echo "<tr><td class=header>" . ($rmsg['from'] ? "<a class=header href=/profile.php?uid=$rmsg[from]>$rmsg[fromname]</a>" : "$rmsg[fromname]" ) . " => " . ($rmsg['to'] ? "<a class=header href=/profile.php?uid=$rmsg[to]>$rmsg[toname]</a>" : "$rmsg[toname]" ) . "</td>";
		echo "<td class=header align=right>" . userdate("D M j, Y g:i a", $rmsg['date']) . "</td></tr>";
		echo "<tr><td class=header colspan=2>Subject: $rmsg[subject]</td></tr>";
		echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($rmsg['msg']))) . "<br><br></td></tr>";
		echo "</table></td></tr>";
	}

	echo "</table>";


	incFooter();
	exit;
}

function viewMsg3($id){
	global $msgs, $userData, $usersdb, $messaging, $uid, $config, $mods, $cache;

die("This function doesn't work. It needs to be ported to the new db layout");

	if(empty($id))
		return false;

	$msg = $messaging->getMsg($uid, $id);

$maxdepth = 5;

	if(!$msg)
		listMsgs();

	if($msg['status']=='new' && $msg['to'] == $userData['userid']){
		$ids = array($id, $msg['othermsgid']);
		//ERROR!
		$messaging->db->squery(array($msg['to'], $msg['from']), $messaging->db->prepare("UPDATE msgs SET status = 'read' WHERE id IN (#)", $ids));
		$usersdb->prepare_query("UPDATE users SET newmsgs = newmsgs - 1 WHERE userid = %", $userData['userid']);
		$userData['newmsgs']--;
//		$cache->decr(array($userData['userid'], "newmsgs-$userData[userid]"));
		$cache->remove("newmsglist-$userData[userid]");
	}

	$reply = $msg['replyto'];

	$depth = 5;
	$rmsgs = array();

	while($depth && $reply){
		$rmsgs[$depth] = $messaging->getMsg($uid, $reply, true);
		$reply = $rmsgs[$depth]['replyto'];
		$depth--;
	}

	ksort($rmsgs);

	if($uid != $userData['userid']){
		$mods->adminlog("view message","View Userid $uid message $id");
	}

	incHeader();

?>
<script>

function swap(name){
	el = document.getElementById(name);

	if (el.style.display == 'none'){
		el.style.display = "";
	} else {
		el.style.display = "none";
	}
}

</script>
<?

	echo "<table width=100%>\n";

	$links = array();

	if($uid == $userData['userid']){
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]\">Message list</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=forward&id=$id\">Forward</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=delete&checkID[]=$id\">Delete</a>";
		if($msg['from'] && $msg['from'] != $uid)
			$links[] = "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=ignore&id=$msg[from]&k=" . makeKey($msg['from']) . "','ignore this user')\">Ignore User</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&fid=$msg[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&fid=$msg[folder]&id=$id\">Previous</a>";
	}else{
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?uid=$uid\">Message list</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&uid=$uid&fid=$msg[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&uid=$uid&fid=$msg[folder]&id=$id\">Previous</a>";
	}

	echo "<tr><td class=body colspan=2>" . implode(" | ", $links) . "</td></tr>";

	foreach($rmsgs as $depth => $rmsg){
		echo "<tr><td class=header colspan=2><table width=100%>";
		echo "<tr><td class=header>" . ($rmsg['from'] ? "<a class=header href=/profile.php?uid=$rmsg[from]><b>$rmsg[fromname]</b></a>" : "<b>$rmsg[fromname]</b>" ) . ": ";
		echo "<a class=header href=\"javascript: swap('msg$depth');\">$rmsg[subject]</a></td>";
		echo "<td class=header align=right>" . userdate("D M j, Y g:i a", $rmsg['date']) . "</td></tr>";
		echo "</table></td></tr>";
		echo "<tr id=msg$depth style=\"display: none\"><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($rmsg['msg']))) . "<br><br></td></tr>";
	}


	echo "<tr><td class=header colspan=2><table width=100%>";
	echo "<tr><td class=header>" . ($msg['from'] ? "<a class=header href=/profile.php?uid=$rmsg[from]><b>$msg[fromname]</b></a>" : "<b>$msg[fromname]</b>" ) . ": ";
	echo "$msg[subject]</td>";
	echo "<td class=header align=right>" . userdate("D M j, Y g:i a", $msg['date']) . "</td></tr>";
	echo "</table></td></tr>";

/*
	echo "<tr><td class=header>From:</td><td class=header>" . ($msg['from'] ? "<a class=header href=/profile.php?uid=$msg[from]>$msg[fromname]</a>" : "$msg[fromname]" ) . "</td></tr>";
	echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a", $msg['date']) . "</td></tr>";
	echo "<tr><td class=header>Subject:</td><td class=header>$msg[subject]</td></tr>";
*/
	echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($msg['msg']))) . "<br><br></td></tr>";

	if($msg['from']){
		echo "<tr><td class=body colspan=2>";

		if(substr($msg['subject'],0,4)=='Re: '){
			$subject = "Re (2): " . substr($msg['subject'],4);
		}elseif(substr($msg['subject'],0,4)=='Re ('){
			$loc = strpos($msg['subject'],")");
			$subject = "Re (" . (substr($msg['subject'],4,$loc-4)+1)  . "): " . substr($msg['subject'],$loc+3);
		}else{
			$subject = "Re: $msg[subject]";
		}

		echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
		echo "<input type=hidden name=replyto value=$msg[id]>";
		echo "<input type=hidden name=to value=$msg[from]>";

	//	echo "<tr><td class=body colspan=2 align=center><b>Please do not share personal or financial information while using Nexopia.com.<br>All information passed through the use of this site, is at the risk of the user.<br>Nexopia.com will assume no liability for any users' actions.</b></td></tr>";

		echo "<tr><td class=header colspan=2 align=center>Reply</td></tr>";

		echo "<tr><td class=body>Subject: </td><td class=body><input class=body type=text name=\"subject\" value=\"" . htmlentities($subject) . "\" style=\"width:300\" maxlength=64></td></tr>\n";
		echo "<tr><td class=body colspan=2>";

		editbox("");

		echo "</td></tr>\n";
		echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=\"Send Message\"></td></tr>\n";

		echo "</form></table>";
		echo "</td></tr>";
	}

	echo "</table>";


	incFooter();
	exit;
}

function listMsgs(){
	global $msgs, $new, $folder, $userData, $page, $config, $messaging, $uid, $mods, $cache, $usersdb;
	$template = new Template("messages/listmessages");
	$marked = getREQval('marked', 'bool');
	$other = getREQval('other');

	if(!isset($uid))
		$uid = $userData['userid'];

	$folders = $messaging->getMsgFolders($uid);

	$pageparams = array();
	$pageparams[] = "folder=$folder";

	$params = array();

	$query = "SELECT SQL_CALC_FOUND_ROWS id, folder, `to`, toname, `from`, fromname, date, mark, status, subject FROM msgs WHERE userid = %";

	$params[] = $uid;

	if($uid != $userData['userid']){
		$pageparams[] = "uid=$uid";
		$mods->adminlog("list messages","List Userid $uid messages");
	}

	if($folder){
	 	$query .= " && folder = #";
		$params[] = $folder;
	}

	if($other){
		$otherid = getUserID($other);

		if($otherid){
			$query .= " && otheruserid = #";
			$params[] = $otherid;
			$pageparams[] = "other=$other";
		}else{
			$msgs->addMsg("User doesn't exist");
			$other = "";
		}
	}

	if($marked){
	 	$query .= " && mark = 'y'";
		$pageparams[] = "marked=$marked";
	}


	$query .= " ORDER BY date DESC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]";

	$res = $usersdb->prepare_array_query($query, $params);

	$showto = false;
	$showfrom = false;

	$pruneSoon = array();
	$messages = array();
	while($line = $res->fetchrow()){
		$messages[] = $line;
		if($line['to'] != $uid)
			$showto = true;
		if($line['from'] != $uid)
			$showfrom = true;
		if ($line['date'] - (time()- 86400*$messaging->prunelength) <= $messaging->warning_threshold*86400)
			$pruneSoon[] = "style='color:red;'";
		else
			$pruneSoon[] = '';
	}
	$template->set('pruneSoon', $pruneSoon);


	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $config['linesPerPage']);


	if(!$showto && !$showfrom)
		$showfrom = true;

	$cols = 5;
	if($showto)
		$cols++;
	if($showfrom)
		$cols++;
	if(!$folder)
		$cols++;

	if(count($pageparams))
		$pagestr = "?" . implode("&", $pageparams);


	$template->set("cols", $cols);

	if($uid == $userData['userid'])
	   $template->set("is_curr_user", true);

	$template->set('folders', $folders);
	$template->set('url_uid', ($uid == $userData['userid'] ? "" : "&uid=$uid"));
	$template->set('uid', $uid);
	$template->set('checkbox_marked', makeCheckBox('marked', 'Marked', $marked));
	$template->set('other', $other);
	$template->set('select_list_folder', make_select_list_key($folders, $folder) );
	$template->set('pagelist',pageList($_SERVER['PHP_SELF'] . $pagestr, $page, $numpages, 'header') );
	$template->set('showto', $showto);
	$template->set('showfrom', $showfrom);
	$template->set('folder', $folder);
	$template->set('pruneLength', $messaging->prunelength);

	$classes = array('body','body2');
	$i=1;

	foreach($messages as &$line){
		$i = !$i;
		$line["show_status"] = $line['to'] == $userData['userid'] || $userData['premium'];
		$status = "";
		if($line['to'] == $userData['userid'] || $userData['premium']){
			switch($line['status']){
				case 'new': 	$status = "<b>New</b>";	break;
				case 'read': 	$status = "Read";		break;
				case 'replied':	$status = "Replied";		break;
				default:	$status =  $line['status'];
			}

		}

		if(($line['to']==$userData['userid'] || $userData['premium']) && $line['status']=='new')
			$line['showsubject'] = true;
		else
			$line['showsubject'] = false;
		$line['status'] = $status;
	}
	$template->set("select_list_move_to_folder",make_select_list_key($folders));
	$template->set("classes", $classes);
	$template->set("messages", $messages);
	$template->display();
	exit();
}

function ignorelist(){
	global $usersdb, $userData, $config, $uid;
	$template= new template("messages/ignorelist");

	$res = $usersdb->prepare_query("SELECT ignoreid FROM `ignore` WHERE userid = %", $uid);

	$uids = array();
	while($line = $res->fetchrow())
		$uids[] = $line['ignoreid'];

	$rows = getUserInfo($uids);

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username');

	$template->set("url_uid", ($uid == $userData['userid'] ? "" : "&uid=$uid"));
	$template->set("delete_img_src", "$config[imageloc]delete.gif");
	foreach($rows as &$line)
		$line['key'] =  makeKey($line['userid']);
	$template->set("rows", $rows);
	$template->set("uid",$uid);
	$template->set("is_curr_user", $uid == $userData['userid']);
	$template->display();
	exit;
}

function ignore($id){
	global $usersdb, $uid, $cache;

	if(!is_numeric($id))
		$id = getUserID($id);

	$usersdb->prepare_query("INSERT IGNORE INTO `ignore` SET userid = %, ignoreid = #", $uid, $id);
	$cache->remove("ignorelist-$uid");

	ignorelist();
}

function unignore($id){
	global $usersdb, $uid, $cache;

	if(!is_numeric($id))
		$id = getUserID($id);

	$usersdb->prepare_query("DELETE FROM `ignore` WHERE userid = % && ignoreid = #", $uid, $id);

	$cache->remove("ignorelist-$uid");

	ignorelist();
}

function archive($checkID){
	global $messaging, $uid, $userData, $config, $emaildomain, $msgs, $useraccounts;

	if(empty($checkID)){
		$msgs->addMsg("You must check the messages you would like archived. They will be emailed to you");
		listMsgs(); //exit
	}

	$messages = $messaging->getMsg($uid, $checkID);

	$output = "";

	foreach($messages as $line){
		$output .= "To:      $line[toname]\n";
		$output .= "From:    $line[fromname]\n";
		$output .= "Date:    " . userDate("D M j, Y g:i a", $line['date']) . "\n";
		$output .= "Subject: $line[subject]\n";
		$output .= "$line[msg]\n\n";
		$output .= "-----------------------------------------------\n\n";
	}
	$output .= "Message sent from $config[title] on " . userDate("D M j, Y g:i a");

	$subject = "Archive messages from $config[title]";

	$email = $useraccounts->getEmail($userData["userid"]);
	smtpmail($email, $subject, $output, "From: $config[title] <no-reply@$emaildomain>");

	incHeader();

	echo "Email sent to $email:";

	echo "<pre>$output</pre>";

	incFooter();
	exit;
}
