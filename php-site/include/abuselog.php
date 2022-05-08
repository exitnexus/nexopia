<?

define('ABUSE_ADMINLEVEL_ADMIN', 1);
define('ABUSE_ADMINLEVEL_FORUMMOD', 2);

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
define('ABUSE_ACTION_FORUM_WARNING',	13);
define('ABUSE_ACTION_FORUM_NOTE',		14);
define('ABUSE_ACTION_LOGGED_MSG',		15);
define('ABUSE_ACTION_BLOG_EDIT',		16);
define('ABUSE_ACTION_TAGLINE_EDIT',		17);
define('ABUSE_ACTION_PICMOD_WARNING',	18);
define('ABUSE_ACTION_EDIT_GALLERY', 	19);
define('ABUSE_ACTION_EDIT_COMMENTS', 	20);
define('ABUSE_ACTION_EDIT_GALLERY_COMMENTS', 21);
define('ABUSE_ACTION_EDIT_BLOG_COMMENTS', 22);

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
define('ABUSE_REASON_DRUGS',	12);
define('ABUSE_REASON_BLOG',     13);
define('ABUSE_REASON_CREDIT',   14);
define('ABUSE_REASON_DISCRIM',	15); // discrimination
define('ABUSE_REASON_WEAPONS',	16);
define('ABUSE_REASON_THREATS',	17);
define('ABUSE_REASON_HACKED',   18);
define('ABUSE_REASON_REQUEST',  19); // by request of the user.

class abuselog{
	public $db;
/*
tables
-abuselog
-abuselogcomments

*/

	public $actions;
	public $manualactions;
	public $reasons;

	function __construct( & $db ){
		$this->db = & $db;

		$this->actions = array(
				ABUSE_ACTION_WARNING		=> 'Official Warning',
				ABUSE_ACTION_LOGGED_MSG		=> 'Logged Message',
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
				ABUSE_ACTION_BLOG_EDIT		=> 'Blog Edit',
				ABUSE_ACTION_TAGLINE_EDIT	=> 'Tagline Edit',
				ABUSE_ACTION_FORUM_BAN 		=> 'Forum Ban',
				ABUSE_ACTION_FORUM_WARNING	=> 'Forum Warning',
				ABUSE_ACTION_FORUM_NOTE		=> 'Forum Note',
				ABUSE_ACTION_PICMOD_WARNING => 'Pic Mod Warning',
				ABUSE_ACTION_EDIT_GALLERY 	=> 'Edit Gallery',
				ABUSE_ACTION_EDIT_COMMENTS 	=> 'Edit Comments',
				ABUSE_ACTION_EDIT_GALLERY_COMMENTS 	=> 'Edit Gallery Comments',
				ABUSE_ACTION_EDIT_BLOG_COMMENTS 	=> 'Edit Blog Comments',
			);

		$this->forumactions = array(
			ABUSE_ACTION_FORUM_BAN 		=> 'Forum Ban',
			ABUSE_ACTION_FORUM_WARNING	=> 'Forum Warning',
			ABUSE_ACTION_FORUM_NOTE		=> 'Forum Note',
			ABUSE_ACTION_SIG_EDIT 		=> 'Signature Edit',
		);

		$this->manualactions = array(
				ABUSE_ACTION_WARNING	=> 'Official Warning',
				ABUSE_ACTION_NOTE 		=> 'Note',
					);

		$this->reasons = array(
				ABUSE_REASON_NUDITY 	=> 'Nudity/Porn',
				ABUSE_REASON_RACISM 	=> 'Racism',
				ABUSE_REASON_DISCRIM	=> 'Discrimination',
				ABUSE_REASON_VIOLENCE 	=> 'Gore/Violence',
				ABUSE_REASON_WEAPONS	=> 'Weapons',
				ABUSE_REASON_DRUGS 		=> 'Drugs',
				ABUSE_REASON_SPAMMING 	=> 'Spamming',
				ABUSE_REASON_FLAMING 	=> 'Harassment',
				ABUSE_REASON_THREATS	=> 'Threats',
				ABUSE_REASON_PEDOFILE 	=> 'Pedophile',
				ABUSE_REASON_UNDERAGE 	=> 'Underage',
				ABUSE_REASON_FAKE 		=> 'Fake',
				ABUSE_REASON_ADVERT		=> 'Advertising',
				ABUSE_REASON_NOT_USER	=> 'User not in Picture',
				ABUSE_REASON_BLOG		=> 'Blog',
				ABUSE_REASON_CREDIT		=> 'Credit Card',
				ABUSE_REASON_HACKED     => 'Hacked',
				ABUSE_REASON_REQUEST	=> 'User Request',
				ABUSE_REASON_OTHER 		=> 'Other',
					);
	}

