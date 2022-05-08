<?

	$login = 1;

	include_once("include/general.lib.php");

	$maxlength = 250;


	$friendnames = getPOSTval('friendnames', 'array');
	$friendemails = getPOSTval('friendemails', 'array');

	$myname = getPOSTval('myname', 'string', $userData['username']);

//	$msg = getPOSTval('msg');
//	$msg = substr($msg, 0, $maxlength);

	if($action == "Send"){
//		$msg = removeHTML($msg);

		if(count($friendnames) && count($friendnames) == count($friendemails)){
			$parts = array();
			$time = time();
			$num = 0;

			$res = $db->prepare_query("SELECT email FROM inviteoptout WHERE email IN (?)", $friendemails);

			$optout = array();
			while($line = $res->fetchrow())
				$optout[$line['email']] = $line['email'];

			$res = $masterdb->prepare_query("SELECT email FROM useremails WHERE email IN (?)", $friendemails);

			$optout = array();
			while($line = $res->fetchrow())
				$optout[$line['email']] = $line['email'];

			for($i = 0; $i < count($friendnames); $i++){

				if(empty($friendnames[$i]) || empty($friendemails[$i]))
					continue;

				if(!isValidEmail($friendemails[$i])) //adds the $msg itself
					continue;

				if(strlen($friendnames[$i]) <= 2){
					$msgs->addMsg("The name: " . htmlentities($friendnames[$i]) . " is too short");
					continue;
				}

				if(strlen($friendnames[$i]) >= 20){
					$msgs->addMsg("The name: " . htmlentities($friendnames[$i]) . " is too long");
					continue;
				}

/*				if(!preg_match("/[\w\d ]* /", $friendnames[$i])){
					$msgs->addMsg("The name: " . htmlentities($friendnames[$i]) . " is too long");
					continue;
				}
*/
				if(isset($optout[$friendemails[$i]]))
					continue;

				$num++;

				$subject = "Invitation to Nexopia.com";
				$message =
"Hey $friendnames[$i],

Your friend, $myname, would like you to come join us at Nexopia, the fastest growing online community in Canada!

What is Nexopia?
Nexopia is an online community where you can keep in touch with your friends and meet new people.

As a member you can:
- Create a customized profile
- Upload and share your pictures
- Send messages and leave comments for your friends
- Create your own blog
- Browse some of the most popular forums in North America

The best part is it's FREE!

Check out " . $myname . "'s profile right now and see what we're all about: http://www.nexopia.com/profile.php?uid=$userData[userid]

If you do not want to receive invitation emails from Nexopia members in the future, you can click here: http://www.nexopia.com/inviteoptout.php?email=$friendemails[$i]&k=" . makeKey($friendemails[$i], -1);

				$email = $useraccounts->getEmail($userData['userid']);

				smtpmail("$friendnames[$i] <$friendemails[$i]>", $subject, $message, "From: $myname <$email>");

				$parts[] = $db->prepare("(?,?,#,#)", $friendnames[$i], $friendemails[$i], $userData['userid'], $time);

				unset($friendnames[$i], $friendemails[$i]);
			}

			if(count($parts))
				$db->query("INSERT INTO invites (name, email, userid, time) VALUES " . implode(',', $parts));

			$msgs->addMsg("$num Invitations sent");
		}else{
			$friendnames = array();
			$friendemails = array();
		}
	}

	$friendnames = array_values($friendnames);
	$friendemails = array_values($friendemails);

	$numFriends = count($friendnames);
	$inviteFriends = array();

	for($i = 0; $i < $numFriends; $i++){
//		if(empty($friendnames[$i]) && empty($friendemails[$i])){
//			unset($friendnames[$i], $friendemails[$i]);
//			continue;
//		}

		$inviteFriends[] = array(
			'name'	=> $friendnames[$i],
			'email'	=> $friendemails[$i]
		);
	}

	for($i = $numFriends; $i < 6; $i++)
		$inviteFriends[] = array(
			'name'	=> '',
			'email'	=> ''
		);

	$template = new template('invite/index');
	$template->set('inviteFriends', $inviteFriends);
	$template->set('myUserName', $myname);
	$template->set('myEmail', $useraccounts->getEmail($userData['userid']));
	$template->display();
