<?

function login($username, $password, $cached=false){				//call destroy session before logging in, or the guest session will persist for a while
	global $db;

	if(empty($username) || empty($password) || trim($username)=="" || trim($password)=="")
		die("Bad username or password");

	$db->prepare_query("SELECT userid,username,(password = PASSWORD(?)) as passmatch ,frozen,activated FROM users WHERE username = ?", $password, $username);

	$line = $db->fetchrow();

	if($line['frozen'] == 'y')
		die("Your account is frozen");
	if(!$line['passmatch'])
		die("Bad username or password");
	if($line['activated'] == 'n')
		die("Your account isn't activated");

	createSession($line['userid'],$cached);

	return $line['userid'];
}

function auth($userid, $key, $kill=true){
	global $config,$cookiedomain,$db,$fastdb,$REQUEST_URI;

//	echo "start auth! userid: '$userid', key: '$key'<br>";

	$time = time();
	$ip = ip2int(getip());

	$userData['loggedIn']=false;
	$userData['timeoffset']=$config['timezone'];

//new user, no old session to destroy before creating a new one
//reuse an old one if possible. Useful for people who won't accept cookies, so the count doesn't just keep going up
	if(empty($userid) || empty($key)){
		if($kill){
			header("location: /login.php?referer=" . urlencode($REQUEST_URI));
			die;
		}

		$result = $fastdb->prepare_query("SELECT id,sessionid FROM sessions WHERE ip = ? && ISNULL(userid)", $ip);
		if($fastdb->numrows($result)==0){
			createSession();
		}else{
			$line = $fastdb->fetchrow($result);

			setCookie("userid",0-$line['id'],0,'/',$cookiedomain);
			setCookie("key",$line['sessionid'],0,'/',$cookiedomain);
		}

		return $userData;
	}



//old session
	if($userid<0){
		if($kill){
			header("location: /login.php?referer=" . urlencode($REQUEST_URI));
			exit;
		}
		$id=abs($userid);

		$result = $fastdb->prepare_query("SELECT sessionid FROM sessions WHERE id = ?", $id);

		if($fastdb->numrows($result)==0){
			destroySession($userid,$key);
			createSession();
		}else{
			$sessionid = $fastdb->fetchfield();

			if($key == $sessionid){
				$fastdb->prepare_query("UPDATE sessions SET activetime = ? WHERE id = ?", $time, $id);
			}else{
				destroySession($userid,$key);
				createSession();
			}
		}
		return $userData;
	}

//logged in user
	$result = $fastdb->prepare_query("SELECT activetime, cachedlogin FROM sessions WHERE userid = ? && sessionid = ?", $userid, $key);

	if($fastdb->numrows($result)==0){
		destroySession($userid,$key);
		createSession();
		if($kill){
			header("location: /login.php?referer=" . urlencode($REQUEST_URI));
			die;
		}
		return $userData;
	}

	$line = $fastdb->fetchrow($result);
	if($line['cachedlogin']=='n' && $line['activetime'] < ($time-$config['maxAwayTime']) ){
		destroySession($userid,$key);
		createSession();
		if($kill){
			header("location: /login.php?referer=" . urlencode($REQUEST_URI));
			die;
		}
		return $userData;
	}

/*	if($line['activetime'] > ($time - $config['friendAwayTime']))
		$db->prepare_query("UPDATE users SET online = 'y' WHERE userid = ?", $userid);
*/

	$fastdb->prepare_query("UPDATE sessions SET activetime = ?, ip = ? WHERE userid = ? && sessionid = ?", $time, $ip, $userid, $key);

	$fastdb->prepare_query("UPDATE userhitlog SET activetime = ?, hits = hits+1 WHERE userid = ? && ip = ?", $time, $userid, $ip);
	if($fastdb->affectedrows()==0)
		$fastdb->prepare_query("INSERT IGNORE INTO userhitlog SET activetime = ?, hits = hits+1, userid = ?, ip = ?", $time, $userid, $ip);


	$fastdb->prepare_query("UPDATE useractivetime SET activetime = ?, hits = hits+1, ip = ?, online = 'y' WHERE userid = ?", $time, $ip, $userid);
	if($fastdb->affectedrows()==0)
		$fastdb->prepare_query("INSERT IGNORE INTO useractivetime SET activetime = ?, hits = 1, ip = ?, online = 'y', userid = ?", $time, $ip, $userid);


	$db->prepare_query("SELECT username, frozen, online, sex, dob, age, showrightblocks, posts, newmsgs, timeoffset, newcomments, premiumexpiry, defaultminage, defaultmaxage, defaultsex, skin FROM users WHERE userid = ?", $userid);
	$line = $db->fetchrow();

	if($line['frozen'] == 'y')
		die("Your account is frozen");

	if($line['online'] == 'n')
		$db->prepare_query("UPDATE users SET online = 'y', activetime = ?, ip = ? WHERE userid = ?", $time, $ip, $userid);

	$userData['loggedIn']=true;
	$userData['userid']=$userid;
	$userData['username']=$line['username'];
	$userData['sessionkey'] = $key; //logout on password change
	$userData['sex']=$line['sex']; //for banners
	$userData['age']=$line['age']; //for banners, ignores
	$userData['showrightblocks']=$line['showrightblocks'];
	$userData['posts']=$line['posts']; //show subscriptions block?
	$userData['newmsgs']=$line['newmsgs'];
	$userData['newcomments']=$line['newcomments'];
	$userData['timeoffset']=$line['timeoffset'];
	$userData['premium'] = ($line['premiumexpiry'] > $time);
	$userData['defaultminage']=$line['defaultminage']; //search block
	$userData['defaultmaxage']=$line['defaultmaxage'];
	$userData['defaultsex']=$line['defaultsex'];
	$userData['skin']=$line['skin'];

    return $userData;
}


