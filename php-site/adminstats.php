<?

	$login=1;

	require_once("include/general.lib.php");

	if(!$mods->isadmin($userData['userid'],"stats"))
		die("Permission denied");


	$selectable=array(	'hitStats' => "Hit Stats",
						'ipStats' => "IP Stats",
						'activityStats' => "Activity Stats",
						'userStats' => "User Stats",
						'hitstoday' => "Hits Today",
						'votestats' => "Vote Stats",
						'forumstats' => "Forum Stats",
						'skinstats' => "Skin Stats",
						'usersbyagesex' => "Users By Age/Sex",
						'usersperloc' => "Users By location",
						'usersByLocRecur' => "Users By Location Recursive",
//						'hitsperweek' => "Hits per week",
//						'usersperweek' => "New Users per week",
						);

	if($mods->isAdmin($userData['userid'], "viewinvoice"))
		$selectable['plususersbyage'] = "Plus Users by Age";

	$selects = array();


	$select = getPOSTval('select', 'array', array('hitStats','userStats','hitstoday'));

	foreach($select as $v)
		if(isset($selectable[$v]))
			$selects[] = $v;

	$mods->adminlog("view stats","View Site Stats: " . implode(",", $selects));

	incHeader(750);

	echo "<table width=100%>";

	echo "<form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr><td class=header colspan=2 align=center>";
	echo "<select class=body name=select[]>";// size=5 multiple=multiple>";
	foreach($selectable as $k => $v){
		echo "<option value='$k'";
		if(in_array($k,$select))
			echo " selected";
		echo ">$v";
	}
	echo "</select>";
	echo "<input class=body type=submit value=Go>";
	echo "</td></tr>";
	echo "</form>\n";



	foreach($selects as $func)
		$func();


echo "<tr><td colspan=2 class=header align=center>General</td></tr>";
	echo "<tr><td class=body>Uptime</td><td class=body>" . exec("uptime") . "</td></tr>";

	echo "</table>\n";


	incFooter();


function hitStats(){
	global $db, $statsdb;
	$statsdb->query("SELECT * FROM stats");

	$hits = $statsdb->fetchrow();

	echo "<tr><td colspan=2 align=center class=header>Distribution of hits</td></tr>";
	echo "<tr><td class=body>Total hits</td><td class=body>" . number_format($hits['hitstotal']) ."</td></tr>";
	echo "<tr><td class=body>Anonymous hits</td><td class=body>" . number_format($hits['hitsanon']) ."</td></tr>";
	echo "<tr><td class=body>Logged In Users</td><td class=body>" .number_format($hits['hitsMale'] + $hits['hitsFemale']) ."</td></tr>";
	echo "<tr><td class=body>Male</td><td class=body>" . number_format($hits['hitsMale']) . "</td></tr>";
	echo "<tr><td class=body>Female</td><td class=body>" . number_format($hits['hitsFemale']) ."</td></tr>";
}

function ipStats(){
	global $logdb;

	$time = time();

	if(isset($logdb->backupdb))
		$ipdb = & $logdb->backupdb;
	else
		$ipdb = & $logdb;

	echo "<tr><td colspan=2 align=center class=header>IP stats</td></tr>";

	$ipdb->prepare_query("SELECT count(*) FROM iplog WHERE time > ?", ($time - 3600));
	$ips = $ipdb->fetchfield();
	echo "<tr><td class=body>Unique ips active in the past hour</td><td class=body>" . number_format($ips) . "</td></tr>";

	$ipdb->prepare_query("SELECT count(*) FROM iplog WHERE time > ?", ($time - 86400));
	$ips = $ipdb->fetchfield();
	echo "<tr><td class=body>Unique ips active in the past day</td><td class=body>" . number_format($ips) . "</td></tr>";

	$ipdb->prepare_query("SELECT count(*) FROM iplog WHERE time > ?", ($time - (86400*7)));
	$ips = $ipdb->fetchfield();
	echo "<tr><td class=body>Unique ips active in the past week</td><td class=body>" . number_format($ips) . "</td></tr>";

	$ipdb->prepare_query("SELECT count(*) FROM iplog WHERE time > ?", ($time - (86400*14)));
	$ips = $ipdb->fetchfield();
	echo "<tr><td class=body>Unique ips active in the past two weeks</td><td class=body>" . number_format($ips) . "</td></tr>";

	$ipdb->prepare_query("SELECT count(*) FROM iplog WHERE time > ?", ($time - (86400*30)));
	$ips = $ipdb->fetchfield();
	echo "<tr><td class=body>Unique ips active in the past month</td><td class=body>" . number_format($ips) . "</td></tr>";

	$ipdb->query("SELECT count(*) FROM iplog");
	$ips = $ipdb->fetchfield();
	echo "<tr><td class=body>Unique ips logged</td><td class=body>" . number_format($ips) . "</td></tr>";
}

