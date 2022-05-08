<?

	$login=-1;

	require_once("include/general.lib.php");

	if(isset($register)){
		if(newAccount($data)){
			incHeader();
			echo "Your account has been created. Expect an email within a few minutes. The instructions on how to activate your account are there.";
			incFooter();
			exit;
		}
	}

	$username="";
	$email="";
	$loc="";
	$month="";
	$day="";
	$year="";
	$sex="";

	if(isset($data))
		extract($data);

	$locations = & new category( $db, "locs");

	incHeader();

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));


	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>\n";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body name='data[username]' maxlength=12 size=32 value='$username' style=\"width:200px\"></td></tr>\n";
	echo "<tr><td class=body>Password:</td><td class=body><input class=body name='data[password]' maxlength=32 size=32 type=password style=\"width:200px\"></td></tr>\n";
	echo "<tr><td class=body>Retype Password:</td><td class=body><input class=body name='data[password2]' maxlength=32 size=32 type=password style=\"width:200px\"></td></tr>\n";
	echo "<tr><td class=body>Email:</td><td class=body><input class=body name='data[email]' maxlength=255 size=32 value='$email' style=\"width:200px\"></td></tr>\n";
	echo "<tr><td class=body valign=top>Birthday:</td><td class=body>";
		echo "<select class=body name='data[month]' style=\"width:90px\"><option value=0>Month" . make_select_list_key($months,$month) . "</select>";
		echo "<select class=body name=data[day] style=\"width:50px\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
		echo "<select class=body name=data[year] style=\"width:60px\"><option value=0>Year" . make_select_list(array_reverse(range(date("Y")-$config['maxAge'],date("Y")-$config['minAge'])),$year) . "</select>";
	echo "</td></tr>\n";
	echo "<tr><td class=body>Location:</td><td class=body><select class=body name=\"data[loc]\" style=\"width:200px\"><option value=0>Location" . makeCatSelect($locations->makeBranch(),$loc) . "</select></td></tr>\n";
	echo "<tr><td class=body>Sex:</td><td class=body>" . make_radio("data[sex]", array("Male","Female"), $sex) . "</td></tr>\n";

	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=body colspan=2><a class=body href=terms.php>Terms and Conditions:</a><br><div style=\"border: solid 1px #000000; width: 500px; height: 150px; overflow: auto; padding: 4px; color: #000000; background-color: #FFFFFF\">\n";
	echo nl2br(getterms());
	echo "\n</div></td></tr>\n";

	echo "<tr><td class=body colspan=2>\n";

	echo makeCheckBox('data[agree18]', ' I, the user, am over the age of 18.') . "<br>\n";
	echo makeCheckBox('data[agree14]', ' If No, I, the user, am over the age of 14.') . "<br>\n";
	echo makeCheckBox('data[agree14guardian]', ' AND I have consent from my legal guardian who is over 18 and accepts these Terms and Conditions.') . "<br>\n";
	echo "<center><b>It is a Fraud to knowingly misstate this information.</b></center>\n";
	echo "<br>\n";
	echo makeCheckBox('data[agreelimit]', ' I, the user, acknowledge and understand that these terms limit my rights and remedies.') . "<br>\n";
	echo makeCheckBox('data[agreeterms]', ' I, the user, read, understand and agree to these Terms and Conditions of Use.') . "<br>\n";

	echo "</td></tr>\n";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=register value=Submit></td></tr>";
	echo "</table>";
	echo "</form>";

	incFooter();

