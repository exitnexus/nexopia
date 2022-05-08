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

	$locations = & new category("locs");

	incHeader();

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));


	echo "<form action=$PHP_SELF method=post>";
	echo "<table align=center>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body name='data[username]' maxlength=32 size=32 value='$username'></td></tr>";
	echo "<tr><td class=body>Password:</td><td class=body><input class=body name='data[password]' maxlength=32 size=32 type=password></td></tr>";
	echo "<tr><td class=body>Retype Password:</td><td class=body><input class=body name='data[password2]' maxlength=32 size=32 type=password></td></tr>";
	echo "<tr><td class=body>Email:</td><td class=body><input class=body name='data[email]' maxlength=255 size=32 value='$email'></td></tr>";
	echo "<tr><td class=body>Location:</td><td class=body><select class=body name=\"data[loc]\"><option value=0>Location" . makeCatSelect($locations->makeBranch(),$loc) . "</select></td></tr>";
	echo "<tr><td class=body>Birthday:</td><td class=body><select class=body name='data[month]'><option value=0>Month" . make_select_list_key($months,$month) . "</select>";
		echo "<select class=body name=data[day]><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
		echo "<select class=body name=data[year]><option value=0>Year" . make_select_list(array_reverse(range(date("Y")-$config['maxAge'],date("Y")-$config['minAge'])),$year) . "</select></td></tr>";
	echo "<tr><td class=body>Sex:</td><td class=body><input class=body type=radio name='data[sex]' value=Male" . ($sex=="Male" ? " checked" : "") . ">Male ";
		echo "<input class=body type=radio name='data[sex]' value='Female'" . ($sex=="Female" ? " checked" : "") . ">Female</td></tr>";
	echo "<tr><td class=body colspan=2><input type=checkbox name=data[agree]> I agree to the <a class=body href=terms.php>Terms and Conditions</a></td></tr>";
	echo "<tr><td class=body colspan=2>Multiple accounts are not allowed</td></tr>";

	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=register value=Submit></td></tr>";
	echo "</table>";
	echo "</form>";

	incFooter();
