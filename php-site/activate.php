<?

	$forceserver = true;
	$login = 0;

	require_once("include/general.lib.php");

	$username = getREQval('username');
	$actkey = getREQval('actkey');

	if($username && $actkey){
		$uid = getUserID($username);

		$activated = ($uid && $useraccounts->activate($uid, $actkey));
	}

	incHeader();

	if(isset($activated)){
		if($activated){
			if($userData['loggedIn']){
				if($userData['userid'] == $uid){
					echo "Email change complete.";
				}else{
					echo "Activation complete. You must logout before you can login to your new account.";
				}
			}else{
				echo "Activation complete. You may now <a class=body href=/login.php>Login</a> to your new account.";
			}
		}else{
			echo "Activation error. Make sure you typed your username and activation key correctly.";
		}
	}else{
echo <<<END
	<table><form action="$_SERVER[PHP_SELF]">
		<tr><td class="body">Username:</td><td class="body"><input class="body" type="text" name="username"></td></tr>
		<tr><td class="body">Activation Key:</td><td class="body"><input class="body" type="text" name="actkey"></td></tr>
		<tr><td class="body"></td><td class="body"><input class="body" type="submit" value="Activate"></td></tr>
	</form></table>
END;
	}

	incFooter();


