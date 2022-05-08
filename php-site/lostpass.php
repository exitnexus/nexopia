<?

	$login=-1;

	require_once("include/general.lib.php");

	switch($action){
		case "Resend Activation":
			$username = getPOSTval('username');
			$email = getPOSTval('email');

			$ip = getip();

			$uid = getUserID($username);

			if(!$uid){
				$msgs->addMsg("You must specify your username");
				break;
			}

			$freqcap = $cache->incr("resendactivation-$uid");
			if(!$freqcap){
				$cache->put("resendactivation-$uid", 1, 1800);
				$freqcap = 1;
			}

			$freqcap2 = $cache->incr("resendactivation2-$ip");
			if(!$freqcap2){
				$cache->put("resendactivation2-$ip", 1, 1800);
				$freqcap2 = 1;
			}

			if($freqcap > 10 || $freqcap2 > 10){
				$msgs->addMsg("Please try again later.");
				break;
			}


			$result = $masterdb->prepare_query("SELECT email FROM useremails WHERE userid = # && email = ?", $uid, $email);
			$line = $result->fetchrow();

			if($line){
				$key = $auth->makeRandKey();
				$masterdb->prepare_query("UPDATE useremails SET `key` = ?, time = # WHERE userid = # && email = ?", $key, time(), $uid, $email);

				$subject = "Change your password at $wwwdomain.";

$urlencoded = urlencode($username);
$message =
"To change your password at $config[title] you'll need your
username: $username
and the activation key: $key
Click the following link to change your password: http://$wwwdomain/lostpass.php?username=$urlencoded&actkey=$key

If you didn't request this email, you can safely ignore it.";

				smtpmail("$line[email]", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");
				$msgs->addMsg("Email sent");
			}else{
				$msgs->addMsg("Either that account doesn't exist, or that email isn't registered to that account.");
			}
			break;
		case "Change Password":
			$username = getPOSTval('username');
			$pass1 = getPOSTval('pass1');
			$pass2 = getPOSTval('pass2');
			$activation = getPOSTval('activation');


			$uid = getUserID($username);

			if(!$uid){
				$msgs->addMsg("You must specify your username");
				break;
			}

			if(blank($pass1, $pass2, $activation)){
				$msgs->addMsg("You must specify your username, new password and the activation key");
				break;
			}
			$res = $masterdb->prepare_query("SELECT time FROM useremails WHERE userid = # && `key` = ?", $uid, $activation);
			$line = $res->fetchrow();

			if($line && $line['time'] > (time() - 86400*7)){ //activation keys only survive for 1 week.

				if(strlen($pass1) < 4){
					$msgs->addMsg("New Password is too short");
				}elseif($pass1==$pass2){
					$auth->changePassword($uid, $pass1);
					$msgs->addMsg("Your password has been changed. You can now use it to log in.");
				}else{
					$msgs->addMsg("New Passwords don't match");
				}
			}else{
				$msgs->addMsg("Bad Activation Key");
			}
			break;
	}

	$lostpassuser = getREQval('username', 'string', '');
	$lostpasskey = getREQval('actkey', 'string', '');

	$template = new template('activations/index');
	$template->set('username', $lostpassuser);
	$template->set('actkey', $lostpasskey);
	$template->display();
