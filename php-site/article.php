<?

	$login=0;

	require_once("include/general.lib.php");

	if(!isset($id)){
		header("location: articlelist.php");
		exit;
	}

	if(!isset($sortt))	$sortt='time';
	if(!isset($sortd))	$sortd='DESC';
	if(!isset($page))	$page='0';
	if(!isset($action))	$action="";

    $query = $db->prepare("SELECT * FROM articles WHERE articles.id = ?", $id);
    $db->query($query);

	$line = $db->fetchrow();

	incHeader(false);


	echo "<table width=100% cellpadding=3>\n";

	echo "<tr><td class=header colspan=2>";

	$categories = & new category("cats");
	$root = $categories->makeroot($line['category']);

	foreach($root as $cat)
		echo "<a class=header href=articlelist.php?cat=$cat[id]>$cat[name]</a> > ";

	echo "<b><a class=header href=\"article.php?sortd=$sortd&sortt=$sortt&page=$page&id=$id\">$line[title]</a></b></td>";
	echo "</tr>";

	echo "<tr><td class=header>Submitted: " . userdate("m/d/y",$line['time']) . "</td>";
	echo "<td class=header>Submitted By: ";

	if($line['authorid'])
		echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
	else
		echo "$line[author]";
	echo "</td>";
	echo "</tr>\n";

	echo "<tr><td colspan=2 class=body>\n";
//	echo nl2br(parseHTML(smilies($line['text'])));
	echo $line['ntext'] . "&nbsp;";
	echo "</td></tr>\n";

	echo "</table>\n";

	echo "<table width=100%>";
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=comments.php?id=$id>Add/Display Comments</a></b></td></tr>";

	$query = $db->prepare("SELECT author, authorid, time, nmsg FROM comments, commentstext WHERE itemid = ? && comments.id = commentstext.id ORDER BY comments.id ASC LIMIT 5", $id);
	$result = $db->query($query);

	if($db->numrows($result)==0)
		echo "<tr><td class=body colspan=2 align=center>No Comments</td>";

	while($line = $db->fetchrow($result)){
		echo "<tr><td class=header>By: ";

		if($line['authorid'])	echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else						echo "$line[author]";

		echo "</td><td class=header>Date: " . userdate("M j, Y G:i:s",$line['time']) . "</td>";
		echo "</tr>";

		echo "<td class=body colspan=2>";

//		echo nl2br(parseHTML(smilies($line['msg'])));
		echo $line['nmsg'] . "&nbsp;";


		echo "</td></tr>";
//		echo "<tr><td colspan=2 class=header2>&nbsp;</td></tr>";
	}
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=comments.php?id=$id>Add/Display Comments</a></b></td></tr>";
	echo "</table>";

	incFooter();
