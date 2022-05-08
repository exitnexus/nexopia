<?

	$login=1;

	require_once("include/general.lib.php");

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
			$msg 	 = getREQval('msg');
			$replyto = getPOSTval('replyto', 'int');

			writeMsg($to, $subject, $msg, $replyto, ($action == "Preview"));	//exit
			break;

		case 'massmessage':

			if($userData['userid'] != $uid || !$userData['premium'])
				break;

			$action2 = getREQval('action2');

			switch($action2){
				case "Send Message":
					$to 	 = getREQval('to', 'array');
					$subject = getREQval('subject');
					$msg 	 = getPOSTval('msg');

					sendMassMesg($to, $subject, $msg);
					header("location: $_SERVER[PHP_SELF]");
					exit;


				case "Preview":
				default:
					$to 	 = getREQval('to', 'array');
					$subject = getREQval('subject');
					$msg 	 = getPOSTval('msg');

					writeMassMsg($to, $subject, $msg, ($action2 == "Preview"));
				break;
			}
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

function sendMesg($to, $subject, $msg, $replyto){
	global $msgs, $userData, $sortt, $sortd, $messaging, $cache;

	$limit = $cache->get("messagesratelimit-$userData[userid]");

	if($limit){
		$cache->put("messagesratelimit-$userData[userid]", 1, 15); //block for another 15 seconds
		$msgs->addMsg("You can only send one message per second");
		return;
	}

	if(!is_numeric($to)){
		$touser=$to;
		$to=getUserID($to);
		if(!$to){
			$msgs->addMsg("Invalid User");
			writeMsg($to,$subject,$msg, $replyto,"Preview");
		}
	}

	$spam = spamfilter(trim($msg));

	if(!$spam || !$messaging->deliverMsg($to, $subject, $msg, $replyto, false, false, true, false))
		writeMsg($to, $subject, $msg, $replyto, null); // exits

	scan_string_for_notables($msg);

	$cache->put("messagesratelimit-$userData[userid]", 1, 3); //block for 3 seconds
}

function writeMsg($to="",$subject="",$msg="",$replyto=0, $preview = false){
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

	$nmsg = removeHTML(trim($msg));


	if($preview){
		$nsubject = trim(removeHTML($subject));

		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);

		$template->set("nsubject", $nsubject);
		$template->set("msg", nl2br($nmsg3));
	}
	$template->set("massmessage", $userData['premium']);
	$template->set("replyto", $replyto);
	$template->set("to", $to);
	$template->set("select_list_friends", make_select_list($friends));
	$template->set("subject", $subject);

	$template->set("editbox", editboxStr($msg));

	$template->display();
	exit();
}

function writeMassMsg($to = array(), $subject = "", $msg = "", $preview = false){
	global $msgs, $userData, $sortt, $sortd, $config, $usersdb, $messaging;

	$friendids = getMutualFriendsList($userData['userid'], USER_FRIENDS); //grabs the userids

	foreach($friendids as $k => $v)
		if(!$v)
			unset($friendids[$k]);

	$friendids = array_combine(array_keys($friendids), array_keys($friendids));

	$usernames = getUserName($friendids);
	uasort($usernames, 'strcasecmp');

	$friendslist = "";
	foreach($usernames as $k => $v){
		if(in_array($k, $to))
			$friendslist .= "<option value=\"" . htmlentities($k) . "\" selected> " . htmlentities($v) . "</option>";
		else
			$friendslist .= "<option value=\"" . htmlentities($k) . "\"> " . htmlentities($v) . "</option>";
	}


	$template = new Template("messages/writemassmsg");
	$template->set("preview", $preview);

	$nmsg = removeHTML(trim($msg));

	if($preview){
		$nsubject = trim(removeHTML($subject));

		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);

		$template->set("nsubject", $nsubject);
		$template->set("msg", nl2br($nmsg3));
	}
	$template->set("select_list_friends", $friendslist);
	$template->set("subject", $subject);
	$template->set("editbox", editboxStr($msg));
	$template->display();
	exit;
}


