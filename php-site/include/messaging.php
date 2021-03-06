<?

define("MSG_INBOX",	1);
define("MSG_SENT",	2);
define("MSG_TRASH",	3);


class messaging{

	public $db;

	public $prunelength = 21;
	public $warning_threshold = 2;
/*
tables:
 -msgs - balanceable by userid
 -msgtext - balanced by userid, but userid is not in this table
 -msgfolder - balanced by userid
*/

	function __construct( & $db ){
		$this->db = & $db;
	}

	function createMsgFolder($name, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$id = $this->db->getSeqID($uid, DB_AREA_MESSAGE_FOLDER, 4); //start after MSG_TRASH

		$this->db->prepare_query("INSERT INTO msgfolder SET userid = %, id = ?, name = ?", $uid, $id, $name);

		$cache->remove("msgfolders-$uid");

		$msgs->addMsg("Folder Created");
	}

	function deleteMsgFolder($id, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$res = $this->db->prepare_query("SELECT id FROM msgs WHERE folder IN (#) && userid = %", $id, $uid);

		$msgids = array();
		while($line = $res->fetchrow())
			$msgids[] = $line['id'];

		$this->moveMsg($msgids, MSG_TRASH, $uid);

		$this->db->prepare_query("DELETE FROM msgfolder WHERE userid = % && id IN (?)", $uid, $id);

		$cache->remove("msgfolders-$uid");

		$msgs->addMsg("Folder(s) deleted");
	}

	function renameMsgFolder($id,$name, $uid = 0){
		global $userData, $msgs, $cache;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("UPDATE msgfolder SET name = ? WHERE userid = % && id = ?", $name, $uid, $id);

		$cache->remove("msgfolders-$uid");

		$msgs->addMsg("Folder renamed");
	}

	function getMsgFolders($uid = 0){
		global $userData, $cache, $config;

		static $folders;

		if(!$uid)
			$uid = $userData['userid'];

		if(!isset($folders[$uid])){

			$folders[$uid] = $cache->get("msgfolders-$uid");

			if(!$folders[$uid]){
				$res = $this->db->prepare_query("SELECT id,name FROM msgfolder WHERE userid = %", $uid);


				$folders[$uid] = array(MSG_INBOX => "Inbox", MSG_SENT => "Sent Items", MSG_TRASH => "Trash");

				$temp = array();
				while($line = $res->fetchrow())
					$temp[$line['id']] = $line['name'];

				sortCols($temp, SORT_ASC, SORT_CASESTR, 'name');

				foreach($temp as $k => $v)
					$folders[$uid][$k] = $v;

				$cache->put("msgfolders-$uid", $folders[$uid], 86400);
			}

		}
		return $folders[$uid];
	}

	function deleteMsg($checkID, $uid = 0){
		global $msgs, $userData;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->moveMsg($checkID, MSG_TRASH, $uid);
	}

	function moveMsg($checkID, $moveto, $uid = 0){
		global $userData, $msgs, $cache;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		if($moveto <= 0)
			return;

		if($moveto >= 4){
			$res = $this->db->prepare_query("SELECT id FROM msgfolder WHERE id = ? && userid = %", $moveto, $uid);
			if(!$res->fetchrow()){
				$msgs->addMsg("Folder doesn't exist");
				return;
			}
		}

		if($moveto == MSG_TRASH){
			$this->db->prepare_query("UPDATE msgs SET status = 'read' WHERE userid = % && `to` = # && status = 'new' && id IN (?)", $uid, $uid, $checkID);

			$num = $this->db->affectedrows();

			if($num > 0){
				global $usersdb;
				$usersdb->prepare_query("UPDATE users SET newmsgs = newmsgs - # WHERE userid = %", $num, $uid);
				$cache->remove("newmsglist-$uid");
				$cache->remove("userinfo-$uid");

				if($uid == $userData['userid'])
					$userData['newmsgs'] -= $num;
			}
		}

		$this->db->prepare_query("UPDATE msgs SET folder = # WHERE userid = % && id IN (?)", $moveto, $uid, $checkID);

		$msgs->addMsg("Message(s) Moved");
		return;
	}

	function markMsg($checkID, $uid = 0){
		global $userData, $msgs;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("UPDATE msgs SET mark = 'y' WHERE userid = % && id IN (?)", $uid, $checkID);

		$msgs->addMsg("Message Marked");
		return;
	}

