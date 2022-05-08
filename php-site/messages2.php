<?

	$login=1;

	require_once("include/general.lib.php");

	$folder = getREQval('folder', MSG_INBOX);

	$page = getREQval('page', 0);

	$isAdmin = $mods->isAdmin($userData['userid'], 'viewmessages');
	if(!isset($uid) || !$isAdmin)
		$uid = $userData['userid'];

	switch($action){
		case "viewnew":
			viewNew(); 		//exit

		case "next":
			if($id = getREQval('id'))
				viewNext($id, getREQval('fid', MSG_INBOX));	//exit
			break;
		case "prev":
			if($id = getREQval('id'))
				viewPrev($id, getREQval('fid', MSG_INBOX));	//exit
			break;
		case "view":
			if($id = getREQval('id'))
				viewMsg($id);	//exit
			break;
		case "reply":
			if($id = getREQval('id'))
				reply($id);		//exit
			break;
		case "forward":
			if($id = getREQval('id'))
				forward($id);	//exit
			break;
		case "Delete":
		case "delete":
			if(($checkID = getREQval('checkID')) && is_array($checkID))
				$messaging->moveMsg($checkID, MSG_TRASH, $uid);
			break;

		case "Write Message":
		case "write":
		case "Preview":
			$to 	 = getREQval('to');
			$subject = getREQval('subject');
			$msg 	 = getPOSTval('msg');
			$replyto = getPOSTval('replyto', 0);
			writeMsg($to, $subject, $msg, $replyto, ($action == "Preview"));	//exit
			break;

		case "Send Message":
		case "send":
			$to 	 = getPOSTval('to');
			$subject = getPOSTval('subject');
			$msg 	 = getPOSTval('msg');
			$replyto = getPOSTval('replyto', 0);
			sendMesg($to, $subject, $msg, $replyto);
			header("location: $_SERVER[PHP_SELF]");
			exit;

		case "Mark":
		case "mark":
			if(($checkID = getREQval('checkID')) && is_array($checkID))
				$messaging->markMsg($checkID);
			break;
		case "UnMark":
		case "unmark":
			if(($checkID = getREQval('checkID')) && is_array($checkID))
				$messaging->unMarkMsg($checkID);
			break;

		case "Move":
		case "move":
			if(($checkID = getPOSTval('checkID')) && is_array($checkID) && ($moveto = getPOSTval('moveto')))
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
			if($id = getREQval('id'))
				$messaging->deleteMsgFolder($id);
			break;
		case "renamefolder":
			if(($id = getREQval('id')) && ($name = getREQval('name')))
				$messaging->renameMsgFolder($id, $name);
			break;

		case "Ignore List":
		case "ignorelist":
			ignorelist(); //exit;
		case "ignore":
		case "Ignore":
			if($id = getREQval('id'))
				ignore($id);
			break;
		case "unignore":
		case "UnIgnore":
			if($id = getREQval('id'))
				unignore($id);
			break;

		case "archive":
		case "Archive":
			if(($checkID = getPOSTval('checkID')) && is_array($checkID))
				archive($checkID);
	}
	listMsgs();		//exit

/////////////////////////////////////////


