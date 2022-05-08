<?

	$forceserver=true;
	$login=-1;

	require_once("include/general.lib.php");
	
	$username = getREQval('username');
	$actkey = getREQval('actkey');

	$activationSuccess = false;

	if($username && $actkey){
		$userid = getUserID($username);
	
		if($userid && $useraccounts->activate($userid, $actkey))
			$activationSuccess = true;
		else
			$msgs->addMsg("Activation error. Make sure you typed your username and activation key correctly.");
	}

	$template = new template('activations/activateonly');
	$template->set('activationSuccess', $activationSuccess);
	$template->display();

