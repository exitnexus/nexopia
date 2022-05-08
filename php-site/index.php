<?

	$login=0;

	require_once("include/general.lib.php");

	$leftBlocks = array('incLoginBlock','incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock','incRecentUpdateProfileBlock');
	$rightBlocks= array('incGoogleBlock','incPollBlock','incActiveForumBlock','incPlusBlock');// ,'incScheduleBlock');//,'incSkinBlock');

	if($userData['loggedIn']){
		$sexes = $userData['defaultsex'];
		$sex = ($sexes == 'Male' ? 'm' : 'f');
		$minage = $userData['defaultminage'];
		$maxage = $userData['defaultmaxage'];
		$news = 'in';
	}else{
		$sexes = array("Male","Female");
		$sex = 'b';
		$minage = 14;
		$maxage = 30;
		$news = 'out';
	}

	$cache->prime(array('online',"news$news",'articles',"top5f:$minage-$maxage","top5m:$minage-$maxage","new5$sex:$minage-$maxage","updt5$sex:$minage-$maxage",'poll','activethread'));

	incHeader(false,$leftBlocks);


	echo "<table width=100% cellspacing=0 cellpadding=3>";

	$news = $cache->get("news$news",300,'getNews');

	foreach($news as $item){
		echo "<tr><td class=header><font size=4><b>$item[title]</b></font></td><td class=header align=right>" . userdate("F j, Y, g:i a",$item['date']) . "</td></tr>";

		echo "<tr><td colspan=2 class=body>";

		echo $item['ntext'];

		echo "</td></tr>";
		echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";
	}


	function getArticles(){
		global $db;

		//only articles in the past 60 days
		$db->prepare_query("SELECT id, time, author, authorid, category, title, ntext, comments FROM articles WHERE moded='y' && time >= ? ORDER BY time DESC LIMIT 5", time() - 86400*600);

		$articledata = array();

		while($line = $db->fetchrow()){
			$line['ntext'] = truncate($line['ntext'],800);
			$articledata[] = $line;
		}
		return $articledata;
	}

	$articledata = $cache->get('articles',180,'getArticles');

	$categories = & new category("cats");

	foreach($articledata as $line){
		echo "<tr><td class=header><font size=4><b>$line[title]</b></font></td><td class=header align=right nowrap>" . userdate("F j, Y, g:i a",$line['time']) . "</td></tr>";
		echo "<tr><td class=header align=left>";

		$root = $categories->makeroot($line['category']);

		$cats = array();
		foreach($root as $category)
			$cats[] = "<a class=header href=articlelist.php?cat=$category[id]>$category[name]</a>";

		echo implode(" > ",$cats);

		echo "</td><td class=header align=right>Posted by: ";

		if($line['authorid'])
			echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else
			echo "$line[author]";

		echo "</td></tr>";
		echo "<tr><td colspan=2 class=body>";

		echo $line['ntext'];

		echo "<br><br>";
		echo "[<a class=body href=article.php?id=$line[id]>Read the whole article</a>] [<a class=body href=comments.php?id=$line[id]>Comments $line[comments]</a>]</td></tr>";
		echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";
	}

	echo "</table>";




	incFooter($rightBlocks);
//poll, lastest posts, new articles, thumbnail of top girl and guy, coming events

