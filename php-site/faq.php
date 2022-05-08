<?

	$login=0;

	require_once("include/general.lib.php");

	$q = getREQval('q', 'int');
	$cat = getREQval('cat', 'int');

	if($q){
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

	$categories = & new category( $db, 'faqcats');

	$branch = $categories->makeBranch();


	if(!empty($q)){
		$answer = $cache->get(array($q, "faqans-$q"));

		if(!$answer){
			$db->prepare_query("SELECT parent, title, text FROM faq WHERE id = ?", $q);
			$answer = $db->fetchrow();

			$cache->put(array($q, "faqans-$q"), $answer, 86400);
		}

		if($answer && !isset($cat))
			$cat = $answer['parent'];
	}

	if(!$cat || !$categories->isValidCat($cat)){
		$query = "SELECT id, parent, priority, title FROM faq ORDER BY priority DESC LIMIT 10";
	}else{
		$query = $db->prepare("SELECT id, parent, priority, title FROM faq WHERE parent = ? ORDER BY priority DESC", $cat);
	}


	$questions = $cache->get(array($cat, "faqquestions-$cat"));

	if(!$questions){
		$db->query($query);

		$questions = array();
		while($line = $db->fetchrow())
			$questions[$line['id']] = $line;

		$cache->put(array($cat, "faqquestions-$cat"), $questions, 86400);
	}

	$catname = "Top Questions";

	incHeader();

	echo "<table align=center><tr>";

	echo "<td class=body valign=top width=150>";

		echo "<table>";
		echo "<tr><td class=header>Categories</td></tr>";
		echo "<tr><td class=body><a class=body href=$_SERVER[PHP_SELF]?cat=0>Top Questions</a></td></tr>";
		foreach($branch as $category){
			if($category['id'] == $cat)
				$catname = $category['name'];
			echo "<tr><td class=body>" . str_repeat("- ", $category['depth'] - 1) . "<a class=body href=$_SERVER[PHP_SELF]?cat=$category[id]>$category[name]</a></td></tr>";
		}
		echo "</table>";

	echo "</td><td class=body valign=top width=500>";

	echo "<table>";

	if(!empty($answer)){
		echo "<tr><td class=header><b>" . $answer['title'] . "</b></td></tr>";
		echo "<tr><td class=body>" . nl2br($answer['text']) . "</td></tr>";

		echo "<tr><td class=body>&nbsp;</td></tr>";
		echo "<form action=$_SERVER[PHP_SELF]>";
		echo "<input type=hidden name=cat value=$cat>";
		echo "<input type=hidden name=q value=$q>";
		echo "<tr><td class=body>Did this answer your question? <input class=body type=submit name=action value=Yes> <input class=body type=submit name=action value=No></td></tr>";
		echo "</form>";
		echo "<tr><td class=body>&nbsp;</td></tr>";
	}

	echo "<tr><td class=header>$catname</td></tr>";

	echo "<tr><td class=body><ul>";
	foreach($questions as $question)
		if($question['id'] != $q)
			echo "<li><a class=body href=$_SERVER[PHP_SELF]?cat=$cat&q=$question[id]>$question[title]</a>";
	echo "</ul></td></tr>";

	echo "</table>";

	echo "</td>";
	echo "</tr></table>";

	incFooter();

