<?

	$login=0;

	require_once("include/general.lib.php");

//spotlight history
	$spotlighthist = $cache->hdget('spotlightpics2', 60, 'getSpotlightPics');
	foreach ($spotlighthist as &$spotlightitem)
	{
		$spotlightitem['pic_url'] = $config['thumbloc'] . $spotlightitem['pic_url'];
	}

//grab the ads
	if(!$userData['limitads']){
		$vulcunbannertext = ""; //$banner->getbanner(BANNER_VULCAN);
		$boxbannertext = $banner->getbanner(BANNER_BIGBOX);
	}

//site news
	$news = $wiki->getPage("/SiteText/news");

//advice
	$advice = $wiki->getPage("/SiteText/frontadvice");

//contests
	$contests = $wiki->getPage("/SiteText/frontcontests");


//articles
	$articledata = $cache->hdget('articles', 180, 'getArticles');

	$categories = new category( $articlesdb, "cats");
	foreach($articledata as & $line)
		$line['cats'] = $categories->makeroot($line['category']);

	$articles = array();
	$articles[0][0] = $articledata[0];
	$articles[0][1] = $articledata[1];
	$articles[1][0] = $articledata[2];
	$articles[1][1] = $articledata[3];


//output
	$template = new template("index/index");

	$template->set("spotlighthist", $spotlighthist);

	$template->set("limit_ads", $userData['limitads']);
	if(!$userData['limitads']){
		$template->set("vulcanbannertext", $vulcunbannertext);
		$template->set("boxbannertext", $boxbannertext);
	}

	$template->set("spaceheight", $skindata['cellspacing']); //HACK!!!

	$template->set("news", $news['output']);
	$template->set("contests", $contests['output']);
	$template->set("advice", $advice['output']);
	$template->set("articledata", $articles);
	$template->display();


function getSpotlightPics(){
	global $config;

	$spotlight = getSpotlight(); //get a new one
	
	$spotlightlist = array($spotlight['userid'] => $spotlight);
	$spotlightlist += getSpotlightHist(); //get old ones

	foreach($spotlightlist as & $user)
		$user['pic_url'] = floor($user['userid']/1000) . "/" . weirdmap($user['userid']) . "/$user[pic].jpg";
	
	return $spotlightlist;
}

function getArticles(){
	global $articlesdb;

	//only articles in the past 60 days
	$result = $articlesdb->prepare_query("SELECT id, time, authorid, category, title, text, comments FROM articles WHERE moded='y' && time >= # ORDER BY time DESC LIMIT 4", time() - 86400*600);

	$articledata = array();

	while($line = $result->fetchrow()){
		$line['ntext'] = $line['text'];
		$line['ntext'] = nl2br(smilies(parseHTML($line['text'])));
		$line['ntext'] = truncate($line['ntext'], 400);
		$line['author'] = getUserName($line['authorid']);
		$articledata[] = $line;
	}

	return $articledata;
}
