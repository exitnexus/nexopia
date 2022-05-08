<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"mailinglist"))
		die("Permission denied");

die("disabled");

	switch($action){
		case "Send":
			$query = "SELECT username,email FROM users";
			$res = $db->query($query);

			while($data = $res->fetchrow()){
				smtpmail("$data[email]", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");
			}

			$msgs->addMsg("Email Sent");
		break;
	}

	incHeader();


	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=2 align=center>Mailing List</td></tr>";

	echo "<tr><td class=body>Subject:</td><td class=body><input class=body type=text name=subject></td></tr>";
	echo "<tr><td class=body valign=top>Message:</td><td class=body><textarea class=body cols=60 rows=10 name=message></textarea>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value=Send></td></tr>";
	echo "</form></table>";

	incFooter();