function listFolders(){
	global $userData, $config, $uid, $messaging;

	$folders = $messaging->getMsgFolders($uid);

	$folderdata = array();
	foreach($folders as $id => $name)
		$folderdata[$id] = array('count' => 0, 'name' => $name);

	unset($folders);

	$messaging->db->prepare_query("SELECT folder,count(*) as count FROM msgs WHERE userid = ? GROUP BY folder", $uid);

	while($line = $messaging->db->fetchrow())
		$folderdata[$line['folder']]['count'] = $line['count'];

	incHeader();

	echo "<table>";
	echo "<tr><td class=header>Name</td>";
	echo "<td class=header>Messages</td>";
	if($userData['userid'] == $uid)
		echo "<td class=header></td></tr>";

	$total=0;
	foreach($folderdata as $id => $folder){
		echo "<tr>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?folder=$id>$folder[name]</a></td>";
		echo "<td class=body align=right>$folder[count]</td>";
		echo "<td class=body>";
		if($userData['userid'] == $uid && $id != MSG_INBOX && $id != MSG_SENT && $id != MSG_TRASH){
			echo "<a class=body href=\"javascript: if(name = prompt('Rename folder to what?','$folder[name]')) location.href= '/$_SERVER[PHP_SELF]?action=renamefolder&id=$id&new=' + name\"><img src=$config[imageloc]rename.gif border=0></a>";
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=deletefolder&id=$id><img src=$config[imageloc]delete.gif border=0></a>";
		}
		echo "</td>";
		echo "</tr>";
		$total += $folder['count'];
	}

	echo "<tr><td class=header></td>";
	echo "<td class=header align=right>$total</td>";
	if($userData['userid'] == $uid)
		echo "<td class=header></td>";
	echo "</tr>";
	echo "</table>";
	echo "<br>";

	if($userData['userid'] == $uid){
		echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
		echo "<tr><td class=header colspan=2 align=center>Create New Folder</td></tr>";
		echo "<tr><td class=body>Name:</td><td class=body><input class=body type=text name=name></td></tr>";
		echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=\"Create Folder\"></td></tr>";
		echo "</form></table>";
	}

	incFooter();
	exit;
}

function sendMesg($to, $subject, $msg, $replyto){
	global $msgs, $userData, $sortt, $sortd, $messaging;

	if(!is_numeric($to)){
		$touser=$to;
		$to=getUserID($to);
		if(!$to){
			$msgs->addMsg("Invalid User, $to");
			writeMsg($to,$subject,$msg, $replyto,"Preview");
		}
	}

	$spam = spamfilter(trim($msg));

	if(!$spam || !$messaging->deliverMsg($to, $subject, $msg, $replyto))
		writeMsg($to, $subject, $msg, $replyto);
}

function writeMsg($to="",$subject="",$msg="",$replyto=0, $preview = false){
	global $msgs, $userData, $sortt, $sortd, $config, $db, $messaging;

	if(is_numeric($to)){
		$to = getUserName($to);
		if(!$to){
			$msgs->addMsg("Invalid User");
			$to="";
		}
	}

	$friends = getFriendsList($userData['userid']);

	$nmsg = removeHTML(trim($msg));

	incHeader();

	echo "<table align=center>";

	if($preview){
		$nsubject = trim(removeHTML($subject));

		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);

		echo "<tr><td colspan=2 class=body>";

		echo "Here is a preview of what the message will look like:";

		echo "<table width=100%>";
		echo "<tr><td class=header>Subject:</td><td class=header>$nsubject</td></tr>";
		echo "<tr><td class=body colspan=2>" . nl2br($nmsg3) . "</td></tr>";

		echo "</table><hr>";
		echo "</td></tr>";
	}
	echo "</table>";

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
	echo "<input type=hidden name=replyto value=$replyto>";
//	echo "<tr><td class=body colspan=2 align=center><b>Please do not share personal or financial information while using Nexopia.com.<br>All information passed through the use of this site, is at the risk of the user.<br>Nexopia.com will assume no liability for any users' actions.</b></td></tr>";
	echo "<tr><td class=body>To: </td><td class=body><input class=body type=text name=to value=\"$to\" style=\"width:120\"><select name=friends style=\"width:120\" class=body onChange=\"if(this.selectedIndex!=0) this.form.to.value=this.options[this.selectedIndex].value;this.selectedIndex=0\"><option>Choose a Friend" . make_select_list($friends) . "</select></td></tr>\n";
	echo "<tr><td class=body>Subject: </td><td class=body><input class=body type=text name=\"subject\" value=\"" . htmlentities($subject) . "\" style=\"width:300\" maxlength=64></td></tr>\n";
	echo "<tr><td class=body colspan=2>";

	editbox($msg);

	echo "</td></tr>\n";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=\"Send Message\"></td></tr>\n";

	echo "</form></table>";

	incFooter();
	exit();
}

