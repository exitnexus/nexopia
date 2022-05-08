<?

	$login=0;

	require_once("include/general.lib.php");

	if(!isset($cat))
		$cat=0;

	$where = array();

	$where[] = "moded='y'";

	$categories = & new category("cats");

	if($cat!=0){
		$catbranch = $categories->makebranch($cat);
		$cats=array();
		$cats[] = $cat;
		foreach($catbranch as $category)
			$cats[] = $category['id'];
		$where[] = $db->prepare("category  IN (?)", $cats);
	}

	if(!empty($search)){
		$where[] = "(title REGEXP '(^|^.* )" . $db->escape($search) . "(\$| .*\$)')";
	}else
		$search='';

	$query = "SELECT * FROM articles WHERE " . implode(" && ",$where) . " ORDER BY time DESC LIMIT 10";
	$result = $db->query($query);



	incHeader(false);

	echo "<table width=100% cellspacing=0 cellpadding=3>";


	$branch = $categories->makebranch(0);

	echo "<form action=$PHP_SELF>";
	echo "<tr><td class=header>";
	echo "<select class=body name=cat onChange=\"location.href='$PHP_SELF?cat='+(this.options[this.selectedIndex].value)\"><option value=0>Choose a Category<option value=0>Home". makeCatSelect($branch,$cat) . "</select> ";
	echo "Search: <input class=body type=text name=search><input class=body type=submit name=action value=Go>";
	echo "</td></form>";
	echo "<td class=header align=right><a class=header href=addarticle.php>Submit an article</a></td></tr>";
	echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";

	$articledata = array();
	$articleids = array();

	while($line = $db->fetchrow($result)){
		$articledata[$line['id']] = $line;
		$articleids[$line['id']] = "itemid='$line[id]'";
	}

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



//		echo truncate(nl2br(parseHTML(smilies($line['text']))),1000);
		echo truncate($line['ntext'],1000);


		echo "</td></tr>";
		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

		echo "<tr><td class=body colspan=2>[<a class=body href=article.php?id=$line[id]>Read the whole article</a>] [<a class=body href=comments.php?type=articles&id=$line[id]>Comments $line[comments]</a>]</td></tr>";

		echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";
	}


	echo "<form action=$PHP_SELF>";
	echo "<tr><td class=header>";
	echo "<select class=body name=cat onChange=\"location.href='$PHP_SELF?cat='+(this.options[this.selectedIndex].value)\"><option value=0>Choose a Category<option value=0>Home". makeCatSelect($branch,$cat) . "</select> ";
	echo "Search: <input class=body type=text name=search><input class=body type=submit name=action value=Go>";
	echo "</td></form>";
	echo "<td class=header align=right><a class=header href=addarticle.php>Submit an article</a></td></tr>";
	echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";
	echo "</table>";

	incFooter(false);

