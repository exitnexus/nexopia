<?

// function checks for a form input that indicates the timezone as the user's browser sees it
// and if there is one, and it differs from the current session data, returns that passed to the user.
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

function login($username, $password, $cached = false, $lockip = false){				//call destroy session before logging in, or the guest session will persist for a while
	global $msgs, $usersdb;

	if(empty($username) || empty($password) || trim($username)=="" || trim($password)==""){
		$msgs->addMsg("Bad username or password");
		return false;
	}

	$userid = getUserID($username);

	if(!$userid){
		$msgs->addMsg("Bad username or password");
		return false;
	}

	$res = $usersdb->prepare_query("SELECT userid, (password = ?) as passmatch, frozen, activated FROM users WHERE userid = #", mysql_hash_password($password), $userid);

	$line = $res->fetchrow();

	if(!$line){
		$msgs->addMsg("Bad username or password");
		return false;
	}

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

	$ip = ip2int(getip());

	$usersdb->prepare_query("INSERT INTO loginlog SET userid = %, time = #, ip = #, result = ?", $line['userid'], time(), $ip, $status);

	if($status == 'success'){
		createSession($line['userid'], $cached, $lockip);

		return $line['userid'];
	}else
		return false;
}

function logout($uids){
	global $usersdb, $fastdb, $cache, $config;
	$time = time();

	$fastdb->prepare_query("UPDATE useractivetime SET online = 'n' WHERE userid IN (%) && online = 'y'", $uids);

	$usersdb->prepare_query("UPDATE users SET online = 'n', timeonline = timeonline + (# - activetime), activetime = # WHERE userid IN (#) && online = 'y'", $time, $time, $uids);

	$usersdb->prepare_query("UPDATE usersearch SET active = 1 WHERE userid IN (#) && active = 2", $uids);

	if(is_array($uids))
		foreach($uids as $uid)
			$cache->put("useractive-$uid", $time - $config['friendAwayTime'], 86400*7);
	else
		$cache->put("useractive-$uids", $time - $config['friendAwayTime'], 86400*7);
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

function auth($userid, $key, $kill = true, $simple = false, $userprefs = array()){
	global $config, $cookiedomain, $usersdb, $fastdb, $sessiondb, $cache, $debuginfousers;

//	echo "start auth! userid: '$userid', key: '$key'<br>";

	$REQUEST_URI = getSERVERval('REQUEST_URI');

	$time = time();
	$ip = ip2int(getip());

	$userData['loggedIn'] = false;
	$userData['timeoffset']=$config['timezone'];
	$userData['limitads'] = false;
	$userData['premium'] = false;
	$userData['debug'] = false;

//new user, no old session to destroy before creating a new one
//reuse an old one if possible. Useful for people who won't accept cookies, so the count doesn't just keep going up
	if(empty($userid) || !settype($userid, 'integer') || empty($key) || !settype($key, 'string') || !ereg('^[a-z0-9]{32}$', $key) ){
		if($kill)
			loginRedirect();

		$sessiondb->squery(0, $sessiondb->prepare("SELECT id, sessionid FROM sessions WHERE ip = # && ISNULL(userid)", $ip));
		$line = $sessiondb->fetchrow();

		if(!$line){
			$userData['userid'] = createSession(); //sets the cookies
		}else{
			$userData['userid'] = 0 - $line['id'];

			setCookie("userid", $userData['userid'], 0, '/', $cookiedomain);
			setCookie("key", $line['sessionid'], 0, '/', $cookiedomain);
		}

		return $userData;
	}


//old anon session
	if($userid < 0){
		if($kill)
			loginRedirect();

		$id = abs($userid);

		$session = $cache->get("session-$userid-$key");

		if(!$session){
			$sessiondb->squery(0, $sessiondb->prepare("SELECT sessionid FROM sessions WHERE id = # && sessionid = ?", $id, $key));
			$sessionrow = $sessiondb->fetchrow();

			if($sessionrow){
				$session = $sessionrow['sessionid'];

				$cache->put("session-$userid-$key", $session, 3600);
			}
		}

		if($session){
			$sessiondb->squery(0, $sessiondb->prepare("UPDATE sessions SET activetime = #, ip = # WHERE id = #", $time, $ip, $id));
			$userData['userid'] = $userid;
		}else{
			destroySession($userid, $key);
			$userData['userid'] = createSession();
		}
		return $userData;
	}

//logged in user
	$session = $cache->get("session-$userid-$key");

	$sessionmemcacheput = false;

	if(!$session){
		$sessiondb->prepare_query("SELECT id, activetime, cachedlogin, ip, lockip, jstimezone FROM sessions WHERE userid = % && sessionid = ?", $userid, $key);
		$session = $sessiondb->fetchrow();

		if($session){
			$session['dbtime'] = $session['activetime'];
			$sessionmemcacheput = true;
		}
	}

	//bad session
	if(!$session){
		destroySession($userid,$key);
		$userData['userid'] = createSession();
		if($kill)
			loginRedirect();

		return $userData;
	}

	if(	($session['cachedlogin']=='n' && $session['activetime'] < ($time-$config['maxAwayTime'])) || //timed out?
		($session['lockip']=='y' && (ip2int(getip()) & 0xFFFFFF00) != ($session['ip'] & 0xFFFFFF00))){ //same subnet?
		destroySession($userid,$key);
		$userData['userid'] = createSession();
		if($kill)
			loginRedirect();

		return $userData;
	}

//logged in
	if($session['ip'] != $ip || !isset($session['dbtime']) || $session['dbtime'] < ($time-$config['friendAwayTime'])){
		$sessiondb->squery($userid, $sessiondb->prepare("UPDATE sessions SET activetime = #, ip = # WHERE id = #", $time, $ip, $session['id']));
		$session['dbtime'] = $time;
	}

	if($session['activetime'] < $time - 120 || $session['ip'] != $ip || $sessionmemcacheput){
		$session['activetime'] = $time;
		$session['ip'] = $ip;
		$cache->put("session-$userid-$key", $session, 3600);
	}


/*
//attempt at full caching, has bugs, likely caused by the cache not being invalidated at all places where it should be


//not cached due to updates. Won't show new messages, etc.
	$prefs = $cache->get("userprefs-$userid");

	if($prefs){
		$prefs['newmsgs'] = $cache->get("newmsgs-$userid");
		$prefs['newcomments'] = $cache->get("newcomments-$userid");

		if($prefs['newmsgs'] === false || $prefs['newcomments'] === false){

			$db->prepare_query("SELECT newmsgs, newcomments FROM users WHERE userid = #", $userid);
			$row = $db->fetchrow();

			if($prefs['newmsgs'] === false)
				$cache->put("newmsgs-$userid", $row['newmsgs'], $config['maxAwayTime']);
			if($prefs['newcomments'] === false)
				$cache->put("newcomments-$userid", $row['newcomments'], $config['maxAwayTime']);
		}
	}else{
		$cols = array("username", "frozen", "online", "sex", "age", "loc", "premiumexpiry", 'email',
					"posts", "newmsgs", "newcomments",
					"showrightblocks", "timeoffset", "enablecomments", "defaultminage", "defaultmaxage", "defaultsex", "skin", "limitads",
					'replyjump','autosubscribe', 'forumsort', 'forumpostsperpage', 'showsigs', 'friendslistthumbs', 'enablecomments', 'journalentries' ,'gallery', 'hideprofile'
					);

//		$cols = array_merge($cols, $userprefs);

		$db->prepare_query("SELECT " . implode(", ", $cols) . " FROM users WHERE userid = #", $userid);
		$prefs = $db->fetchrow();

		if(!$prefs)
			die("That account doesn't exist");

		$temp = $prefs;
		$temp['online'] = 'y';

		$cache->put("userprefs-$userid", $temp, $config['maxAwayTime']);
		$cache->put("newmsgs-$userid", $prefs['newmsgs'], $config['maxAwayTime']);
		$cache->put("newcomments-$userid", $prefs['newcomments'], $config['maxAwayTime']);
	}
*/


//simple version where only the prefs are cached so that simple pages (ie pages without the messages/comments) can pull the prefs from the cache


	if($simple)
		$prefs = $cache->get("userprefs-$userid");
	else
		$prefs = false;

	if($prefs === false){
		$cols = array("username", "frozen", "online", "sex", "age", "loc", "premiumexpiry", 'email', 'activetime',
				"posts", "newmsgs", "newcomments", 'trustjstimezone',
				"showrightblocks", "timeoffset", "enablecomments", "defaultminage", "defaultmaxage", "defaultsex", "defaultloc", "skin", "limitads", 'onlyfriends', 'ignorebyage',
				'replyjump','onlysubscribedforums','orderforumsby','autosubscribe', 'forumsort', 'forumpostsperpage', 'showsigs', 'friendslistthumbs','recentvisitlistthumbs',
				'enablecomments', 'gallery', 'hideprofile', 'forumjumplastpost', 'firstpic', 'signpic', 'blogskin', 'commentskin', 'friendskin', 'galleryskin', 'parse_bbcode, bbcode_editor'
				);


		$query = $usersdb->prepare("SELECT " . implode(", ", $cols) . " FROM users WHERE userid = #", $userid);

		$hour = gmdate("H");
		if(isset($usersdb->backupdb) && ($hour < 9 || $hour > 14)){ //don't use between 3am - 8am
			$usersdb->backupdb->query($query);
			$prefs = $usersdb->backupdb->fetchrow();
			$usersdb->backupdb->close();
		}else{
			$usersdb->query($query);
			$prefs = $usersdb->fetchrow();
		}


		if(!$prefs)
			die("That account doesn't exist");


		$temp = $prefs;
		$temp['online'] = 'y';

		$cache->put("userprefs-$userid", $temp, $config['maxAwayTime']);
		if($prefs['frozen'] == 'n')
			$cache->put("useractive-$userid", time(), 86400*7);
	}

	if($prefs['frozen'] == 'y')
		die("Your account is frozen");

	if($prefs['online'] == 'n' || $prefs['activetime'] < $time - 3600){
		$usersdb->prepare_query("UPDATE users SET online = 'y', activetime = #, ip = # WHERE userid = #", $time, $ip, $userid);
		$usersdb->prepare_query("UPDATE usersearch SET active = 2 WHERE userid = #", $userid);
	}

	$interests = $cache->get("userinterests-$userid");

	if($interests === false){
		$usersdb->prepare_query("SELECT interestid FROM userinterests WHERE userid = #", $userid);

		$interests = array();
		while($line = $usersdb->fetchrow())
			$interests[] = $line['interestid'];

		$interests = implode(',', $interests); //could be blank

		$cache->put("userinterests-$userid", $interests, 86400);
	}

	$userData['loggedIn']=true;
	$userData['userid']=$userid;
	$userData['username']=$prefs['username'];
	$userData['sessionkey'] = $key; //logout on password change
	$userData['sessionlockip'] = ($session['lockip']=='y'); //disallow admin powers unless locked ip
	$userData['sex']=$prefs['sex']; //for banners
	$userData['age']=$prefs['age']; //for banners, ignores
	$userData['loc']=$prefs['loc']; //for banners
	$userData['interests']=$interests;   //for banners
	$userData['showrightblocks']=$prefs['showrightblocks'];
	$userData['posts']=$prefs['posts']; //show subscriptions block?
	$userData['newmsgs']=$prefs['newmsgs'];
	$userData['enablecomments']=$prefs['enablecomments'];
	$userData['newcomments']=$prefs['newcomments'];
	$userData['timeoffset']=$prefs['timeoffset'];
	$userData['trustjstimezone']=$prefs['trustjstimezone'];
	$userData['premium'] = ($prefs['premiumexpiry'] > $time);
	$userData['premiumexpiry'] = $prefs['premiumexpiry'];
	$userData['defaultminage']=$prefs['defaultminage']; //search block
	$userData['defaultmaxage']=$prefs['defaultmaxage'];
	$userData['defaultsex']=$prefs['defaultsex'];
	$userData['defaultloc'] = $prefs['defaultloc'];
	$userData['skin']=$prefs['skin'];
	$userData['limitads']=($prefs['limitads'] == 'y' && $userData['premium']);
	$userData['ignorebyage']=$prefs['ignorebyage'];
	$userData['onlyfriends']=$prefs['onlyfriends'];
	$userData['forumpostsperpage']=$prefs['forumpostsperpage'];
	$userData['gallery']=$prefs['gallery'];
	$userData['debug'] = in_array($userid, $debuginfousers);
	$userData['email']=$prefs['email'];
	$userData['autosubscribe']=$prefs['autosubscribe'];
	$userData['signpic']=$prefs['signpic'];
	$userData['firstpic']=$prefs['firstpic'];
	$userData['bbcode_editor'] = $prefs['bbcode_editor']== 'y'? true: false;
	$userData['parse_bbcode']= $prefs['parse_bbcode']== 'y'? true: false;
	$userData['recentvisitlistthumbs'] = $prefs['recentvisitlistthumbs'];

	// fix up timezone info, first from the session table if set
	if (isset($session['jstimezone']))
	{
		$userData['jstimezone'] = $session['jstimezone'];
	}
	// now fix it up again if we've got one in from the user and it differs
	if ($newtz = checktimezone())
	{
		$userData['jstimezone'] = $newtz;
		// update the session table to reflect the new one.
		$sessiondb->squery($userid, $sessiondb->prepare("UPDATE sessions SET jstimezone = # WHERE id = #", $newtz, $session['id']));
		$cache->remove("session-$userid-$key");
	}

	foreach($userprefs as $pref)
		$userData[$pref] = $prefs[$pref];

	return $userData;
}


function createSession($userid=0, $cachedlogin=false, $lockip=false){
	global $cookiedomain, $sessiondb;
	$ip = ip2int(getip());

	$time = time();

	$key = makeRandkey();

	$cachedlogin = ($cachedlogin ? 'y' : 'n');
	$lockip = ($lockip ? 'y' : 'n');

	if ($newtz = checktimezone())
		$sessiondb->squery($userid, $sessiondb->prepare("INSERT INTO sessions SET ip = #, userid = #, activetime = #, sessionid = ?, cachedlogin = ?, lockip = ?, jstimezone = #", $ip, (!$userid ? NULL : $userid ), $time, $key, $cachedlogin, $lockip, $newtz));
	else
		$sessiondb->squery($userid, $sessiondb->prepare("INSERT INTO sessions SET ip = #, userid = #, activetime = #, sessionid = ?, cachedlogin = ?, lockip = ?", $ip, (!$userid ? NULL : $userid ), $time, $key, $cachedlogin, $lockip));

	$expire = ($cachedlogin == 'y' ? $time + 86400*31 : 0);  //cache for 1 month

	if($userid == 0){
		$userid = 0 - $sessiondb->insertid();
		setCookie("userid", $userid, $expire, '/', $cookiedomain);
	}else{
		setCookie("userid", $userid, $expire, '/', $cookiedomain);
	}
	setCookie("key", $key, $expire, '/', $cookiedomain);

	return $userid;
}

function destroySession($userid,$key){
	global $cookiedomain, $cache, $sessiondb;

	if($userid<0){
		$sessiondb->squery(0, $sessiondb->prepare("DELETE FROM sessions WHERE id = # && sessionid = ?", abs($userid), $key));
	}else{
		$sessiondb->squery($userid, $sessiondb->prepare("DELETE FROM sessions WHERE userid = # && sessionid = ?", $userid, $key));
	}

	$cache->remove("session-$userid-$key");

	setCookie("userid",$userid,time()-10000000,'/',$cookiedomain);
	setCookie("key",$key,time()-10000000,'/',$cookiedomain);
}

function newAccount($data){
	global $wwwdomain, $emaildomain, $msgs, $config, $db, $masterdb, $usersdb, $configdb;
	$error = false;

	$ip = ip2int(getip());

	if(isBanned($ip,'ip')){
		$error=true;
		$msgs->addMsg("Your IP has been banned due to abuse. Please email <a class=msg href=\"mailto:info@$emaildomain\">info@$emaildomain</a> if you have questions.");
	}

	if(!userNameLegal($data['username']))
		$error=true;

	if(!isset($data['password']) || strlen($data['password'])>32 || strlen($data['password'])<4 || $data['password']!=$data['password2']){
		$msgs->addMsg("Invalid password or passwords don't match");
		$error=true;
	}
	if(!isset($data['email']) || strlen($data['email'])>255 || !isValidEmail($data['email']) || isEmailInUse($data['email'])){
		$msgs->addMsg("Invalid email address");
		$error=true;
	}

	if(isBanned($data['email'],'email')){
		$error=true;
		$msgs->addMsg("Your email has been banned due to abuse. Please email <a class=msg href=\"mailto:info@$emaildomain\">info@$emaildomain</a> if you have questions.");
	}

	if(!isset($data['month']) || $data['month']<=0 || $data['month']>12 || !isset($data['day']) || $data['day']<=0 || $data['day']>31 || !isset($data['year']) || !checkdate($data['month'],$data['day'],$data['year'])){
		$msgs->addMsg("Invalid date of birth");
		$error=true;
		$age = 0;
	}else{
		$dob = my_gmmktime(0,0,0,$data['month'],$data['day'],$data['year']);
	 	$age = getAge($dob);
		if($age < $config['minAge'] || $age > $config['maxAge']){
			$msgs->addMsg("Invalid date of birth");
			$error=true;
		}
	}
	if(!isset($data['sex']) || !($data['sex']=="Male" || $data['sex']=="Female")){
		$msgs->addMsg("Please specify your sex");
		$error = true;
	}

	$locations = new category( $configdb, "locs");

	if(!isset($data['loc']) || !$locations->isValidCat($data['loc'])){
		$msgs->addMsg("Please specify your location");
		$error=true;
	}

	if(!(	isset($data['agreelimit']) &&
			isset($data['agreeterms']) &&
			(	( isset($data['agree18']) && !isset($data['agree14']) && !isset($data['agree14guardian']) && $age >= 18) ||
				(!isset($data['agree18']) &&  isset($data['agree14']) &&  isset($data['agree14guardian']) && $age >= 14 && $age < 18)
			)
		)	){

		$msgs->addMsg("You must read and agree to the Terms and Conditions");
		$error=true;
	}

	$jointime = time();

	$db->prepare_query("SELECT id FROM deletedusers WHERE email = ? && jointime > #", $data['email'], $jointime - 86400*7); //past 7 days

	if($db->fetchrow()){
		$msgs->addMsg("This email was used to create an account this week, and can't be used again until that period is over.");
		$error=true;
	}


	if($error)
		return false;

//	$dob = my_gmmktime(0,0,0,$data['month'],$data['day'],$data['year']);
//	$age = getAge($dob);

	if($data['sex'] == 'Male'){
		$defaultsex = 'Female';
		$defaultminage = floor($age/2+7);
		$defaultmaxage = ceil(3*$age/2-7);

	}else{
		$defaultsex = 'Male';
		$defaultminage = floor($age/2+7);
		$defaultmaxage = ceil(3*$age/2-5);
	}
	$defaultloc = $data['loc'];


	$key = makeRandkey();

	$dob = my_gmmktime(0,0,0,$data['month'],$data['day'],$data['year']);


	$old_user_abort = ignore_user_abort(true);

	$plustime = 0;

	$usersdb->prepare_query("INSERT INTO `users` SET username = ?, `password` = ?, email = ?, dob = #, age = #, loc = #, sex = ?, jointime = #, timeoffset = #, activatekey = ?, ip = #, defaultsex = ?, defaultloc=#, defaultminage = #, defaultmaxage = #, premiumexpiry = #",
		$data['username'], mysql_hash_password($data['password']), $data['email'], $dob, $age, $data['loc'], $data['sex'], $jointime, $config['timezone'], $key, $ip, $defaultsex, $defaultloc, $defaultminage, $defaultmaxage, $plustime);

	$userid = $usersdb->insertid();

	$usersdb->prepare_query("INSERT IGNORE INTO profile SET userid = %", $userid);
	$usersdb->prepare_query("INSERT IGNORE INTO usernames SET userid = #, username = ?, live = 'y'", $userid, $data['username']);

	$masterdb->query("UPDATE stats SET userstotal = userstotal + 1");


$message="Thanks for joining $config[title]! (http://$wwwdomain)

Believe it or not, we're just as bored as you.  So we figured we'd do something about it, and ended up with Nexopia.com.  In a nutshell, it's a customizable online community, with user interests kept in mind.  Rather then just have the traditional online community where you can surf around, rate pictures, and message all the people you think are cute, we're constantly incorporating thoughts and ideas that come from you!

All you have to do now is activate your account by clicking on this link:
http://$wwwdomain/activate.php?username=" . urlencode($data['username']) . "&actkey=$key

Account information:
        Username: $data[username]
        Activation Key: $key

Thanks and enjoy,
The $config[title] Team

$wwwdomain


Please do not respond to this email. Always use the Contact section of our website instead.";
//        Password: $data[password]

	$subject="Activate your account at $wwwdomain.";


	smtpmail("$data[email]", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");



	global $messaging;

	$subject = "Welcome To Nexopia";
	$welcomemsg = getStaticValue('welcomemsg');

	$messaging->deliverMsg($userid, $subject, $welcomemsg, 0, "Nexopia", 0, false);




	$db->prepare_query("SELECT * FROM invites WHERE email = ?", $data['email']);

	$invites = $db->fetchrowset();

	global $usersdb;


	$friendparts = array();
	$msgto = array();

	foreach($invites as $line){
		$friendparts[] = $usersdb->prepare("(%,#)", $line['userid'], $userid);
		$friendparts[] = $usersdb->prepare("(%,#)", $userid, $line['userid']);
		$msgto[] = $line['userid'];
	}

	if(count($friendparts))
		$usersdb->query("INSERT IGNORE INTO friends (userid, friendid) VALUES " . implode(',', $friendparts));

	if(count($msgto)){
		foreach($invites as $line){
			$subject = "Friend Joined";
			$message = "Your friend $line[name] has joined Nexopia.com, and has been added to your friends list. Click [url=profile.php?uid=$userid]here[/url] to see your friends profile.";
			$messaging->deliverMsg($msgto, $subject, $message, 0, "Nexopia", 0, false);
		}
	}

	$db->prepare_query("DELETE FROM invites WHERE email = ?", $data['email']);

	ignore_user_abort($old_user_abort);

	return true;
}

function activateAccount($username,$key=0){
	global $msgs, $userData, $db, $usersdb, $mods;

	if($userData['loggedIn'] && !$mods->isAdmin($userData['userid'],"activateusers"))
		die("You are already logged in");

	$uid = getUserID($username);

	if(!$uid){
		$msgs->addMsg("Bad Username. If you are sure there is no error, contact the webmaster");
		return false;
	}
	$usersdb->prepare_query("SELECT userid, activated, activatekey, username, age, sex FROM users WHERE userid = #", $uid);

	$line = $usersdb->fetchrow();

	if(!$line){
		$msgs->addMsg("Bad Username. If you are sure there is no error, contact the webmaster");
		return false;
	}

	if($line['activated']=='y'){
		$msgs->addMsg("Account already activated");
		return false;
	}

	if(!$userData['loggedIn']){
		if($key != $line['activatekey'] && !($userData['loggedIn'] && $mods->isAdmin($userData['userid'],"activateusers"))){
			$msgs->addMsg("Bad username or activation key");
			return false;
		}
	}

	$usersdb->prepare_query("UPDATE users SET activated = 'y', activatekey='' WHERE userid = #", $line['userid']);

	$usersdb->prepare_query("INSERT IGNORE INTO profile SET userid = %", $line['userid']);

	$db->prepare_query("INSERT IGNORE INTO newestusers SET userid = %, username = ?, time = #, age = #, sex = ?", $line['userid'], $line['username'], time(), $line['age'], $line['sex']);

	return true;
}

function deactivateAccount($uid){
	global $userData,$key,$config,$wwwdomain,$emaildomain,$db, $usersdb, $cache;

	if(!is_numeric($uid))
		$uid=getUserID($uid);


	$key = makeRandkey();

	$usersdb->prepare_query("UPDATE users SET activated = 'n',activatekey = ? WHERE userid = #", $key, $uid);

	$usersdb->prepare_query("SELECT username, email FROM users WHERE userid = #", $uid);
	$line = $usersdb->fetchrow();

	$cache->remove("userprefs-$uid");

	$message="To activate your account at http://$config[title] click on the following link or copy it into your webbrowser: http://$wwwdomain/activate.php?username=$line[username]&actkey=$key";
	$subject="Activate your account at $wwwdomain.";

	smtpmail("$line[email]", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");

	if($uid==$userData['userid'])
		destroySession($uid,$key);
}

function deleteAccount($uids,$reason=""){
	global $userData, $msgs, $db, $usersdb, $masterdb, $forums, $mods, $cache, $weblog;

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


	$result = $usersdb->prepare_query("SELECT userid, username, frozen, email, jointime, ip FROM users WHERE userid IN (#)", $uids);
	$lines = $result->fetchrowset();

	if(!$lines)
		return;

	$usernames = array();

	foreach ($lines as $line){
		if($line['frozen']=='y'){
			$msgs->addMsg("Account $line[userid] is frozen, and wasn't deleted");
			unset($uids[$line['userid']]);
		}else{
			$db->prepare_query("INSERT INTO deletedusers SET userid = #, username = ?, email = ?, time = #, reason = ?, deleteid = #, jointime = #, ip = #",
						$line['userid'], $line['username'], $line['email'], $time, $reason, $deleteid, $line['jointime'], $line['ip']);

			$usernames[$line['userid']] = $line['username'];
		}
	}

//	$db->prepare_query("INSERT INTO deletedusers (userid, username, email, time, reason, deleteid, jointime, ip)
//							SELECT userid, username, email, #, ?, #, jointime, ip FROM users WHERE userid IN (#)", time(), $reason, $deleteid, $uids);



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
	}


	$usersdb->prepare_query("DELETE FROM users WHERE userid IN (#)", $uids);
	$usersdb->prepare_query("DELETE FROM profile WHERE userid IN (%)", $uids);
//	$db->prepare_query("DELETE FROM abuse WHERE userid IN (#)", $uids);

	$usersdb->prepare_query("UPDATE usernames SET live = NULL WHERE userid IN (#)", $uids);

	$db->prepare_query("DELETE FROM `ignore` WHERE userid IN (#)", $uids);
	$db->prepare_query("DELETE FROM `ignore` WHERE ignoreid IN (#)", $uids);

	$usersdb->prepare_query("DELETE FROM friends WHERE userid IN (%)", $uids);
	$usersdb->prepare_query("DELETE FROM friends WHERE friendid IN (#)", $uids);

//	$db->prepare_query("DELETE FROM bookmarks WHERE userid IN (#)", $uids);

	$mods->deleteAdmin($uids);
	$mods->deleteMod($uids);

	$forums->db->prepare_query("DELETE FROM forummods WHERE userid IN (#)", $uids);
	$forums->db->prepare_query("DELETE FROM foruminvite WHERE userid IN (#)", $uids);

//	$db->prepare_query("DELETE FROM schedule WHERE authorid IN (#) && scope!='global'", $uids);

	$sessiondb->prepare_query("DELETE FROM sessions WHERE userid IN (%)", $uids);

	$usersdb->prepare_query("DELETE FROM profileviews WHERE userid IN (%)", $uids);
//	$usersdb->prepare_query(false, "DELETE FROM profileviews WHERE viewuserid IN (#)", $uids); //slow, pointless?

	removeAllUserPics($uids);

	$masterdb->query("UPDATE stats SET userstotal = userstotal - " . count($uids));

	ignore_user_abort($old_user_abort);

	return true;
}

function deleteUserCleanup(){
	global $db, $usersdb, $weblog, $messaging, $usercomments, $forums, $articlesdb;
	$db->prepare_query("SELECT userid FROM deletedusers WHERE time > #", (time() - 25*3600)); //get users deleted in past 25 hours

	$old_user_abort = ignore_user_abort(true);

	$users = array();
	$i=0;
	while($line = $db->fetchrow()){
		$users[floor($i/1000)][] = $line['userid'];
		$i++;
	}


	for($i=0;$i<count($users);$i++){
		$messaging->db->prepare_query("DELETE FROM msgs WHERE userid IN (%)", $users[$i]);
		$messaging->db->prepare_query("DELETE FROM msgtext WHERE userid IN (%)", $users[$i]);
		$messaging->db->prepare_query("DELETE FROM msgfolders WHERE userid IN (%)", $users[$i]);
//		$forums->db->prepare_query("UPDATE forumposts SET authorid='0' WHERE authorid IN (#)", $users[$i]);
//		$forums->db->prepare_query("UPDATE forumpostsdel SET authorid='0' WHERE authorid IN (#)", $users[$i]);
//		$forums->db->prepare_query("UPDATE forumthreads SET authorid='0' WHERE authorid IN (#)", $users[$i]);
//		$forums->db->prepare_query("UPDATE forumthreadsdel SET authorid='0' WHERE authorid IN (#)", $users[$i]);
//		$messaging->db->prepare_query("UPDATE msgs SET `from`='0' WHERE `from` IN (#)", $users[$i]);
//		$messaging->db->prepare_query("UPDATE msgs SET `to`='0' WHERE `to` IN (#)", $users[$i]);
//		$usercomments->db->prepare_query("UPDATE usercomments SET authorid='0' WHERE authorid IN (#)", $users[$i]);
		$db->prepare_query("UPDATE comments SET authorid='0' WHERE authorid IN (#)", $users[$i]);
		$articlesdb->prepare_query("UPDATE articles SET authorid='0' WHERE authorid IN (#)", $users[$i]);

		foreach ($users[$i] as $uid)
		{
			$userblog = new userblog($weblog, $uid);
			$userblog->deleteBlog();
		}
	}

	ignore_user_abort($old_user_abort);
}

function addRefreshHeaders(){
//	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
//	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: must-revalidate, proxy-revalidate, no-cache");  // HTTP/1.1
//	header("Pragma: no-cache");                          // HTTP/1.0
}

function makeRandKey(){
	return md5(uniqid(rand(),1));
}