	function addAbuse($uid, $action, $reason, $subject, $message){
		global $userData, $msgs, $messaging, $mods, $usersdb, $db, $cache;

		if(is_numeric($uid)){
			$username = getUserName($uid);
			$userid = $uid;
		}else{
			$username = $uid;
			$userid = getUserID($uid);
		}

		if($username == $userid){
			$msgs->addMsg("Bad user, not added to the abuse log");
			return 0;
		}

		switch($action){
			case ABUSE_ACTION_WARNING:
			case ABUSE_ACTION_FORUM_WARNING:
			case ABUSE_ACTION_PICMOD_WARNING:
				$messaging->deliverMsg($userid, $subject, $message, 0, false, false, false);
		}

		$subject = removeHTML($subject);

		$nmsg = cleanHTML($message);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		$col = ($action == ABUSE_ACTION_USER_REPORT ? 'reportuserid' : 'modid');

		$this->db->prepare_query("INSERT INTO abuselog SET userid = #, $col = #, action = #, reason = #, time = #, subject = ?, msg = ?",
								$userid, $userData['userid'], $action, $reason, time(), $subject, $nmsg3);

		$id = $this->db->insertid();

		$usersdb->prepare_query("UPDATE users SET abuses = abuses + 1 WHERE userid = %", $userid);

		$cache->remove("userinfo-$userid");

		if($mods->isAdmin($userData['userid'], "abuselog"))
			$msgs->addMsg("Abuse Log Entry added: <a class=msg href=/adminabuselog.php?action=view&id=$id>View Entry</a>");

		return $id;
	}

	function addAbuseComment($id, $msg){
		global $userData;

		$msg = trim($msg);

		$nmsg = cleanHTML($msg);
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);

		$time = time();

		$res = $this->db->prepare_query("SELECT id FROM abuselogcomments WHERE abuseid = # && time > #", $id, $time - 5);

		if($res->fetchrow()) //double post
			return false;

		$this->db->prepare_query("INSERT INTO abuselogcomments SET abuseid = #, userid = #, time = #, msg = ?", $id, $userData['userid'], $time, $nmsg3);
	}

	function getAbuseID($id){
		$res = $this->db->prepare_query("SELECT * FROM abuselog WHERE id = #", $id);
		$row = $res->fetchrow();

		if(!$row)
			return false;

		$row['username'] = getUserName($row['userid']);
		$row['reportname'] = getUserName($row['reportuserid']);
		$row['modname'] = getUserName($row['modid']);

		$res = $this->db->prepare_query("SELECT * FROM abuselogcomments WHERE abuseid = # ORDER BY id", $id);
		$comments = $res->fetchrowset();

		if($comments){
			$uids = array();
			foreach($comments as $line)
				$uids[] = $line['userid'];

			$usernames = getUserName($uids);

			foreach($comments as $k => $v)
				$comments[$k]['username'] = $usernames[$v['userid']];
		}
		else
			$comments = array();

		return array('abuse' => $row, 'comments' => $comments);
	}

	function hasAccess ($action, $adminLevel) {
		if ($adminLevel == ABUSE_ADMINLEVEL_ADMIN && isset($this->actions[$action]))
			return true;
		if ($adminLevel == ABUSE_ADMINLEVEL_FORUMMOD && isset($this->forumactions[$action]))
			return true;

		return false;
	}

	function getActions ($adminLevel = 0) {
		switch ($adminLevel) {
			case ABUSE_ADMINLEVEL_ADMIN:
				return $this->actions;
				break;
			case ABUSE_ADMINLEVEL_FORUMMOD:
				return $this->forumactions;
				break;
			default:
				return array();
		}
	}

}

