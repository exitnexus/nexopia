<?
//	$forceserver=true;
	$login=0;

	require_once("include/general.lib.php");

	if($userData['loggedIn']) {
		$referer = getREQval('referer', 'string', '/');
		$nreferer = urldecode($referer);
		header("location: $nreferer");
		exit;
	}

	if($userData['halfLoggedIn']){
		header("location: /");
		exit;
	}

	if(($username = getPOSTval('login_username')) && ($password = getPOSTval('login_password'))){
		$username = substr(str_replace(" ", "", $username), 0, 20);

		$limit = $cache->get("loginratelimit-$username");

		if($limit){
			$cache->put("loginratelimit-$username", 1, 5); //block for another 5 seconds
		}else{
			addRefreshHeaders();
			if($cookie = getCOOKIEval('sessionkey')){
				list($userid, $key) = explode(':', $cookie, 2);
				$auth->destroySession($userid, $key);
			}

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
	
	//http redirect to the new login page in ruby-site	
	header("HTTP/1.1 301 Moved Permanently");
	$redirect_string = "/account/login";
	if(getREQval('referer', 'string', '/') != '/')
	{
		$redirect_string = $redirect_string . "?referer=" . getREQval('referer', 'string', '/');
	}
	header("Location: http://". $wwwdomain . $redirect_string);
	exit;
	
	$template = new template('login/login');
	$template->set('checkSecure', makeCheckBox('lockip', " Secure this Session", true));
	$template->set('checkRememberMe', makeCheckBox('cachedlogin', " Remember Me", false));
	$template->set('referer', getREQval('referer', 'string', '/'));
	$template->display();
