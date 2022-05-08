<?

	$login=0;

	require_once("include/general.lib.php");
	
	$leftBlocks = array('incLoginBlock','incSpotlightBlock','incSortBlock','incNewestMembersBlock','incRecentUpdateProfileBlock'); // 'incSkyAdBlock',
	$rightBlocks= array('incTextAdBlock','incPollBlock','incActiveForumBlock','incPlusBlock');

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
	$template = new template("index/index");
	
	$template->set("limit_ads", $userData['limitads']);
	if(!$userData['limitads']){
		$bannertext = $banner->getbanner(BANNER_VULCAN);
		$template->set("bannertext", $bannertext);
		

		$bannertext = $banner->getbanner(BANNER_BIGBOX);
		$template->set("big_bannertext", $bannertext);
	}
	
	$news = $wiki->getPage("/SiteText/news");
	$template->set("news", $news['output']);


function getArticles(){
	global $articlesdb;

	//only articles in the past 60 days
	$result = $articlesdb->prepare_query("SELECT id, time, authorid, category, title, text, comments, parse_bbcode FROM articles WHERE moded='y' && time >= # ORDER BY time DESC LIMIT 4", time() - 86400*600);

	$articledata = array();

	while($line = $result->fetchrow()){
		$line['ntext'] = $line['text'];
		
		if($line['parse_bbcode'] == 'y')
			$line['ntext'] = nl2br(smilies(parseHTML($line['text'])));
		
		$line['ntext'] = truncate($line['ntext'],800);
		$line['author'] = getUserName($line['authorid']);
		$articledata[] = $line;
	}

	return $articledata;
}

	$articledata = $cache->hdget('articles', 180, 'getArticles');


	$categories = new category( $articlesdb, "cats");

	foreach($articledata as &$line){

		$root = $categories->makeroot($line['category']);
		
		$line['cats'] = $root;
		
	}
	$template->set("articledata", $articledata);
	$template->display();


