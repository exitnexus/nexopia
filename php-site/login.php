<?
//	$forceserver=true;
	$login=0;

	require_once("include/general.lib.php");

	if($userData['halfLoggedIn']){
		header("location: /");
		exit;
	}

	if(($username = getPOSTval('username')) && ($password = getPOSTval('password'))){
		$username = substr(str_replace(" ", "", $username), 0, 20);

	        $limit = $cache->get("loginratelimit-$username");

		if($limit){
			$cache->put("loginratelimit-$username", 1, 5); //block for another 5 seconds
		}else{
			addRefreshHeaders();
			if(($userid = getCOOKIEval('userid', 'int')) && ($key = getCOOKIEval('key')))
				$auth->destroySession($userid, $key);

			$cachedlogin = getPOSTval('cachedlogin', 'bool');
			$lockip = getPOSTval('lockip', 'bool');

			if($auth->login($username, $password, $cachedlogin, $lockip)){
				$referer = getREQval('referer', 'string', '/');

				if (!getPOSTval('skip_redirect', 'bool'))
					header("location: $referer");
				exit;
			}

			$cache->put("loginratelimit-$username", 1, 3); //block spam
		}
	}

	$template = new template('login/login');
	$template->set('checkSecure', makeCheckBox('lockip', " Secure this Session", true));
	$template->set('checkRememberMe', makeCheckBox('cachedlogin', " Remember Me", false));
	$template->set('referer', getREQval('referer', 'string', '/'));
	$template->display();
