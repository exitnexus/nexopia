<?

	$login=1;

	require_once("include/general.lib.php");

	$uid = getREQval('uid', 'int');

	$isAdmin = $mods->isAdmin($userData['userid'],'editprofile');
	if(empty($uid) || !$isAdmin)
		$uid = $userData['userid'];
	else
		$mods->adminlog("view subscriptions","View Subscriptions for userid: $uid");

	switch($action){
		case "Unsubscribe":
			if($deleteID = getPOSTval('deleteID', 'array')){
				$forums->db->prepare_query("UPDATE forumread SET subscribe='n' WHERE userid = ? && threadid IN (?)", $uid, $deleteID);
				$msgs->addMsg("Unsubscribed");
			}
		break;
	}

	$sortlist = array(  "threadtime" => "threadtime",
						"forumtitle" => "forumtitle",
						"threadtitle" => "threadtitle",
						"new" => "forumread.time < forumthreads.time"
						);

	$sortt = getREQval('sortt');
	$sortd = getREQval('sortt');

	isValidSortt($sortlist,$sortt);
	isValidSortd($sortd,'DESC');

	$page = getREQval('page', 'int');

	$forums->db->prepare_query("SELECT DISTINCT SQL_CALC_FOUND_ROWS
						forumread.threadid,
						forumthreads.forumid,
						forumthreads.title as threadtitle,
						forums.name as forumtitle,
						forumread.time as readtime,
						forumthreads.time as threadtime,
						forumthreads.lastauthor,
						forumthreads.lastauthorid,
						forumthreads.author,
						forumthreads.authorid,
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

	$rows = array();
	while($line = $forums->db->fetchrow())
		$rows[] = $line;

	$forums->db->query("SELECT FOUND_ROWS()");

	$numthreads = $forums->db->fetchfield();

	$numpages = ceil($numthreads / $config['linesPerPage']);


	incHeader();

	echo "<table width=100%>";
	if($uid == $userData['userid'])
		echo "<form action=$_SERVER[PHP_SELF] method=post>";

	echo "<tr>";
		if($uid == $userData['userid'])
			echo "<td class=header width=25></td>";
		makeSortTableHeader($sortlist,"Thread Title","threadtitle");
		makeSortTableHeader($sortlist,"Forum Title","forumtitle");
		echo "<td class=header>Author</td>";
		makeSortTableHeader($sortlist,"New Posts","new");
		makeSortTableHeader($sortlist,"Last Post","threadtime");
	echo "</tr>";

	$classes = array('body','body2');
	$i=1;

	foreach($rows as $line){
		$i = !$i;
		echo "<tr>";
		if($uid == $userData['userid'])
			echo "<td class=$classes[$i]><input class=body type=checkbox name=deleteID[] value=$line[threadid]></td>";
		echo "<td class=$classes[$i]>";
		if($line['locked']=='y')
			echo "<img src=$config[imageloc]locked.png> ";
		echo "<a class=body href=forumviewthread.php?tid=$line[threadid]>$line[threadtitle]</a></td>";
		echo "<td class=$classes[$i]><a class=body href=forumthreads.php?fid=$line[forumid]>$line[forumtitle]</a></td>";
		echo "<td class=$classes[$i]>";
		if($line['lastauthorid']!=0)
			echo "<a class=body href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else
			echo $line['author'];
		echo "</td>";
		echo "<td class=$classes[$i]>" . ($line['new'] ? "Yes" : "" ) . "</td>";
		echo "<td class=$classes[$i]>";
		echo userdate("M j, y g:i a",$line['threadtime']) . " by ";
		if($line['lastauthorid']!=0)
			echo "<a class=body href=profile.php?uid=$line[lastauthorid]>$line[lastauthor]</a>";
		else
			echo $line['lastauthor'];
		echo "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=6>";
	echo "<table width=100%><tr>";
	if($uid == $userData['userid'])
		echo "<td class=header><input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'deleteID')\"><input class=body type=submit name=action value=Unsubscribe></td>";
	echo "<td class=header align=right>Page: " . pageList("$_SERVER[PHP_SELF]?sortt=$sortt&sortd=$sortd",$page,$numpages,'header') . "</td>";
	echo "</tr></table>";
	echo "</td></tr>";

	if($uid == $userData['userid'])
		echo "</form>";
	echo "</table>";

	incFooter();

