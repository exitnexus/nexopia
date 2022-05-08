<?

global $config;

$config['activetimeout'] = 600;
$config['sessiontimeout'] = 3600;
$config['longsessiontimeout'] = 30*86400;

define("ACCOUNT_STATE_NEW", 1);
define("ACCOUNT_STATE_ACTIVE", 2);
define("ACCOUNT_STATE_FROZEN", 11);
//perm frozen vs temp frozen?
define("ACCOUNT_STATE_DELETED", 15);

class accounts {

	public $masterdb;
	public $usersdb;
	public $db;

	function __construct($masterdb, $usersdb, $db){
		$this->masterdb = $masterdb;
		$this->usersdb = $usersdb;
		$this->db = $db;
	}

	function createAccount($type){
		global $cache;

		// TODO: Pull db_type_user from generaldb.typeid instead.
		$res = $this->masterdb->prepare_query("SELECT serverid FROM serverbalance WHERE type = # AND weight > 0 ORDER BY (realaccounts/weight) ASC LIMIT 1", $type);
		$serverid = $res->fetchfield();

		$this->masterdb->prepare_query("UPDATE serverbalance SET totalaccounts = totalaccounts + 1, realaccounts = realaccounts + 1 WHERE serverid = #", $serverid);

		$this->masterdb->prepare_query("INSERT INTO accounts SET type = #, serverid = #, state = #", $type, $serverid, ACCOUNT_STATE_NEW);

		$id = $this->masterdb->insertid();

		// Create the primary relationship.
		$this->masterdb->prepare_query("INSERT INTO accountmap SET primaryid = #, accountid = #", $id, $id);

		$cache->remove("serverid-user-$id");

		return $id;
	}

	function getUserAccounts($userid){
		$res = $this->masterdb->prepare_query("SELECT relid, accountid, type, serverid FROM accountmap, accounts WHERE accountmap.accountid = accounts.id && primaryid = #", $userid);

		return $res->fetchrowset();
	}

	function getAccountUsers($accountid){
		$res = $this->masterdb->prepare_query("SELECT relid, primaryid FROM accountmap WHERE accountid = #", $userid);

		return $res->fetchrowset();
	}

	function getServerID($accountid){
		$res = $this->masterdb->prepare_query("SELECT serverid FROM accounts WHERE accountid = #", $accountid);

		return $res->fetchfield();
	}

}

class useraccounts {

	public $usersdb;
	public $masterdb;

	function __construct($masterdb, $usersdb){
		$this->usersdb = $usersdb;
		$this->masterdb = $masterdb;
	}

	function createAccount($username, $password, $email, $dob, $sex, $loc){
		global $accounts, $auth, $config, $msgs, $typeid;

		$type = $typeid->getTypeID("User");
		$userid = $accounts->createAccount($type);

		$this->masterdb->prepare_query("INSERT IGNORE INTO usernames SET userid = #, username = ?, live='y'", $userid, $username);

		if(!$this->masterdb->affectedrows())
			return false;

		if(!$userid){
			$msgs->addMsg("Username already in use");
			return false;
		}

		$key = $this->makeRandkey();
		$time = time();

		$this->masterdb->prepare_query("INSERT IGNORE INTO useremails SET userid = #, active = 'n', email = ?, `key` = ?, time = #", $userid, $email, $key, $time);

		if($this->masterdb->affectedrows() == 0){
			$msgs->addMsg("Email already in use");
			return false;
		}

		$ip = ip2int(getip());
	 	$age = getAge($dob);
		$hash = $auth->hash_password($password);

		if($sex == 'Male'){
			$defaultsex = 'Female';
			$defaultminage = floor($age/2+7);
			$defaultmaxage = ceil(3*$age/2-7);
		}else{
			$defaultsex = 'Male';
			$defaultminage = floor($age/2+7);
			$defaultmaxage = ceil(3*$age/2-5);
		}
		$defaultloc = $loc;

		$this->usersdb->prepare_query("INSERT INTO usernames SET userid = %, username = ?", $userid, $username);
		$this->usersdb->prepare_query("INSERT INTO userpasswords SET userid = %, password = ?", $userid, $hash);
		$this->usersdb->prepare_query("INSERT INTO profile SET userid = %", $userid);

		$this->usersdb->prepare_query("INSERT INTO users SET userid = %, dob = #, age = #, loc = #, sex = ?, jointime = #, timeoffset = #, ip = #, defaultsex = ?, defaultloc = #, defaultminage = #, defaultmaxage = #",
			$userid, $dob, $age, $loc, $sex, $time, $config['timezone'], $ip, $defaultsex, $defaultloc, $defaultminage, $defaultmaxage);


		$this->usersdb->squery($userid, "UPDATE stats SET userstotal = userstotal + 1");


		return array($userid, $key);
	}

