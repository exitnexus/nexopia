<?

	$login=1;

	require_once("include/general.lib.php");

	$folder = getREQval('folder', 'int', MSG_INBOX);

	$page = getREQval('page', 'int');

	$isAdmin = $mods->isAdmin($userData['userid'], 'viewmessages');
	if(!isset($uid) || !$isAdmin)
		$uid = $userData['userid'];

	switch($action){
		case "viewnew":
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
			if($id = getREQval('id', 'int'))
				viewMsg($id);	//exit
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
		case "Preview":
			$to 	 = getREQval('to');
			$subject = getREQval('subject');
			$msg 	 = getPOSTval('msg');
			$replyto = getPOSTval('replyto', 'int');
			writeMsg($to, $subject, $msg, $replyto, ($action == "Preview"));	//exit
			break;

		case "Send Message":
		case "send":
			$to 	 = getPOSTval('to');
			$subject = getPOSTval('subject');
			$msg 	 = getPOSTval('msg');
			$replyto = getPOSTval('replyto', 'int');
			sendMesg($to, $subject, $msg, $replyto);
			header("location: $_SERVER[PHP_SELF]");
			exit;

		case "Mark":
		case "mark":
			if(($checkID = getREQval('checkID', 'array')))
				$messaging->markMsg($checkID);
			break;
		case "UnMark":
		case "unmark":
			if(($checkID = getREQval('checkID', 'array')))
				$messaging->unMarkMsg($checkID);
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
		case "ignore":
		case "Ignore":
			if($id = getREQval('id'))
				ignore($id);
			break;
		case "unignore":
		case "UnIgnore":
			if($id = getREQval('id', 'int'))
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
	global $userData, $config, $uid, $messaging;

	$folders = $messaging->getMsgFolders($uid);

	$folderdata = array();
	foreach($folders as $id => $name)
		$folderdata[$id] = array('count' => 0, 'name' => $name);

	unset($folders);

	$messaging->db->prepare_query($uid, "SELECT folder, count(*) as count FROM msgs WHERE userid = # GROUP BY folder", $uid);

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
	global $messaging, $uid;

	$msg = $messaging->getMsg($uid, $id);

	if(!$msg)
		die("query failed, likely trying to access message not to the user");

	$subject = "Fw: $msg[subject]";
	$message = "\n\n";

	$message .= str_replace("\n","\n>",	"\n----- Original Message -----\n" .
										"To: [user]" . $msg['toname'] . "[/user]\n" .
										"From: [user]" . $msg['fromname'] . "[/user]\n" .
										"Sent: " . userdate("D M j, Y g:i a", $msg['date']) . "\n" .
										"Subject: $msg[subject]\n\n" .
										"$msg[msg]");


	if($msg['replyto']){
		$rmsg = $messaging->getMsg($uid, $msg['replyto']);

		$message .= "\n>" . str_replace("\n","\n>>",	"\n----- Original Message -----\n" .
														"To: [user]" . $rmsg['toname'] . "[/user]\n" .
														"From: [user]" . $rmsg['fromname'] . "[/user]\n" .
														"Sent: " . userdate("D M j, Y g:i a", $rmsg['date']) . "\n" .
														"Subject: $rmsg[subject]\n\n" .
														"$rmsg[msg]");
	}

	writeMsg("", $subject, $message);
}

function viewNext($id, $fid){
	global $messaging, $uid;

	$messaging->db->prepare_query($uid, "SELECT id FROM msgs WHERE userid = # && folder = # && id > # ORDER BY id ASC LIMIT 1", $uid, $fid, $id);

	if($messaging->db->numrows()){
		viewMsg($messaging->db->fetchfield());
	}else{
		listMsgs();
	}
}

function viewPrev($id, $fid){
	global $messaging, $uid;
	$messaging->db->prepare_query($uid, "SELECT id FROM msgs WHERE userid = # && folder = # && id < # ORDER BY id DESC LIMIT 1", $uid, $fid, $id);

	if($messaging->db->numrows()){
		viewMsg($messaging->db->fetchfield());
	}else{
		listMsgs();
	}
}