	function unMarkMsg($checkID, $uid = 0){
		global $userData, $msgs;

		if(!isset($checkID) || !is_array($checkID) || count($checkID)==0)
			return;

		if(!$uid)
			$uid = $userData['userid'];

		$this->db->prepare_query("UPDATE msgs SET mark = 'n' WHERE userid = % && id IN (?)", $uid, $checkID);

		$msgs->addMsg("Message UnMarked");
		return;
	}

	function setNumNewMsgs($uid = 0){
		global $usersdb, $userData, $cache, $config;

		if(!$uid)
			$uid = $userData['userid'];

		$res = $this->db->prepare_query("SELECT count(*) FROM msgs WHERE userid = % && `to` = # && new = 'y'", $uid, $uid);
		$numnew = $res->fetchfield();

		if($uid != $userData['userid'] || $numnew != $userData['newmsgs']){
			$usersdb->prepare_query("UPDATE users SET newmsgs = # WHERE userid = %", $numnew, $uid);
			$cache->remove("newmsglist-$uid");
			$cache->remove("userinfo-$uid");
		}
		if($uid == $userData['userid'])
			$userData['newmsgs'] = $numnew;
	}

	function getMsg($uid, $ids, $cached = false){ //cached version has an outdated status, and may not show the right folder
		global $cache;

		$multiple = is_array($ids);

		if(!$multiple)
			$ids = array($ids);

		$msgs = array();

		if($cached)
			$msgs = $cache->get_multi($ids, "msgs-$uid-");

		$missingids = array_diff($ids, array_keys($msgs));

		if(count($missingids)){
			$res = $this->db->prepare_query("SELECT * FROM msgs WHERE userid = % && id IN (?)", $uid, $missingids);

			while($line = $res->fetchrow()){
				$msgs[$line['id']] = $line;
				$cache->put("msgs-$uid-$line[id]", $line, 86400);
			}
		}

		if(!count($msgs))
			return ($multiple ? array() : false);


		$msgtexts = $cache->get_multi($ids, "msgtext-$uid-");

		$missingids = array_diff($ids, array_keys($msgtexts));

		if(count($missingids)){
			$res = $this->db->prepare_query("SELECT id, msg, html FROM msgtext WHERE userid = % && id IN (?)", $uid, $missingids);

			while($line = $res->fetchrow()){
				$msgtexts[$line['id']] = $line;

				$cache->put("msgtext-$uid-$line[id]", $line, 86400);
			}
		}

		foreach($msgs as $msg){
			$msgs[$msg['id']]['msg'] = $msgtexts[$msg['id']]['msg'];
			$msgs[$msg['id']]['html'] = $msgtexts[$msg['id']]['html'];
		}

		return ($multiple ? $msgs : array_shift($msgs));
	}

