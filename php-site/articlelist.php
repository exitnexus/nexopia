<?

	$login=0;

	require_once("include/general.lib.php");

	$cat = getREQval('cat', 'int');

	$where = array();

	$where[] = "moded='y'";

	$categories = new category( $articlesdb, "cats");

	if($cat!=0){
		$catbranch = $categories->makebranch($cat);
		$cats=array();
		$cats[] = $cat;
		foreach($catbranch as $category)
			$cats[] = $category['id'];
		$where[] = $articlesdb->prepare("category  IN (?)", $cats);
	}

	if(!($day = getREQval('day')))
		$day = userdate("j");
	if(!($month = getREQval('month')))
		$month = userdate("n");
	if(!($year = getREQval('year')))
		$year = userdate("Y");

	if(!empty($day) && !empty($month) && !empty($year)){
		$date = userMkTime(0,0,0, $month, $day, $year);
		$where[] = $articlesdb->prepare("time >= ? && time <= ?", $date - 86400, $date + 86400);
	}


	$res = $articlesdb->query("SELECT * FROM articles WHERE " . implode(" && ",$where) . " ORDER BY time DESC LIMIT 25");

	$articledata = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$articledata[$line['id']] = $line;
		$uids[$line['authorid']] = $line['authorid'];
	}
	
	if(count($uids)){
		$usernames = getUserName($uids);
		
		foreach($articledata as $k => $v)
			$articledata[$k]['author'] = $usernames[$v['authorid']];
	}

	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));


	
	
	$template =  new template("articles/articlelist/articlelist");
	$branch = $categories->makebranch(0);
	$template->set("catselect", makeCatSelect($branch,$cat));

	$template->set("select_list_key", make_select_list_key($months, $month));
	$template->set("select_list_day", make_select_list(range(1,31), $day) );
	$template->set("select_list_year", make_select_list(range(2003,userdate("Y")), $year) );
	

	foreach($articledata as &$line){
		$root = $categories->makeroot($line['category']);
		$line['cat'] = $root;
		$line['text'] = truncate(nl2br(smilies(parseHTML($line['text']))), 1000);
	}

	$template->set("articledata", $articledata);
	$template->display();