function viewNew(){
	global $messaging, $userData, $db, $cache, $config;

	$messaging->db->prepare_query($userData['userid'], "SELECT id FROM msgs WHERE userid = # && folder = # && `to` = # && status='new' ORDER BY id ASC LIMIT 1", $userData['userid'], MSG_INBOX, $userData['userid']);

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

function viewMsg($id){
	global $msgs, $userData, $db, $messaging, $uid, $config, $mods, $cache;

	if(empty($id))
		return false;

	$msg = $messaging->getMsg($uid, $id);

	if(!$msg)
		listMsgs();

	if($msg['replyto'])
		$rmsg = $messaging->getMsg($uid, $msg['replyto']);

	if($msg['status']=='new' && $msg['to'] == $userData['userid']){
		$ids = array($id, $msg['othermsgid']);
		$messaging->db->prepare_query(array($msg['to'], $msg['from']), "UPDATE msgs SET status = 'read' WHERE id IN (#)", $ids);
		$db->prepare_query("UPDATE users SET newmsgs = newmsgs - 1 WHERE userid = #", $userData['userid']);
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
		if($msg['from'] && $msg['from'] != $uid)
			$links[] = "<a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=ignore&id=$msg[from]','ignore this user')\">Ignore User</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&fid=$msg[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&fid=$msg[folder]&id=$id\">Previous</a>";
	}else{
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?uid=$uid\">Message list</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=next&uid=$uid&fid=$msg[folder]&id=$id\">Next</a>";
		$links[] = "<a class=body href=\"$_SERVER[PHP_SELF]?action=prev&uid=$uid&fid=$msg[folder]&id=$id\">Previous</a>";
	}

	echo "<tr><td class=body colspan=2>" . implode(" | ", $links) . "</td></tr>";


	echo "<tr><td class=header>To:</td><td class=header>" . ($msg['to'] ? "<a class=header href=profile.php?uid=$msg[to]>$msg[toname]</a>" : "$msg[toname]" ) . "</td></tr>";
	echo "<tr><td class=header>From:</td><td class=header>" . ($msg['from'] ? "<a class=header href=profile.php?uid=$msg[from]>$msg[fromname]</a>" : "$msg[fromname]" ) . "</td></tr>";
	echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a", $msg['date']) . "</td></tr>";
	echo "<tr><td class=header>Subject:</td><td class=header>$msg[subject]</td></tr>";
	echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($msg['msg']))) . "<br><br></td></tr>";

	if($msg['replyto'] && $rmsg){
//		echo "<tr><td class=body colspan=2 align=right><table width=97%>";
//		echo "<tr><td class=header colspan=2 align=center>Original Message</td></tr>";
//		echo "<tr><td class=header>To:</td><td class=header>" . ($rmsg['to'] ? "<a class=header href=profile.php?uid=$rmsg[to]>$rmsg[toname]</a>" : "$rmsg[toname]" ) . "</td></tr>";
//		echo "<tr><td class=header>From:</td><td class=header>" . ($rmsg['from'] ? "<a class=header href=profile.php?uid=$rmsg[from]>$rmsg[fromname]</a>" : "$rmsg[fromname]" ) . "</td></tr>";
		echo "<tr><td class=header>Date:</td><td class=header>" . userdate("D M j, Y g:i a", $rmsg['date']) . "</td></tr>";
		echo "<tr><td class=header>Subject:</td><td class=header>$rmsg[subject]</td></tr>";
		echo "<tr><td class=body valign=top colspan=2>" . nl2br(parseHTML(smilies($rmsg['msg']))) . "<br><br></td></tr>";
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

function listMsgs(){
	global $msgs, $new, $marked, $other, $folder, $userData, $page, $config, $messaging, $uid, $mods;

	if(!isset($uid))
		$uid = $userData['userid'];

	$folders = $messaging->getMsgFolders($uid);

	$pageparams = array();
	$pageparams[] = "folder=$folder";

	$params = array();

	$query = "SELECT SQL_CALC_FOUND_ROWS id, folder, `to`, toname, `from`, fromname, date, mark, status, subject FROM msgs WHERE userid = #";

	$params[] = $uid;

	if($uid != $userData['userid']){
		$pageparams[] = "uid=$uid";
		$mods->adminlog("list messages","List Userid $uid messages");
	}

	if($folder){
	 	$query .= " && folder = #";
		$params[] = $folder;
	}

	if(isset($other) && $other != ""){
		$otherid = getUserID($other);

		if($otherid){
			$query .= " && otheruserid = #";
			$params[] = $otherid;
			$pageparams[] = "other=$other";
		}else{
			$msgs->addMsg("User $other doesn't exist");
			$other = "";
		}
	}

	if(isset($marked)){
	 	$query .= " && mark = 'y'";
		$pageparams[] = "marked=$marked";
	}

	$query .= " ORDER BY date DESC LIMIT ".$page*$config['linesPerPage'].", $config[linesPerPage]";

	$messaging->db->prepare_array_query($uid, $query, $params);

	$showto = false;
	$showfrom = false;

	$messages = array();
	while($line = $messaging->db->fetchrow()){
		$messages[] = $line;
		if($line['to'] != $uid)
			$showto = true;
		if($line['from'] != $uid)
			$showfrom = true;
	}

	$messaging->db->query($uid, "SELECT FOUND_ROWS()");
	$numrows = $messaging->db->fetchfield();
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

	addRefreshHeaders();

	incHeader();

	echo "<table width=100%>\n";
	echo "<tr><td class=body colspan=$cols>&nbsp;";


	if($uid == $userData['userid'])
		echo "<a class=body href=$_SERVER[PHP_SELF]?action=write" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">Write Message</a> | ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=ignorelist" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">Ignore List</a> | ";
	foreach($folders as $id => $foldername)
		echo "<a class=body href=$_SERVER[PHP_SELF]?folder=$id" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">$foldername</a> | ";
	echo "<a class=body href=$_SERVER[PHP_SELF]?action=folders" . ($uid == $userData['userid'] ? "" : "&uid=$uid") . ">Manage Folders</a>";

	echo "</td></tr>";

	echo "<tr><td class=header colspan=$cols>";

	echo "<table width=100% cellspacing=0 cellpadding=0>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header>";

	if($uid != $userData['userid'])
		echo "<input type=hidden name=uid value=$uid>";

	echo "&nbsp;Only show ";
	echo makeCheckBox('marked', 'Marked', isset($marked)) . ", ";
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

		if($line['to'] == $userData['userid'] || $userData['premium']){
			switch($line['status']){
				case 'new': 	echo "<b>New</b>";	break;
				case 'read': 	echo "Read";		break;
				case 'replied':	echo "Replied";		break;
				default:	echo $line['status'];
			}
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
		if(($line['to']==$userData['userid'] || $userData['premium']) && $line['status']=='new')
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

	$db->prepare_query("SELECT ignoreid, username FROM `ignore`,users WHERE `ignore`.userid = # && `ignore`.ignoreid=users.userid", $uid);

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

	$db->prepare_query("INSERT IGNORE INTO `ignore` SET userid = #, ignoreid = #", $uid, $id);
	$cache->remove(array($uid, "ignorelist-$uid"));

	ignorelist();
}

function unignore($id){
	global $db, $uid, $cache;

	if(!is_numeric($id))
		$id = getUserID($id);

	$db->prepare_query("DELETE FROM `ignore` WHERE userid = # && ignoreid = #", $uid, $id);

	$cache->remove(array($uid, "ignorelist-$uid"));

	ignorelist();
}

function archive($checkID){
	global $messaging, $uid, $userData, $config, $emaildomain, $msgs;

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

	$email = getUserInfo("email",$userData['userid']);

	smtpmail("$email", $subject, $output, "From: $config[title] <no-reply@$emaildomain>");

	incHeader();

	echo "Email sent to $email:";

	echo "<pre>$output</pre>";

	incFooter();
	exit;
}


