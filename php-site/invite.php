<?

	$login = 1;
	$userprefs = array("email");

	include_once("include/general.lib.php");

	if($action == "Send"){

		$subject = "Invitation to Nexopia.com";
		$message =
"Your friend $myname would like to invite you to join Nexopia.com. The reason given is:

$msg

Check it out here: http://www.nexopia.com
You can see " . ($userData['sex'] == 'Male' ? 'his' : 'her') . " profile here: http://www.nexopia.com/profile.php?uid=$userData[userid]
You may join here: http://www.nexopia.com/create.php";

		smtpmail("$friendname <$friendemail>", $subject, $message, "From: $myname <$myemail>") or die("Error sending email");

		incHeader();

		echo "An invitation has been sent to $friendname.";

		incFooter();
		exit;
	}


	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header align=center colspan=2>Refer a Friend</td></tr>";

	echo "<tr><td class=body>Your Name:</td><td class=body><input class=body type=text name=myname value='$userData[username]'></td></tr>";
	echo "<tr><td class=body>Your Email:</td><td class=body><input class=body type=text name=myemail value='$userData[email]'></td></tr>";
	echo "<tr><td class=body>Friends Name:</td><td class=body><input class=body type=text name=friendname></td></tr>";
	echo "<tr><td class=body>Friends Email:</td><td class=body><input class=body type=text name=friendemail></td></tr>";

	echo "<tr><td class=body colspan=2>Message:<br><textarea class=body cols=50 rows=6 name=msg></textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Send></td></tr>";

	echo "</form></table>";

	incFooter();

