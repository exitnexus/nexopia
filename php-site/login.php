<?
	$forceserver=true;
	$login=0;

	require_once("include/general.lib.php");

	if($userData['loggedIn']){
		header("location: /");
		exit;
	}

	if(isset($username) && isset($password)){
		addRefreshHeaders();
		destroySession($userid,$key);

		if(!isset($cachedlogin))
			$cachedlogin=false;

		if(login($username,$password,$cachedlogin)){
			if(empty($referer))
				$referer = "/";
			header("location: $referer");
			exit;
		}
	}


	incHeader();

	echo "<table border=0 cellspacing=0 align=center><form action=$PHP_SELF method=post>";
	if(isset($referer))
		echo "<input type=hidden name=referer value='" . htmlentities($referer) . "'>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username size=10 style=\"width:93px\"></td></tr>";
	echo "<tr><td class=body>Password:</td><td class=body><input class=body type=password name=password size=10 style=\"width:93px\"></td></tr>";
	echo "<tr><td class=body colspan=2><table cellspacing=0 cellpadding=0 width=100%><tr><td class=body><input type=checkbox name=cachedlogin value=y> <label for='cachedlogin'>Remember Me</label></td><td class=body align=right><input class=body type=submit value=Login style=\"width=60px\"></td></tr></table></td></tr>";
	echo "</table><table align=center>";
	echo "<tr><td class=body>&nbsp;</td></tr>";
	echo "<tr><td class=body align=center><input type=button class=body onClick=\"javascript: location.href='create.php'\" value='Join'> <input type=button class=body onClick=\"javascript: location.href='lostpass.php'\" value='Lost Password'> <input type=button class=body onClick=\"javascript: location.href='lostpass.php'\" value='Resend Activation'> <input type=button class=body onClick=\"javascript: location.href='lostpass.php'\" value='Activate'></td></tr>";
	echo "<tr><td class=body align=center>If you are having trouble logging in, make sure you have cookies enabled, and that your computer's clock is correct.</td></tr>";
	echo "</form></table>";

	incFooter();


