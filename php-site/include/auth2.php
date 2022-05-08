<?


class auth {

	var $db;

	function auth( & $sessdb, & $userdb ){
		$this->db = & $db;
	}

	function login($username, $password, $cached = false, $lockip = false){				//call destroy session before logging in, or the guest session will persist for a while
		global $db, $fastdb, $msgs;

		if(empty($username) || empty($password) || trim($username)=="" || trim($password)==""){
			$msgs->addMsg("Bad username or password");
			return false;
		}

		$db->prepare_query("SELECT userid, username, (password = PASSWORD(?)) as passmatch, frozen, activated FROM users WHERE username = ?", $password, $username);

		if(!$db->numrows()){
			$msgs->addMsg("Bad username or password");
			return false;
		}

		$line = $db->fetchrow();

		$status = 'success';

		if($line['frozen'] == 'y'){
			$msgs->addMsg("Your account is frozen");
			$status = 'frozen';
		}
		if(!$line['passmatch']){
			$msgs->addMsg("Bad username or password");
			$status = 'badpass';
		}
		if($line['activated'] == 'n'){
			$msgs->addMsg("Your account isn't activated");
			$status = 'unactivated';
		}

		$fastdb->prepare_query("INSERT INTO loginlog SET userid = ?, time = ?, ip = ?, result = ?", $line['userid'], time(), ip2int(getip()), $status);

		if($status == 'success'){
			createSession($line['userid'], $cached, $lockip);

			return $line['userid'];
		}else
			return false;
	}

	function logout($uids){
		global $db, $fastdb;
		$time = time();
		$db->prepare_query("UPDATE users SET online = 'n', timeonline = timeonline + (? - activetime), activetime = ? WHERE userid IN (?) && online = 'y'", $time, $time, $uids);
		$fastdb->prepare_query("UPDATE useractivetime SET online = 'n' WHERE userid IN (?) && online = 'y'", $uids);
	}

