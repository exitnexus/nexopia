<?

	$login=1;

	require_once("include/general.lib.php");

	if(!isset($fid) || !is_numeric($fid))
		die("Bad Forum id");

	if($fid){
		$perms = getForumPerms($fid,array('name','official'));	//checks it's a forum, not a realm

		if(!$perms['modlog'])
			die("You don't have permission to mute people in this forum");

		$forumdata = $perms['cols'];
	}else{ //fid = 0
		if(!$mods->isAdmin($userData['userid'],'forums')){
			$db->prepare_query("SELECT modlog FROM forummods WHERE userid = ? && forumid = 0", $userData['userid']);

			if($db->numrows() == 0 || $db->fetchfield() == 'n')
				die("You don't have permission to see the global mod log");
		}

		$forumdata = array("name" => "Global", 'official' => 'y');
	}


	if(!isset($page) || $page<0) $page=0;

	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS action,threadid,var1,var2,time,username,forummodlog.userid FROM forummodlog LEFT JOIN users ON users.userid=forummodlog.userid WHERE forummodlog.forumid = ? ORDER BY time DESC LIMIT " . $page*$config['linesPerPage'].", $config[linesPerPage]", $fid);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line;


	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
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
	echo "<a class=header href=$PHP_SELF?fid=$fid>Mod Log</a>";

	echo "</td><td class=header align=right>";
	echo "Page: " . pageList("$PHP_SELF?fid=$fid",$page,$numpages,'header');
	echo "</td></tr></table>";
	echo "</td></tr>";

	echo "<tr><td class=header>Who</td><td class=header>Action</td><td class=header>Time</td><td class=header>Threadid</td><td class=header>More Info</td></tr>";

	foreach($rows as $line){
		echo "<tr>";

		echo "<td class=body>" . ($line['userid']==0 ? 'auto' : "<a class=body href=profile.php?uid=$line[userid]>$line[username]</a>" ) . "</td>";
		echo "<td class=body>$line[action]</td>";
		echo "<td class=body nowrap>" . userdate("F j, Y, g:i a", $line['time']) . "</td>";

		echo "<td class=body>";
		if($line['threadid'] > 0){
			$db->prepare_query("SELECT title FROM forumthreads WHERE id = ?", $line['threadid']);

			if($db->numrows() == 1){
				$title = $db->fetchfield();
				echo "<a class=body href=forumviewthread.php?tid=$line[threadid]>$title</a>";
				$deleted = false;
			}else{
				$db->prepare_query("SELECT title FROM forumthreadsdel WHERE id = ?", $line['threadid']);
				$title = $db->fetchfield();
				echo "$title";
				$deleted = true;
			}
		}
		echo "</td><td class=body>";

		switch($line['action']){
			case "lock":
				echo "Thread by: ";
				$db->prepare_query("SELECT username FROM users WHERE userid = ?", $line['var1']);
				if($db->numrows() == 0)
					echo "(user deleted)";
				else
					echo "'" . $db->fetchfield() . "'";

				echo ", Last Post by: ";
				$db->prepare_query("SELECT username FROM users WHERE userid = ?", $line['var2']);
				if($db->numrows() == 0)
					echo "(user deleted)";
				else
					echo "'" . $db->fetchfield() . "'";
				break;
			case "unlock":
			case "stick":
			case "unstick":
			case "announce":
			case "unannounce":
				break;
			case "deletethread":
				echo "Thread by " . getUserName($line['var1']);
				break;
			case "deletepost":
				echo "Post by " . getUserName($line['var2']);
				break;
			case "move":
				if($fid == $line['var1']){
					$db->prepare_query("SELECT name FROM forums WHERE id = ?", $line['var2']);

					echo "Moved to <a class=body href=forumthreads.php?fid=$line[var2]>" . $db->fetchfield() . "</a>";
				}else{
					$db->prepare_query("SELECT name FROM forums WHERE id = ?", $line['var1']);

					echo "Moved from <a class=body href=forumthreads.php?fid=$line[var1]>" . $db->fetchfield() . "</a>";
				}
				break;
			case "mute":
			case "unmute":
				$db->prepare_query("SELECT username FROM users WHERE userid = ?", $line['var1']);
				if($db->numrows() == 0)
					echo "(user deleted)";
				else
					echo $db->fetchfield();
				echo " muted ";
				if($line['var2']==0)
					echo "indefinately";
				else
					echo "till " . userdate("F j, Y, g:i a", $line['var2']);

				break;
			case "editpost":
				echo "Post id $line[var1] by: ";
				$db->prepare_query("SELECT username FROM users WHERE userid = ?", $line['var2']);
				if($db->numrows() == 0)
					echo "(user deleted)";
				else
					echo $db->fetchfield();

				break;
			case "addmod":
			case "editmod":
			case "removemod":
				echo "Mod: ";
				$db->prepare_query("SELECT username FROM users WHERE userid = ?", $line['var1']);
				if($db->numrows() == 0)
					echo "(user deleted)";
				else
					echo $db->fetchfield();

				break;
		}

		echo "</td>";
		echo "</tr>";
	}

	echo "</table>";

	incFooter();