function activityStats(){
	global $db;

	$time = time();

	if(isset($db->backupdb))
		$thisdb = & $db->backupdb;
	else
		$thisdb = & $db;

	echo "<tr><td colspan=2 align=center class=header>Activity stats</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 3600));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past hour</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past day</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400*3));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past 3 days</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400*7));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past week</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400*14));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past 2 weeks</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400*30));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past month</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400*90));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past 3 months</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400*180));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past 6 months</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->prepare_query("SELECT count(*) FROM users WHERE activetime >= ?", ($time - 86400*365));
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts active in the past year</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->query("SELECT count(*) FROM users WHERE activated = 'y'");
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Activated Accounts total</td><td class=body>" . number_format($ips) . "</td></tr>";

	$thisdb->query("SELECT count(*) FROM users");
	$ips = $thisdb->fetchfield();
	echo "<tr><td class=body>Accounts total</td><td class=body>" . number_format($ips) . "</td></tr>";
}

function userStats(){
	global $db, $statsdb;

	$query = "SELECT age, sex, total, active FROM agesexgroups WHERE age < 40";
	$result = $db->unbuffered_query($query);

	$total = array("Male" => 0, "Female" => 0);
	$num = array("Male" => 0, "Female" => 0);
	$active = array("Male" => 0, "Female" => 0);

	while($line = $db->fetchrow($result)){
		$total[$line['sex']] += $line['total']*$line['age'];
		$num[$line['sex']] += $line['total'];
		$active[$line['sex']] += $line['active'];
	}

echo "<tr><td colspan=2 align=center class=header>User Stats</td></tr>";
	echo "<tr><td class=body>Number of Male</td><td class=body>". number_format($num['Male']) . " - " . number_format(100*$num['Male']/($num['Male']+$num['Female']), 2) . "%</td></tr>";
	echo "<tr><td class=body>Number of Female</td><td class=body>". number_format($num['Female']) . " - " . number_format(100*$num['Female']/($num['Male']+$num['Female']), 2) . "%</td></tr>";
	echo "<tr><td class=body>Active Male</td><td class=body>". number_format($active['Male']) . " - " . number_format(100*$active['Male']/$num['Male'], 2) . "%</td></tr>";
	echo "<tr><td class=body>Active Female</td><td class=body>". number_format($active['Female']) . " - " . number_format(100*$active['Female']/$num['Female'], 2) . "%</td></tr>";

	echo "<tr><td class=body>Average Age</td><td class=body>" . number_format(($total['Male']+$total['Female'])/($num['Male']+$num['Female']),2) . "</td></tr>";
	echo "<tr><td class=body>Average Age Male</td><td class=body>" . number_format($total['Male']/$num['Male'],2) . "</td></tr>";
	echo "<tr><td class=body>Average Age Female</td><td class=body>" . number_format($total['Female']/$num['Female'],2) . "</td></tr>";

	$query = "SELECT age, count(*) as count FROM usersearch WHERE active = 2 && age < 40 GROUP BY age";
	$result = $db->unbuffered_query($query);

	$total=0;
	$num=0;
	while($line = $db->fetchrow($result)){
		$total+=$line['age']*$line['count'];
		$num+=$line['count'];
	}

	echo "<tr><td class=body>Average Age Online</td><td class=body>" . number_format($total/$num,2) . "</td></tr>";

	$statsdb->query("SELECT onlineusersmax FROM stats");
	$num = $statsdb->fetchfield();

	echo "<tr><td class=body>Max Users Online at a time</td><td class=body>" . number_format($num) ."</td></tr>";

	$next = $db->nextAuto("users");

	$db->prepare_query("SELECT count(*) FROM users WHERE jointime >= ? && userid >= ?", (time()-86400), round($next*0.98)); //only check the last 5% of users
	$numnew = $db->fetchfield();

	echo "<tr><td class=body>New accounts in the past day</td><td class=body>" . number_format($numnew) . "</td></tr>";
}