	function delete($uids, $reason=""){
		global $userData, $msgs, $forums, $mods, $cache, $weblog, $db;

		$old_user_abort = ignore_user_abort(true);

		if(!is_array($uids))
			$uids = array($uids);


		foreach($uids as $k => $uid){
			if(!is_numeric($uid))
				$uids[$k]=getUserID($uid);

			if($mods->isAdmin($uid) || $mods->isMod($uid)){
				if($userData['loggedIn'] && $userData['userid']!=$uid){
					$msgs->addMsg("Cannot delete an admin/mod");
					unset($uids[$k]);
				}
			}
		}

		if(!count($uids))
			return false;

		$uids = array_combine($uids, $uids); // keys == vals

		if($userData['loggedIn'])
			$deleteid=$userData['userid'];
		else
			$deleteid=0;

		//admins/mods can delete themselves, but no one else can

		$time = time();


		$result = $this->usersdb->prepare_query("SELECT userid, state, jointime, ip FROM users WHERE userid IN (%)", $uids);
		$lines = $result->fetchrowset();

		if(!$lines)
			return;

		$usernames = getUserName($uids);

		$res = $this->masterdb->prepare_query("SELECT userid, email FROM useremails WHERE userid IN (#) ORDER BY active", $uids); //ie active='y' second if there is one
		$emails = $res->fetchfields('userid');

		foreach ($lines as $line){
			if($line['state']=='frozen'){
				$msgs->addMsg("Account $line[userid] is frozen, and wasn't deleted");
				unset($uids[$line['userid']]);
			}else{
				$userName = $usernames[$line['userid']];
				$db->prepare_query("REPLACE INTO deletedusers SET userid = #, username= ?, email = ?, time = #, reason = ?, deleteid = #, jointime = #, ip = #",
				$line['userid'], $userName, $emails[$line['userid']], $time, $reason, $deleteid, $line['jointime'], $line['ip']);
			}
		}


		if(!count($uids))
			return false;

		foreach($uids as $uid){
			$cache->remove("userprefs-$uid");
			$cache->remove("userinfo-$uid");
			$cache->remove("useractive-$uid");
			$cache->remove("profileviews-$uid");
			$cache->remove("profile-$uid");
			$cache->remove("comments5-$uid");
			$cache->remove("username2userid-" . strtolower($usernames[$uid]));

			$friends1 = getFriendsListIDs($uid, USER_FRIENDS);
			$friends2 = getFriendsListIDs($uid, USER_FRIENDOF);

			$cache->remove("friendids" . USER_FRIENDS . "-$uid");
			$cache->remove("friendids" . USER_FRIENDOF . "-$uid");
			$cache->remove("friendsonline-$uid");

			foreach($friends1 as $id)
				$cache->remove("friendids" . USER_FRIENDOF . "-$id");

			foreach($friends2 as $id){
				$cache->remove("friendids" . USER_FRIENDS . "-$id");
				$cache->remove("friendsonline-$id");
			}
		}


		$this->usersdb->prepare_query("DELETE FROM users WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM userpasswords WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM usercounter WHERE id IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM useractivetime WHERE userid IN (%)", $uids);
		
		$this->usersdb->prepare_query("DELETE FROM profile WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM profileblocks WHERE userid IN (%)", $uids);

		$this->masterdb->prepare_query("UPDATE usernames SET live = NULL WHERE userid IN (#)", $uids);
		$this->masterdb->prepare_query("DELETE FROM useremails WHERE userid IN (#)", $uids);

		$this->usersdb->prepare_query("DELETE FROM `ignore` WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM `ignore` WHERE ignoreid IN (#)", $uids); //yes, all dbs


		$this->usersdb->prepare_query("DELETE FROM friends WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM friends WHERE friendid IN (#)", $uids); //yes, all dbs
		$this->usersdb->prepare_query("DELETE FROM friendscomments WHERE userid IN (%)", $uids);

		$mods->deleteAdmin($uids);
		$mods->deleteMod($uids);

		$forums->db->prepare_query("DELETE FROM forummods WHERE userid IN (#)", $uids);
		$forums->db->prepare_query("DELETE FROM foruminvite WHERE userid IN (#)", $uids);
		$forums->db->prepare_query("DELETE forummute, forummutereason FROM forummute, forummutereason WHERE forummute.id=forummutereason.id && userid IN (#)", $uids);
		$forums->db->prepare_query("DELETE FROM forumrankspending WHERE userid IN (#)", $uids);
		$forums->db->prepare_query("DELETE FROM forumupdated WHERE userid IN (#)", $uids);
		$forums->db->prepare_query("DELETE FROM forumread WHERE userid IN (#)", $uids);
		$forums->db->prepare_query("DELETE FROM forumcatcollapse WHERE userid IN (#)", $uids);
		$forums->db->prepare_query("DELETE FROM forumcats WHERE ownerid IN (#)", $uids);

		$this->usersdb->prepare_query("DELETE FROM usernames WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM sessions WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM profileviews WHERE userid IN (%)", $uids);

		removeAllUserPics($uids);

		foreach($uids as $uid){
			$userblog = new userblog($weblog, $uid);
			$userblog->deleteBlog();
		}

		$this->usersdb->prepare_query("DELETE FROM msgs WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM msgtext WHERE userid IN (%)", $uids);
		$this->usersdb->prepare_query("DELETE FROM msgfolder WHERE userid IN (%)", $uids);

		$this->usersdb->prepare_query("DELETE FROM usercomments WHERE userid IN (%)", $uids);

		foreach($uids as $uid)
			$this->usersdb->squery($uid, "UPDATE stats SET userstotal = userstotal - 1"); //can't do as a single query, as it'd substract the total from all dbs

		$this->masterdb->prepare_query("UPDATE serverbalance, accounts SET serverbalance.realaccounts = serverbalance.realaccounts - 1, state = # WHERE accounts.id IN (#) && accounts.serverid = serverbalance.serverid", ACCOUNT_STATE_DELETED, $uids);


		ignore_user_abort($old_user_abort);

		return true;
	}

