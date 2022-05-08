<?

	$login=0;

	require_once("include/general.lib.php");


	$categories = & new category('faqcats');

	$branch = $categories->makeBranch();

	if(!isset($cat) || !$categories->isValidCat($cat))
		$cat = 0;

	if(!isset($q)){
		$q=0;
	}else{
		switch($action){
			case "Yes":
				$db->prepare_query("UPDATE faq SET priority = priority + 1 WHERE id = ?", $q);
				$msgs->addMsg("Thanks for your feedback");
				$q=0;
				break;
			case "No":
				$db->prepare_query("UPDATE faq SET priority = priority - 1 WHERE id = ?", $q);
				$msgs->addMsg("Thanks for your feedback");
				$q=0;
				break;
		}
	}

	if($cat == 0)		$db->query("SELECT * FROM faq ORDER BY priority DESC LIMIT 10");
	else				$db->prepare_query("SELECT * FROM faq WHERE parent = ? ORDER BY priority DESC", $cat);

	$questions = array();
	while($line = $db->fetchrow())
		$questions[$line['id']] = $line;

	$catname = "Top Questions";

	incHeader();

	echo "<table width=100%><tr>";

	echo "<td class=body valign=top width=150>";

		echo "<table>";
		echo "<tr><td class=header>Categories</td></tr>";
		echo "<tr><td class=body><a class=body href=$PHP_SELF?cat=0>Top Questions</a></td></tr>";
		foreach($branch as $category){
			if($category['id'] == $cat)
				$catname = $category['name'];
			echo "<tr><td class=body>" . str_repeat("- ", $category['depth'] - 1) . "<a class=body href=$PHP_SELF?cat=$category[id]>$category[name]</a></td></tr>";
		}
		echo "</table>";

	echo "</td><td class=body valign=top>";

	echo "<table>";

	if(isset($questions[$q])){
		echo "<tr><td class=body><b>" . $questions[$q]['title'] . "</b></td></tr>";
		echo "<tr><td class=body>" . nl2br($questions[$q]['text']) . "</td></tr>";

		echo "<tr><td class=body>&nbsp;</td></tr>";
		echo "<form action=$PHP_SELF>";
		echo "<input type=hidden name=cat value=$cat>";
		echo "<input type=hidden name=q value=$q>";
		echo "<tr><td class=body>Did this answer your question? <input class=body type=submit name=action value=Yes> <input class=body type=submit name=action value=No></td></tr>";
		echo "</form>";
		echo "<tr><td class=body>&nbsp;</td></tr>";
	}

	echo "<tr><td class=header>$catname</td></tr>";

	foreach($questions as $question){
		if($question['id'] != $q)
			echo "<tr><td class=body><a class=body href=$PHP_SELF?cat=$cat&q=$question[id]>$question[title]</a></td></tr>";
	}

	echo "</table>";

	echo "</td>";
	echo "</tr></table>";

	incFooter();