function votestats(){
	global $db,$config;

	$query = "SELECT vote,count(*) AS count FROM votehist WHERE blocked='n' GROUP BY vote";
	$result= $db->query($query);

echo "<tr><td colspan=2 class=header align=center>Counted Vote Distribution</td></tr>";
	echo "<tr><td class=body>Score</td><td class=body>Votes</td></tr>";
	$total=0;
	$num=0;
	while($line=$db->fetchrow($result)){
		echo "<tr><td class=body>$line[vote]</td><td class=body>" . number_format($line['count']) . "</td></tr>";
		$total+=$line['count']*$line['vote'];
		$num+=$line['count'];
	}
	$average = ($num==0? 0 : $total/$num);
	echo "<tr><td class=body>Average Vote</td><td class=body>" . number_format($average,3) . "</td></tr>";


	$query = "SELECT vote,count(*) FROM votehist WHERE blocked='y' GROUP BY vote";
	$result= $db->query($query);

echo "<tr><td colspan=2 class=header align=center>Blocked Vote Distribution</td></tr>";
	echo "<tr><td class=body>Score</td><td class=body>Votes</td></tr>";
	while($line=$db->fetchrow($result)){
		echo "<tr>";
		foreach($line as $value)
			echo "<td class=body>" . number_format($value) . "</td>";
		echo "</tr>";
	}

echo "<tr><td colspan=2 class=header align=center>Vote Stats</td></tr>";

	$query = "SELECT count(*) AS count FROM votehist";
	$result = $db->query($query);
	$line = $db->fetchrow($result);
	$total = $line['count'];

	echo "<tr><td class=body>Total Votes</td><td class=body>" . number_format($total) . "</td></tr>";


	$query = "SELECT count(*) AS count FROM votehist GROUP BY userid";
	$result = $db->query($query);
	$total=0;
	$totalOverMin=0;
	$maxVoteUser=0;
	$num=0;
	while($line = $db->fetchrow($result)){
		$total+=$line['count'];
		if($line['count']>$config['minVotesToBlock'])
			$totalOverMin++;
		if($line['count']>$maxVoteUser)
			$maxVoteUser=$line['count'];
		$num++;
	}
	$average = ($num==0 ? 0 : $total/$num);
	echo "<tr><td class=body>Unique Voters</td><td class=body>" . number_format($num) . "</td></tr>";
	echo "<tr><td class=body>Voters over $config[minVotesToBlock] votes</td><td class=body>" . number_format($totalOverMin) . "</td></tr>";
	echo "<tr><td class=body>Votes by max voting user</td><td class=body>" . number_format($maxVoteUser) . "</td></tr>";
	echo "<tr><td class=body>Average Votes per Voter</td><td class=body>" . number_format($average,3) . "</td></tr>";

/*
	$totalVS =0;
	$totalS = 0;
	$totalV = 0;
	$query = "SELECT votes,score FROM pics";
	$result = $db->unbuffered_query($query);
	for($i=0;$line = $db->fetchrow($result);$i++){
		$totalVS+=$line['score']*$line['votes'];
		$totalS +=$line['score'];
		$totalV +=$line['votes'];
	}

echo "<tr><td colspan=2 class=header align=center>Average</td></tr>";
	echo "<tr><td class=body>Average Vote</td><td class=body>" . number_format($totalVS/$totalV,3) . "</td></tr>";
	echo "<tr><td class=body>Total Votes</td><td class=body>" . number_format($totalV) . "</td></tr>";
	echo "<tr><td class=body>Number of Pics</td><td class=body>" . number_format($i) . "</td></tr>";

*/

}



