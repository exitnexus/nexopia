<?

	$login=1;

	require_once("include/general.lib.php");

	$levels = $mods->getModLvl($userData['userid']);

	if(!$levels)
		return false;

	if(empty($type) || !isset($levels[$type]))
		$type = MOD_PICS;

	$types = array();
	foreach($levels as $n => $v)
		$types[$n] = $mods->modtypes[$n];

	$prefs = $mods->getModPrefs($userData['userid'], $type);

	if(!empty($action)){
		if(isset($autoscroll))	$autoscroll = 'y';
		else					$autoscroll = 'n';

		if(empty($picsperpage))			$picsperpage = 35;
		if($picsperpage > 60)			$picsperpage = 60;
		if($picsperpage < 10)			$picsperpage = 10;


		$mods->setModPrefs($userid, $type, $autoscroll, $picsperpage);

		$prefs['picsperpage'] = $picsperpage;
		$prefs['autoscroll'] = $autoscroll;

		$msgs->addMsg("Preferences Updated");
	}

	incHeader();

	echo "<table align=center>";

	if(count($types) > 1){
		echo "<form action=$PHP_SELF>";
		echo "<tr><td class=header colspan=2 align=center>Mod Type: <select class=body name=type>" . make_select_list_key($types,$type) . "</select><input class=body type=submit name=selecttype value=Go></td></tr>";
		echo "</form>";
	}

/*	$total = $data['right'] + $data['wrong'];
	echo "<tr><td class=header colspan=2 align=center>Stats</td></tr>";
	echo "<tr><td class=body>Level:</td><td class=body>$data[level]</td></tr>";
	echo "<tr><td class=body>Total:</td><td class=body>$total</td></tr>";
	echo "<tr><td class=body>Right:</td><td class=body>$data[right]</td></tr>";
	echo "<tr><td class=body>Wrong:</td><td class=body>$data[wrong]</td></tr>";
	echo "<tr><td class=body>Too lenient:</td><td class=body>$data[lenient]</td></tr>";
	echo "<tr><td class=body>Too strict:</td><td class=body>$data[strict]</td></tr>";
	echo "<tr><td class=body>Error Rate:</td><td class=body>" . number_format(($total == 0 ? 0 : ((100.0 * $data['wrong']) / ($total))), 2) . "%</td></tr>";
	echo "<tr><td class=body>Active Time:</td><td class=body>" . ($data['time'] == 0 ? "Never" : userDate("D M j, Y G:i:s", $data['time']) ) . "</td></tr>";
*/

	echo "<tr><td class=header colspan=2 align=center>Stats</td></tr>";
	echo "<tr><td class=body>Level:</td><td class=body>$prefs[level]</td></tr>";
	echo "<tr><td class=body>Total:</td><td class=body>$prefs[total]</td></tr>";
	echo "<tr><td class=body>Active Time:</td><td class=body>" . ($prefs['time'] == 0 ? "Never" : userDate("D M j, Y G:i:s", $prefs['time']) ) . "</td></tr>";

	echo "<form action=$PHP_SELF>";
	echo "<input type=hidden name=type value=$type>";
	echo "<tr><td class=header colspan=2 align=center>Prefs</td></tr>";
	echo "<tr><td class=body>Auto-Scroll:</td><td class=body><input type=checkbox name=autoscroll" . ($prefs['autoscroll'] == 'y' ? " checked" : "") . "></td></tr>";
	echo "<tr><td class=body>Pics per page:</td><td class=body><input class=body type=test size=3 name=picsperpage value=$prefs[picsperpage]></td></tr>";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Update></td></tr>";
	echo "</form>";

	echo "<tr><td class=header align=center colspan=2>Links:</td></tr>";
	echo "<tr><td class=body><a class=body href=/mod/>Mod Training</td></tr>";
	echo "</table>";

	incFooter();

