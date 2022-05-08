<?

	$login=1;

	require_once("include/general.lib.php");
	
	$searchtimes = array(0 => "Anytime",
						 1 => "1 Day",
						 2 => "2 Days",
						 3 => "3 Days",
						 7 => "1 Week",
						 14 => "2 Weeks",
						 30 => "1 Month",
						 60 => "2 Months");
	

	$query = "SELECT id,name FROM forums" . (isAdmin($userData['userid']) ? '' : " WHERE adminonly='n'" ) . " ORDER BY priority";
	$result = mysql_query($query);
	while($line=mysql_fetch_assoc($result))
		$forums[$line['id']]=$line['name'];
	
	sqlSafe(&$search);
	
	if(!isset($search['text']))
		$search['text']="";
	if(!isset($search['searchin']) || !($search['searchin']=="topic" || $search['searchin']=="text" || $search['searchin']=="both"))
		$search['searchin']="both";
	if(!isset($search['author']))
		$search['author']="";
	if(!isset($search['forums']) || !in_array($search['forums'],array_keys($forums)))
		$search['forums']="0";
	if(!isset($search['ageval']) || !($search['ageval']=="newer" || $search['ageval']=="older"))
		$search['ageval']="newer";
	if(!isset($search['age']) || !in_array($search['age'],array_keys($searchtimes)))
		$search['age']=30;
	
	if(isset($action) && ($search['text']!="" || $search['author']!="")){	

		$query = "SELECT DISTINCT forumthreads.id, forumthreads.forumid, forumthreads.title, forumthreads.author, forumthreads.authorid, forumthreads.lastauthor, forumthreads.lastauthorid, forumthreads.reads, forumthreads.posts, forumthreads.time, forumthreads.locked, forumthreads.sticky,'0' as new, '0' as subscribe";
		$query .= " FROM forumposts,forumthreads,forums WHERE ";
		
		$commands[] = "forumthreads.id=forumposts.threadid";
		$commands[] = "forums.id=forumthreads.forumid";
		
		
		if(!isAdmin($userData['userid']))
			$commands[] = "forums.adminonly='n'";
		if($search['forums']!=0)
			$commands[] = "forums.id = '$search[forums]'";
		if($search['age']!=0)
			$commands[] = "forumposts.time " . ($search['ageval']=='newer'? ">" : "<") . (time() - $search['age']*24*3600);
	
		$searchstr = str_replace(array('%','_'),array('\%','\_'),$search['text']);

		if($search['searchin']=='text'){
			if($search['text']!="")
				$commands[] = "forumposts.msg LIKE '%" . $searchstr . "%'";
			if($search['author']!="")
				$commands[] = "forumposts.author= '$search[author]'";
		}elseif($search['searchin']=='topic'){
			if($search['text']!="")
				$commands[] = "forumthreads.title LIKE '%" . $searchstr . "%'";
			if($search['author']!="")
				$commands[] = "forumthreads.author= '$search[author]'";
		}else{
			$part = "((";
			if($search['text']!="")
				$part .= "forumposts.msg LIKE '%" . $searchstr . "%'";
			if($search['text']!="" && $search['author']!="")
				$part .= " && ";
			if($search['author']!="")
				$part .= "forumposts.author= '$search[author]'";
			$part .= ") || (";
			if($search['text']!="")
				$part .= "forumthreads.title LIKE '%" . $searchstr . "%'";
			if($search['text']!="" && $search['author']!="")
				$part .= " && ";
			if($search['author']!="")
				$part .= "forumthreads.author= '$search[author]'";
			$part .= " ))";
			$commands[] = $part;
		}

		$query .= implode(" && ",$commands);
		$query .= " ORDER BY forumposts.time DESC";
		
		$result = mysql_query($query);


/*
	if(!isset($search['text']))
		$search['text']="";
	if(!isset($search['searchin']) || !($search['searchin']=="topic" || $search['searchin']=="text" || $search['searchin']=="both"))
		$search['searchin']="both";
	if(!isset($search['author']))
		$search['author']="";
	if(!isset($search['forums']) || !in_array($search['forums'],array_keys($forums)))
		$search['forums']="0";
	if(!isset($search['ageval']) || !($search['ageval']=="newer" || $search['ageval']=="older"))
		$search['ageval']="newer";
	if(!isset($search['age']) || !in_array($search['age'],array_keys($searchtimes)))
		$search['age']=30;
*/
/*

		$query = "SELECT DISTINCT forumthreads.id, forumthreads.forumid, forumthreads.title, forumthreads.author, forumthreads.authorid, forumthreads.lastauthor, forumthreads.lastauthorid, forumthreads.reads, forumthreads.posts, forumthreads.time, forumthreads.locked, forumthreads.sticky,'0' as new, '0' as subscribe";
		$query .= " FROM words,wordmath,forumposts,forumthreads,forums WHERE ";

		$query .= "words.word='$search[text]' && words.id=wordmatch.wordid && wordmatch.postid=forumposts.id && forumposts.threadid=forumthreads.id && forumthreads.forumid=forums.id";


		$query .= " ORDER BY forumposts.time DESC";
		
		$result = mysql_query($query);
*/

//	echo $query;
//	echo mysql_error();

		$readalltime = 0;

		$threaddata = array();
		$threadids = array();
		while($line=mysql_fetch_assoc($result)){
			$threaddata[$line['id']] = $line;
			$threadids[$line['id']] = "threadid = '$line[id]'";
		}

		if($userData['loggedIn'] && count($threadids)>0){
			$subscribes = array();
			$query = "SELECT threadid,time,subscribe FROM forumread WHERE userid='$userData[userid]' && (" . implode(" || ", $threadids) . ")";
			$result = mysql_query($query);
	
			while($line = mysql_fetch_assoc($result))
				$subscribes[$line['threadid']] = $line;
			
			foreach($threaddata as $threadid => $data){
				if(!isset($subscribes[$threadid])){
					if($readalltime < $threaddata[$threadid]['time'])
						$threaddata[$threadid]['new'] = 2; //subscribe is already 0
				}else{
					$threaddata[$threadid]['new'] = (($readalltime < $data['time']) && ($subscribes[$threadid]['time'] < $data['time']) ? 1 : 0);
					$threaddata[$threadid]['subscribe'] = ($subscribes[$threadid]['subscribe'] == 'y');
				}
			}
		}
	}

	
	incHeader(false);
	
	if(isset($action) && ($search['text']!="" || $search['author']!="")){
		echo "<table width=100% cellspacing=1 cellpadding=3>";
		echo "<tr><td class=header width=120>Forum</td><td class=header>Topics</td><td class=header width=120>Author</td><td class=header width=40>Replies</td><td class=header width=40>Views</td><td class=header width=120>Last Post</td></tr>\n";


		foreach($threaddata as $line){




	
			echo "<tr>";
			
	
			echo "<td class=forumlst nowrap><a class=forumlst href=listthreads.php?fid=$line[forumid]>" . $forums[$line['forumid']] . "</a></td>";
	
			echo "<td class=forumlst>";
			if($line['locked']=='y')
				echo "<img src=/images/locked.png> ";
			if($line['sticky']=='y')
				echo "<img src=/images/up.png> ";
			echo "<a class=forumlst$line[new] href=viewthread.php?tid=$line[id]>";
			if($line['subscribe'])
				echo "<b>$line[title]</b>";
			else
				echo "$line[title]";
			echo "</a></td><td class=forumlst>";
			$uid = getUserId($line['author']);
			if($uid==$line['authorid'])
				echo "<a class=forumlst href=profile.php?uid=$line[authorid]>$line[author]</a>";
			else
				echo "$line[author]";
			echo "</td><td class=forumlst>$line[posts]</td><td class=forumlst>$line[reads]</td>";
			echo "<td class=forumlst nowrap>" . userdate("M j, y g:i a",$line['time']);
	
			echo "<br>by ";
			$uid = getUserId($line['lastauthor']);
			if($uid==$line['lastauthorid'])
				echo "<a class=forumlst href=profile.php?uid=$line[lastauthorid]>$line[lastauthor]</a>";
			else
				echo "$line[lastauthor]";
	
			echo "</td></tr>\n";
		}
		echo "</table>";
	
	}
	
	
	echo "<table border=0 cellspacing=1 cellpaccing=3><form action=$_SERVER[PHP_SELF] method=get>";
	echo "<tr><td class=header valign=top align=right>Search for:</td><td class=body>";
	echo "	<input class=body type=text name=search[text] value=\"$search[text]\"><br>";
	echo "	<input type=radio name=search[searchin] value=topic" . ($search['searchin']=='topic'? " checked" : "") . ">In the topic";
	echo "	<input type=radio name=search[searchin] value=text" . ($search['searchin']=='text'? " checked" : "") . ">In the text";
	echo "	<input type=radio name=search[searchin] value=both" . ($search['searchin']=='both'? " checked" : "") . ">In Both";
	echo "</td></tr>";
	echo "<tr><td class=header valign=top align=right>Written By:</td><td class=body>";
	echo "	<input class=body type=text name=search[author] value=\"$search[author]\"><br>";
	echo "</td></tr>";
	echo "<tr><td class=header valign=top align=right>Search in Forums</td><td class=body>";
	echo "	<select class=body name=search[forums]><option value=0" . ($search['forums']=='0'? " selected" : "") . ">All";
	echo make_select_list_key($forums,$search['forums']);
	echo "</select>";
	echo "</td></tr>";
	echo "<tr><td class=header valign=top align=right>Only return results</td><td class=body>";
	echo "	<select class=body name=search[ageval]><option value=newer" . ($search['ageval']=='newer'? " selected" : "") . ">Newer";
	echo "<option value=older" . ($search['ageval']=='older'? " selected" : "") . ">Older</select>";
	
	echo "	than <select class=body name=search[age]>" . make_select_list_key($searchtimes,$search['age']) ."</select>";
	echo "</td></tr>";
	echo "<tr><td class=header>&nbsp;</td><td class=header><input class=body type=submit name=action value=Search></td></tr>";
	echo "</form></table>";
	
	incFooter();