	function changeEmail($userid, $email){
		global $msgs, $config, $wwwdomain, $emaildomain;

		$key = $this->makeRandkey();

		$this->masterdb->prepare_query("DELETE FROM useremails WHERE userid = # && active = 'n'", $userid);
		$this->masterdb->prepare_query("INSERT IGNORE INTO useremails SET userid = #, active = 'n', email = ?, `key` = ?, time = #", $userid, $email, $key, time());

		if($this->masterdb->affectedrows() == 0){
			$msgs->addMsg("Email already in use");
			return false;
		}

		$username = getUserName($userid);

		$message="To activate your account at $config[title] click on the following link or copy it into your webbrowser: http://$wwwdomain/activate.php?username=$username&actkey=$key";
		$subject="Change email for your account at $wwwdomain.";

		smtpmail("$email", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");

		return $key;
	}

	function getEmail($userid)
	{
		global $cache;

		$email = $cache->get("useremail-$userid");
		if ($email)
			return $email;

		$res = $this->masterdb->prepare_query("SELECT email FROM useremails WHERE userid = # AND active = 'y'", $userid);
		$row = $res->fetchrow();

		if ($row)
		{
			$email = $row['email'];
			$cache->put("useremail-$userid", $email, 24*60*60);
		}

		return $email;
	}

	function activate($userid, $key = false){
		global $msgs, $cache;

		if($key === false){ //admin override
		//check it can be activated
			$res = $this->masterdb->prepare_query("SELECT time FROM useremails WHERE userid = # && active = 'n'", $userid);
			$time = $res->fetchrow();

			if(!$time){
				$msgs->addMsg("Cannot be activated");
				return false;
			}
		}else{
		//check if it exists
			$res = $this->masterdb->prepare_query("SELECT time FROM useremails WHERE userid = # && active = 'n' && `key` = ?", $userid, $key);
			$time = $res->fetchrow();

			if(!$time)
				return false;

			if($time['time'] < (time()-86400*7)){
				$msgs->addMsg("That activation key has expired. Have one resent.");
				return false;
			}
		}

	//delete the active one, replace it with the newly activated one, and update the users status
		$this->masterdb->prepare_query("DELETE FROM useremails WHERE userid = # && active = 'y'", $userid);
		$prev = $this->masterdb->affectedrows();

		$this->masterdb->prepare_query("UPDATE useremails SET active = 'y' WHERE userid = # && active = 'n'", $userid);

		$this->usersdb->prepare_query("UPDATE users SET state = 'active' WHERE userid = % && state IN ('new','active')", $userid);
		$this->masterdb->prepare_query("UPDATE accounts SET state = # WHERE id = #", ACCOUNT_STATE_ACTIVE, $userid);

		$cache->remove("userprefs-$userid");
		$cache->remove("userinfo-$userid");
		$cache->remove("useremail-$userid");



		if(!$prev){
			$line = getUserInfo($userid);

			global $db;

			$db->prepare_query("INSERT IGNORE INTO newestusers SET userid = %, username = ?, time = #, age = #, sex = ?", $line['userid'], $line['username'], time(), $line['age'], $line['sex']);
		}

		return true;
	}

	function freeze($userid, $time = 0){
		global $cache;

		$this->usersdb->prepare_query("UPDATE users SET state = 'frozen', frozentime = # WHERE userid = %", ($time ? (time() + $time) : 0), $userid);
		$this->masterdb->prepare_query("UPDATE accounts SET state = # WHERE id = #", ACCOUNT_STATE_FROZEN, $userid);

		$cache->remove("userinfo-$userid");
		$cache->remove("userprefs-$userid");

		return (bool) $this->usersdb->affectedrows();
	}

	function unfreeze($userid){
		global $cache;

		$this->usersdb->prepare_query("UPDATE users SET state = 'active' WHERE userid = %", $userid);
		$this->masterdb->prepare_query("UPDATE accounts SET state = # WHERE id = #", ACCOUNT_STATE_ACTIVE, $userid);

		$cache->remove("userinfo-$userid");
		$cache->remove("userprefs-$userid");

		return (bool) $this->usersdb->affectedrows();
	}

	function makeRandKey(){
		return md5(uniqid(rand(),1));
	}

}

class authentication {