function forumstats(){
	global $forums, $db;

	echo "<tr><td class=header align=center colspan=2>Forum Stats</td></tr>";

	$forums->db->prepare_query("SELECT count(*) as total, count(DISTINCT authorid) as users FROM forumposts WHERE time >= ?", time() - 86400);
	$line = $forums->db->fetchrow();

	echo "<tr><td class=body>Number of posts today:</td><td class=body>$line[total]</td></tr>";

	$forums->db->prepare_query("SELECT count(*) FROM forumthreads WHERE time >= ?", time() - 86400);
	$num = $forums->db->fetchfield();

	echo "<tr><td class=body>Threads with new posts today:</td><td class=body>$num</td></tr>";

	$forums->db->prepare_query("SELECT count(DISTINCT userid) FROM forumread WHERE readtime >= ?", time() - 86400);
	$num = $forums->db->fetchfield();

	echo "<tr><td class=body>Users reading the forums today:</td><td class=body>$num</td></tr>";

	echo "<tr><td class=body>Users posting in the forums today:</td><td class=body>$line[users]</td></tr>";


	echo "<tr><td class=header align=center colspan=2>User Posting Stats</td></tr>";

	$vals = array(1,10,20,50,100,200,500,1000,2000,5000,10000);

	foreach($vals as $val){
		$db->prepare_query("SELECT count(*) FROM users WHERE posts >= ?", $val);
		$num = $db->fetchfield();

		echo "<tr><td class=body>Users with at least $val posts:</td><td class=body>$num</td></tr>";
	}
}

function usersbyagesex(){
	global $db;

	$db->query("SELECT age,Male,Female FROM agegroups ORDER BY age ASC");

	echo "<tr><td class=header align=center colspan=2>Number of Users by Age and Sex</td></tr>";
	echo "<tr><td class=body colspan=2>";
	echo "<table>";
	echo "<tr><td class=header>Age</td><td class=header>Male</td><td class=header>Male</td><td class=header>Female</td><td class=header>Female</td><td class=header>Total</td><td class=header>Total</td></tr>";

	$total = 0;
	$rows = array();
	while($line = $db->fetchrow()){
		$rows[] = $line;
		$total += $line['Male'] + $line['Female'];
	}

	foreach($rows as $line){
		if($line['Male'] || $line['Female']){
			echo "<tr>";
			echo "<td class=body>$line[age]</td>";
			echo "<td class=body>$line[Male]</td><td class=body>" . number_format(($line['Male']/$total)*100,2) . "%</td>";
			echo "<td class=body>$line[Female]</td><td class=body>" . number_format(($line['Female']/$total)*100,2) . "%</td>";
			echo "<td class=body>" . ($line['Male'] + $line['Female']) . "</td><td class=body>" . number_format((($line['Male'] + $line['Female'])/$total)*100,2) . "%</td>";
			echo "</tr>";
		}
	}

	echo "</table>";
	echo "</td></tr>";
}

function usersperloc(){
	global $db;
	$db->query("SELECT name,users FROM locs ORDER BY users DESC LIMIT 50");

	echo "<tr><td class=header align=center colspan=2>Number of Users by Location</td></tr>";

	while($line = $db->fetchrow())
		echo "<tr><td class=body>$line[name]</td><td class=body>$line[users]</td></tr>";
}

