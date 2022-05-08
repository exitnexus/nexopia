<?


define('ABUSE_ACTION_WARNING', 			1);
define('ABUSE_ACTION_FORUM_BAN',		2);
define('ABUSE_ACTION_DELETE_PIC', 		3);
define('ABUSE_ACTION_PROFILE_EDIT', 	4);
define('ABUSE_ACTION_FREEZE_ACCOUNT', 	5);
define('ABUSE_ACTION_DELETE_ACCOUNT', 	6);
define('ABUSE_ACTION_NOTE', 			7);
define('ABUSE_ACTION_SIG_EDIT', 		8);
define('ABUSE_ACTION_IP_BAN', 			9);
define('ABUSE_ACTION_EMAIL_BAN', 		10);
define('ABUSE_ACTION_UNFREEZE_ACCOUNT', 11);
define('ABUSE_ACTION_USER_REPORT',		12);

define('ABUSE_REASON_NUDITY', 	1);
define('ABUSE_REASON_RACISM', 	2);
define('ABUSE_REASON_VIOLENCE', 3);
define('ABUSE_REASON_SPAMMING', 4);
define('ABUSE_REASON_FLAMING',	5); //harassment
define('ABUSE_REASON_PEDOFILE', 6);
define('ABUSE_REASON_FAKE',		7);
define('ABUSE_REASON_OTHER',	8);
define('ABUSE_REASON_UNDERAGE',	9);
define('ABUSE_REASON_ADVERT',	10);
define('ABUSE_REASON_NOT_USER',	11);


class abuselog{
	var $db;
/*
tables
-abuselog
-abuselogcomments

*/

	var $actions;
	var $manualactions;
	var $reasons;

	function abuselog( & $db ){
		$this->db = & $db;


		$this->actions = array(
				ABUSE_ACTION_WARNING		=> 'Official Warning',
				ABUSE_ACTION_FORUM_BAN 		=> 'Forum Ban',
				ABUSE_ACTION_DELETE_PIC 	=> 'Delete Picture',
				ABUSE_ACTION_PROFILE_EDIT	=> 'Profile Edit',
				ABUSE_ACTION_SIG_EDIT 		=> 'Signature Edit',
				ABUSE_ACTION_FREEZE_ACCOUNT => 'Freeze Account',
				ABUSE_ACTION_UNFREEZE_ACCOUNT => 'Unfreeze Account',
				ABUSE_ACTION_DELETE_ACCOUNT => 'Delete Account',
				ABUSE_ACTION_IP_BAN 		=> 'IP Ban',
				ABUSE_ACTION_EMAIL_BAN		=> 'Email Ban',
				ABUSE_ACTION_NOTE 			=> 'Note',
				ABUSE_ACTION_USER_REPORT	=> 'User Report',
					);

		$this->manualactions = array(
				ABUSE_ACTION_WARNING	=> 'Official Warning',
				ABUSE_ACTION_NOTE 		=> 'Note'
					);

		$this->reasons = array(
				ABUSE_REASON_NUDITY 	=> 'Nudity/Porn',
				ABUSE_REASON_RACISM 	=> 'Racism',
				ABUSE_REASON_VIOLENCE 	=> 'Gore/Violence',
				ABUSE_REASON_SPAMMING 	=> 'Spamming',
				ABUSE_REASON_FLAMING 	=> 'Harassment',
				ABUSE_REASON_PEDOFILE 	=> 'Pedofile',
				ABUSE_REASON_UNDERAGE 	=> 'Underage',
				ABUSE_REASON_FAKE 		=> 'Fake',
				ABUSE_REASON_ADVERT		=> 'Advertising',
				ABUSE_REASON_NOT_USER	=> 'User not in Picture',
				ABUSE_REASON_OTHER 		=> 'Other'
					);



	}

	function addAbuse($uid, $action, $reason, $subject, $message){
		global $userData, $msgs, $messaging, $mods, $db;

		if(is_numeric($uid)){
			$username = getUserName($uid);
			$userid = $uid;
		}else{
			$username = $uid;
			$userid = getUserID($uid);
		}

		if($username == $userid){
			$msgs->addMsg("Bad user, not added to the abuse log");
			return;
		}


		if($action == ABUSE_ACTION_WARNING)
			$messaging->deliverMsg($userid, $subject, $message, 0, false, false, false);

		$subject = removeHTML($subject);

		$nmsg = removeHTML($message);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);

		$this->db->prepare_query("INSERT INTO abuselog SET userid = #, username = ?, modid = #, modname = ?, action = #, reason = #, time = #, subject = ?, msg = ?",
								$userid, $username, $userData['userid'], $userData['username'], $action, $reason, time(), $subject, $nmsg3);

		$id = $this->db->insertid();

		$db->prepare_query("UPDATE users SET abuses = abuses + 1 WHERE userid = #", $userid);

		if($mods->isAdmin($userData['userid'], "abuselog"))
			$msgs->addMsg("Abuse Log Entry added: <a class=msg href=/adminabuselog.php?action=view&id=$id>View Entry</a>");
	}

	function addAbuseComment($id, $msg){
		global $userData;

		$msg = trim($msg);

		$nmsg = removeHTML($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);

		$time = time();

		$this->db->prepare_query("SELECT id FROM abuselogcomments WHERE abuseid = ? && time > ?", $id, $time - 30);

		if($this->db->numrows() > 0) //double post
			return false;

		$this->db->prepare_query("INSERT INTO abuselogcomments SET abuseid = ?, username = ?, userid = ?, time = ?, msg = ?", $id, $userData['username'], $userData['userid'], $time, $nmsg3);
	}

	function getAbuseID($id){
		$this->db->prepare_query("SELECT * FROM abuselog WHERE id = ?", $id);
		$row = $this->db->fetchrow();

		$this->db->prepare_query("SELECT * FROM abuselogcomments WHERE abuseid = ? ORDER BY id", $id);
		$comments = $this->db->fetchrowset();

		return array('abuse' => $row, 'comments' => $comments);
	}



}