function forward($id){
	global $msgs,$userData,$sortt,$sortd,$messaging,$uid;

	$messaging->db->prepare_query("SELECT msgs.id, msgheader.id as headerid, msgheader.`to`, msgheader.toname, msgheader.`from`, msgheader.fromname, msgheader.date, msgheader.subject, msgheader.replyto > 0 as reply, msgtext.msg,
				replyheader.`to` as replyto, replyheader.toname as replytoname, replyheader.`from` as replyfrom, replyheader.fromname as replyfromname, replyheader.date as replydate, replyheader.subject as replysubject, replytext.msg as replymsg
		FROM 	msgs
				INNER JOIN msgheader 				ON msgs.msgheaderid = msgheader.id
				INNER JOIN msgtext 					ON msgheader.msgtextid = msgtext.id
				LEFT  JOIN msgheader as replyheader ON msgheader.replyto > 0 && replyheader.id = msgheader.replyto
				LEFT  JOIN msgtext as replytext 	ON replyheader.msgtextid = replytext.id
		WHERE msgs.userid = ? && msgs.id = ?", $uid, $id);

	$data = $messaging->db->fetchrow();

	if($data){
		$subject = "Fw: $data[subject]";


		if($data['reply'])
			$message = "\n".str_replace("\n","\n>","$data[msg]\n----- Original Message -----\nFrom: $data[fromname]\nTo: $data[toname]\nSent: " . userdate("D M j, Y G:i a",$data['date']) . "\nSubject: $data[subject]\n\n$data[replymsg]");
		else
			$message = $data['msg'];

		$message = "\n".str_replace("\n","\n>","\n----- Original Message -----\nFrom: $data[fromname]\nTo: $data[toname]\nSent: " . userdate("D M j, Y G:i a",$data['date']) . "\nSubject: $data[subject]\n\n$message");

		writeMsg("",$subject,$message);
	}else
		die("query failed, likely trying to access message not to the user");
}

function viewNext($id, $fid){
	global $messaging, $uid;

	$messaging->db->prepare_query("SELECT id FROM msgs WHERE userid = ? && folder = ? && id > ? ORDER BY id ASC LIMIT 1", $uid, $fid, $id);

	if($messaging->db->numrows()){
		viewMsg($messaging->db->fetchfield());
	}else{
		listMsgs();
	}
}

function viewPrev($id, $fid){
	global $messaging, $uid;
	$messaging->db->prepare_query("SELECT id FROM msgs WHERE userid = ? && folder = ? && id < ? ORDER BY id DESC LIMIT 1", $uid, $fid, $id);

	if($messaging->db->numrows()){
		viewMsg($messaging->db->fetchfield());
	}else{
		listMsgs();
	}
}

function viewNew(){
	global $messaging, $userData, $db, $cache, $config;

	$messaging->db->prepare_query("SELECT msgs.id FROM msgs, msgheader WHERE msgs.msgheaderid=msgheader.id && msgs.userid = # && msgheader.to = # && msgheader.new='y' && msgs.folder=1 ORDER BY msgs.id ASC LIMIT 1", $userData['userid'], $userData['userid']);

	if($messaging->db->numrows()){
		viewMsg($messaging->db->fetchfield());
	}else{
		if($userData['newmsgs']){
			$db->prepare_query("UPDATE users SET newmsgs = ? WHERE userid = ?", 0, $userData['userid']);
			$userData['newmsgs'] = 0;
//			$cache->put(array($userData['userid'], "newmsgs-$userData[userid]"), 0, $config['maxAwayTime']);
			$cache->remove(array($userData['userid'], "newmsglist-$userData[userid]"));
		}

		listMsgs();
	}
}

//*/
function viewMsg($id){
	global $msgs, $userData, $db, $messaging, $uid, $config, $mods, $cache;

	if(empty($id))
		return false;

	$messaging->db->prepare_query("SELECT id, folder, msgheaderid FROM msgs WHERE msgs.userid = # && msgs.id = #", $uid, $id);

	$data = $messaging->db->fetchrow();

	if(!$data)
		listMsgs();

//cache at send time

	$messaging->db->prepare_query("SELECT id, `to`, toname, `from`, fromname, date, new, subject, replyto, msgtextid FROM msgheader WHERE id = #", $data['msgheaderid']);
	$msg = $messaging->db->fetchrow();


	$msgtext = $cache->get(array($msg['msgtextid'], "msgtext-$msg[msgtextid]"));

	if($msgtext === false){
		$messaging->db->prepare_query("SELECT msg FROM msgtext WHERE id = #", $msg['msgtextid']);
		$msgtext = $messaging->db->fetchfield();

		$cache->put(array($msg['msgtextid'], "msgtext-$msg[msgtextid]"), $msgtext, 86400);
	}

	if($msg['replyto']){
		$rmsg = $cache->get(array($msg['replyto'], "msgheader-$msg[replyto]"));

		if($rmsg === false){
			$messaging->db->prepare_query("SELECT id, `to`, toname, `from`, fromname, date, new, subject, replyto, msgtextid FROM msgheader WHERE id = #", $msg['replyto']);
			$rmsg = $messaging->db->fetchrow();

			$cache->put(array($msg['replyto'], "msgheader-$msg[replyto]"), $rmsg, 86400);
		}


		$rmsgtext = $cache->get(array($rmsg['msgtextid'], "msgtext-$rmsg[msgtextid]"));

		if($rmsgtext === false){
			$messaging->db->prepare_query("SELECT msg FROM msgtext WHERE id = #", $rmsg['msgtextid']);
			$rmsgtext = $messaging->db->fetchfield();

			$cache->put(array($rmsg['msgtextid'], "msgtext-$rmsg[msgtextid]"), $rmsgtext, 86400);
		}

	}

	if($msg['new']=='y' && $msg['to'] == $userData['userid']){
		$messaging->db->prepare_query("UPDATE msgheader SET new = 'n' WHERE id = ?", $data['headerid']);
		$db->prepare_query("UPDATE users SET newmsgs = newmsgs - 1 WHERE userid = ?", $userData['userid']);
		$userData['newmsgs']--;
//		$cache->decr(array($userData['userid'], "newmsgs-$userData[userid]"));
		$cache->remove(array($userData['userid'], "newmsglist-$userData[userid]"));
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
		if($data['from'] && $data['from'] != $uid)
			$links[] = "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=ignore&id=$msg[from]','ignore this user')\">Ignore User</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&fid=$data[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&fid=$data[folder]&id=$id\">Previous</a>";
	}else{
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?uid=$uid\">Message list</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&uid=$uid&fid=$data[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&uid=$uid&fid=$data[folder]&id=$id\">Previous</a>";
	}

	echo "<tr><td class=body colspan=2>" . implode(" | ", $links) . "</td></tr>";


	echo "<tr><td class=header>To:</td><td class=header>" . ($msg['to'] ? "<a class=header href=profile.php?uid=$msg[to]>$msg[toname]</a>" : "$msg[toname]" ) . "</td></tr>";
	echo "<tr><td class=header>From:</td><td class=header>" . ($msg['from'] ? "<a class=header href=profile.php?uid=$msg[from]>$msg[fromname]</a>" : "$msg[fromname]" ) . "</td></tr>";
	echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a", $msg['date']) . "</td></tr>";
	echo "<tr><td class=header>Subject:</td><td class=header>$data[subject]</td></tr>";
	echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($msgtext))) . "<br><br></td></tr>";

	if($msg['replyto']){
//		echo "<tr><td class=body colspan=2 align=right><table width=97%>";
//		echo "<tr><td class=header colspan=2 align=center>Original Message</td></tr>";
//		echo "<tr><td class=header>To:</td><td class=header>" . ($rmsg['to'] ? "<a class=header href=profile.php?uid=$rmsg[to]>$rmsg[toname]</a>" : "$rmsg[toname]" ) . "</td></tr>";
//		echo "<tr><td class=header>From:</td><td class=header>" . ($rmsg['from'] ? "<a class=header href=profile.php?uid=$rmsg[from]>$rmsg[fromname]</a>" : "$rmsg[fromname]" ) . "</td></tr>";
		echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a", $rmsg['date']) . "</td></tr>";
		echo "<tr><td class=header>Subject:</td><td class=header>$rmsg[subject]</td></tr>";
		echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($rmsgtext))) . "<br><br></td></tr>";