function sendMassMesg($to, $subject, $msg){
	global $msgs, $userData, $messaging, $cache;

	$limit = $cache->get("messagesratelimit-$userData[userid]");

	if($limit){
		$cache->put("messagesratelimit-$userData[userid]", 1, 15); //block for another 15 seconds
		$msgs->addMsg("You can only send one message per second");
		return;
	}

	$friendids = getMutualFriendsList($userData['userid'], USER_FRIENDS); //grabs the userids

	foreach($friendids as $k => $v)
		if(!$v)
			unset($friendids[$k]);

	$friendids = array_combine(array_keys($friendids), array_keys($friendids));


	$uids = array();
	foreach($to as $uid)
		if(isset($friendids[$uid]))
			$uids[] = $uid;


	$spam = spamfilter(trim($msg));

	if(!$spam || !$messaging->deliverMsg($uids, $subject, $msg, 0, false, false, true, false))
		writeMassMsg($to, $subject, $msg, 0, null);


	$cache->put("messagesratelimit-$userData[userid]", 1, 3); //block for 3 seconds
}

function forward($id){
	global $messaging, $uid, $userData;

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
													"Subject: $rmsg[subject]\n\n" .	"$rmsg[msg]");
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

function viewMsg($id){
	global $msgs, $userData, $usersdb, $messaging, $uid, $config, $mods, $cache;

	if(empty($id))
		return false;

	$template = new Template("messages/viewMsg1");

	$msg = $messaging->getMsg($uid, $id);


	if(!$msg)
		listMsgs();

	if($msg['replyto'])
		$rmsg = $messaging->getMsg($uid, $msg['replyto'], true);

	if($msg['status']=='new' && $msg['to'] == $userData['userid']){
		$usersdb->prepare_query("UPDATE msgs SET status='read' WHERE (userid = % && id = #) || (userid = % && id = #)", $msg['to'], $msg['id'], $msg['from'], $msg['othermsgid']);
		$usersdb->prepare_query("UPDATE users SET newmsgs = newmsgs - 1 WHERE userid = % && newmsgs > 0", $userData['userid']);
		if($userData['newmsgs'] > 0)
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

	$msg['msg'] = removeHTML($msg['msg']);
	$msg['msg'] = nl2br(parseHTML(smilies($msg['msg'])));


	if($msg['replyto'] && $rmsg)
	{
		$msg['replyto'] = $msg['replyto'] && $rmsg;
		$rmsg['msg'] = removeHTML($rmsg['msg']);
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
		$template->set("editbox", editBoxStr(""));
	}

	$template->set("msg", $msg);
	$template->display();
	exit;
}


function listMsgs(){
	global $msgs, $new, $folder, $userData, $page, $config, $messaging, $uid, $mods, $cache, $usersdb;
	$template = new Template("messages/listmessages");
	$marked = getREQval('marked', 'bool');
	$other = trim(getREQval('other'));

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
			$pageparams[] = "other=" . urlencode($other);
		}else{
			$msgs->addMsg("User doesn't exist");
			$other = "";
		}
	}

	if($marked){
	 	$query .= " && mark = 'y'";
		$pageparams[] = "marked=$marked";
	}

	$linesPerPage = $config['linesPerPage'];
	if ($largePage = getREQval('largepage', 'boolean'))
		$linesPerPage = $linesPerPage * 10;

	$query .= " ORDER BY date DESC LIMIT " . ($page*$linesPerPage) . ", $linesPerPage";

	$res = $usersdb->prepare_array_query($query, $params);

	$showto = false;
	$showfrom = false;

	$time = time();

	$pruneSoon = array();
	$messages = array();
	while($line = $res->fetchrow()){
		$messages[] = $line;
		if($line['to'] != $uid)
			$showto = true;
		if($line['from'] != $uid)
			$showfrom = true;
		if ($line['date'] - ($time - 86400*$messaging->prunelength) <= $messaging->warning_threshold*86400)
			$pruneSoon[] = "style='color:red;'";
		else
			$pruneSoon[] = '';
	}
	$template->set('pruneSoon', $pruneSoon);


	$numrows = $res->totalrows();
	$numpages =  ceil($numrows / $linesPerPage);


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
	$pagestr .= $largePage ? "&largepage=y" : "";

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
