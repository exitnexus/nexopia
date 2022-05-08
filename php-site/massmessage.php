<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'listusers');

	if(!$isAdmin)
		die("Permission denied");


	if($action == "Send Message"){

		$to = trim(getPOSTval('to'));
		$subject = getPOSTval('subject');
		$msg = getPOSTval('msg');

		if($to && $subject && $msg){
			$usernames = preg_split("/[\s]+/", $to);
	
			$uids = getUserID($usernames);
		
	
			$messaging->deliverMsg($uids, $subject, $msg);
	
	
			incHeader();
	
			echo "Message Sent";
	
			incFooter();
			exit;
		}
	}

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF] method=post name=editbox>\n";
	echo "<tr><td class=body valign=top><b>Space</b> separated<br>list of users</td><td class=body><textarea class=body cols=50 rows=3 style=\"width:300\" name=to></textarea></td></tr>\n";
	echo "<tr><td class=body>Subject: </td><td class=body><input class=body type=text name=subject style=\"width:300\" maxlength=64></td></tr>\n";
	echo "<tr><td class=body colspan=2>";

	editbox("");

	echo "</td></tr>\n";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action accesskey='s' value=\"Send Message\"></td></tr>\n";

	echo "</form></table>";

	incFooter();

