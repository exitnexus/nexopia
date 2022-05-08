<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($id))
		die("no id");

	$query = "SELECT * FROM items WHERE id='$id'";
	$result = mysql_query($query);
	$itemdata = mysql_fetch_assoc($result);

	$query = "SELECT * FROM itemcompatibility WHERE itemid='$id'";
	$result = mysql_query($query);


	incHeader();


	echo "<table><tr>";

	if($itemdata['picup']=='y')
		echo "<td><img src=$config[itemdir]$itemdata[id].jpg></td>";

	echo "<td>";
	print_r($itemdata);


	echo "</td></tr></table>";





	echo "<form action=/cart.php>";
	echo "<input type=hidden name=id value=$itemdata[id]>";
	echo "<input class=body type=text size=3 name=qty value=1>";
	echo "<input class=body type=submit name=action value=Add>";
	echo "</form>";


	incFooter();
