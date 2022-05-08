<?
//	$forceserver=true;
	$login=0;

	require_once("include/general.lib.php");

	if($userData['loggedIn']){
		header("location: /");
		exit;
	}

/*	$msgs->addMsg("req " . var_export($_REQUEST, true));
	$msgs->addMsg("post " . var_export($_POST, true));
	$msgs->addMsg(getPOSTval('username') . " : " . getPOSTval('password'));
	$msgs->addMsg("$username : $password");
*/
	if(($username = getPOSTval('username')) && ($password = getPOSTval('password'))){
		addRefreshHeaders();
		if(($userid = getCOOKIEval('userid', 'int')) && ($key = getCOOKIEval('key')))
			$auth->destroySession($userid, $key);

		$cachedlogin = getPOSTval('cachedlogin', 'bool');
		$lockip = getPOSTval('lockip', 'bool');

		if($auth->login($username, $password, $cachedlogin, $lockip)){
			$referer = getREQval('referer', 'string', '/');

			header("location: $referer");
			exit;
		}
	}

	$template = new template('login/login');
	$template->set('checkSecure', makeCheckBox('lockip', " Secure this Session", false));
	$template->set('checkRememberMe', makeCheckBox('cachedlogin', " Remember Me", false));
	$template->set('referer', getREQval('referer', 'string', '/'));
	$template->display();