	public $masterdb;
	public $usersdb;
	public $guestbuckets;

	function __construct($masterdb, $usersdb){
		$this->masterdb = $masterdb;
		$this->usersdb = $usersdb;

		$this->guestbuckets = 30; //used to track number of guests online
	}

	function login($username, $password, $cached = false, $lockip = false){
		global $msgs;

		$res = $this->masterdb->prepare_query("SELECT userid FROM usernames WHERE username = ? && live = 'y'", $username);
		$row = $res->fetchrow();

		if(!$row){
			$msgs->addMsg("Bad username or password");
			return false;
		}
		$userid = $row['userid'];

		if(!$this->checkpassword($userid, $password)){
			$this->loginlog($userid, 'badpass');
			$msgs->addMsg("Bad username or password");
			return false;
		}


		$res = $this->usersdb->prepare_query("SELECT state, frozentime FROM users WHERE userid = %", $userid);
		$user = $res->fetchrow();

		switch($user['state']){
			case 'active':
				break;

			case 'new':
				$this->loginlog($userid, 'unactivated');
				$msgs->addMsg("You must activate your account before using it");
				return false;

			case 'frozen':
				if($user['frozentime'] && $user['frozentime'] < time()){
					global $useraccounts;
					$useraccounts->unfreeze($userid);
					break;
				}

				$this->loginlog($userid, 'frozen');
				if($user['frozentime'])
					$msgs->addMsg("Your account is frozen for another " . number_format(($user['frozentime'] - time())/86400,3) . " days. <a class=body href=/contactus.php>Contact an admin if you've got questions.</a>");
				else
					$msgs->addMsg("Your account is frozen. <a class=body href=/contactus.php>Contact an admin if you've got questions.</a>");
				return false;

		//TODO: show a reason for deleted (frozen?) users
		//If the account doesn't exist above, check for non-live users that match the username and password.
		//if the account was recently deleted, give the reason here.
			case 'deleted':
				$this->loginlog($userid, 'deleted');
				$msgs->addMsg("That account is deleted");
				return false;


			default:
				trigger_error("login state ERROR: $userid?", E_USER_ERROR);
		}

		$this->loginlog($userid, 'success');
		$this->createSession($userid, $cached, $lockip);

		return $userid;
	}

	function loginlog($userid, $status){
		$this->usersdb->prepare_query("INSERT INTO loginlog SET userid = %, time = #, ip = #, result = ?", $userid, time(), ip2int(getip()), $status);
	}