//		echo "</table></td></tr>";
	}

	echo "</table>";

	if($msg['from']){
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
	}


	incFooter();
	exit;
}
/*/
function viewMsg($id){
	global $msgs, $userData, $db, $messaging, $uid, $config, $mods, $cache;

	if(empty($id))
		return false;

	$messaging->db->prepare_query("SELECT msgs.id, msgs.folder,
				msgheader.id as headerid, msgheader.`to`, msgheader.toname, msgheader.`from`, msgheader.fromname, msgheader.date, msgheader.new, msgheader.subject, msgheader.replyto > 0 as reply,
				msgtext.msg,
				replyheader.`to` as replyto, replyheader.toname as replytoname, replyheader.`from` as replyfrom, replyheader.fromname as replyfromname, replyheader.date as replydate, replyheader.subject as replysubject,
				replytext.msg as replymsg
		FROM 	msgs
				INNER JOIN msgheader 				ON msgs.msgheaderid = msgheader.id
				INNER JOIN msgtext 					ON msgheader.msgtextid = msgtext.id
				LEFT  JOIN msgheader as replyheader ON msgheader.replyto > 0 && replyheader.id = msgheader.replyto
				LEFT  JOIN msgtext as replytext 	ON replyheader.msgtextid = replytext.id
		WHERE msgs.userid = ? && msgs.id = ?", $uid, $id);

	$data = $messaging->db->fetchrow();

	if(!$data)
		listMsgs();

	if($data['new']=='y' && $data['to'] == $userData['userid']){
		$messaging->db->prepare_query("UPDATE msgheader SET new = 'n' WHERE id = ?", $data['headerid']);
		$db->prepare_query("UPDATE users SET newmsgs = newmsgs - 1 WHERE userid = ?", $userData['userid']);
		$userData['newmsgs']--;
//		$cache->decr(array($userData['userid'], "newmsgs-$userData[userid]"));
		$cache->remove(array($userData['userid'], "newmsglist-$userData[userid]"));
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
		if($data['from'] && $data['from'] != $uid)
			$links[] = "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=ignore&id=$data[from]','ignore this user')\">Ignore User</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&fid=$data[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&fid=$data[folder]&id=$id\">Previous</a>";
	}else{
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?uid=$uid\">Message list</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&uid=$uid&fid=$data[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&uid=$uid&fid=$data[folder]&id=$id\">Previous</a>";
	}

	echo "<tr><td class=body colspan=2>" . implode(" | ", $links) . "</td></tr>";


	echo "<tr><td class=header>To:</td><td class=header>" . ($data['to'] ? "<a class=header href=profile.php?uid=$data[to]>$data[toname]</a>" : "$data[toname]" ) . "</td></tr>";
	echo "<tr><td class=header>From:</td><td class=header>" . ($data['from'] ? "<a class=header href=profile.php?uid=$data[from]>$data[fromname]</a>" : "$data[fromname]" ) . "</td></tr>";
	echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a",$data['date']) . "</td></tr>";
	echo "<tr><td class=header>Subject:</td><td class=header>$data[subject]</td></tr>";
	echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($data['msg']))) . "<br><br></td></tr>";

	if($data['reply']){
//		echo "<tr><td class=body colspan=2 align=right><table width=97%>";
//		echo "<tr><td class=header colspan=2 align=center>Original Message</td></tr>";
//		echo "<tr><td class=header>To:</td><td class=header>" . ($data['replyto'] ? "<a class=header href=profile.php?uid=$data[replyto]>$data[replytoname]</a>" : "$data[replytoname]" ) . "</td></tr>";
//		echo "<tr><td class=header>From:</td><td class=header>" . ($data['replyfrom'] ? "<a class=header href=profile.php?uid=$data[replyfrom]>$data[replyfromname]</a>" : "$data[replyfromname]" ) . "</td></tr>";
		echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a",$data['replydate']) . "</td></tr>";
		echo "<tr><td class=header>Subject:</td><td class=header>$data[replysubject]</td></tr>";
		echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($data['replymsg']))) . "<br><br></td></tr>";
//		echo "</table></td></tr>";
	}

	echo "</table>";

	if($data['from']){
		if(substr($data['subject'],0,4)=='Re: '){
			$subject = "Re (2): " . substr($data['subject'],4);
		}elseif(substr($data['subject'],0,4)=='Re ('){
			$loc = strpos($data['subject'],")");
			$subject = "Re (" . (substr($data['subject'],4,$loc-4)+1)  . "): " . substr($data['subject'],$loc+3);
		}else{
			$subject = "Re: $data[subject]";
		}

		echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
		echo "<input type=hidden name=replyto value=$data[headerid]>";
		echo "<input type=hidden name=to value=$data[from]>";

	//	echo "<tr><td class=body colspan=2 align=center><b>Please do not share personal or financial information while using Nexopia.com.<br>All information passed through the use of this site, is at the risk of the user.<br>Nexopia.com will assume no liability for any users' actions.</b></td></tr>";

		echo "<tr><td class=header colspan=2 align=center>Reply</td></tr>";

		echo "<tr><td class=body>Subject: </td><td class=body><input class=body type=text name=\"subject\" value=\"" . htmlentities($subject) . "\" style=\"width:300\" maxlength=64></td></tr>\n";
		echo "<tr><td class=body colspan=2>";

		editbox("");

		echo "</td></tr>\n";
		echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Preview> <input class=body type=submit name=action accesskey='s' value=\"Send Message\"></td></tr>\n";

		echo "</form></table>";
	}


	incFooter();
	exit;
}
//*/

