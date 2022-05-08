<?

	$login=0;

	require_once("include/general.lib.php");

	$targets = $contactemails;

	if($userData['loggedIn']){
		$db->prepare_query("SELECT email FROM users WHERE userid = ?", $userData['userid']);
		$email = $db->fetchfield();
	}

	if($action=='Send'){
		$error=false;
		if(!isset($name) || $name==""){
			$msgs->addMsg("Please fill in your name");
			$error=true;
		}
		if(!isset($email) || $email=="" || strpos($email,"@")===false){
			$msgs->addMsg("Please fill in your email address");
			$error=true;
		}
		if(!isset($email) || !isValidEmail($email)){
			$error=true;
		}
		if(!isset($subject) || $subject==""){
			$msgs->addMsg("The message has no subject");
			$error=true;
		}
		if(!isset($text) || $text==""){
			$msgs->addMsg("You must put in a message to send");
			$error=true;
		}
		if(!isset($to) || !in_array($to,array_keys($targets))){
			$msgs->addMsg("You must specify a destination");
			$error=true;
		}
		if(!$error){
			if($userData['loggedIn'])
				$text .= "\n------------------------------\n\nUsername: $userData[username]\nName: $name\nEmail: $email";
			else
				$text .= "\n------------------------------\n\nUser not logged in\nName: $name\nEmail: $email";

			smtpmail("$to <$targets[$to]>", $config['contactsubjectPrefix'] . " " . $subject, $text, "From: $name <$email>") or die("Error sending email");

			incHeader();

			echo "Your message has been sent.";

			incFooter();
			exit;
		}
	}


	if(!isset($userData['username'])) $userData['username']="";
	if(!isset($email)) $email="";
	if(!isset($target) || !in_array($target,$targets))
		$target="";


	incHeader();

echo "<b>This is used to contact site Administrators only.  The following will be ignored and deleted: <br><br>

Any questions already answered in the <a class=body href=faq.php>FAQ</a>. <br>
Any complaints about a user being fake or a user using your pictures.  Report this in the <a class=body href=forumthreads.php?fid=39>Fakers forum</a> or report their pictures as fake. <br>
Any complaints about a user harassing you through private messaging, comments or in the forums.  Read the <a class=body href=faq.php>FAQ</a> for information on how to block people and report forum abuses to forum moderators. <br>
Any site suggestions.  Post these in the <a class=body href=forumthreads.php?fid=4>Suggestions forum</a>, which the Administrators regularly read. <br>
Any issues regarding your email service, Internet browser settings or any other programs.  Consult the provider of the product or service for support. <br><br><br>
</b>";

	echo "<table><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=body>Your Name:</td><td><input class=body type=text style=\"width:250px\" size=30 name=name value=\"$userData[username]\"></td></tr>";
	echo "<tr><td class=body>Your Email:</td><td><input class=body type=text style=\"width:250px\" size=30 name=email value=\"$email\"></td></tr>";
	echo "<tr><td class=body>To:</td><td><select class=body style=\"width:250px\" name=to>" . make_select_list_key_key($targets,$target) . "</select></td></tr>";
	echo "<tr><td class=body>Subject:</td><td><input class=body type=text style=\"width:250px\" size=30 name=subject></td></tr>";
	echo "<tr><td colspan=2 class=body><textarea class=body cols=50 rows=8 style=\"width:350px\" name=text></textarea></td></tr>";
	echo "<tr><td></td><td><input class=body type=submit name=action value=Send></td></tr>";
	echo "</form></table>";


	incFooter();