	function checkpassword($userid, $password){
		$res = $this->usersdb->prepare_query("SELECT password FROM userpasswords WHERE userid = %", $userid);
		$hash = $res->fetchfield();

		$len = strlen($hash);

		switch($len){
			case 16: //mysql hash
				$match = ($hash == $this->mysql_hash_password($password));

				if($match)
					$this->changePassword($userid, $password);

				return $match;

			case 32: //md5
				return ($hash == $this->hash_password($password));
		}

		return false;
	}

	function changePassword($userid, $password){
		$hash = $this->hash_password($password);

		$this->usersdb->prepare_query("UPDATE userpasswords SET password = ? WHERE userid = %", $hash, $userid);
	}

	// based on http://search.cpan.org/src/IKEBE/Crypt-MySQL-0.02/MySQL.xs
	function mysql_hash_password($pass)
	{
		$res = $this->masterdb->prepare_query("SELECT PASSWORD(?) as pass", $pass);
		return $res->fetchfield();

		$nr = 1345345333;
		$add = 7;
		$nr2 = 0x12345671;

		$tmp = 0;
		for ($i = 0; $i < strlen($pass); $i++)
		{
			if ($pass[$i] == ' ' || $pass[$i] == '\t')
				continue;
			$tmp = ord($pass[$i]);
			$nr ^= ((($nr & 63)+$add)*$tmp)+ ($nr << 8);
			$nr2+=($nr2 << 8) ^ $nr;
			$add+=$tmp;
		}
		$out0 = $nr & 0x7fffffff;
		$out1 = $nr2 & 0x7fffffff;
		return sprintf("%08lx%08lx", $out0, $out1);

	}

	function hash_password($pass){
		$salt = "<removed>"; //random string from random.org

		return md5($salt . $pass);
	}

	function logout($uids){
		global $cache, $config;
		$time = time();

		$this->usersdb->prepare_query("UPDATE useractivetime SET online = 'n' WHERE userid IN (%) && online = 'y'", $uids);
		$this->usersdb->prepare_query("UPDATE users SET online = 'n', timeonline = timeonline + (# - activetime), activetime = # WHERE userid IN (%) && online = 'y'", $time, $time, $uids);
		$this->usersdb->prepare_query("UPDATE usersearch SET active = 1 WHERE userid IN (%) && active = 2", $uids);

		if(is_array($uids))
			foreach($uids as $uid)
				$cache->put("useractive-$uid", $time - $config['activetimeout'], 86400*7);
		else
			$cache->put("useractive-$uids", $time - $config['activetimeout'], 86400*7);
	}

	function loginRedirect(){
		header("location: /login.php?referer=" . urlencode($_SERVER['REQUEST_URI']));
		exit;
	}

	function statsHeaders($userData){ //for use with logstats.php as a log parser
		header("X-LIGHTTPD-usertype: " . ($userData['loggedIn'] ? ($userData['premium'] ? 'plus' : 'user') : 'anon'));

		if($userData['loggedIn']){
			header("X-LIGHTTPD-userid: $userData[userid]");
			header("X-LIGHTTPD-age: $userData[age]");
			header("X-LIGHTTPD-sex: $userData[sex]");
			header("X-LIGHTTPD-loc: $userData[loc]");
		}
	}

	function getGuestCount(){
		global $cache, $config;

		$start = floor(time()/($config['activetimeout']/$this->guestbuckets));
		$ids = range($start - $this->guestbuckets + 1, $start);

		$counts = $cache->get_multi($ids, 'guest-count-');

		return array_sum($counts);
	}

	function getUserCount(){
		$res = $this->usersdb->query("SELECT count(*) as count FROM usersearch WHERE active = 2"); //if load balanced, may return multiple rows

		$count = 0;
		while($line = $res->fetchrow())
			$count += $line['count'];

		return $count;
	}

	function getUserCountSex(){
		$res = $this->usersdb->query("SELECT sex, count(*) as count FROM usersearch WHERE active = 2 GROUP BY sex"); //if load balanced, may return multiple rows

		$count = array('Male' => 0, 'Female' => 0);
		while($line = $res->fetchrow())
				$count[$line['sex']] += $v;

		return $count;
	}

