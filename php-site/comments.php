<?

	$login=0;

	require_once("include/general.lib.php");

	if(empty($id))
		die("Bad id");

	$isAdmin = false;
	if($userData['loggedIn'])
		$isAdmin = $mods->isAdmin($userData['userid'],'deletecomments');

//add comment
	if($userData['loggedIn'] && isset($postaction) && isset($msg) && isset($id))
		addComment($id,$msg,$postaction);

	$db->prepare_query("SELECT title,category FROM articles WHERE id = ?", $id);
	if($db->numrows()==0)
		die("Bad id");
	$data = $db->fetchrow();

//delete
	if($isAdmin && $action=="Delete" && isset($checkID) && is_array($checkID)){

		$db->prepare_query("DELETE comments, commentstext FROM comments LEFT JOIN commentstext ON comments.id = commentstext.id WHERE itemid = ? && comments.id IN (?)", $id, $checkID);

		$db->prepare_query("UPDATE articles SET comments = comments - ? WHERE id = ?", count($checkID), $id);
		$mods->adminlog("delete article comments", "Delete article comments: article $id");
	}

	$page = getREQval('page', 'int');

	$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS comments.id, author, authorid, time, nmsg FROM comments, commentstext WHERE itemid = ?  && comments.id = commentstext.id ORDER BY comments.id ASC LIMIT " . ($page*$config['linesPerPage']) . ", $config[linesPerPage]", $id);

	$comments = array();
	while($line = $db->fetchrow())
		$comments[] = $line;

	$db->query("SELECT FOUND_ROWS()");
	$numrows = $db->fetchfield();
	$numpages =  ceil($numrows / $config['linesPerPage']);

	incHeader(0);

	echo "<table width=100% cellpadding=3>";


	echo "<tr><td class=header colspan=2>";
	echo "<table width=100%><tr><td class=header>";

	$cats = & new category( $db, "cats");
	$root = $cats->makeroot($data['category']);

	foreach($root as $cat)
		echo "<a class=header href=articlelist.php?cat=$cat[id]>$cat[name]</a> > ";

	echo "<a class=header href=\"article.php?id=$id\">$data[title]</a> > ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?id=$id>Comments</a>";

	echo "</td>";

	echo "<form>";
	echo "<td class=header align=right>";

	echo "Page:";
	echo pageList("$_SERVER[PHP_SELF]?id=$id",$page,$numpages,'header');

	echo "</td></tr></table></td></tr>";

	if(count($comments)==0)
		echo "<tr><td class=body colspan=2 align=center>No Comments</td>";

	if($isAdmin){
		echo "<form action=$_SERVER[PHP_SELF] method=post>";
		echo "<input type=hidden name=id value='$id'>";
	}

	foreach($comments as $line){
		echo "<tr><td class=header>";

		if($isAdmin)
			echo "<input type=checkbox name=checkID[] value=$line[id]>";

		echo "By: ";

		if($line['authorid'])	echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else					echo "$line[author]";

		echo "</td><td class=header>Date: " . userdate("F j, Y, g:i a",$line['time']) . "</td>";
		echo "</tr>";

		echo "<td class=body colspan=2>";

		echo $line['nmsg'] . "&nbsp;";

		echo "</td></tr>";
//		echo "<tr><td colspan=2 class=header2>&nbsp;</td></tr>";
	}
	echo "<tr><td class=header colspan=2>";
	echo "<table width=100%><tr>";

	if($isAdmin){
		echo "<td class=header>";
		echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'check')\">";
		echo "<input class=body type=submit name=action value=Delete></td></form>";
	}

	echo "<form>";
	echo "<td class=header align=right>";

	echo "Page:";
	echo pageList("$_SERVER[PHP_SELF]?id=$id",$page,$numpages,'header');

	echo "</td>";
	echo "</form>";
	echo "</tr></table>";

	echo "</td></tr>";


	if($userData['loggedIn']){
		echo "<tr><td colspan=3>";
		echo "<table  cellspacing=0 align=center>";
		echo "<tr><td class=header2><a name=reply>Post a Comment:</a></td></tr>\n";

		echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
		echo "<input type=hidden name=id value=$id>\n";

		echo "<tr><td class=header2 align=center>";

		editBox("",true);

		echo "</td></tr>\n";
		echo "<tr><td class=header2 align=center><select class=body name=postaction><option value=changed>Preview if changes made<option value=Post>Post without previewing<option value=Preview>Preview</select><input class=body type=submit value=Post accesskey='s' onClick='checksubmit()'></td></tr>\n";
		echo "</form>";
		echo "</table>\n";
		echo "</td></tr>";
	}
	echo "</table>\n";

	incFooter();