	function auth($userid, $key, $kill=true, $userprefs = array()){
		global $config, $cookiedomain, $db, $fastdb, $REQUEST_URI, $cache;

	//	echo "start auth! userid: '$userid', key: '$key'<br>";

		$time = time();
		$ip = ip2int(getip());

		$userData['loggedIn'] = false;
		$userData['timeoffset']=$config['timezone'];
		$userData['limitads'] = false;

	//new user, no old session to destroy before creating a new one
	//reuse an old one if possible. Useful for people who won't accept cookies, so the count doesn't just keep going up
		if(empty($userid) || !settype($userid, 'integer') || empty($key) || !settype($key, 'string') || !ereg('^[a-z0-9]{32}$', $key) ){
			if($kill){
				header("location: /login.php?referer=" . urlencode($REQUEST_URI));
				die;
			}

			$fastdb->prepare_query("SELECT id,sessionid FROM sessions WHERE ip = ? && ISNULL(userid)", $ip);
			if($fastdb->numrows()==0){
				createSession();
			}else{
				$line = $fastdb->fetchrow();

				setCookie("userid",0-$line['id'],0,'/',$cookiedomain);
				setCookie("key",$line['sessionid'],0,'/',$cookiedomain);
			}

			return $userData;
		}


	//old anon session
		if($userid<0){
			if($kill){
				header("location: /login.php?referer=" . urlencode($REQUEST_URI));
				exit;
			}

			$id = abs($userid);

			$session = $cache->get(array($userid, "session-$userid-$key"));

			if(!$session){
				$fastdb->prepare_query("SELECT sessionid FROM sessions WHERE id = ? && sessionid = ?", $id, $key);

				if($fastdb->numrows()){
					$session = $fastdb->fetchfield();

					$cache->put(array($userid, "session-$userid-$key"), $session, 3600);
				}
			}

			if($session){
				$fastdb->prepare_query("UPDATE sessions SET activetime = ?, ip = ? WHERE id = ?", $time, $ip, $id);
			}else{
				destroySession($userid,$key);
				createSession();
			}
			return $userData;
		}

	//logged in user
		$session = $cache->get(array($userid, "session-$userid-$key"));

		if(!$session){
			$fastdb->prepare_query("SELECT id, activetime, cachedlogin, ip, lockip FROM sessions WHERE userid = ? && sessionid = ?", $userid, $key);

			if($fastdb->numrows()){
				$session = $fastdb->fetchrow();
				$session['dbtime'] = $session['activetime'];
			}
		}

	//bad session
		if(!$session){
			destroySession($userid,$key);
			createSession();
			if($kill){
				header("location: /login.php?referer=" . urlencode($REQUEST_URI));
				die;
			}
			return $userData;
		}

		if(	($session['cachedlogin']=='n' && $session['activetime'] < ($time-$config['maxAwayTime'])) || //timed out?
			($session['lockip']=='y' && (ip2int(getip()) & 0xFFFFFF00) != ($session['ip'] & 0xFFFFFF00))){ //same subnet?
			destroySession($userid,$key);
			createSession();
			if($kill){
				header("location: /login.php?referer=" . urlencode($REQUEST_URI));
				die;
			}
			return $userData;
		}

		if($session['ip'] != $ip || !isset($session['dbtime']) || $session['dbtime'] < ($time-$config['maxAwayTime'])){
			$fastdb->prepare_query("UPDATE sessions SET activetime = ?, ip = ? WHERE id = ?", $time, $ip, $session['id']);
			$session['dbtime'] = $time;
		}

		$session['activetime'] = $time;
		$session['ip'] = $ip;
		$cache->put(array($userid, "session-$userid-$key"), $session, 3600);

		$fastdb->prepare_query("UPDATE userhitlog SET activetime = ?, hits = hits+1 WHERE userid = ? && ip = ?", $time, $userid, $ip);
		if($fastdb->affectedrows()==0)
			$fastdb->prepare_query("INSERT IGNORE INTO userhitlog SET activetime = ?, hits = hits+1, userid = ?, ip = ?", $time, $userid, $ip);


		$fastdb->prepare_query("UPDATE useractivetime SET activetime = ?, hits = hits+1, ip = ?, online = 'y' WHERE userid = ?", $time, $ip, $userid);
		if($fastdb->affectedrows()==0)
			$fastdb->prepare_query("INSERT IGNORE INTO useractivetime SET activetime = ?, hits = 1, ip = ?, online = 'y', userid = ?", $time, $ip, $userid);

	/*
	//not cached due to updates. Won't show new messages, etc.
		$line = $cache->get(array($userid, "UserPrefs-$userid"));

		if(!$line){
	*/
			$cols = array("username", "frozen", "online", "sex", "age", "loc", "premiumexpiry",
						"posts", "newmsgs", "newcomments",
						"showrightblocks", "timeoffset", "enablecomments", "defaultminage", "defaultmaxage", "defaultsex", "skin", "limitads");

			$cols = array_merge($cols, $userprefs);

			$db->prepare_query("SELECT " . implode(", ", $cols) . " FROM users WHERE userid = ?", $userid);
			$line = $db->fetchrow();
	/*
			$temp = $line;
			$temp['online'] = 'y';

			$cache->put(array($userid, "UserPrefs-$userid"), $temp, $config['maxAwayTime']);
		}
	*/
		if($line['frozen'] == 'y')
			die("Your account is frozen");

		if($line['online'] == 'n')
			$db->prepare_query("UPDATE users SET online = 'y', activetime = ?, ip = ? WHERE userid = ?", $time, $ip, $userid);

		$userData['loggedIn']=true;
		$userData['userid']=$userid;
		$userData['username']=$line['username'];
		$userData['sessionkey'] = $key; //logout on password change
		$userData['sessionlockip'] = ($session['lockip']=='y'); //disallow admin powers unless locked ip
		$userData['sex']=$line['sex']; //for banners
		$userData['age']=$line['age']; //for banners, ignores
		$userData['loc']=$line['loc']; //for banners
		$userData['showrightblocks']=$line['showrightblocks'];
		$userData['posts']=$line['posts']; //show subscriptions block?
		$userData['newmsgs']=$line['newmsgs'];
		$userData['enablecomments']=$line['enablecomments'];
		$userData['newcomments']=$line['newcomments'];
		$userData['timeoffset']=$line['timeoffset'];
		$userData['premium'] = ($line['premiumexpiry'] > $time);
		$userData['defaultminage']=$line['defaultminage']; //search block
		$userData['defaultmaxage']=$line['defaultmaxage'];
		$userData['defaultsex']=$line['defaultsex'];
		$userData['skin']=$line['skin'];
		$userData['limitads']=($line['limitads'] == 'y' && $userData['premium']);

		foreach($userprefs as $pref)
			$userData[$pref] = $line[$pref];

	    return $userData;
	}


	function createSession($userid=0, $cachedlogin=false, $lockip=false){
		global $cookiedomain,$fastdb;
		$ip = ip2int(getip());

		$time = time();

		$key = makekey();

		$cachedlogin = ($cachedlogin ? 'y' : 'n');
		$lockip = ($lockip ? 'y' : 'n');

		$fastdb->prepare_query("INSERT INTO sessions SET ip = ?, userid = " . (!$userid ? "NULL" : $fastdb->escape($userid) ) . ", activetime = ?, sessionid = ?, cachedlogin = ?, lockip = ?", $ip, $time, $key, $cachedlogin, $lockip);

		if($cachedlogin == 'y')
			$expire = $time + 86400*31; //cache for 1 month
		else
			$expire = 0;

		if($userid == 0){
			$id = $fastdb->insertid();
			setCookie("userid", 0 - $id, $expire, '/', $cookiedomain);
		}else{
			setCookie("userid", $userid, $expire, '/', $cookiedomain);
		}
		setCookie("key",$key,$expire,'/',$cookiedomain);
	}

	function destroySession($userid,$key){
		global $cookiedomain,$fastdb, $cache;

		if($userid<0){
			$fastdb->prepare_query("DELETE FROM sessions WHERE id = ? && sessionid = ?", abs($userid), $key);
		}else{
			$fastdb->prepare_query("DELETE FROM sessions WHERE userid = ? && sessionid = ?", $userid, $key);
		}

		$cache->remove(array($userid, "session-$userid-$key"));

		setCookie("userid",$userid,time()-10000000,'/',$cookiedomain);
		setCookie("key",$key,time()-10000000,'/',$cookiedomain);
	}



}
