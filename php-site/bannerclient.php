<?

	$login=1;

	require_once("include/general.lib.php");

	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");

	$scope = array('Active','Moded','All');

	$banner->db->prepare_query("SELECT id FROM bannerclients WHERE userid = ?" , $userData['userid']);

	if($banner->db->numrows()==0)
		die("You are not a banner client");
	else
		$clientid = $banner->db->fetchfield();

	if($action == "bannerstats"){
		if(empty($interval))
			$interval = 3600;
		if(empty($month) || empty($day) || empty($year))
			$start = time() - $interval*30;
		else
			$start = mktime(0,0,0,$month,$day,$year);

		bannerStats($id, $interval, $start);	//exit
	}

	listBanners($clientid);  //exit


function listBanners($clientid){
	global $banner;

	$where = array("clientid = #");
	$params = array($clientid);

	$banner->db->prepare_array_query("SELECT * FROM banners WHERE " . implode(" && ", $where), $params);

	$banners = array();
	while($line = $banner->db->fetchrow())
		$banners[] = $line;

	sortCols($banners, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_STRING, 'bannersize');

	incHeader();

	echo "<table align=center>";

	$cols = 15;

	echo "<tr>";
	echo "<td class=header>Title</td>";
	echo "<td class=header>Size</td>";
	echo "<td class=header>Start</td>";
	echo "<td class=header>End</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Clickthru</td>";
	echo "<td class=header></td>";
	echo "</tr>";

	$classes = array('body','body2');
	$i=1;

	foreach($banners as $line){
		$i = !$i;
		echo "<tr>";
		echo "<td class=$classes[$i]>$line[title]</td>";
		echo "<td class=$classes[$i]>" . $banner->sizes[$line['bannersize']] . "</td>";
		echo "<td class=$classes[$i]>" . ($line['startdate'] > 0 ? userdate("M j, Y", $line['startdate']) : "Started") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['enddate'] > 0 ? userdate("M j, Y", $line['enddate']) : "Never")  . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($line['views']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] ? number_format($line['clicks']) : "N/A") . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] && $line['views'] ? number_format($line['clicks']/$line['views']*100, 3) . "%" : "N/A") . "</td>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=bannerstats&id=$line[id]>Stats</a></td>";

		echo "</tr>";
	}
	echo "</table>";

	incFooter();
	exit;
}


function bannerStats($id){
	global $banner;

	$interval = 86400;

	$banner->db->prepare_query("SELECT time, views, clicks FROM bannerstats WHERE bannerid = ? ORDER BY time", $id);

	$stats = array();
	while($line = $banner->db->fetchrow())
		$stats[] = $line;

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";

	echo "<tr>";
	echo "<td class=header>Time</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Click Thru</td>";
	echo "</tr>";

	$totalviews = 0;
	$totalclicks = 0;

	$periodviews = 0;
	$periodclicks = 0;

	$periodstart = "";

	foreach($stats as $line){

		$periodviews += $line['views'];
		$periodclicks += $line['clicks'];

		$totalviews += $line['views'];
		$totalclicks += $line['clicks'];

		if(userDate("M j, Y", $line['time']) != $periodstart){
			echo "<tr>";
			echo "<td class=body>" . userDate("M j, Y", $line['time']) . "</td>";
			echo "<td class=body align=right>" . number_format($periodviews) . "</td>";
			echo "<td class=body align=right>" . number_format($periodclicks) . "</td>";
			echo "<td class=body align=right>" . ($periodviews ? number_format(100*$periodclicks / $periodviews,3) . "%" : "N/A" ) ."</td>";
			echo "</tr>";

			$periodviews = 0;
			$periodclicks = 0;
			$periodstart = userDate("M j, Y", $line['time']);
		}
	}

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header align=right>$totalviews</td>";
	echo "<td class=header align=right>$totalclicks</td>";
	echo "<td class=header align=right>" . ($totalviews ? number_format(100*$totalclicks / $totalviews,3) . "%" : "N/A" ) . "</td>";
	echo "</tr>";

	echo "</table>";

	incFooter();
	exit;
}

