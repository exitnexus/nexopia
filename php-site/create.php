<?

	$login=-1;

	require_once("include/general.lib.php");

	$register = getPOSTval('register', 'bool');
	$data = getPOSTval('data', 'array');

	$defaults = array(
			'username' => "",
			'email' => "",
			'loc' => "",
			'month' => "",
			'day' => "",
			'year' => "",
			'sex' => "",
		);

	if($register){
		if(newAccount($data)){
			incHeader();
			echo "Your account has been created. Expect an email within a few minutes. The instructions on how to activate your account are there.";
			incFooter();
			exit;
		}
	}



	extract(setDefaults($data, $defaults));

	$locations = new category( $configdb, "locs");


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));


	$template = new template('create/create');
	$template->set('username', $username);
	$template->set('usernamelength', $config['maxusernamelength']);
	$template->set('email', $email);
	$template->set('selectMonth',make_select_list_key($months, $month));
	$template->set('selectDay', make_select_list(range(1,31), $day));
	$template->set('selectYear', make_select_list(array_reverse(range(date("Y")-$config['maxAge'],date("Y")-$config['minAge'])), $year));
	$template->set('selectLocation', makeCatSelect($locations->makeBranch(), $loc));
	$template->set('radioSex', make_radio("data[sex]", array("Male","Female"), $sex));
	$template->set('terms', nl2br(getterms()));
	$template->set('check18', makeCheckBox('data[agree18]', ' I, the user, am over the age of 18.'));
	$template->set('check14', makeCheckBox('data[agree14]', ' If No, I, the user, am over the age of 14.'));
	$template->set('checkParent', makeCheckBox('data[agree14guardian]', ' AND I have consent from my legal guardian who is over 18 and accepts these Terms and Conditions.'));
	$template->set('checkLimit', makeCheckBox('data[agreelimit]', ' I, the user, acknowledge and understand that these terms limit my rights and remedies.'));
	$template->set('checkAgree', makeCheckBox('data[agreeterms]', ' I, the user, read, understand and agree to these Terms and Conditions of Use.'));

	$template->display();



function newAccount($data){
	global $useraccounts, $wwwdomain, $emaildomain, $msgs, $config, $db, $masterdb, $usersdb, $configdb, $messaging;
	$error = false;

	$ip = ip2int(getip());

	if(isBanned($ip)){
		$error=true;
		$msgs->addMsg("Your IP has been banned due to abuse. Please <a class=\"body\" href=\"/contactus.php\">contact us</a> if you need details.");
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
		$msgs->addMsg("Your email has been banned due to abuse. Please <a class=\"body\" href=\"/contactus.php\">contact us</a> if you need details.");
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

	if($data['email']){
		$db->prepare_query("SELECT userid FROM deletedusers WHERE email = ? && jointime > #", $data['email'], $jointime - 86400*7); //past 7 days

		if($db->fetchrow()){
			$msgs->addMsg("This email was used to create an account this week, and can't be used again until that period is over.");
			$error=true;
		}
	}


	if($error)
		return false;

	$ret = $useraccounts->createAccount($data['username'], $data['password'], $data['email'], $dob, $data['sex'], $data['loc']);

	if(!$ret)
		return false;

	list($userid, $key) = $ret;


	$template = new template('create/welcomeemail');
	$template->set('wwwdomain', $wwwdomain);
	$template->set('username', $data['username']);
	$template->set('key', $key);

	$message = $template->toString();
	$subject="Activate your account at Nexopia.com";

	smtpmail("$data[email]", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");



	$subject = "Welcome To Nexopia";
	$welcomemsg = getStaticValue('welcomemsg');

	$messaging->deliverMsg($userid, $subject, $welcomemsg, 0, "Nexopia", 0, false);



	$db->prepare_query("SELECT * FROM invites WHERE email = ?", $data['email']);

	$invites = $db->fetchrowset();

	$msgto = array();

	foreach($invites as $line){
		$usersdb->prepare_query("INSERT IGNORE INTO friends (userid, friendid) VALUES (%,#)", $line['userid'], $userid);
		$usersdb->prepare_query("INSERT IGNORE INTO friends (userid, friendid) VALUES (%,#)", $userid, $line['userid']);
		$msgto[] = $line['userid'];
	}

	if(count($msgto)){
		foreach($invites as $line){
			$subject = "Friend Joined";
			$message = "Your friend $line[name] has joined Nexopia.com, and has been added to your friends list. Click [url=/profile.php?uid=$userid]here[/url] to see your friends profile.";
			$messaging->deliverMsg($msgto, $subject, $message, 0, "Nexopia", 0, false);
		}
	}

	$db->prepare_query("DELETE FROM invites WHERE email = ?", $data['email']);

	return true;
}
