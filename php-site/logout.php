<?

	$login = 0.5;
	$forceserver = true;

	require_once("include/general.lib.php");

	$k = getREQval('k');
	$referer = getREQval('referer','string',"http://$wwwdomain/");

	if($_SERVER["REQUEST_METHOD"] == "POST" || checkKey($userData['userid'], $k)){
		// This is needed for a rare case where you let your session timeout, click the logout link, and then click the
		//  "Click here to logout link". If a user follows those steps they will try to log out an anonymous user.
		if(isset($userData['userid']) && isset($userData['sessionkey']) && $userData['userid'] > 0)
		{
			$auth->destroySession($userData['userid'], $userData['sessionkey']);

			$auth->logout($userData['userid']);
			
			header("location: $referer");
		}
		else
		{
			incHeader();
			echo "<p class=body>Congratulations! You've successfully logged out.</p>";
			incFooter();
		}
	
	}else{

		incHeader();

		if ($referer != "http://$wwwdomain/")
			echo "<p class=body>You must be logged out to see this page. Click below and you will be logged out and automatically redirected to the page you were trying to reach.</p>";
			
		echo "<a class=body href=/logout.php?k=" . makekey($userData['userid']) . "&referer=" . urlencode($referer) . " target=_top>Click here to logout</a>";

		incFooter();
	}


