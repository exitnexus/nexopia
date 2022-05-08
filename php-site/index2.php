<?

	$login=0;

	require_once("include/enternexus.lib.php");

	$news = getNews();

	foreach($news as $k => $item)
		$news[$k]['text'] = nl2br(parseHTML(smilies($item['text'])));

	$smarty->assign_by_ref("news",$news);	


	$query = "SELECT id,time,author,authorid,category,title,ntext, comments FROM articles WHERE moded='y' && score>='$config[minarticlescore]' ORDER BY time DESC LIMIT 4";
	$result = mysql_query($query) or die("Query failed");

	$parent = getParentData("cats");	

	$articledata = array();

	while($line = mysql_fetch_assoc($result)){
		$articledata[$line['id']] = $line;

		$articledata[$line['id']]['cats'] = makeroot($parent,$line['category']);
	}

	$smarty->assign_by_ref("articles",$articledata);
	
	$smarty->assign("incCenter",false);
	
	$smarty->display("index.tpl");
