<?

	$login=0;

	require_once("include/general.lib.php");

	if(!isset($id)){
		header("location: articlelist.php");
		exit;
	}

	$sortd = getREQval('sortd', 'string', 'DESC');
	$sortt = getREQval('sortt', 'string', 'time');
	$page = getREQval('page', 'int');

	$article = $cache->get(array($id, "article-$id"));

	if(!$article){
		$db->prepare_query("SELECT * FROM articles WHERE articles.id = ?", $id);
		$article = $db->fetchrow();

		$cache->put(array($id, "article-$id"), $article, 86400);
	}

	$db->prepare_query("SELECT author, authorid, time, nmsg FROM comments, commentstext WHERE itemid = ? && comments.id = commentstext.id ORDER BY comments.id ASC LIMIT 5", $id);

	$comments = array();
	while($line = $db->fetchrow())
		$comments[] = $line;

	incHeader(false);


	echo "<table width=100% cellpadding=3>\n";

	echo "<tr><td class=header colspan=2>";

	$categories = & new category( $db, "cats");
	$root = $categories->makeroot($article['category']);

	foreach($root as $cat)
		echo "<a class=header href=articlelist.php?cat=$cat[id]>$cat[name]</a> > ";

	echo "<b><a class=header href=\"article.php?sortd=$sortd&sortt=$sortt&page=$page&id=$id\">$article[title]</a></b></td>";
	echo "</tr>";

	echo "<tr><td class=header>Submitted: " . userdate("m/d/y",$article['time']) . "</td>";
	echo "<td class=header>Submitted By: ";

	if($article['authorid'])
		echo "<a class=header href=profile.php?uid=$article[authorid]>$article[author]</a>";
	else
		echo "$article[author]";
	echo "</td>";
	echo "</tr>\n";

	echo "<tr><td colspan=2 class=body>\n";
	echo nl2br(smilies(parseHTML($article['text']))) . "&nbsp;";
//	echo $article['ntext'] . "&nbsp;";
	echo "</td></tr>\n";

	echo "</table>\n";

	echo "<table width=100%>";
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=comments.php?id=$id>Add/Display Comments</a></b></td></tr>";

	if(count($comments)==0)
		echo "<tr><td class=body colspan=2 align=center>No Comments</td>";

	foreach($comments as $line){
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
