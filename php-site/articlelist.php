<?

	$login=0;

	require_once("include/general.lib.php");

	if(!isset($cat))
		$cat=0;

	$where = array();

	$where[] = "moded='y'";

	$categories = & new category( $db, "cats");

	if($cat!=0){
		$catbranch = $categories->makebranch($cat);
		$cats=array();
		$cats[] = $cat;
		foreach($catbranch as $category)
			$cats[] = $category['id'];
		$where[] = $db->prepare("category  IN (?)", $cats);
	}

	if(!isset($day))
		$day = userdate("j");
	if(!isset($month))
		$month = userdate("n");
	if(!isset($year))
		$year = userdate("Y");

	if(!empty($day) && !empty($month) && !empty($year)){
		$date = userMkTime(0,0,0, $month, $day, $year);
		$where[] = $db->prepare("time >= ? && time <= ?", $date - 86400, $date + 86400);
	}


	$db->query("SELECT * FROM articles WHERE " . implode(" && ",$where) . " ORDER BY time DESC LIMIT 25");

	$articledata = array();
	while($line = $db->fetchrow())
		$articledata[$line['id']] = $line;

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));


	incHeader(false);

	echo "<table width=100% cellspacing=0 cellpadding=3>";

	$branch = $categories->makebranch(0);

	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header>";
	echo "<select class=body name=cat><option value=0>Choose a Category<option value=0>Home". makeCatSelect($branch,$cat) . "</select> ";
	echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months, $month) . "</select>";
	echo "<select class=body name=\"day\"><option value=0>Day" . make_select_list(range(1,31), $day) . "</select>";
	echo "<select class=body name=\"year\"><option value=0>Year" . make_select_list(range(2003,userdate("Y")), $year) . "</select>";
	echo " <input class=body type=submit name=action value=Go>";
	echo "</td></form>";
	echo "<td class=header align=right><a class=header href=addarticle.php>Submit an article</a></td></tr>";
	echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";

	foreach($articledata as $line){
		echo "<tr><td class=header><font size=4><b>$line[title]</b></font></td><td class=header align=right>" . userdate("F j, Y, g:i a",$line['time']) . "</td></tr>";
		echo "<tr><td class=header align=left>";
		$root = $categories->makeroot($line['category']);

		$cat = array();
		foreach($root as $category)
			$cat[] = "<a class=header href=articlelist.php?cat=$category[id]>$category[name]</a>";

		echo implode(" > ",$cat);

		echo "</td><td class=header align=right>Posted by: ";

		if($line['authorid'])
			echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else
			echo "$line[author]";

		echo "</td></tr>";
		echo "<tr><td colspan=2 class=body>";

		echo truncate(nl2br(smilies(parseHTML($line['text']))), 1000) . "&nbsp;";
//		echo truncate($line['ntext'],1000);

		echo "</td></tr>";
		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

		echo "<tr><td class=body colspan=2>[<a class=body href=article.php?id=$line[id]>Read the whole article</a>] [<a class=body href=comments.php?type=articles&id=$line[id]>Comments $line[comments]</a>]</td></tr>";

		echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";
	}

	echo "</table>";

	incFooter(false);