function createSession($userid=0,$cachedlogin=false){
	global $cookiedomain,$fastdb;
	$ip = ip2int(getip());

	$time = time();

	$key = makekey();
	if($cachedlogin==false || $cachedlogin=='n')
		$cachedlogin='n';
	else
		$cachedlogin='y';


	$fastdb->prepare_query("INSERT INTO sessions SET ip = ?, userid = " . (!$userid ? "NULL" : $fastdb->escape($userid) ) . ", activetime = ?, sessionid = ?, cachedlogin = ?", $ip, $time, $key, $cachedlogin);

	if($cachedlogin=='y')
		$expire=$time+3600*24*30;
	else
		$expire=0;

	if($userid==0){
		$id = $fastdb->insertid();
		setCookie("userid",0-$id,$expire,'/',$cookiedomain);
	}else{
		setCookie("userid",$userid,$expire,'/',$cookiedomain);
	}
	setCookie("key",$key,$expire,'/',$cookiedomain);
}

function destroySession($userid,$key){
	global $cookiedomain,$fastdb;

	if($userid<0)
		$fastdb->prepare_query("DELETE FROM sessions WHERE id = ? && sessionid = ?", abs($userid), $key);
	else
		$fastdb->prepare_query("DELETE FROM sessions WHERE userid = ? && sessionid = ?", $userid, $key);

	setCookie("userid",$userid,time()-10000000,'/',$cookiedomain);
	setCookie("key",$key,time()-10000000,'/',$cookiedomain);
}

