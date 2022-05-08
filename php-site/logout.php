<?

	$login = 1;

	require_once("include/general.lib.php");

	$k = getREQval('k');

	if(checkKey($userData['userid'], $k)){

		$auth->destroySession($userData['userid'], $userData['sessionkey']);

		$auth->logout($userData['userid']);

		header("location: /");
	}else{

		incHeader();

		echo "<a class=body href=/logout.php?k=" . makekey($userData['userid']) . ">Click here to logout</a>";

		incFooter();
	}


