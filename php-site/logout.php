<?

	$login = 0.5;

	require_once("include/general.lib.php");

	$k = getREQval('k');
	$referer = getREQval('referer','string',"http://$wwwdomain/");

	if($_SERVER["REQUEST_METHOD"] == "POST" || checkKey($userData['userid'], $k)){

		$auth->destroySession($userData['userid'], $userData['sessionkey']);

		$auth->logout($userData['userid']);

		header("location: $referer");
	}else{

		incHeader();

		if ($referer != "http://$wwwdomain/")
			echo "<p class=body>You must be logged out to see this page. Click below and you will be logged out and automatically redirected to the page you were trying to reach.</p>";
			
		echo "<a class=body href=/logout.php?k=" . makekey($userData['userid']) . "&referer=" . urlencode($referer) . " target=_top>Click here to logout</a>";

		incFooter();
	}