	function auth($userid, $key, $kill = true, $simple = false){
		global $config, $cookiedomain, $cache, $debuginfousers, $msgs;

		settype($userid, 'int');
		settype($key, 'string');

		$REQUEST_URI = getSERVERval('REQUEST_URI');

		$time = time();
		$ip = ip2int(getip());

		$userData['loggedIn'] = false;
		$userData['halfLoggedIn'] = false;
		$userData['timeoffset']=$config['timezone'];
		$userData['limitads'] = false;
		$userData['premium'] = false;
		$userData['debug'] = false;

	//new user or logged out user
	//don't bother keeping anything in db, as nothing useful would be stored anyway
	//use memcache to keep the state and stats
		if(empty($userid) || empty($key) || !ereg('^[a-z0-9]{32}$', $key) ){
			if($kill)
				$this->loginRedirect();

			$session = $cache->get("anon-session-$ip");

			if(!$session){
				$cache->put("anon-session-$ip", 1, $config['activetimeout']);
				if(!$cache->incr("guest-count-" . floor($time/($config['activetimeout']/$this->guestbuckets))))
					$cache->put("guest-count-" . floor($time/($config['activetimeout']/$this->guestbuckets)), 1, $config['activetimeout']);
			}

			$userData['userid'] = 0 - abs($ip);

			return $userData;
		}

	//potentially logged in user
		$session = $cache->get("session-$userid-$key");

		$sessionmemcacheput = false;

		if(!$session){
			$res = $this->usersdb->prepare_query("SELECT activetime, cachedlogin, ip, lockip, jstimezone FROM sessions WHERE userid = % && sessionid = ?", $userid, $key);
			$session = $res->fetchrow();

			if($session){
				$session['dbtime'] = $session['activetime'];
				$sessionmemcacheput = true;
			}
		}

	//bad session
		if(!$session){
			$this->destroySession($userid, $key);
			$userData['userid'] = 0 - abs($ip);

			if($kill)
				$this->loginRedirect();

			return $userData;
		}

	//expired or bad session
		if(	($session['cachedlogin'] == 'n' && $session['activetime'] < ($time-$config['sessiontimeout'])) || //timed out?
			($session['lockip'] == 'y' && ($ip & 0xFFFFFF00) != ($session['ip'] & 0xFFFFFF00))){ //same subnet?
			$this->destroySession($userid, $key);

			$userData['userid'] = 0 - abs($ip);

			if($kill)
				$this->loginRedirect();

			return $userData;
		}

	//logged in
		if($session['ip'] != $ip || !isset($session['dbtime']) || $session['dbtime'] < ($time-$config['activetimeout'])){
			$this->usersdb->prepare_query("UPDATE sessions SET activetime = #, ip = # WHERE userid = % && sessionid = ?", $time, $ip, $userid, $key);
			$session['dbtime'] = $time;
			$session['ip'] = $ip;
			$sessionmemcacheput = true;
		}

		//update memcached more often than the db
		if($session['activetime'] < $time - 120 || $sessionmemcacheput){
			$session['activetime'] = $time;
			$session['username'] = getUserName($userid);
			$cache->put("session-$userid-$key", $session, $config['sessiontimeout']);
		}




	//simple version where only the prefs are cached so that simple pages (ie pages without the messages/comments) can pull the prefs from the cache


		if($simple)
			$prefs = $cache->get("userprefs-$userid");
		else
			$prefs = false;

		if($prefs === false){
			$res = $this->usersdb->prepare_query("SELECT * FROM users WHERE userid = %", $userid);
			$prefs = $res->fetchrow();

			if(!$prefs)
				die("That account doesn't exist");

			$prefs['username'] = $username = $session['username']; //getUserName($userid);

			$temp = $prefs;
			$temp['online'] = 'y';

			$cache->put("userprefs-$userid", $temp, $config['activetimeout']);
			if($prefs['state'] == 'active')
				$cache->put("useractive-$userid", time(), 86400*7);
		}

		$activated = false;
		switch($prefs['state']){
			case "active":  $activated = true; break;
			case "new":     break;//die("You have to activate your account?"); //how do you get here if it's in state=new?
			case "frozen":
				$this->destroySession($userid, $key);
				die("Your account is frozen. If it was a timed freeze, try logging in again");
			case "deleted": die("Your account is deleted");
			default:        die("Error: unknown account state for account $userid");
		}

	//update online status if needed
		if($prefs['online'] == 'n' || $prefs['activetime'] < $time - 1800){
			$this->usersdb->prepare_query("UPDATE users SET online = 'y', activetime = #, ip = # WHERE userid = %", $time, $ip, $userid);
			$this->usersdb->prepare_query("UPDATE usersearch SET active = 2 WHERE userid = %", $userid);
		}

	//get interests
		$interests = $cache->get("userinterests-$userid");

		if($interests === false){
			$res = $this->usersdb->prepare_query("SELECT interestid FROM userinterests WHERE userid = %", $userid);

			$interests = array();
			while($line = $res->fetchrow())
				$interests[] = $line['interestid'];

			$interests = implode(',', $interests); //could be blank

			$cache->put("userinterests-$userid", $interests, 86400);
		}

	//set $userData with default of prefs
		$userData = $prefs;

	//set the more complex stuff that isn't in prefs, or that needs manipulation
		$userData['loggedIn'] = $activated? true : false;
		$userData['halfLoggedIn'] = true;
		$userData['sessionkey'] = $key; //logout on password change
		$userData['sessionlockip'] = ($session['lockip']=='y'); //disallow admin powers unless locked ip
		$userData['interests'] = $interests;   //for banners
		$userData['premium'] = $userData['loggedIn'] && ($prefs['premiumexpiry'] > $time);
		$userData['limitads'] = ($prefs['limitads'] == 'y' && $userData['premium']);
		$userData['debug'] = in_array($userid, $debuginfousers);
		$userData['trustjstimezone'] = ($prefs['trustjstimezone'] == 'y' ? true : false);

		// fix up timezone info, first from the session table if set
		if(isset($session['jstimezone']))
			$userData['jstimezone'] = $session['jstimezone'];

		// now fix it up again if we've got one in from the user and it differs
		if($newtz = $this->checktimezone()){
			$userData['jstimezone'] = $newtz;
			// update the session table to reflect the new one.
			$this->usersdb->prepare_query("UPDATE sessions SET jstimezone = # WHERE userid = % && sessionid = ?", $newtz, $userid, $key);
			$cache->remove("session-$userid-$key");
		}

		$this->statsHeaders($userData);

		if ($userData['halfLoggedIn'] && !$userData['loggedIn'])
			$msgs->addMsg("Your account has not been activated yet.<br>You can use some features of the site until then, but you will not be able to log back in until you have activated.");

		return $userData;
	}