function usersByLocRecur(){
	global $db;

	function recurTotal(& $locs, &$parents, $id){
		$total = $locs[$id]['users'];

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				$total += recurTotal($locs, $parents, $child);

		$locs[$id]['total'] = $total;

		return $total;
	}

	function recurOutput(&$locs, &$parents, $id, $level, $total, $threshhold = 0){
		echo "<tr>";
		echo "<td class=body>" . str_repeat("- ", $level) . $locs[$id]['name'] . "</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['users']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['users']/$total, 2) . "%</td>";
		echo "<td class=body align=right>" . number_format($locs[$id]['total']) . "</td><td class=body align=right>" . number_format(100*$locs[$id]['total']/$total, 2) . "%</td>";
		echo "</tr>";

		if(isset($parents[$id]))
			foreach($parents[$id] as $child)
				if(!$threshhold || $locs[$child]['total'] >= $threshhold)
					recurOutput($locs, $parents, $child, $level+1, $total, $threshhold);
	}


	$db->query("SELECT id, name, parent, users FROM locs ORDER BY name");

	$locs = array();
	$parents = array();

	while($line = $db->fetchrow()){
		$locs[$line['id']] = $line;
		$locs[$line['id']]['total'] = 0;
		$parents[$line['parent']][] = $line['id'];
	}

	$total = 0;
	foreach($parents[0] as $child)
		$total += recurTotal($locs, $parents, $child);

	echo "<tr><td class=header align=center colspan=2>Users By Location</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";
	echo "<tr>";
	echo "<td class=header>Location</td>";
	echo "<td class=header>Users</td><td class=header>Users</td>";
	echo "<td class=header>Total</td><td class=header>Total</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class=body>Location</td>";
	echo "<td class=body align=right>0</td><td class=body align=right>0.00%</td>";
	echo "<td class=body align=right>" . number_format($total) . "</td><td class=body align=right>100.00%</td>";
	echo "</tr>";

	foreach($parents[0] as $child)
		recurOutput($locs, $parents, $child, 1, $total, 0);
	echo "</table>";

	echo "</td></tr>";
}

function hitstoday(){
	global $db, $statsdb;

	$statsdb->query("SELECT * FROM stats");

	$hits = $statsdb->fetchrow();

	echo "<tr><td colspan=2 align=center class=header>Distribution of hits by hour</td></tr>";

	$statsdb->prepare_query("SELECT * FROM statshist WHERE time >= ? ORDER BY time DESC", time() - 86400);//-3600);

	$lasthour = 0;
	while($line = $statsdb->fetchrow()){
		if($lasthour==0)
			echo "<tr><td class=body>" . userdate("M d - h a") . "</td><td class=body>" . number_format($hits['hitstotal'] - $line['hitstotal']) . " (" . number_format(($hits['hitstotal'] - $line['hitstotal'])*3600/(time()-$line['time'])) . " expected)</td></tr>";
		else
			echo "<tr><td class=body>" . userdate("M d - h a", $line['time']) . "</td><td class=body>" . number_format($lasthour['hitstotal'] - $line['hitstotal']) . "</td></tr>";
		$lasthour=$line;
	}

	echo "<tr><td class=body>Total:</td><td class=body>" . number_format($hits['hitstotal'] - $lasthour['hitstotal']) . "</td></tr>";

	echo "<tr><td class=body>Max hits in an hour:</td><td class=body>" . number_format($hits['hitsmaxhour']) . "</td></tr>";
	echo "<tr><td class=body>Max hits in a day (24h period)</td><td class=body>" . number_format($hits['hitsmaxday']) . "</td></tr>";
}

function hitsperweek(){
	global $db;
/*
	echo "<tr><td class=header align=center colspan=2>Hits by week</td></tr>";
	echo "<tr><td class=header>First of the week</td><td class=header>Hits that week</td></tr>";
	$time = time();

	$query = "SELECT total FROM hithist WHERE time <= $time ORDER BY time DESC LIMIT 1";
	$db->query($query);
	$oldnum = $db->fetchfield();

	while(1){
		$time -= 7*86400;
		$query = "SELECT total FROM hithist WHERE time <= $time ORDER BY time DESC LIMIT 1";
		$db->query($query);
		if($db->numrows()==0 && $time < gmmktime(0,0,0,3,1,2003))
			break;
		$num = $db->fetchrow();
		$num = $num['total'];

		echo "<tr><td class=body>" . date("m/d/y",$time) . "</td><td class=body>" . number_format($oldnum - $num,0) . "</td></tr>";
		$oldnum = $num;
	}
*/
}