	function deliverMsg($to, $subject, $message, $replyto = 0, $fromname = false, $fromid = false, $ignorable = true, $html = false, $allowemail = -1){
		global $userData, $msgs, $emaildomain, $config, $usersdb, $cache, $useraccounts, $archive, $Ruby;

		// The allow email parameter is never used on per-message basis.
		// It's always set by the default for the server.
		if ($allowemail === -1) {
			$allowemail = $config['defaultMessageAllowEmails'];
		}
		
		// The $html parameter is legacy and isn't used in any way.
		// It should be removed from all the code but hasn't been yet.
		$html = 'n';
		
		// If fromname isn't set use the current user.
		if($fromname === false) {
			$fromname = $userData['username'];
		}
		
		// If fromid isn't specified then use the current userid and set the age based on the current user
		// Otherwise use an age of 0.
		if($fromid === false) {
			$fromid = $userData['userid'];
			$age = $userData['age'];
		} else {
			$age = 0;
		}
		
		// Get the user info for the recipient(s)
		// If there's more than one recipient it means
		// that it was a mass message and can't possibly be a reply to
		// another message to so set replyto to 0.
		if(is_array($to)){
			$replyto = 0;
			$tousers = getUserInfo($to);
		}else{
			$tousers[$to] = getUserInfo($to);
		}

		// Can't find the recipient, bail out.
		if(!count($tousers)){
			$msgs->addMsg("User doesn't exist");
			return false;
		}

		// Remove HTML from the subject and message text
		$nsubject = removeHTML(trim($subject));
		$nmsg = cleanHTML($message);

		if($nsubject=="") {
			$nsubject="No Subject";
		}
		
		// If the message is ignorable then loop through all the recipients and
		// remove any who have ignored the user.
		if($ignorable && $fromid) {
			foreach($tousers as $to => $line) {
				if($ignoreid = isIgnored($to, $fromid, 'msgs', $age, ($replyto > 0) )) { //if ignored by age group or friends list, allow if it is a reply
					if($ignoreid == 1) {
						$msgs->addMsg("This user only accepts messages from friends.");
					} else {
						$msgs->addMsg("Message Ignored");
					}
					unset($tousers[$to]);
				}
			}
			// Exit if we no longer have any recipients
			if(!count($tousers)) {
				return false;
			}
		}

		$time = time();

		$otherreply = 0;

		// If this is a reply to a previous message figure out what the 
		// parent message id is for the other user.
		if($replyto){
			$res = $this->db->prepare_query("SELECT othermsgid FROM msgs WHERE userid = % && id = ?", $fromid, $replyto);
			$otherreply = $res->fetchrow();

			if($otherreply) {
				$otherreply = $otherreply['othermsgid'];
			}
		}

		// msgtext is what ends up in the cache.
		// note that $html is always 'n'.
		$msgtext = array('html' => $html, 'msg' => $nmsg);
		$ip = ($fromid ? ip2int(getip()) : 0);

		// Send the message by saving it in the DB.
		foreach($tousers as $toid => $user){
			$firstmsgid = $this->db->getSeqID($toid, DB_AREA_MESSAGE);
			$secondmsgid = ($fromid ? $this->db->getSeqID($fromid, DB_AREA_MESSAGE) : 0);

			$this->db->prepare_query("INSERT INTO msgs SET userid = %, id = ?, folder = #, otheruserid = #, `to` = #, toname = ?, `from` = #, fromname = ?, date = #, status = 'new', subject = ?, replyto = #, othermsgid = #, sentip = #",
								$toid, $firstmsgid, MSG_INBOX, $fromid, $toid, $user['username'], $fromid, $fromname, $time, $nsubject, $otherreply, $secondmsgid, $ip);

			$this->db->prepare_query("INSERT INTO msgtext SET userid = %, id = ?, msg = ?, date = #, html = ?", $toid, $firstmsgid, $nmsg, $time, $html);
			$cache->put("msgtext-$toid-$firstmsgid", $msgtext, 86400);

			if($fromid){
				$this->db->prepare_query("INSERT INTO msgs SET userid = %, id = ?, folder = #, otheruserid = #, `to` = #, toname = ?, `from` = #, fromname = ?, date = #, status = 'new', subject = ?, replyto = #, othermsgid = #, sentip = #",
									$fromid, $secondmsgid, MSG_SENT, $toid, $toid, $user['username'], $fromid, $fromname, $time, $nsubject, $replyto, $firstmsgid, $ip);

				$this->db->prepare_query("INSERT INTO msgtext SET userid = %, id = ?, msg = ?, date = #, html = ?", $fromid, $secondmsgid, $nmsg, $time, $html);
				$cache->put("msgtext-$fromid-$secondmsgid", $msgtext, 86400);
			}

			$archive->save($fromid, $secondmsgid, ARCHIVE_MESSAGE, ARCHIVE_VISIBILITY_PRIVATE, $toid, 0, $nsubject, $nmsg);

			if($otherreply)
				$this->db->prepare_query("UPDATE msgs SET status='replied' WHERE (userid = % && id = #) || (userid = % && id = #)", $toid, $otherreply, $fromid, $replyto);

			$new = $cache->remove("newmsglist-$toid");
			// Sending a message to ourselves?  Update the message count.
			if($userData['loggedIn'] && $toid == $userData['userid'])
				$userData['newmsgs']++;
			$cache->remove("userinfo-$toid");
		}

		// update message counts
		$this->db->prepare_query("UPDATE users SET newmsgs = newmsgs+1 WHERE userid IN (%)", array_keys($tousers));

		// Get the message ready for sending to email.
		$nmsg2 = smilies($nmsg);
		$nmsg2 = parseHTML($nmsg2);

		// For each user if email forwarding is turned on send that user an email copy of the message.
		if($allowemail){
			foreach($tousers as $to => $user){
				if ($fromname != "Nexopia") {
					$forwardmessages = $user['fwmsgs'] == 'y';
				} else {
					$forwardmessages = $user['fwsitemsgs'] == 'y';
				}
					
				if($forwardmessages){
					$emailaddr = $useraccounts->getEmail($user['userid']);
					$from_line = "From: " . (strpos($fromname, ':') === false && strpos($fromname, ';') === false && strpos($fromname,',') === false ? "$fromname on " : "") . "$config[title] <no-reply@$emaildomain>";
					$messagecontent = "From: $fromname\n\n$nmsg2\n\n------Forwarded Offline Message From $config[title]-----\nThe return address is NOT valid\nYou can disable these emails from your preferences page.";
					$Ruby->Message->forward_site_message($user['userid'], $nsubject, $nmsg2, $fromname);
				}
			}
		}
		
		return true;
	}

