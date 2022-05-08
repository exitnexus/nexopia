<?
	$forceserver=true;
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
			destroySession($userid, $key);

		$cachedlogin = getPOSTval('cachedlogin', 'bool');
		$lockip = getPOSTval('lockip', 'bool');

		if(login($username, $password, $cachedlogin, $lockip)){
			$referer = getREQval('referer', 'string', '/');

			header("location: $referer");
			exit;
		}
	}


	incHeader();

	echo "<table border=0 cellspacing=0 align=center><form action=$_SERVER[PHP_SELF] method=post target=_top>";
	if($referer = getREQval('referer', 'string', '/'))
		echo "<input type=hidden name=referer value='" . htmlentities($referer) . "'>";
	echo "<tr><td class=body>Username:</td><td class=body><input type=text name=username size=10 style=\"width:93px\"></td></tr>";
	echo "<tr><td class=body>Password:</td><td class=body><input type=password name=password size=10 style=\"width:93px\"></td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('lockip', " Secure this Session", false) . " &nbsp; (<a class=body href=faq.php?q=68> ? </a>)</td></tr>";
	echo "<tr><td class=body colspan=2><table cellspacing=0 cellpadding=0 width=100%><tr><td class=body>" . makeCheckBox('cachedlogin', " Remember Me", false) . "</td>";
	echo "<td class=body align=right><input type=submit value=Login style=\"width=60px\"></td></tr></table></td></tr>";
	echo "</table><table align=center>";
	echo "<tr><td class=body>&nbsp;</td></tr>";
	echo "<tr><td class=body align=center>";
	echo "<a class=body href=create.php>Join</a> | ";
	echo "<a class=body href=lostpass.php>Lost Password</a> | ";
	echo "<a class=body href=lostpass.php>Resend Activation</a> | ";
	echo "<a class=body href=lostpass.php>Activate</a>";
/*
	echo "<input type=button onClick=\"javascript: location.href='create.php'\" value='Join'>";
	echo "<input type=button onClick=\"javascript: location.href='lostpass.php'\" value='Lost Password'>";
	echo "<input type=button onClick=\"javascript: location.href='lostpass.php'\" value='Resend Activation'>";
	echo "<input type=button onClick=\"javascript: location.href='lostpass.php'\" value='Activate'>";
*/
	echo "</td></tr>";

	echo "<tr><td class=body align=center>If you are having trouble logging in, make sure you have cookies enabled, and that your computer's clock is correct.</td></tr>";
	echo "</form></table>";

	incFooter();