function usersperweek(){
	global $db;
/*
//damn slow, rewrite
	echo "<tr><td class=header align=center colspan=2>New users by week</td></tr>";
	echo "<tr><td class=header>First of the week</td><td class=header>New users that week</td></tr>";
	$time = time();

	$query = "SELECT userid FROM users WHERE jointime <= $time ORDER BY userid DESC LIMIT 1";
	$db->query($query);
	$oldnumusers = $db->fetchfield();

	while(1){
		$time -= 7*86400;
		$query = "SELECT userid FROM users WHERE jointime <= $time ORDER BY userid DESC LIMIT 1";
		$db->query($query);
		if($db->numrows()==0 && $time < gmmktime(0,0,0,3,1,2003))
			break;
		$numusers = $db->fetchrow();
		$numusers = $numusers['userid'];

		echo "<tr><td class=body>" . date("m/d/y",$time) . "</td><td class=body>" . ($oldnumusers - $numusers) . "</td></tr>";
		$oldnumusers = $numusers;
	}
*/
}

function ageStats(){
	global $db;
/*
	$query = "SELECT dob FROM users";
	$result = $db->unbuffered_query($query);

	$total=0;
	$num=0;
	while($line = $db->fetchrow($result)){
		$total+=$line['dob'];
		$num++;
	}

	echo "Overall: " . getAge($total/$num,2) . "<br>\n";

	$query = "SELECT dob FROM users WHERE age < 35";
	$result = $db->unbuffered_query($query);

	$total=0;
	$num=0;
	while($line = $db->fetchrow($result)){
		$total+=$line['dob'];
		$num++;
	}

	echo "Overall under 35: " . getAge($total/$num,2) . "<br>\n";

	$query = "SELECT dob FROM users WHERE sex = 'Female' && age < 35";
	$result = $db->unbuffered_query($query);

	$total=0;
	$num=0;
	while($line = $db->fetchrow($result)){
		$total+=$line['dob'];
		$num++;
	}

	echo "Female: " . getAge($total/$num,2) . "<br>\n";

	$query = "SELECT dob FROM users WHERE sex = 'Male' && age < 35";
	$result = $db->unbuffered_query($query);

	$total=0;
	$num=0;
	while($line = $db->fetchrow($result)){
		$total+=$line['dob'];
		$num++;
	}

	echo "Male: " . getAge($total/$num,2) . "<br>\n";
*/
}

function skinstats(){
	global $db, $skins;

	$db->query("SELECT skin, count(*) as count FROM users GROUP BY skin ORDER BY count DESC");

	while($line = $db->fetchrow()){
		echo "<tr><td class=body>";
		if($line['skin'] == "")
			echo "default";
		elseif(isset($skins[$line['skin']]))
			echo $skins[$line['skin']]['name'];
		else
			echo "unknown: $line[skin]";
		echo "</td><td class=body>$line[count]</td>";
		echo "</tr>";
	}

}

function plususersbyage(){
	global $db;

	$db->prepare_query("SELECT age, sex, count(*) as count FROM users WHERE premiumexpiry > ? GROUP BY age, sex ORDER BY age", time());

	$plususers = array();
	for($i=14; $i<=60; $i++)
		$plususers[$i] = array('Male' => 0, 'Female' => 0);

	while($line = $db->fetchrow())
		$plususers[$line['age']][$line['sex']] = $line['count'];

	echo "<tr><td class=header colspan=2 align=center>Plus Users By age/sex</td></tr>";
	echo "<tr><td class=body colspan=2>";

	echo "<table>";

	$male = 0;
	$female = 0;
	echo "<tr><td class=header>Age</td><td class=header>Male</td><td class=header>Female</td><td class=header>Total</td></tr>";
	foreach($plususers as $age => $sex){
		echo "<tr>";
		echo "<td class=body>$age</td>";
		echo "<td class=body>$sex[Male]</td>";
		echo "<td class=body>$sex[Female]</td>";
		echo "<td class=body>" . ($sex['Male'] + $sex['Female']) . "</td>";
		echo "</tr>";
		$male += $sex['Male'];
		$female += $sex['Female'];
	}
	echo "<tr><td class=header></td><td class=header>$male</td><td class=header>$female</td><td class=header>" . ($male + $female) . "</td></tr>";

	echo "</table>";
	echo "</td></tr>";
}

