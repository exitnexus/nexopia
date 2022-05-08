<?

	$login=0;

	require_once("include/general.lib.php");

	$targets = $contactemails;

	$name = getPOSTval('name');
	$email = getPOSTval('email', 'string', ($userData['loggedIn'] ? $useraccounts->getEmail($userData['userid']) : ''));
	$subject = getPOSTval('subject');
	$text = getPOSTval('text');
	$to = getPOSTval('to');

	$messageSent = false;

	if($action=='Send'){
		$error=false;

		if(!$name || strlen($name) > 20){
			$msgs->addMsg("Please fill in your name");
			$error=true;
		}
		if(!$email || strpos($email,"@")===false){
			$msgs->addMsg("Please fill in your email address");
			$error=true;
		}
		if(!isValidEmail($email)){
			$error=true;
		}
		if(!$subject){
			$msgs->addMsg("The message has no subject");
			$error=true;
		}
		if(!$text){
			$msgs->addMsg("You must put in a message to send");
			$error=true;
		}
		if(!isset($targets[$to])){
			$msgs->addMsg("You must specify a destination");
			$error=true;
		}
		if(!$error){
			if($userData['loggedIn'])
				$text .= "\n------------------------------\n\nUsername: $userData[username]\nName: $name\nEmail: $email\nUser-Agent: $_SERVER[HTTP_USER_AGENT]";
			else
				$text .= "\n------------------------------\n\nUser not logged in\nName: $name\nEmail: $email";

			smtpmail("$to <$targets[$to]>", $config['contactsubjectPrefix'] . " " . $subject, $text, "From: $name <$email>") or die("Error submitting message. Please try again later.");

			$msgs->addMsg("Thank you, your message has been sent.");
			$messageSent = true;
		}
	}


	if(!isset($targets[$to]))
		$to="";

	$template = new template('contactus/index');
	$template->setMultiple(array(
		'name'			=> $name,
		'email'			=> $email,
		'subject'		=> $subject,
		'text'			=> $text,
		'to'			=> $to,
		'selectOpts'	=> make_select_list_key_key($targets, $to),
		'messageSent'	=> $messageSent
	));

	$template->display();