function listMsgs(){
	global $msgs, $new, $marked, $other, $folder, $userData, $page, $config, $messaging, $uid, $mods;

	if(!isset($uid))
		$uid = $userData['userid'];

	$folders = $messaging->getMsgFolders($uid);

	$pageparams = array();
	$pageparams[] = "folder=$folder";

	$params = array();

	$query = "SELECT SQL_CALC_FOUND_ROWS id, msgheaderid, mark, folder FROM msgs WHERE msgs.userid = ?";

	$params[] = $uid;

	if($uid != $userData['userid']){
		$pageparams[] = "uid=$uid";
		$mods->adminlog("list messages","List Userid $uid messages");
	}


	if($folder){
	 	$query .= " && msgs.folder = ?";
	 	$params[] = $folder;
	}

	if(isset($other) && $other != ""){
		$otherid = getUserID($other);

		if($otherid){
			$query .= " && msgs.other = ?";
			$params[] = $otherid;
			$pageparams[] = "other=$other";
		}else{
			$msgs->addMsg("User $other doesn't exist");
			$other = "";
		}
	}

	if(isset($marked)){
	 	$query .= " && msgs.mark = 'y'";
		$pageparams[] = "marked=$marked";
	}

	$query .= " ORDER BY msgs.id DESC LIMIT ".$page*$config['linesPerPage'].", $config[linesPerPage]";

	$messaging->db->prepare_array_query($query, $params);

	$showto = false;
	$showfrom = false;

	$messages = array();
	while($line = $messaging->db->fetchrow())
		$messages[$line['msgheaderid']] = $line;

	$messaging->db->query("SELECT FOUND_ROWS()");
	$numrows = $messaging->db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	if(count($messages)){
		$messaging->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id as msgheaderid, `to`, toname, `from`, fromname, date, new, subject, replied FROM msgheader WHERE id IN (#)", array_keys($messages));

		while($line = $messaging->db->fetchrow()){
			$messages[$line['msgheaderid']] += $line;
			if($line['to'] != $uid)
				$showto = true;
			if($line['from'] != $uid)
				$showfrom = true;
		}
	}
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

	addRefreshHeaders();

	incHeader();

	echo "<table width=100%>\n";
	echo "<tr><td class=header colspan=$cols>&nbsp;";


	if($uid == $userData['userid'])
		echo "<a class=header href=$_SERVER[PHP_SELF]?action=write" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">Write Message</a> | ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?action=ignorelist" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">Ignore List</a> | ";
	foreach($folders as $id => $foldername)
		echo "<a class=header href=$_SERVER[PHP_SELF]?folder=$id" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">$foldername</a> | ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?action=folders" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">Manage Folders</a>";

	echo "</td></tr>";

	echo "<tr><td class=header colspan=$cols>";

	echo "<table width=100% cellspacing=0 cellpadding=0>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header>";

	if($uid != $userData['userid'])
		echo "<input type=hidden name=uid value=$uid>";

	echo "&nbsp;Only show ";
	echo makeCheckBox('marked', 'Marked', 'header', isset($marked)) . ", ";
	echo "messages to or from ";
	echo "<input class=body type=text name=other size=10 value=\"$other\"> in ";
	echo "<select class=body name=folder><option value=0>All Folders" . make_select_list_key($folders, $folder) . "</select> ";
	echo "<input class=body type=submit name=action value=Filter>";

	echo "</td>";
	echo "</form>";

	echo "<td class=header align=right>";
	echo "Page: " . pageList($_SERVER['PHP_SELF'] . $pagestr, $page, $numpages, 'header');
	echo "</td></tr></table>";
	echo "</td></tr>";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";

	if($uid != $userData['userid'])
		echo "<input type=hidden name=uid value=$uid>";

	echo "<input type=hidden name=folder value=$folder>";
	echo "<tr>\n";
		echo "<td class=header width=25></td>\n";
		echo "<td class=header width=25>Mark</td>\n";
		echo "<td class=header width=15%>Status</td>\n";
		if($showto)
			echo "<td class=header width=15%>To</td>\n";
		if($showfrom)
			echo "<td class=header width=15%>From</td>\n";
		if(!$folder)
			echo "<td class=header width=15%>Folder</td>\n";
		echo "<td class=header width=50%>Subject</td>\n";
		echo "<td class=header width=20%>Received</td>\n";
	echo "</tr>\n";

	$classes = array('body','body2');
	$i=1;

	foreach($messages as $line){
		$i = !$i;
		echo "<tr><td class=$classes[$i]><input type=checkbox name=checkID[] value=\"$line[id]\"></td>";
		echo "<td class=$classes[$i] align=center>";
		if($line['mark']=='y')
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=unmark&checkID[]=$line[id]><img src=$config[imageloc]flag.gif border=0 alt=Mark></a>";
		else
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=mark&checkID[]=$line[id]><img src=$config[imageloc]dot.gif border=0></a>";
		echo "</td>";

		echo "<td class=$classes[$i]>";

		if($line['to']==$userData['userid'] || $userData['premium']){
			if($line['new']=='y')
				echo "<b>New</b>";
			elseif($line['replied']=='y')
				echo "Replied";
			else
				echo "Read";
		}

		echo "</td>";

		if($showto){
			echo "<td class=$classes[$i]>";
			if($line['to'])
				echo "<a class=body href=profile.php?uid=$line[to]>$line[toname]</a>";
			else
				echo "$line[toname]";
			echo "</td>";
		}
		if($showfrom){
			echo "<td class=$classes[$i]>";
			if($line['from'])
				echo "<a class=body href=profile.php?uid=$line[from]>$line[fromname]</a>";
			else
				echo "$line[fromname]";
			echo "</td>";
		}
		if(!$folder)
			echo "<td class=$classes[$i]>" . $folders[$line['folder']] . "</td>";

		echo "<td class=$classes[$i]><a class=body href=\"$_SERVER[PHP_SELF]?action=view&id=$line[id]" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . "\">";
		if(($line['to']==$userData['userid'] || $userData['premium']) && $line['new']=='y')
		 	echo"<b>$line[subject]</b>";
		else
			echo "$line[subject]";
		echo "</a></td>";
		echo "<td class=$classes[$i] nowrap>" . userdate("D M j, Y g:i a",$line['date']) . "</td></tr>\n";
	}

	echo "<tr><td class=header colspan=$cols><table width=100% cellpadding=0 cellspacing=0><tr><td class=header>";

//echo "<table cellpadding=0 cellspacing=0><tr><td class=header>";
	echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";

//echo "</td><td class=header>";

	echo "<input class=body type=submit name=action value=Delete>";

//echo "</td><td class=header>";
	echo "<select class=body name=moveto><option> Move to:" . make_select_list_key($folders) . "</select>";

	echo "<input class=body type=submit name=action value=Move>";
	echo "<input class=body type=submit name=action value=Archive>";

//echo "</td><td class=header>";


//echo "</td></tr></table>";

	echo "</td>\n<td class=header></td></form>";

	echo "<td align=right class=header>Page: " . pageList($_SERVER['PHP_SELF'] . $pagestr, $page, $numpages, 'header');

	echo "</td></tr></table>";
	echo "</table>\n";

	incFooter();
	exit();
}

function ignorelist(){
	global $db, $userData, $config, $uid;

	incHeader();

	$db->prepare_query("SELECT ignoreid, username FROM `ignore`,users WHERE `ignore`.userid = ? && `ignore`.ignoreid=users.userid", $uid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	sortCols($rows, SORT_ASC, SORT_CASESTR, 'username');

	echo "<table>";
	echo "<tr><td class=header>Username</td><td class=header></td></tr>";

	foreach($rows as $line){
		echo "<tr><td class=body><a class=body href='profile.php?uid=$line[ignoreid]'>$line[username]</a></td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=unignore&id=$line[ignoreid]" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . "><img src=$config[imageloc]delete.gif border=0></a></td></tr>";
	}
	echo "</table><br>";

	echo "<table><form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header colspan=2>Add to ignore list</td></tr>";
	if($uid != $userData['userid'])
		echo "<input type=hidden name=uid value=$uid>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=id></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Ignore></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function ignore($id){
	global $db, $uid, $cache;

	if(!is_numeric($id))
		$id = getUserID($id);

	$db->prepare_query("INSERT IGNORE INTO `ignore` SET userid = ?, ignoreid = ?", $uid, $id);
	$cache->remove(array($uid, "ignorelist-$uid"));

	ignorelist();
}

function unignore($id){
	global $db, $uid, $cache;

	if(!is_numeric($id))
		$id = getUserID($id);

	$db->prepare_query("DELETE FROM `ignore` WHERE userid = ? && ignoreid = ?", $uid, $id);

	$cache->remove(array($uid, "ignorelist-$uid"));

	ignorelist();
}

function archive($checkID){
	global $messaging, $uid, $userData, $config, $emaildomain, $msgs;

	if(empty($checkID)){
		$msgs->addMsg("You must check the messages you would like archived. They will be emailed to you");
		listMsgs(); //exit
	}

	$messaging->db->prepare_query("SELECT msgs.id, msgheader.id as headerid,  msgheader.toname, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg
		FROM msgs INNER JOIN msgheader ON msgs.msgheaderid = msgheader.id INNER JOIN msgtext ON msgheader.msgtextid = msgtext.id
		WHERE msgs.userid = ? && msgs.id IN (?) ORDER BY id ASC", $uid, $checkID);

	$message = "";

	while($line = $messaging->db->fetchrow()){
		$message .= "To:      $line[toname]\n";
		$message .= "From:    $line[fromname]\n";
		$message .= "Date:    " . userDate("D M j, Y g:i a",$line['date']) . "\n";
		$message .= "Subject: $line[subject]\n";
		$message .= "$line[msg]\n\n";
		$message .= "-----------------------------------------------\n\n";
	}
	$message .= "Message sent from $config[title] on " . userDate("D M j, Y g:i a");

	$subject = "Archive messages from $config[title]";

	$email = getUserInfo("email",$userData['userid']);

	smtpmail("$email", $subject, $message, "From: $config[title] <no-reply@$emaildomain>");

	incHeader();

	echo "Email sent to $email:";

	echo "<pre>$message</pre>";

	incFooter();
	exit;
}


