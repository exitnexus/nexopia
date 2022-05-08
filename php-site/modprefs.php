<?

	$login=1;

	require_once("include/general.lib.php");

	$levels = $mods->getModLvl($userData['userid']);

	if(!$levels)
		return false;

	$type = getREQval('type');

	if(empty($type) || !isset($levels[$type])){
//		$type = MOD_PICS;
		$type = key($levels); //should be the first entry in $levels, generally MOD_PICS
	}

	$types = array();
	foreach($levels as $n => $v)
		$types[$n] = $mods->modtypes[$n];

	$prefs = $mods->getModPrefs($userData['userid'], $type);

	if(!empty($action)){
		$autoscroll = (getREQval('autoscroll', 'bool') ? 'y' : 'n');

		$picsperpage = getREQval('picsperpage', 'int', 35);
		if($picsperpage > 100)		$picsperpage = 100;
		if($picsperpage < 10)		$picsperpage = 10;


		$mods->setModPrefs($userData['userid'], $type, $autoscroll, $picsperpage);

		$prefs['picsperpage'] = $picsperpage;
		$prefs['autoscroll'] = $autoscroll;

		$msgs->addMsg("Preferences Updated");
	}

	incHeader();

	echo "<table align=center>";

	if(count($types) > 1){
		echo "<form action=$_SERVER[PHP_SELF]>";
		echo "<tr><td class=header colspan=2 align=center>Mod Type: <select class=body name=type>" . make_select_list_key($types,$type) . "</select><input class=body type=submit name=selecttype value=Go></td></tr>";
		echo "</form>";
	}

	echo "<tr><td class=header colspan=2 align=center>My Stats</td></tr>";

/*	$total = $data['right'] + $data['wrong'];
	echo "<tr><td class=body>Level:</td><td class=body>$data[level]</td></tr>";
	echo "<tr><td class=body>Total:</td><td class=body>$total</td></tr>";
	echo "<tr><td class=body>Right:</td><td class=body>$data[right]</td></tr>";
	echo "<tr><td class=body>Wrong:</td><td class=body>$data[wrong]</td></tr>";
	echo "<tr><td class=body>Too lenient:</td><td class=body>$data[lenient]</td></tr>";
	echo "<tr><td class=body>Too strict:</td><td class=body>$data[strict]</td></tr>";
	echo "<tr><td class=body>Error Rate:</td><td class=body>" . number_format(($total == 0 ? 0 : ((100.0 * $data['wrong']) / ($total))), 2) . "%</td></tr>";
	echo "<tr><td class=body>Active Time:</td><td class=body>" . ($data['time'] == 0 ? "Never" : userDate("D M j, Y G:i:s", $data['time']) ) . "</td></tr>";
*/

	echo "<tr><td class=body>Level:</td><td class=body>$prefs[level]</td></tr>";
	echo "<tr><td class=body>Total:</td><td class=body>{$prefs['total']}</td></tr>";
	echo "<tr><td class=body>Last Modded:</td><td class=body>" . ($prefs['time'] == 0 ? "Never" : userDate("D M j, Y G:i:s", $prefs['time']) ) . "</td></tr>";


	// pic mod stats (pics modded, error rate, strict/leniency hint. given for 3 periods: weekly, monthly, lifetime.
	// also, provides the 5th lowest error rate
	if ($type == MOD_PICS) {
		$modstats = $mods->getPicModStats($userData['userid']);
		$top5 = getTop5ErrRate();

//		echo "<tr><td class=header colspan=2 align=center>Picmod Stats</td></tr>";

		if ($modstats['weekly']['earnedplus'])
			echo "<tr><td class=body colspan=2 align=center><strong>You have earned Plus this week!</strong></td></tr>";

			echo "<tr><td class=body colspan=2 align=center>To rank in the top 5 Pic Mods this month you need<br />to beat a monthly error rate of ${top5}%.</td></tr>";
			
		echo "<tr><td class=body colspan=2><table width='100%'>";
		echo "<tr><td class=body2>&nbsp;</td><td class=body2>This Week</td><td class=body2>This Month</td><td class=body2>Lifetime</td></tr>";
		echo "<tr><td class=body2>Pics Modded</td><td class=body>{$modstats['weekly']['picsmodded']}</td><td class=body>{$modstats['monthly']['picsmodded']}</td><td class=body>{$modstats['lifetime']['picsmodded']}</td></tr>";
		echo "<tr><td class=body2>Error Rate</td><td class=body>{$modstats['weekly']['errrate']} %</td><td class=body>{$modstats['monthly']['errrate']} %</td><td class=body>{$modstats['lifetime']['errrate']} %</td></tr>";
		echo "<tr><td class=body2>Strict/Lenient</td><td class=body>{$modstats['weekly']['lenientOrStrict']}</td><td class=body>{$modstats['monthly']['lenientOrStrict']}</td><td class=body>{$modstats['lifetime']['lenientOrStrict']}</td></tr>";
		echo "</table>";
	}


	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<input type=hidden name=type value=$type>";
	echo "<tr><td class=header colspan=2 align=center>Preferences</td></tr>";
	echo "<tr><td class=body>Auto-Scroll:</td><td class=body><input type=checkbox name=autoscroll" . ($prefs['autoscroll'] == 'y' ? " checked" : "") . "></td></tr>";
	echo "<tr><td class=body>Pics per page:</td><td class=body><input class=body type=test size=3 name=picsperpage value=$prefs[picsperpage]></td></tr>";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=action value=Update></td></tr>";
	echo "</form>";

	echo "<tr><td class=header align=center colspan=2>Helpful Links</td></tr>";
	echo "<tr><td class=body><a class=body href=\"/wiki/ModGuides/PicMods\">Mod Training</td></tr>";
	echo "<tr><td class=body><a class=body href=\"/wiki/ModGuides/CodeofConduct\">Code of Conduct</td></tr>";

	if ($type == MOD_PICS)
		echo "<tr><td class=body><a class=body href=\"/wiki/ModGuides/PicMods/TheBank/\">The Bank</a></td></tr>";
	echo "</table>";

	incFooter();


	function getTop5ErrRate () {
		global $cache, $mods;

		if ( ($top5 = $cache->get("modprefs-stats-top5")) !== false )
			return $top5;

		$top5 = array('100.00');

		$modids = $mods->getMods();
		foreach ($modids as $modid) {
			if ($mods->getModLvl($modid, MOD_PICS) === false)
				continue;

			$modstats = $mods->getPicModStats($modid);

			if ($modstats['monthly']['picsmodded'] == 0)
				continue;

			$addit = false;
			foreach ($top5 as $top) {
				if ($modstats['monthly']['errrate'] < $top && ! in_array($modstats['monthly']['errrate'], $top5)) {
					$addit = true;
					break;
				}
			}

			if ($addit || (! $addit && count($top5) < 5 && ! in_array($modstats['monthly']['errrate'], $top5))) {
				if (count($top5) == 5)
					array_splice($top5, array_search(max($top5), $top5) + 1, 1);
				$top5[] = $modstats['monthly']['errrate'];
			}
		}

		if (count($top5) <= 5)
			$top5 = number_format(max($top5), 2);
		else {
			sort($top5);
			$top5 = number_format($top5[4], 2);
		}

		$cache->put("modprefs-stats-top5", $top5, 60 * 60); // updated once an hour
		return $top5;
	}
