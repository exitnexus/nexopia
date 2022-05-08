<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($fid) || !is_numeric($fid))
		die("Bad Forum id");

	if($fid){
		$perms = $forums->getForumPerms($fid);	//checks it's a forum, not a realm

		if(!$perms['modlog'])
			die("You don't have permission to mute people in this forum");

		$forumdata = $perms['cols'];
	}else{ //fid = 0
		if(!$mods->isAdmin($userData['userid'],'forums')){
			$forums->db->prepare_query("SELECT modlog FROM forummods WHERE userid = ? && forumid = 0", $userData['userid']);

			if($forums->db->numrows() == 0 || $forums->db->fetchfield() == 'n')
				die("You don't have permission to see the global mod log");
		}

		$forumdata = array("name" => "Global", 'official' => 'y');
	}


	$page = getREQval('page', 'int');

	$forums->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS action,threadid,var1,var2,time,forummodlog.userid FROM forummodlog WHERE forummodlog.forumid = ? ORDER BY time DESC LIMIT " . $page*$config['linesPerPage'].", $config[linesPerPage]", $fid);

	$rows = array();
	while($line = $forums->db->fetchrow())
		$rows[] = $line;


	$forums->db->query("SELECT FOUND_ROWS()");
	$numrows = $forums->db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);


	incHeader();

	echo "<table width=100%>";

	echo "<tr><td class=header colspan=5>";
	echo "<table cellspacing=0 cellpadding=0 width=100%><tr><td class=header>";

	if($forumdata['official']=='y')
		echo "<a class=header href=forums.php>Forums</a> > ";
	else
		echo "<a class=header href=forumsusercreated.php>User Created Forums</a> > ";

	if($fid != 0)
		echo "<a class=header href=forumthreads.php?fid=$fid>$forumdata[name]</a> > ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?fid=$fid>Mod Log</a>";

	echo "</td><td class=header align=right>";
	echo "Page: " . pageList("$_SERVER[PHP_SELF]?fid=$fid",$page,$numpages,'header');
	echo "</td></tr></table>";
	echo "</td></tr>";

	echo "<tr><td class=header>Who</td><td class=header>Action</td><td class=header>Time</td><td class=header>Threadid</td><td class=header>More Info</td></tr>";

	$threadnames = array();
	$threadnames2 = array();
	$forumnames = array();
	foreach($rows as $line){
		echo "<tr>";

		echo "<td class=body>" . ($line['userid']==0 ? 'auto' : "<a class=body href=profile.php?uid=$line[userid]>" . getUserName($line['userid']) . "</a>" ) . "</td>";
		echo "<td class=body>$line[action]</td>";
		echo "<td class=body nowrap>" . userdate("F j, Y, g:i a", $line['time']) . "</td>";

		echo "<td class=body>";
		if($line['threadid']){
			if(!isset($threadnames[$line['threadid']])){
				$forums->db->prepare_query("SELECT title FROM forumthreads WHERE id = ?", $line['threadid']);

				if($forums->db->numrows()){
					$threadnames[$line['threadid']] = $forums->db->fetchfield();
				}else{
					$forums->db->prepare_query("SELECT title FROM forumthreadsdel WHERE id = ?", $line['threadid']);
					$threadnames[$line['threadid']] = $threadnames2[$line['threadid']] = $forums->db->fetchfield();
				}
			}
			if(isset($threadnames2[$line['threadid']]))
				echo $threadnames[$line['threadid']];
			else
				echo "<a class=body href=forumviewthread.php?tid=$line[threadid]>" . $threadnames[$line['threadid']] . "</a>";
		}
		echo "</td><td class=body>";

		switch($line['action']){
			case "lock":
			case "unlock":
			case "stick":
			case "unstick":
			case "announce":
			case "unannounce":
			case "flag":
			case "unflag":
			case "deletethread":
				if($line['var1'])
					echo "Thread by: " . (($uname = getUserName($line['var1'])) ? "'<a class=body href=profile.php?uid=$line[var1]>$uname</a>'" : "(user deleted)");
				if($line['var2'])
					echo ", Last Post by: " . (($uname = getUserName($line['var2'])) ? "'<a class=body href=profile.php?uid=$line[var2]>$uname</a>'" : "(user deleted)");
				break;
			case "deletepost":
				echo "Post by " . (($uname = getUserName($line['var2'])) ? "'<a class=body href=profile.php?uid=$line[var2]>$uname</a>'" : "(user deleted)");
				break;
			case "move":
				if($fid == $line['var1']){
					if(!isset($forumnames[$line['var2']])){
						$forums->db->prepare_query("SELECT name FROM forums WHERE id = ?", $line['var2']);
						$forumnames[$line['var2']] = $forums->db->fetchfield();
					}

					echo "Moved to <a class=body href=forumthreads.php?fid=$line[var2]>" . $forumnames[$line['var2']] . "</a>";
				}else{
					if(!isset($forumnames[$line['var1']])){
						$forums->db->prepare_query("SELECT name FROM forums WHERE id = ?", $line['var1']);
						$forumnames[$line['var1']] = $forums->db->fetchfield();
					}

					echo "Moved from <a class=body href=forumthreads.php?fid=$line[var1]>" . $forumnames[$line['var1']] . "</a>";
				}
				break;
			case "mute":
			case "unmute":
				echo (($uname = getUserName($line['var1'])) ? "'<a class=body href=profile.php?uid=$line[var1]>$uname</a>'" : "(user deleted)") . " muted ";
				if($line['var2']==0)
					echo "indefinitely";
				else
					echo "till " . userdate("F j, Y, g:i a", $line['var2']);

				break;
			case "editpost":
				echo "Post id $line[var1] by: " . (($uname = getUserName($line['var1'])) ? "'<a class=body href=profile.php?uid=$line[var1]>$uname</a>'" : "(user deleted)");
				break;
			case "addmod":
			case "editmod":
			case "removemod":
				echo "Mod: ";
				echo (($uname = getUserName($line['var1'])) ? "'<a class=body href=profile.php?uid=$line[var1]>$uname</a>'" : "(user deleted)");

				break;
		}

		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";

	incFooter();
