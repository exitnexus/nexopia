<?

	$forceserver=true;
	$login=-1;

	require_once("include/general.lib.php");

	if(isset($username) && isset($actkey)){
		if(activateAccount($username,$actkey)){
			incHeader();
			echo "Activation complete.";
			incFooter();
			exit;
		}else{
			$msgs->addMsg("Activation error.");
		}
	}

	incHeader();

	echo "<table><form action=$PHP_SELF>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=username></td></tr>";
	echo "<tr><td class=body>Activation Key:</td><td class=body><input class=body type=text name=actkey></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Activate></td></tr>";
	echo "</form></table>";

	incFooter();
