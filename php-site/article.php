<?

	$login=0;

	require_once("include/general.lib.php");

	$id = getREQval('id', 'int');

	if(!isset($id)){
		header("location: articlelist.php");
		exit;
	}

	$sortd = getREQval('sortd', 'string', 'DESC');
	$sortt = getREQval('sortt', 'string', 'time');
	$page = getREQval('page', 'int');

	$article = $cache->get("article-$id");
	
	if(isset($article) && isset($article['time']) && $article['time'] == 0)
	{
		$article = null;
	}
	
	if(!$article){
		$res = $articlesdb->prepare_query("SELECT * FROM articles WHERE id = #", $id);
		$article = $res->fetchrow();
		if (!$article)
			die("Invalid article id");

		$article['author'] = getUserName($article['authorid']);

		$cache->put("article-$id", $article, 86400);
	}

	$res = $articlesdb->prepare_query("SELECT authorid, time, nmsg FROM comments, commentstext WHERE itemid = # && comments.id = commentstext.id ORDER BY comments.id ASC LIMIT 5", $id);

	$comments = array();
	$uids = array();
	while($line = $res->fetchrow()){
		$comments[] = $line;
		$uids[$line['authorid']] = $line['authorid'];
	}
	
	if(count($uids)){
		$usernames = getUserName($uids);
		
		foreach($comments as $k => $v)
			$comments[$k]['author'] = $usernames[$v['authorid']];
	}

	$categories = new category( $articlesdb, "cats");


	$article['text'] = smilies(parseHTML($article['text']));


	$template =  new template("articles/article/article");

	$root = $categories->makeroot($article['category']);
	$template->set("page", $page);
	$template->set("id", $id);
	$template->set("root", $root);
	$template->set("sortd",$sortd);
	$template->set("sortt",$sortt);
	$template->set("article", $article);
	$template->set("count_comments", count($comments) );
	$template->set("comments", $comments);
	$template->display();
