<?

	$login = 1;

	require_once("include/general.lib.php");

	if(!in_array($userData['userid'],$debuginfousers))
		die("Permission Denied");


	$key = getPOSTval('key');
	
	if($key){
		$cache->remove($key);
	}


	incHeader();

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<table align=center>";
	echo "<tr><td class=header align=center colspan=2>Delete a memcache key</td></tr>";
	echo "<tr><td class=body>Key:</td><td class=body><input class=body type=text name=key></td></tr>";
	echo "<tr><td class=body align=center colspan=2><input class=body type=submit name=action value=Delete></td></tr>";
	echo "</table>";
	echo "</form>";
	
	incFooter();

