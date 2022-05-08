<?

	$login=0;

	require_once("include/general.lib.php");

	incHeader();

	echo "Your skin choice will only be saved if you login. Certain skins only work for plus accounts.";

	echo "<table><form action=$PHP_SELF method=get>";
	echo "<tr><td class=body>Choose a skin: <select class=body name=newskin>";
	echo make_select_list_col_key($skins,'name',$skin);
	echo "</select><input class=body type=submit name=chooseskin value=Go></td></tr>";
	echo "</form></table>";

	incFooter();
