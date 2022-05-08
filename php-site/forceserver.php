<?

	if(!isset($forceserver))
		$forceserver = false;

	if(isset($action)){
		setcookie("forceserver", isset($force));
		$forceserver = isset($force);
	}

	echo "<form action=$PHP_SELF>";
	echo "<input type=checkbox name=force " . ($forceserver ? " checked" : "") . "> Force Server ? <input type=submit name=action value=Update></form>";