	function prune(){
		$time = time();

	$timer = new timer();
	$timer->start("messaging prune - " . gmdate("F j, g:i a T"));

	echo $timer->lap("delete msgs from trash");
//		$this->db->prepare_query(false, "DELETE FROM msgs WHERE folder = #", MSG_TRASH);
		$this->db->repeatquery($this->db->prepare("DELETE FROM msgs WHERE folder = #", MSG_TRASH), 10000);

	echo $timer->lap("delete old msgs");
//		$this->db->prepare_query(false, "DELETE FROM msgs WHERE date <= #", $time - 86400*$this->prunelength);
		$this->db->repeatquery($this->db->prepare("DELETE FROM msgs WHERE date <= #", $time - 86400*$this->prunelength), 10000);

	echo $timer->lap("delete stranded msgtext rows");
//		$this->db->prepare_query(false, "DELETE FROM msgtext WHERE date <= #", $time - 86400*$this->prunelength);
		$this->db->repeatquery($this->db->prepare("DELETE FROM msgtext WHERE date <= #", $time - 86400*$this->prunelength), 10000);

	echo $timer->stop();
	}

/*
	function dumpMsgs($uids){
		die("Rewrite to use the archive");
	//	$this->db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgs.to, msgs.toname, msgs.from, msgs.fromname, msgs.date, msgs.subject, msgtext.msg FROM msgs,msgtext WHERE msgs.id=msgtext.id && msgs.userid IN (?)", $uids);

	//	$this->db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgs.userid IN (?)", $uids);

	//SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgheader.from IN (319711)
	}

	function viewDumpedMsgs($uids, $sortbyuser = false){
		$res = $this->db->prepare_query("SELECT msgdump.*, IF(`to`=msgdump.userid, toname, fromname) AS username, IF(`to`=msgdump.userid, `from`, `to`) AS other, users.age, users.sex FROM msgdump LEFT JOIN users ON IF(`to`=msgdump.userid, `from`, `to`)=users.userid WHERE msgdump.userid IN (#) ORDER BY " . ($sortbyuser ? "other, " : "") . "date", $uids);

		$output = "dumping " . $this->db->numrows() . " messages<br>\n";

		while($line = $res->fetchrow()){
			$output .= "Msg id:  $line[id]\n";
			$output .= "User:    $line[username]\n";
			$output .= "Folder:  " . ($line['to'] == $line['userid'] ? "Inbox" : "Outbox") . "\n";
			$output .= "To:      $line[toname] ($line[to])" . ($line['to'] == $line['userid'] ? "" : " age: $line[age], sex: $line[sex]") . "\n";
			$output .= "From:    $line[fromname] ($line[from])" . ($line['from'] == $line['userid'] ? "" : " age: $line[age], sex: $line[sex]") . "\n";
			$output .= "Date:    " . userDate("D M j, Y g:i a", $line['date']) . "\n";
			$output .= "Subject: $line[subject]\n";
			$output .= "$line[msg]";

			$output .= "\n\n------------------------------------------------------------\n\n";
		}

		echo "<pre>$output</pre>";
	}
*/

	function archiveMonth($month, $year){
		$month = (int)$month;
		$year = (int)$year;

		if($year < 100)
			$year += 2000;

		$table = "msgarchive$year" . ($month < 10 ? '0' : '') . $month;

		$start = gmmktime(0,0,0,$month,1,$year);
		$end = gmmktime(23,59,59,$month,gmdate('t',$startdate),$year);

		$this->archivedb->query(
			"CREATE TABLE IF NOT EXISTS `$table` (
			  `id` int(10) unsigned NOT NULL default '0',
			  `to` int(10) unsigned NOT NULL default '0',
			  `toname` varchar(12) NOT NULL default '',
			  `from` int(10) unsigned NOT NULL default '0',
			  `fromname` varchar(12) NOT NULL default '',
			  `date` int(11) NOT NULL default '0',
			  `subject` varchar(64) NOT NULL default '',
			  `msg` text NOT NULL,
			  PRIMARY KEY  (`id`)
			) TYPE=MyISAM MAX_ROWS=4294967295 AVG_ROW_LENGTH=50;");

		$this->archivedb->prepare_query("INSERT IGNORE INTO $table SELECT * FROM msgarchive WHERE date BETWEEN # AND #", $start, $end);
		$this->archivedb->prepare_query("DELETE FROM msgarchive WHERE date BETWEEN # AND #", $start, $end);
	}
}
