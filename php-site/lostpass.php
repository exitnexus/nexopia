<?

	$login=-1;

	require_once("include/general.lib.php");

	switch($action){
		case "Resend Activation":
			if(!isset($username) || $username==""){
				$msgs->addMsg("You must specify your username");
				break;
			}

			$result = $db->prepare_query("SELECT userid,username,activatekey,email FROM users WHERE username = ?", $username);

			if($db->numrows($result)==1){
				$line  = $db->fetchrow($result);
				if($line['activatekey']==''){
					$key=makekey();
					$query = $db->prepare_query("UPDATE users SET activatekey = ? WHERE userid = ?", $key, $line['userid']);
				}else
					$key = $line['activatekey'];

				$message = "To change your password at $config[title] you'll need your\n";
				$msssage.= "username: $line[username]\n";
				$message.= "and the activation key: $key\n";
				$message.= "To activate your account after signup, or changing emails click\n";
				$message.= "the link: http://$wwwdomain/activate.php?username=" . urlencode($line['username']) . "&actkey=$key\n";
				$message.= "If you didn't request this email, you can safely ignore it.";
				$subject = "Change your password at $wwwdomain.";

				smtpmail("$username <$line[email]>", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");
				$msgs->addMsg("Email sent");
			}
			break;
		case "Change Password":
			if(!isset($username) || !isset($pass1) || !isset($activation) || $username=="" || $pass1=="" || $activation==""){
				$msgs->addMsg("You must specify your username, new password and the activation key");
				break;
			}
			$db->prepare_query("SELECT userid,activatekey FROM users WHERE username = ?", $username);

			if($db->numrows()==1){
				$line  = $db->fetchrow();

				if($line['activatekey']==$activation){
					if(strlen($pass1) < 4){
						$msgs->addMsg("New Password is too short");
					}elseif($pass1==$pass2){
						$db->prepare_query("UPDATE users SET `password`=PASSWORD(?), activated='y', activatekey='' WHERE userid = ?", $pass1, $line['userid']);
					}else{
						$msgs->addMsg("New Passwords don't match");
					}
				}else{
					$msgs->addMsg("Bad Activation Key");
				}
			}
			break;
		case "Activate":
			if(isset($username) && isset($actkey) && strlen($username)>0 && strlen($actkey)>0){
				if(activateAccount($username,$actkey))
					$msgs->addMsg("Activation complete.");
				else
					$msgs->addMsg("Activation failed");
			}else
				$msgs->addMsg("You must input both your username and the activation key");
			break;
	}


	incHeader();

	echo "<table width=100%><form action=\"$PHP_SELF\" method=post>";
	echo "<tr><td class=header colspan=2 align=center>Resend Activation</td></tr>";
//	echo "<tr><td class=body colspan=2>This will allow you to change your password below. It is useful if you lost your password, or the activation email didn't arrive. If you login before changing passwords, the activation key sent to you won't work.</td></tr>";
	echo "<tr><td class=body>Your Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body></td><td><input class=body type=submit name=action value=\"Resend Activation\"></td></tr>";
	echo "<tr><td colspan=2>&nbsp;</td></tr>";
	echo "</form>";

	echo "<tr><td class=header colspan=2 align=center>Lost Password</td></tr>";

	echo "<form action=\"$PHP_SELF\" method=post>";
	echo "<tr><td class=body colspan=2>If you have logged in since requesting the activation key, the key sent won't work.</td></tr>";
	echo "<tr><td class=body>Your Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Your Activation Key:</td><td class=body><input class=body type=text name=activation></td></tr>";
	echo "<tr><td class=body>New Password:</td><td class=body><input class=body type=password name=pass1></td></tr>";
	echo "<tr><td class=body>Retype New Password:</td><td class=body><input class=body type=password name=pass2></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=\"Change Password\"></td></tr>";
	echo "<tr><td colspan=2>&nbsp;</td></tr>";
	echo "</form>";

	echo "<form action=$PHP_SELF method=post>";
	echo "<tr><td class=header colspan=2 align=center>Activate Account</td></tr>";
	echo "<tr><td class=body colspan=2>If you didn't receive an email when you signed up, enter your username above to have it resent.</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Activation Key:</td><td class=body><input class=body type=text name=actkey></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Activate></td></tr>";
	echo "</form>";

	echo "</table>";

	incFooter();
