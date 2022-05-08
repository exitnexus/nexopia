<?

	$login=0;

	require_once("include/general.lib.php");

//spotlight history
	$spotlighthist = $cache->hdget('spotlightpics2', 60, 'getSpotlightPics');
	foreach ($spotlighthist as &$spotlightitem)
	{
		$spotlightitem['pic_url'] = $spotlightitem['pic_url'];
	}

//grab the ads
	if(!($userData['limitads'] && $userData['premium'])){
		$vulcunbannertext = "";//$banner->getbanner(BANNER_VULCAN);
		$boxbannertext = $banner->getbanner(BANNER_BIGBOX);  // this doesn't use the iframe to allow for expanding adds.
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

	$template->set("limit_ads", ($userData['limitads'] && $userData['premium']));
	if(!($userData['limitads'] && $userData['premium'])){
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
	global $config, $usersdb, $cache;

	$spotlight = getSpotlight(); //get a new one
	
	$spotlightlist = array($spotlight['userid'] => $spotlight);
	$spotlightlist += getSpotlightHist(); //get old ones

	// NEX-801
	// Get any cached image paths
	$picids = array();
	foreach( $spotlightlist as $line ) {
		$picids[] = $line['userid'] . "-" . $line['pic'];
	}	
	$imagepaths = $cache->get_multi($picids, 'galleryimagepaths-');
	
	// figure out if there are any images that didn't have cached paths
	$missingpaths = array_diff($picids, array_keys($imagepaths));

	// If there are any missing paths get them from the DB.
	if(count($missingpaths)){

		// Generate a list of user id, pic id pairs for the query.	
		$keys = array('userid' => '%', 'id' => '#');
		$itemid = array();
		foreach( $spotlightlist as $line ) {
			$itemid[] = array($line['userid'], $line['pic']);
		}
		
		// Get any remaining images
		$res = $usersdb->prepare_query("SELECT userid, revision, id FROM gallerypics WHERE ^", $usersdb->prepare_multikey($keys, $itemid));
		while($line = $res->fetchrow()){

			// Generate the uncached image paths.
			$imagepaths[$line['userid'] . "-" . $line['id']] = $line['revision'] . '/' . weirdmap($line['userid']) . "/" . $line['id'] . ".jpg";

			// Cache the paths.
			$cache->put("galleryimagepaths-$line[userid]-$line[id]", $imagepaths[$line['userid'] . "-" . $line['id']], 86400*7);
		}
	
	}


	foreach($spotlightlist as & $user)
		$user['pic_url'] = $config['thumbloc'] . $imagepaths[$user['userid'] . "-" . $user['pic']];
	
	return $spotlightlist;
}

function getArticles(){
	global $articlesdb;

	//only articles in the past 60 days
	$result = $articlesdb->prepare_query("SELECT id, time, authorid, category, title, text, comments FROM articles WHERE moded='y' && time >= # ORDER BY time DESC LIMIT 4", time() - 86400*600);

	$articledata = array();

	while($line = $result->fetchrow()){
		$line['ntext'] = $line['text'];
		$line['ntext'] = smilies(parseHTML($line['text']));
		$line['ntext'] = truncate($line['ntext'], 400);
		$line['author'] = getUserName($line['authorid']);
		$articledata[] = $line;
	}

	return $articledata;
}