function newAccount($data){
	global $wwwdomain,$emaildomain,$PHP_SELF,$msgs,$config,$db, $fastdb;
	$error = false;

	$ip = ip2int(getip());

	if(isBanned($ip,'ip')){
		$error=true;
		$msgs->addMsg("Your IP has been banned due to abuse. Please email <a href=\"mailto:info@$emaildomain\">info@$emaildomain</a> if you have questions.");
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
		$msgs->addMsg("Your email has been banned due to abuse. Please email <a href=\"mailto:info@$emaildomain\">info@$emaildomain</a> if you have questions.");
	}

	if(!isset($data['month']) || $data['month']<0 || $data['month']>12 || !isset($data['day']) || $data['day']<0 || $data['day']>31 || !isset($data['year']) || !checkdate($data['month'],$data['day'],$data['year']) ||	getAge(my_gmmktime(0,0,0,$data['month'],$data['day'],$data['year']))<$config['minAge'] || getAge(my_gmmktime(0,0,0,$data['month'],$data['day'],$data['year']))>$config['maxAge']){
		$msgs->addMsg("Invalid date of birth");
		$error=true;
	}
	if(!isset($data['sex']) || !($data['sex']=="Male" || $data['sex']=="Female")){
		$msgs->addMsg("Please specify your sex");
		$error = true;
	}

	$locations = & new category("locs");

	if(!isset($data['loc']) || !$locations->isValidCat($data['loc'])){
		$msgs->addMsg("Please specify your location");
		$error=true;
	}

	if(!isset($data['agree'])){
		$msgs->addMsg("You must read and agree to the Terms and Conditions");
		$error=true;
	}

	$jointime = time();

	$db->prepare_query("SELECT id FROM deletedusers WHERE email = ? && jointime > ?", $data['email'], $jointime - 86400*7); //past 7 days

	if($db->numrows()>0){
		$msgs->addMsg("This email was used to create an account this week, and can't be used again until that period is over.");
		$error=true;
	}


	if($error)
		return false;

	$dob = my_gmmktime(0,0,0,$data['month'],$data['day'],$data['year']);

	$age = getAge($dob);

	if($data['sex'] == 'Male'){
		$defaultsex = 'Female';
		$defaultminage = floor($age/2+7);
		$defaultmaxage = ceil(3*$age/2-7);
	}else{
		$defaultsex = 'Male';
		$defaultminage = floor($age/2+7);
		$defaultmaxage = ceil(3*$age/2-5);
	}


	$key = makekey();

	$dob = my_gmmktime(0,0,0,$data['month'],$data['day'],$data['year']);


	$old_user_abort = ignore_user_abort(true);

	$plustime = 0;

	$db->prepare_query("INSERT INTO `users` SET username = ?, `password` = PASSWORD(?), email = ?, dob = ?, age = ?, loc = ?, sex = ?, jointime = ?, timeoffset = ?, activatekey = ?, ip = ?, defaultsex = ?, defaultminage = ?, defaultmaxage = ?, premiumexpiry = ?",
		$data['username'], $data['password'], $data['email'], $dob, $age, $data['loc'], $data['sex'], $jointime, $config['timezone'], $key, $ip, $defaultsex, $defaultminage, $defaultmaxage, $plustime);

	$userid = $db->insertid();

	$db->prepare_query("INSERT IGNORE INTO profile SET userid = ?", $userid);
	$fastdb->prepare_query("INSERT IGNORE INTO useractivetime SET userid = ?", $userid);


	$db->query("SELECT count(*) FROM users");
	$num = $db->fetchfield();

	$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='users' && var='total'", $num);
	$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='users' && var='maxid'", $userid);



$message="Thanks for joining $config[title]! (http://$wwwdomain)

Believe it or not, we're just as bored as you.  So we figured we'd do something about it, and ended up with Nexopia.com.  In a nutshell, it's a customizable online community, with user interests kept in mind.  Rather then just have the traditional online community where you can surf around, rate pictures, and message all the people you think are cute, we're constantly incorporating thoughts and ideas that come from you!

All you have to do now is activate your account by clicking on this link:
http://$wwwdomain/activate.php?username=" . urlencode($data['username']) . "&actkey=$key

Account information:
        Username: $data[username]
        Password: $data[password]


Thanks and enjoy,
The $config[title] Team

$wwwdomain


Please do not respond to this email. Always use the Contact section of our website instead.";


	$subject="Activate your account at $wwwdomain.";


	smtpmail("$data[username] <$data[email]>", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");



	global $userData;

	$userData['userid']=0;
	$userData['username']=$config['title'];
$subject = "Welcome To $config[title]";
$welcomemsg = "Ladies and Gentlemen, boys and girls, coming live to you from Edmonton to the world, welcome to Nexopia, community for all, playground of the sexes both short and tall.  Make yourself at home, and look around, I知 sure you値l find here features abound, from [url=/forums.php]forums[/url] to express your view, to [url=profile.php]personal pages[/url] with [url=profile.php?sort%5Bmode%5D=mypage]pics of you[/url], you may be interested in the [url=/faq.php]FAQ[/url], which will outline more than I can do.  Articles and events stay up to date, but if you池e like most here, you池e here to rate, and debate about the state of our lives, be positive and help our family thrive.

Keep in mind we have all ages, so lewd content is not tolerated, be your own judge of character and keep it clean, if you have a conscience you値l know what I mean.  As for photos, we have a few rules, first and foremost they must be of you, secondly no severe editing please, we will be forced to remove these.

Have a great time and contribute to the site, I知 sure you値l see that it offers delight, so if you find you have little to do, feel free to sign up and join people like you.";

	deliverMsg($userid,$subject, $welcomemsg);


	ignore_user_abort($old_user_abort);

	return true;
}

function activateAccount($username,$key=0){
	global $msgs,$userData,$db, $fastdb, $mods;

	if($userData['loggedIn'] && !$mods->isAdmin($userData['userid'],"activateusers"))
		die("You are already logged in");


	if(!is_Numeric($username))	$col = 'username';
	else 						$col = 'userid';

	$result = $db->prepare_query("SELECT userid,activated,activatekey,username,age,sex FROM users WHERE $col = ?", $username);

	if($db->numrows()==0){
		$msgs->addMsg("Bad Username. If you are sure there is no error, contact the webmaster");
		return false;
	}

	$line = $db->fetchrow($result);

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

	$db->prepare_query("UPDATE users SET activated = 'y', activatekey='' WHERE userid = ?", $line['userid']);

	$db->prepare_query("INSERT IGNORE INTO profile SET userid = ?", $line['userid']);
	$fastdb->prepare_query("INSERT IGNORE INTO useractivetime SET userid = ?", $line['userid']);

	$db->prepare_query("INSERT IGNORE INTO newestusers SET userid = ?, username = ?, age = ?, sex = ?", $line['userid'], $line['username'], $line['age'], $line['sex']);

	return true;
}

function deactivateAccount($uid){
	global $userData,$key,$config,$wwwdomain,$emaildomain,$PHP_SELF,$db;

	if(!is_numeric($uid))
		$uid=getUserID($uid);


	$key = makekey();

	$db->prepare_query("UPDATE users SET activated = 'n',activatekey = ? WHERE userid = ?", $key, $uid);

	$db->prepare_query("SELECT username,email FROM users WHERE userid = ?", $uid);
	$line = $db->fetchrow();


	$message="To activate your account at http://$config[title] click on the following link or copy it into your webbrowser: http://$wwwdomain/activate.php?username=$line[username]&actkey=$key";
	$subject="Activate your account at $wwwdomain.";

	smtpmail("$line[username] <$line[email]>", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");

	if($uid==$userData['userid'])
		destroySession($uid,$key);
}

function deleteAccount($uids,$reason=""){
	global $userData,$msgs,$db,$fastdb, $mods;

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

	$uids = array_combine($uids, $uids);

	if($userData['loggedIn'])
		$deleteid=$userData['userid'];
	else
		$deleteid=0;

//admins/mods can delete themselves, but no one else can

	$db->prepare_query("SELECT userid, frozen FROM users WHERE userid IN (?)", $uids);

	if($db->numrows()==0)
		return;

	while($line = $db->fetchrow()){
		if($line['frozen']=='y'){
			$msgs->addMsg("Account $line[userid] is frozen, and wasn't deleted");
			unset($uids[$line['userid']]);
		}
	}

	if(!count($uids))
		return false;

//	$db->prepare_query("INSERT INTO deletedusers SET userid = ?, username = ?, email = ?, time = ?, reason = ?, deleteid = ?,jointime = ?, ip = ?",
//		$uid,  $line['username'], $line['email'], time(), $reason, $deleteid, $line['jointime'], $line['ip']);


	$db->prepare_query("INSERT INTO deletedusers (userid, username, email, time, reason, deleteid, jointime, ip)
							SELECT userid, username, email, ?, ?, ?, jointime, ip FROM users WHERE userid IN (?)", time(), $reason, $deleteid, $uids);


	$db->prepare_query("DELETE FROM users WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM profile WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM abuse WHERE userid IN (?)", $uids);

	$db->prepare_query("DELETE FROM `ignore` WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM `ignore` WHERE ignoreid IN (?)", $uids);
	$db->prepare_query("DELETE FROM friends WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM friends WHERE friendid IN (?)", $uids);
//	$db->prepare_query("DELETE FROM bookmarks WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM admin WHERE userid IN (?)", $uids);
	$mods->deleteMod($uids);
	$db->prepare_query("DELETE FROM modvotes WHERE modid IN (?)", $uids);
	$db->prepare_query("DELETE FROM forummods WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM schedule WHERE authorid IN (?) && scope!='global'", $uids);
	$fastdb->prepare_query("DELETE FROM sessions WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM picbans WHERE userid IN (?)", $uids);
	$db->prepare_query("DELETE FROM profileviews WHERE userid IN (?)", $uids);


	$db->prepare_query("SELECT picid,vote FROM votehist WHERE userid IN (?)", $uids);

	$votes = array();
	while($line = $db->fetchrow())
		$votes[$line['vote']][] = $line['picid'];

	foreach($votes as $score => $pics)
		$db->prepare_query("UPDATE pics SET score=IF(votes=1,0,((score*votes)-$score)/(votes-1)), votes=votes-1, v$score=v$score-1 WHERE id IN (?)", $pics);

	$db->prepare_query("DELETE FROM votehist WHERE userid IN (?)", $uids);

	$db->prepare_query("SELECT id FROM pics WHERE itemid IN (?)", $uids);

	while($line = $db->fetchrow())
		removePic($line['id']);

	$db->prepare_query("SELECT id FROM picspending WHERE itemid IN (?)", $uids);
	while($line = $db->fetchrow())
		removePicPending($line['id']);

	$db->query("SELECT count(*) FROM users");
	$num = $db->fetchfield();

	$fastdb->prepare_query("UPDATE stats SET count = ? WHERE type='users' && var='total'", $num);

	ignore_user_abort($old_user_abort);

	return true;
}

function deleteUserCleanup(){
	global $db;
	$db->prepare_query("SELECT userid FROM deletedusers WHERE time > ?", (time() - 25*3600)); //get users deleted in past 25 hours

	$old_user_abort = ignore_user_abort(true);

	$users = array();
	$i=0;
	while($line = $db->fetchrow()){
		$users[floor($i/1000)][] = $line['userid'];
		$i++;
	}


	for($i=0;$i<count($users);$i++){
		$db->prepare_query("DELETE FROM msgs WHERE userid IN (?)", $users[$i]);
		$db->prepare_query("UPDATE forumposts SET authorid='0' WHERE authorid IN (?)", $users[$i]);
		$db->prepare_query("UPDATE forumpostsdel SET authorid='0' WHERE authorid IN (?)", $users[$i]);
		$db->prepare_query("UPDATE forumthreads SET authorid='0' WHERE authorid IN (?)", $users[$i]);
		$db->prepare_query("UPDATE forumthreadsdel SET authorid='0' WHERE authorid IN (?)", $users[$i]);
//		$db->prepare_query("UPDATE msgs SET `from`='0' WHERE `from` IN (?)", $users[$i]);
//		$db->prepare_query("UPDATE msgs SET `to`='0' WHERE `to` IN (?)", $users[$i]);
		$db->prepare_query("UPDATE usercomments SET authorid='0' WHERE authorid IN (?)", $users[$i]);
		$db->prepare_query("UPDATE comments SET authorid='0' WHERE authorid IN (?)", $users[$i]);
		$db->prepare_query("UPDATE schedule SET authorid='0' WHERE authorid IN (?)", $users[$i]);
		$db->prepare_query("UPDATE articles SET authorid='0' WHERE authorid IN (?)", $users[$i]);
	}

	ignore_user_abort($old_user_abort);
}

function addRefreshHeaders(){
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
	header("Pragma: no-cache");                          // HTTP/1.0
}

function makeKey(){
	return md5(uniqid(rand(),1));
}
