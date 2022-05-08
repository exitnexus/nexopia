<?

	$forceserver=true;
	$login=-1;

	require_once("include/general.lib.php");

	if(($username = getREQval('username')) && ($actkey = getREQval('actkey'))){
		$uid = getUserID($username);
		if($uid && activateAccount($uid, $actkey)){
			incHeader();
			echo "Activation complete. You may now <a class=body href=login.php>Login</a> to your new account.";
			incFooter();
			exit;
		}else{
			$msgs->addMsg("Activation error. Make sure you typed your username and activation key correctly.");
		}
	}

	incHeader();

	echo "<table><form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Activation Key:</td><td class=body><input class=body type=text name=actkey></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Activate></td></tr>";
	echo "</form></table>";

	incFooter();

