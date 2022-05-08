<?

	$login=0;

	require_once("include/general.lib.php");

	$leftBlocks = array('incLoginBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock','incRecentUpdateProfileBlock'); // 'incSkyAdBlock',
	$rightBlocks= array('incTextAdBlock','incSpotlightBlock','incPollBlock','incActiveForumBlock','incPlusBlock');

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

	incHeader(false,$leftBlocks);


	echo "<table width=100% cellspacing=0 cellpadding=3>";




	echo "<tr><td class=header colspan=2><font size=4><b>What's New</b></font></td></tr>";
	echo "<tr><td class=body colspan=2>";

	if(!$userData['limitads']){
		$bannertext = $banner->getbanner(BANNER_VULCAN);

		if($bannertext)
			echo $bannertext;


		$bannertext = $banner->getbanner(BANNER_BIGBOX);

		if($bannertext)
			echo "<table align=right cellspacing=0 cellspacing=0><tr><td width=4></td><td align=center><font size=1>Advertisement</font><br>$bannertext</td></tr></table>";
	}

	$news = $cache->get("news$news",300,'getNews');

	foreach($news as $item)
		echo "<b>$item[title]</b><br>$item[ntext]<br><br>";

	echo "</td></tr>";
	echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";



	echo "<tr><td class=header colspan=2><font size=4><b>Advice for users of Online Communities:</b></font></td></tr>";
	echo "<tr><td class=body colspan=2>";
?>
The mandate of Nexopia is to be an Online Community for young people. To ensure their well being, we have the following advice:<br>
<br>
<b>Nexopia's Advice for users of Online Communities:</b><br>
<ul>
<li> Anyone can be anything on the Internet; someone claiming to be a 15 year old girl may in fact be a 45 year old man.<br>
<li> Don't ever give out personal information on your Profile or to Strangers. Profiles are publicly visible to anyone, so don't include your last name, phone numbers, addresses, school names, or anything else that can lead someone to you.<br>
<li> Only use sites/services like Nexopia that allow you to block Messages from people you're not comfortable with - if you're uncomfortable with the way a conversation is going, just get out, do not worry about offending the other person<br>
<li> If you're going to send personal information, send it as a message, not a comment, as comments are visible to the public.
<li> Take precautions when meeting people - If you're going to meet someone, always take a friend, go to an open and public place and make sure someone you trust knows where you're going and when you'll be back. Taking a cell phone helps provide extra security.
</ul>
<br>
<b>Nexopia's Advice to parents of teens:</b>
<ul>
<li> Be aware of what your child is doing online<br>
<li> Place the computer in a common family area. This way you can easily keep an eye on what they're doing<br>
<li> Have a talk with your child about the dangers associated with any communication medium<br>
<li> Educate yourself. If you understand how the internet works, you can better understand the dangers and risks
</ul>
<br>
<b>What Nexopia does to protect it's users:</b>
<ul>
<li> Every picture put on the site is checked for content before going on your profile - If you can't wear it in the street/beach, it's not allowed<br>
<li> Users can block anyone from sending them Messages and can choose to only receive messages from Friends<br>
<li> Over 300 people check content on the site and remove anything inappropriate<br>
<li> We do not knowingly allow anyone under the age of 14 to join.<br>
<li> We regularly liaise with the Police on issues of legality and safety and do everything we can to co-operate with any investigation involving the site<br>
<li> There is a "Report Abuse" button on every profile. This means that if there is any perceived offense by anyone, that comment or picture can, and if needed, will be removed promptly.
</ul>
<br>
We're proud to work with Edmonton Police Service, Calgary Police Serivce, and Royal Canadian Mounted Police to help keep our users safe. Any suggestions are welcome.<br>
<br>
We would also like to take this opportunity to mention that Nexopia is probably the largest site of its type in Canada, and also one of the most responsible. A lot of other sites do not have any safe guards in place to protect users.<br>
<br>
Your online experience with sites like Nexopia can be safe, as long as you follow common sense rules like those listed above.
<?
	echo "</td></tr>";
	echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";


	function getArticles(){
		global $db;

		//only articles in the past 60 days
		$db->prepare_query("SELECT id, time, author, authorid, category, title, text, comments FROM articles WHERE moded='y' && time >= ? ORDER BY time DESC LIMIT 7", time() - 86400*60);

		$articledata = array();

		while($line = $db->fetchrow()){
			$line['ntext'] = nl2br(smilies(parseHTML($line['text'])));
			$line['ntext'] = truncate($line['ntext'],800);
			$articledata[] = $line;
		}
		return $articledata;
	}

	$articledata = $cache->get('articles',180,'getArticles');

	$categories = & new category( $db, "cats");

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

		echo "<br>";
		echo "</td></tr>";
		echo "<tr><td class=body colspan=2>";
		echo "[<a class=body href=article.php?id=$line[id]>Read the whole article</a>] [<a class=body href=comments.php?id=$line[id]>Comments $line[comments]</a>]</td></tr>";
		echo "<tr><td class=header2 colspan=2>&nbsp;</td></tr>";
	}

	echo "</table>";




	incFooter($rightBlocks);
//poll, lastest posts, new articles, thumbnail of top girl and guy, coming events


