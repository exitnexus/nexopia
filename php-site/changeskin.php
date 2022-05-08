<?

	$login=0;
	$simplepage = 1;
	$deprecated_skins = array(
		"orange",
		"halloween",
		"newflowers",
		"verypink"
	);

	require_once("include/general.lib.php");

	incHeader(750);

?>
<script>
function chooseSkin(name){
	document.getElementById('newskin').value=name;
	document.getElementById('skinform').submit();
}
</script>
<?

	echo "<form id=skinform action=\"$_SERVER[PHP_SELF]\" method=\"post\" target=\"_top\">";
	echo "<input type=hidden id='newskin' name='newskin' value='$skin'>";


	$i = 0;	
	$cols = 4;
	
	
	echo "<table align=center width=100%>";
	echo "<tr><td class=header align=center colspan=$cols>Choose a Skin</td></tr>";

	if(!$userData['loggedIn'] || $userData['premium']){
		echo "<tr><td class=body colspan=$cols>";
	
		if(!$userData['loggedIn'])
			echo "You must be logged in to save your preferences";
		else
			echo makeCheckBox('newskinframes', 'Use Frames', !($userData['loggedIn'] && $userData['premium'] && $userData['skintype'] == 'normal'));
	
		echo "</td></tr>";
	}

	foreach($skins as $name => $skin){
		if (array_intersect(array($name), $deprecated_skins)) {
			continue;
		}
		if($i % $cols == 0)
			echo "<tr>";
		echo "<td class=body align=center>";
		echo "<a class=body href=\"javascript: chooseSkin('$name');\">$skin</a><br>";
		echo "<a class=body href=\"javascript: chooseSkin('$name');\"><img src='$config[skinloc]$name/thumb.png' border=0></a><br><br>";
		
		if($i % $cols == $cols-1)
			echo "</tr>";
		$i++;
	}
	if($i % $cols != 0)
		echo "</tr>";


	echo "</table>";
	
	echo "</form>";

	incFooter();

