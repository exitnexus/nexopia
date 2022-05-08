<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'editprofile');
	if(empty($uid) || !$isAdmin)
		$uid = $userData['userid'];
	else
		$mods->adminlog("view subscriptions","View Subscriptions for userid: $uid");

	switch($action){
		case "Unsubscribe":
			if(isset($deleteID)){
				$query = $db->prepare("UPDATE forumread SET subscribe='n' WHERE userid = ? && threadid IN (?)", $uid, $deleteID);
				$db->query($query);
				$msgs->addMsg("Unsubscribed");
			}
		break;
	}

	$sortlist = array(  "threadtime" => "threadtime",
						"forumtitle" => "forumtitle",
						"threadtitle" => "threadtitle",
						"new" => "forumread.time < forumthreads.time"
						);

	isValidSortt($sortlist,$sortt);
	isValidSortd($sortd,'DESC');

	if(!isset($page) || $page<0) $page=0;

	$query = $db->prepare("SELECT DISTINCT SQL_CALC_FOUND_ROWS
						forumread.threadid,
						forumthreads.forumid,
						forumthreads.title as threadtitle,
						forums.name as forumtitle,
						forumread.time as readtime,
						forumthreads.time as threadtime,
						forumthreads.lastauthor,
						forumthreads.lastauthorid,
						forumthreads.locked,
						forumread.time < forumthreads.time AS new
			  FROM 		forumread,
						forumthreads,
						forums
			  WHERE 	forumread.userid = ? &&
						forumread.subscribe='y' &&
						forumread.threadid=forumthreads.id &&
						forumthreads.forumid=forums.id
			  ORDER BY $sortt $sortd LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]"
			, $uid);

	$db->query($query);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;

	$query = "SELECT FOUND_ROWS()";
	$db->query($query);

	$numthreads = $db->fetchfield();

	$numpages = ceil($numthreads / $config['linesPerPage']);


	incHeader();

	echo "<table width=100%>";
	if($uid == $userData['userid'])
		echo "<form action=$PHP_SELF>";

	echo "<tr>";
		if($uid == $userData['userid'])
			echo "<td class=header width=25></td>";
		makeSortTableHeader($sortlist,"Thread Title","threadtitle");
		makeSortTableHeader($sortlist,"Forum Title","forumtitle");
		makeSortTableHeader($sortlist,"New Posts","new");
		makeSortTableHeader($sortlist,"Last Post","threadtime");
	echo "</tr>";

	foreach($rows as $line){
		echo "<tr>";
		if($uid == $userData['userid'])
			echo "<td class=body><input class=body type=checkbox name=deleteID[] value=$line[threadid]></td>";
		echo "<td class=body>";
		if($line['locked']=='y')
			echo "<img src=$config[imageloc]locked.png> ";
		echo "<a class=body href=forumviewthread.php?tid=$line[threadid]>$line[threadtitle]</a></td>";
		echo "<td class=body><a class=body href=forumthreads.php?fid=$line[forumid]>$line[forumtitle]</a></td>";
		echo "<td class=body>" . ($line['new'] ? "Yes" : "" ) . "</td>";
		echo "<td class=body>";
		echo userdate("M j, y g:i a",$line['threadtime']) . " by ";
		if($line['lastauthorid']!=0)
			echo "<a class=body href=profile.php?uid=$line[lastauthorid]>$line[lastauthor]</a>";
		else
			echo $line['lastauthor'];
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=5>";
	echo "<table width=100%><tr>";
	if($uid == $userData['userid'])
		echo "<td class=header><input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'deleteID')\"><input class=body type=submit name=action value=Unsubscribe></td>";
	echo "<td class=header align=right>Page: " . pageList("$PHP_SELF",$page,$numpages,'header') . "</td>";
	echo "</tr></table>";
	echo "</td></tr>";

	if($uid == $userData['userid'])
		echo "</form>";
	echo "</table>";

	incFooter();