	function createSession($userid, $cachedlogin=false, $lockip=false){
		global $cookiedomain, $config;

		$ip = ip2int(getip());
		$time = time();
		$key = $this->makeRandkey();

		$cachedlogin = ($cachedlogin ? 'y' : 'n');
		$lockip = ($lockip ? 'y' : 'n');

		if ($newtz = $this->checktimezone())
			$this->usersdb->prepare_query("INSERT INTO sessions SET ip = #, userid = %, activetime = #, sessionid = ?, cachedlogin = ?, lockip = ?, jstimezone = #", $ip, $userid, $time, $key, $cachedlogin, $lockip, $newtz);
		else
			$this->usersdb->prepare_query("INSERT INTO sessions SET ip = #, userid = %, activetime = #, sessionid = ?, cachedlogin = ?, lockip = ?", $ip, $userid, $time, $key, $cachedlogin, $lockip);

		$expire = ($cachedlogin == 'y' ? $time + $config['longsessiontimeout'] : 0);  //cache for 1 month

	//use a set cookie wrapper, so on php 5.2+ the http only flag works, but doesn't fail on earlier versions.
		set_cookie('userid', $userid, $expire, '/', $cookiedomain, false, true);
		set_cookie('key',    $key,    $expire, '/', $cookiedomain, false, true);

		return $userid;
	}

	function destroySession($userid, $key){
		global $cookiedomain, $cache;

		$this->usersdb->prepare_query("DELETE FROM sessions WHERE userid = % && sessionid = ?", $userid, $key);

		$cache->remove("session-$userid-$key");

		$expire = time()-10000000;

		set_cookie("userid", $userid, $expire, '/', $cookiedomain, false, true);
		set_cookie("key",    $key,    $expire, '/', $cookiedomain, false, true);
	}

	function checktimezone()
	{
		global $userData;
		$jstimezone = getPOSTval('js_clienttimezone', 'integer');
		if (!empty($jstimezone))
		{
			// convert to sane format (minutes right of GMT. ie. -360 for MDT)
			$timeoffset = -$jstimezone;
			if (!isset($userData['jstimezone']) || $userData['jstimezone'] != $timeoffset)
				return $timeoffset;
		}
		return null;
	}

	function makeRandKey(){
		return md5(uniqid(rand(),1));
	}

}

