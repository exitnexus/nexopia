<?

function truncate($text,$length){
	$len = strlen($text);

	if($len < $length+3)
		return $text;

	$html=false;

	for($i=0;$i < $len;$i++){
		if($text[$i]=='<' || $text[$i]=='[')
			$html=true;
		elseif($text[$i]=='>' || $text[$i]==']')
			$html=false;

		if($i>$length && $html==false){
			$text = substr($text,0,$i) . "...";
			return $text;
		}
	}
	return $text;
}

function getCensoredWords(){
	global $db;

	$res = $db->query("SELECT word FROM bannedwords WHERE type='word'");

	$censoredWords = array();
	while($line = $res->fetchrow())
		$censoredWords[] = $line['word'];

	return $censoredWords;
}

function censor($text){
	global $cache;

	$censoredWords = $cache->hdget("censorwords", 0, 'getCensoredWords');

	foreach($censoredWords as $word){
		$text = preg_replace("/(?Ui)(^|\W)" . $word . "(\$|\W)/", "\\1" . str_repeat("*",strlen($word)) . "\\2", $text);
		$text = preg_replace("/(?Ui)(^|\W)" . $word . "(\$|\W)/", "\\1" . str_repeat("*",strlen($word)) . "\\2", $text);
	}

	return $text;
}

global $video_regexes;
$video_regexes = array(
	// youtube
	'/(<object width="[0-9]+" height="[0-9]+"><param name="movie" value="http:\/\/www\.youtube\.com\/v\/([\w\-]+)(&rel=1)?"><\/param><param name="wmode" value="transparent"><\/param><embed src="http:\/\/www\.youtube\.com\/v\/[\w\-]+(&rel=1)?" type="application\/x\-shockwave\-flash" wmode="transparent" width="[0-9]+" height="[0-9]+"><\/embed><\/object>)/' => array('YouTube video', 'http://www.youtube.com/watch?v=%s'),

	// break.com
	'/(<object width="[0-9]+" height="[0-9]+"><param name="movie" value="(http:\/\/embed\.break\.com\/[\w=\/]+)"><\/param><embed src="http:\/\/embed\.break\.com\/[\w=\/]+" type="application\/x\-shockwave\-flash" width="[0-9]+" height="[0-9]+"><\/embed><\/object>)(?:<br><font.*?<\/font>)?/s' => array('Break.com video', '%s'),

	// google
	'/(<embed style="width:[0-9]+px; height:[0-9]+px;" id="VideoPlayback" type="application\/x\-shockwave\-flash" src="(http:\/\/video.google.com\/)googleplayer\.swf\?docId=(\-?\d+)[&\w=\-]+" flashvars="(?:&subtitle=on)?">\s*<\/embed>)/' => array('Google video', '%svideoplay?docid=%s'),

	// photobucket
	'/(?:<a .*?'.'>)?(<embed width="[0-9]+" height="[0-9]+" type="application\/x\-shockwave\-flash" wmode="transparent" src="(http:\/\/(?:\w+\.)?photobucket\.com)\/player\.swf\?file=http:\/\/\w+\.photobucket\.com(\/albums\/(?:[\w%\-]+\/)+)([\w%\-]+\.flv)"><\/embed>)(?:<\/a>)?/' => array('Photobucket video', '%s%s?action=view&current=%s'),
	'/(<embed width="[0-9]+" height="[0-9]+" type="application\/x\-shockwave\-flash" wmode="transparent" src="(http:\/\/\w+\.photobucket\.com)\/remix\/player\.swf\?videoURL=http%3A%2F%2F\w+\.photobucket\.com(%2Falbums%2F(?:[\w\-]+%2F)+)([\w\-]+\.(?:flv|pbr))&amp;hostname=\w+\.photobucket\.com"><\/embed>)/' => array('Photobucket video', '%s%s?action=view&current=%s'),

	// vimeo
	'/(<embed src="http:\/\/www\.vimeo\.com\/moogaloop\.swf\?clip_id=\d+" quality="best" scale="exactfit" width="400" height="300" type="application\/x\-shockwave\-flash"><\/embed>)(\s*<br \/>\s*<a href="http:\/\/www\.vimeo\.com\/clip:\d+">.*?<\/a> from <a href="http:\/\/www\.vimeo\.com\/user:\w+">.*?<\/a> on <a href="http:\/\/www\.vimeo\.com\/">Vimeo<\/a>)?/' => array('Vimeo Video', 'hello'),
	// vimeo new
	'/(<object type="application\/x\-shockwave\-flash" width="[0-9]+" height="[0-9]+" data="http:\/\/vimeo\.com\/moogaloop\.swf\?clip_id=\d+&(amp;)?server=vimeo\.com&(amp;)?fullscreen=1(&(amp;)?show_title=1)?(&(amp;)?show_byline=1)?(&(amp;)?show_portrait=1)?(&(amp;)?color=\w+)?">\s*<param name="quality" value="best" \/>\s*<param name="allowfullscreen" value="true" \/>\s*<param name="scale" value="showAll" \/>\s*<param name="movie" value="http:\/\/vimeo\.com\/moogaloop.swf\?clip_id=\d+&(amp;)?server=vimeo.com&(amp;)?fullscreen=1(&(amp;)?show_title=1)?(&(amp;)?show_byline=1)?(&(amp;)?show_portrait=1)?(&(amp;)?color=\w+)?" \/><\/object>)(\s*<br \/><br \/><a href="http:\/\/vimeo.com\/\d+">.*?<\/a> from <a href="http:\/\/vimeo.com\/\w+">\w+<\/a> and <a href="http:\/\/vimeo.com">Vimeo<\/a>\.)?/' => array('Vimeo Video (new)', 'hello'),

	// last.fm
	'/(<style type="text\/css">table\.lfmWidget\d+ td {margin:0 !important;padding:0 !important;border:0 !important;}table.lfmWidget\d+ tr.lfmHead a:hover {background:url\(http:\/\/panther1\.last\.fm\/widgets\/images\/en\/header\/chart\/recenttracks_regular_blue\.png\) no\-repeat 0 0 !important;}table.lfmWidget\d+ tr.lfmEmbed object {float:left;}table.lfmWidget\d+ tr.lfmFoot td.lfmConfig a:hover {background:url\(http:\/\/panther1.last.fm\/widgets\/images\/en\/footer\/blue\.png\) no\-repeat 0px 0 !important;;}table\.lfmWidget\d+ tr.lfmFoot td.lfmView a:hover {background:url\(http:\/\/panther1.last.fm\/widgets\/images\/en\/footer\/blue\.png\) no\-repeat \-85px 0 !important;}table.lfmWidget\d+ tr\.lfmFoot td\.lfmPopup a:hover {background:url\(http:\/\/panther1\.last\.fm\/widgets\/images\/en\/footer\/blue\.png\) no\-repeat \-159px 0 !important;}<\/style>\s+<table class="lfmWidget\d+" cellpadding="0" cellspacing="0" border="0" style="width:184px;"><tr class="lfmHead"><td><a title="[^"]+" href="http:\/\/www.last.fm\/user\/\w+\/" target="_blank" style="display:block;overflow:hidden;height:20px;width:184px;background:url\(http:\/\/panther1.last.fm\/widgets\/images\/en\/header\/chart\/recenttracks_regular_blue.png\) no\-repeat 0 \-20px;text\-decoration:none;"><\/a><\/td><\/tr><tr class="lfmEmbed"><td><object classid="clsid:d27cdb6e\-ae6d\-11cf\-96b8\-444553540000" width="184" height="199" codebase="http:\/\/fpdownload.macromedia.com\/pub\/shockwave\/cabs\/flash\/swflash.cab%23version=7,0,0,0" style="float:left;"><param name="bgcolor" value="6598cd" \/><param name="movie" value="http:\/\/panther1.last.fm\/widgets\/chart\/friends_[0-9]+.swf" \/><param name="quality" value="high" \/><param name="allowScriptAccess" value="sameDomain" \/><param name="FlashVars" value="type=recenttracks&amp;user=\w+&amp;theme=blue&amp;lang=en" \/><embed src="http:\/\/panther1.last.fm\/widgets\/chart\/friends_[0-9]+.swf" type="application\/x\-shockwave\-flash" name="widgetPlayer" bgcolor="6598cd" width="184" height="199" quality="high" pluginspage="http:\/\/www.macromedia.com\/go\/getflashplayer"  FlashVars="type=recenttracks&amp;user=\w+&amp;theme=blue&amp;lang=en" allowScriptAccess="sameDomain"><\/embed><\/object><\/td><\/tr><tr class="lfmFoot"><td style="background:url\(http:\/\/panther1.last.fm\/widgets\/images\/footer_bg\/blue.png\) repeat\-x 0 0;text\-align:right;"><table cellspacing="0" cellpadding="0" border="0" style="width:184px;"><tr><td class="lfmConfig"><a href="http:\/\/www.last.fm\/widgets\/\?widget=chart&amp;colour=blue&amp;chartType=recenttracks&amp;user=\w+&amp;chartFriends=[0-9]+(&amp;path=\w*)?&amp;from=code" title="Get your own widget" target="_blank" style="display:block;overflow:hidden;width:85px;height:20px;float:right;background:url\(http:\/\/panther1.last.fm\/widgets\/images\/en\/footer\/blue.png\) no\-repeat 0px \-20px;text\-decoration:none;"><\/a><\/td><td class="lfmView" style="width:74px;"><a href="http:\/\/www.last.fm\/user\/\w+\/" title="View [^"]+\'s profile" target="_blank" style="display:block;overflow:hidden;width:74px;height:20px;background:url\(http:\/\/panther1.last.fm\/widgets\/images\/en\/footer\/blue.png\) no\-repeat \-85px \-20px;text\-decoration:none;"><\/a><\/td><td class="lfmPopup"style="width:25px;"><a href="http:\/\/www.last.fm\/widgets\/popup\/\?widget=chart&amp;colour=blue&amp;chartType=recenttracks&amp;user=\w+&amp;chartFriends=[0-9]+(&amp;path=\w*)?&amp;from=code&amp;resize=1" title="Load this chart in a pop up" target="_blank" style="display:block;overflow:hidden;width:25px;height:20px;background:url\(http:\/\/panther1.last.fm\/widgets\/images\/en\/footer\/blue.png\) no\-repeat \-159px \-20px;text-decoration:none;" onclick="window.open\(this.href \+ \'&amp;resize=0\',\'lfm_popup\',\'height=299,width=234,resizable=yes,scrollbars=yes\'\); return false;"><\/a><\/td><\/tr><\/table><\/td><\/tr><\/table>)/' => array('Last.fm', 'hello'),

	// imeem
	'/(<object width="\d{1,3}" height="\d{1,3}"><param name="movie" value="http:\/\/media.imeem.com\/\w+\/[^\/]+\/aus=false\/"><\/param>(<param name="wmode" value="transparent"><\/param>)?<embed src="http:\/\/media.imeem.com\/\w+\/[^\/]+\/aus=false\/" type="application\/x-shockwave-flash" width="\d{1,3}" height="\d{1,3}"( wmode="transparent")?'.'><\/embed><\/object>)/' => array('imeem', 'boo'),
);

// look through the string for bits of information we want to collect for whatever
// reason (ie. videos)
function scan_string_for_notables($str){
	global $video_regexes;

	$videos = array();
	foreach ($video_regexes as $regex => $data){
//		$regex = str_replace('<', '&lt;', $regex);
		$matches = array();
		if (preg_match_all($regex, $str, $matches, PREG_PATTERN_ORDER)){
			foreach ($matches[1] as $match){
				$videos[] = $match;
			}
		}
	}

	foreach ($videos as $video)
	{
		post_process_queue("Vid::EmbedHandler", "handle_content_from_profile", array($video));
	}
}

function removeHTML($str){
	global $video_regexes;

	$str = str_replace("<", "&lt;", $str);
//	$str = preg_replace_callback('/(\[video\])(.*?)(\[\/video\])/is', create_function('$str', 'return $str[1] . str_replace("&lt;", "<", $str[2]) . $str[3];') , $str);

	$max_videos = 6;
	$num_videos = 0;

	$match = array();
	foreach ($video_regexes as $regex => $data) {
		$regex = str_replace('<', '&lt;', $regex);

		$pos = 0;
		while (preg_match($regex, $str, $match, PREG_OFFSET_CAPTURE, $pos)) {
			if (++$num_videos <= $max_videos)
				$repl = str_replace('&lt;', '<', $match[1][0]);

			else {
				$vars = array();
				foreach (array_slice($match, 2) as $item)
					$vars[] = urldecode($item[0]);

				$repl = vsprintf("[comment]{$match[1][0]}[/comment]\n[url={$data[1]}]{$data[0]}. Click to view.[/url]", $vars);
			}

			$str = substr_replace($str, $repl, $match[0][1], strlen($match[0][0]));
			$pos = $match[0][1] + strlen($repl);
		}
	}


	for($i = 0; $i <= 32; $i++)
		$str = str_replace("&#$i;", "", $str);

	$str = str_replace("&nbsp;", " ", $str);

	return $str;

//	return str_replace("&amp;", "&", htmlentities($str));
}

function smilies($str){
	global $config, $cache;

	$smilies = $cache->hdget("smilieslength", 0, 'getSmiliesLength');

	foreach($smilies as $smile => $pic)
		if(stripos($str, $smile) !== false)
			$str = preg_replace("/(?i)(\s|\A|>)" . preg_quote($smile) . "(\s|\Z|<)/","\\1<img src=\"$config[smilyloc]$pic.gif\" alt=\"$smile\">\\2", $str);

	return $str;
}

function wrap($text, $length = 50){ //called after parseHTML and smilies!
	$len = strlen($text);
	$wordlen=0;
	$html=false;

	for($i=0;$i < $len;$i++){
		if($text[$i]=='<' || $text[$i]=='[')
			$html=true;
		elseif($text[$i]=='>' || $text[$i]==']')
			$html=false;

		if(in_array($text[$i], array(' ', "\t", "\n", "\r", '[', ']', '<', '>')))
			$wordlen=0;
		else
			$wordlen++;

		if($wordlen>$length && $html==false){
			$text = substr($text,0,$i) . ' ' . substr($text,$i);
			$len++;
			$wordlen=0;
		}
	}
/*
	$pos = 0;
	$matches = array();

	while(preg_match('/(?:<[^>]+>)*\s*([^\s<]{' . ($length+1) . ',}?)/', $text, $matches, PREG_OFFSET_CAPTURE, $pos)){
		$repl = wordwrap($matches[1][0], $length, " ", true);
		$text = substr_replace($text, $repl, $matches[1][1], strlen($matches[1][0]));
		$pos = $matches[1][1] + strlen($repl);
	}
*/
	return $text;
}

function spamfilter($msg){
	global $msgs;

	$length = strlen($msg);

	if($length <= 1){
		$msgs->addMsg("Message is too short.");
		return false;
	}
	if($length > 15000){
		$msgs->addMsg("Message is too long.");
		return false;
	}

//can't break the rest if it's that short
	if($length < 200)
		return true;


	$numlines = substr_count($msg,"\n");
	$numsmileys = substr_count($msg,":");
	$numimages = substr_count($msg,"[img");

	if($numlines > 150){
		$msgs->addMsg("Message is too many paragraphs.");
		return false;
	}
	if($numsmileys > 200){
		$msgs->addMsg("Too many smileys.");
		return false;
	}
/*	if($numsmileys > 50	 && $numsmileys / $length > 0.125 ){
		$msgs->addMsg("Too many smileys.");
		return false;
	}
*/
	if($numimages > 30){
		$msgs->addMsg("Too many images.");
		return false;
	}

	if($length > 500){
		$wordlen=0;
		$html=false;
		$total = 0;

		for($i=0;$i < $length;$i++){
			if($msg[$i]=='<' || $msg[$i]=='[')
				$html=true;
			elseif($msg[$i]=='>' || $msg[$i]==']')
				$html=false;

			if(!$html){
				if(in_array($msg[$i], array(' ', "\t", "\n", "\r", '[', ']', '<', '>'))){
					if($wordlen > 35)
						$total += $wordlen;
					$wordlen=0;
				}else
					$wordlen++;
			}
		}

		if($total > 1000){
			$msgs->addMsg("Too many overly long words.");
			return false;
		}

		$whitespace = strlen(preg_replace('/[^\s]+/', '', $msg));

		if($whitespace > $length / 2){
			$msgs->addMsg("Too much white space.");
			return false;
		}
/*		if($upper > $length / 3){
			$msgs->addMsg("Turn off Capslock.");
			return false;
		}

		if($char > $length / 2){
			$msgs->addMsg("Too many non-text characters");
			return false;
		}
*/
	}

	return true;
}

function parseHTML($str){
	global $config;

	if(strpos($str,'[')===false)
		return $str;

	$pos = 0;
	$match = array();
	while ( preg_match('/\[code\](.*?)\[\/code\]/s', $str, $match, PREG_OFFSET_CAPTURE, $pos) ) {
		$insideText = str_replace('[', '&#91;', $match[1][0]);
		$str = substr_replace($str, $insideText, $match[0][1], strlen($match[0][0]));
		$pos = $match[0][1] + strlen($insideText);
 	}

	$mul = $config['maxusernamelength']; //set to a short variable just to make the regex readable

	$str = preg_replace("/(?Uis)\[comment\](.*)\[\/comment\]/", "",	$str);
	$str = preg_replace("/(?Uis)\[comment=(.*)\]/", 			"",	$str);

//	$str = preg_replace_callback("/\[video\](.*?)\[\/video\]/si", 'videotag_parse', $str);
	$str = preg_replace_callback("/(?Uis)\[img=([^`'\"()<>;\?]{1,200}\.(jpg|jpeg|gif|png|mng|bmp))\]/", 		'forumcode_img',	$str);
	$str = preg_replace_callback("/(?Uis)\[img\]([^`'\"()<>;\?]{1,200}\.(jpg|jpeg|gif|png|mng|bmp))\[\/img\]/",	'forumcode_img',	$str);
	$str = preg_replace_callback("/(?Uis)\[url\]([^`'\"()<>]{1,500})\[\/url\]/",             'forumcode_url1',	$str);  // [url]stuff[/url] -> <a href="stuff">stuff</a>
	$str = preg_replace_callback("/(?Uis)\[url=([^`'\"()<>]{1,500})\]((?s).*)\[\/url\]/",    'forumcode_url2',	$str);
	$str = preg_replace_callback("/(?Uis)\[user=([^`'\"()<>\?]{1,$mul})\]/",                 'forumcode_user',	$str);
	$str = preg_replace_callback("/(?Uis)\[user\]([^`'\"()<>\?]{1,$mul})\[\/user\]/",        'forumcode_user',	$str);
	$str = preg_replace_callback("/(?Uis)\[email\]([^`'\"()<>;\?]{1,100})\[\/email\]/",      'forumcode_email1',	$str);
	$str = preg_replace_callback("/(?Uis)\[email=([^`'\"()<>;\?]{1,100})\](.*)\[\/email\]/", 'forumcode_email2',	$str);
	$str = preg_replace("/(?Uis)\[size=([1-7])\](.*)\[\/size\]/",					"<font size=\"\\1\">\\2</font>",	$str);
	$str = preg_replace("/(?Uis)\[font=([a-zA-Z0-9 ]{1,24})\](.*)\[\/font\]/",		"<font face=\"\\1\">\\2</font>",	$str);
	$str = preg_replace("/(?Uis)\[color=([#a-zA-Z0-9]{1,16})\](.*)\[\/color\]/",	"<font color=\"\\1\">\\2</font>",	$str);
	$str = preg_replace("/(?Uis)\[colour=([#a-zA-Z0-9]{1,16})\](.*)\[\/colour\]/",	"<font color=\"\\1\">\\2</font>",	$str);
	$str = preg_replace("/(?Uis)\[b\](.*)\[\/b\]/", 			"<strong>\\1</strong>",			$str);
	$str = preg_replace("/(?Uis)\[u\](.*)\[\/u\]/", 			"<u>\\1</u>",			$str);
	$str = preg_replace("/(?Uis)\[i\](.*)\[\/i\]/", 			"<em>\\1</em>",			$str);
	$str = preg_replace("/(?Uis)\[sup\](.*)\[\/sup\]/",			"<sup>\\1</sup>",		$str);
	$str = preg_replace("/(?Uis)\[sub\](.*)\[\/sub\]/",			"<sub>\\1</sub>",		$str);
//	$str = preg_replace("/(?Uis)\[code\](.*)\[\/code\]/",		"<pre>\\1</pre>",		$str);
//	$str = preg_replace("/(?Uis)\[\/?code\]/", "", $str);
	$str = preg_replace("/(?Uis)\[strike\](.*)\[\/strike\]/",	"<strike>\\1</strike>",	$str);
	$str = preg_replace("/(?Uis)\[center\](.*)\[\/center\]/",	"<center>\\1</center>",	$str);
	$str = preg_replace("/(?Uis)\[left\](.*)\[\/left\]/",		"<div style=\"text-align:left\">\\1</div>",		$str);
	$str = preg_replace("/(?Uis)\[right\](.*)\[\/right\]/",		"<div style=\"text-align:right\">\\1</div>",	$str);
	$str = preg_replace("/(?Uis)\[justify\](.*)\[\/justify\]/",	"<div style=\"text-align:justify\">\\1</div>",	$str);

	$i=4;
	do{
		$str1=$str;
		$str = preg_replace("/(?Ui)\[quote\]((?s).*)\[\/quote\]/","<br><blockquote>Quote:<hr>\\1<hr></blockquote>",$str);
	}while($str1 != $str && --$i);

//	$str = preg_replace_callback("/(?Ui)\[php\]((?s).*)\[\/php\]/","replace_php_callback",$str);
	$str = str_replace("[hr]", "<hr>",$str);

	$str = str_replace("&#91;", "[", $str);
	$str= parseLists($str);

	return $str;
}
/*
function videotag_parse ($str) {
	$str = $str[1];

	$match = array();

	// youtube
	if (preg_match('/(<object width="425" height="350"><param name="movie" value="http:\/\/www\.youtube\.com\/v\/\w+"><\/param><param name="wmode" value="transparent"><\/param><embed src="http:\/\/www\.youtube\.com\/v\/\w+" type="application\/x-shockwave-flash" wmode="transparent" width="425" height="350"><\/embed><\/object>)/is', $str, $match))
		return $match[1];

	// photobucket
	if (preg_match('/(<embed width="430" height="389" type="application\/x\-shockwave\-flash" wmode="transparent" src="http:\/\/\w+\.photobucket\.com\/player\.swf\?file=http:\/\/\w+\.photobucket\.com\/albums\/(?:[\w%]+\/)+[\w%]+\.flv"><\/embed>)/is', $str, $match))
		return $match[1];

	// google
	if (preg_match('/(<embed style="width:400px; height:326px;" id="VideoPlayback" type="application\/x\-shockwave\-flash" src="http:\/\/video.google.com\/googleplayer\.swf\?docId=\-?\d+[&\w=\-]+" flashvars="">\s*<\/embed>)/', $str, $match))
		return $match[1];

	// break.com
	if (preg_match('/(<object width="425" height="350"><param name="movie" value="http:\/\/embed\.break\.com\/\w+"><\/param><embed src="http:\/\/embed\.break\.com\/\w+" type="application\/x\-shockwave\-flash" width="425" height="350"><\/embed><\/object>)/', $str, $match))
		return $match[1];

	return "[ Invalid video tag format. ]";
}
 */
function forumcode_safeurl($url){
	$replace = array(	'%' => '%25',
						'"' => '%22',
						"'" => '%27',
						'<' => '%3C',
						'>' => '%3E',
						'#' => '%23',
					);

	for($i=0; $i <= 31; $i++)
		$replace[chr($i)] = '';

	for($i=127; $i <= 255; $i++)
		$replace[chr($i)] = '';

	foreach($replace as $s => $r)
		$url = str_replace($s, $r, $url);

	do{
		$url1 = $url;
		$url = preg_replace("/(?i)j(\s*)a(\s*)v(\s*)a(\s*)s(\s*)c(\s*)r(\s*)i(\s*)p(\s*)t(\s*):/","", $url); //ie javascript: with spaces between it
		$url = preg_replace("/(?i)v(\s*)b(\s*)s(\s*)c(\s*)r(\s*)i(\s*)p(\s*)t(\s*):/","", $url); //ie vbscript: with spaces between it
		$url = preg_replace("/(?i)d(\s*)a(\s*)t(\s*)a(\s*):/","", $url); //ie data: with spaces between it, used with: data:text/html;base64,.....
	}while($url1 != $url);

	return $url;
}

function forumcode_url1($matches){
	return "<a class=body target=_new href=\"" . forumcode_safeurl($matches[1]) . "\">$matches[1]</a>";
}

function forumcode_url2($matches){
	return "<a class=body target=_new href=\"" . forumcode_safeurl($matches[1]) . "\">$matches[2]</a>";
}

function forumcode_img($matches){
	return "<img src=\"" . forumcode_safeurl($matches[1]) . "\" border=0>";
}

function forumcode_user($matches){
	return "<a class=body target=_new href=\"/profile.php?uid=" . urlencode(forumcode_safeurl($matches[1])) . "\">$matches[1]</a>";
}

function forumcode_email1($matches){
	return "<a class=body href=\"/mailto:" . forumcode_safeurl($matches[1]) . "\">$matches[1]</a>";
}
function forumcode_email2($matches){
	return "<a class=body href=\"/mailto:" . forumcode_safeurl($matches[1]) . "\">$matches[2]</a>";
}

function replace_php_callback($match){
	$str = $match[1];

	$str = str_replace("&lt;", "<", $str);
	$str = str_replace("&gt;", ">", $str);

	$added=false;
	if(substr($str, 0, 2) != '<?' && strpos($str, '<?') === false){
		$str = "<?\n$str";
		$added=true;
	}

	ob_start();
	highlight_string($str);
	$str = ob_get_contents();
	ob_end_clean();

	if($added)
		$str = str_replace('&lt;?', '', $str);

	return $str;
}

function reduce(&$array, $inc, $condition){
	foreach($array as $key => $value){
		if($array[$key] > $condition)
			$array[$key]+=$inc;
	}
}

function parseLists($data){
	$poslist1o = array();
	$poslist1c = array();
	$poslist1t = array();
	$poslist2o = array();
	$poslist2c = array();
	$poscnt = 0;
	$poscnt2 = 0;

	$validlist1o = array();
	$validlist1c = array();
	$validlist1t = array();
	$validlist2o = array();
	$validlist2c = array();

	$pos1 = 0;
	$pos2 = 0;

	while(1){
		$pos1 = strpos($data, "[", $pos1);
		$pos2 = @strpos($data, "]", $pos1+1);

		if($pos1 === false || $pos2 === false)
			break;

		$tempstr = substr($data, $pos1, $pos2-$pos1+1);
		$tempstr = ereg_replace("[\r\n\t\v\ ]", "",trim($tempstr));

		if(strcasecmp(substr($tempstr,0,5), "[list") == 0){
			if($tempstr[5] == ']'){
				$poslist1o[$poscnt] = $pos1;
				$poslist1c[$poscnt] = $pos2;
				$poslist1t[$poscnt] = 1;
				$poscnt++;
			}else{
				$tempdat = substr($tempstr, 5, 3);
				if( strcasecmp($tempdat, "=1]")==0){
					$poslist1o[$poscnt] = $pos1;
					$poslist1c[$poscnt] = $pos2;
					$poslist1t[$poscnt] = 2;
					$poscnt++;
				}else if(strcasecmp($tempdat, "=a]")==0){
					$poslist1o[$poscnt] = $pos1;
					$poslist1c[$poscnt] = $pos2;
					$poslist1t[$poscnt] = 3;
					$poscnt++;
				}else if(strcasecmp($tempdat, "=i]")==0){
					$poslist1o[$poscnt] = $pos1;
					$poslist1c[$poscnt] = $pos2;
					$poslist1t[$poscnt] = 4;
					$poscnt++;
				}
			}
		}else if(strcasecmp(substr($tempstr,0,7), "[/list]") == 0){
			$poslist2o[$poscnt2] = $pos1;
			$poslist2c[$poscnt2] = $pos2;
			$poscnt2 += 1;
		}

		$pos1 += 1;
	}

	$poscnt2 = 0;
	$poscnt3 = 0;

	while($poscnt3 < count($poslist2o)){
		$lastvalid = -1;
		$poscnt = 0;
		while($poscnt < count($poslist1o)){
			if($poslist1o[$poscnt] != -1 && $poslist1o[$poscnt] > $poslist2o[$poscnt3]){
				break;
			}else if($poslist1o[$poscnt] != -1){
				$lastvalid = $poscnt;
			}

			$poscnt += 1;
		}

		if($lastvalid != -1){
			$validlist1o[$poscnt2] = $poslist1o[$lastvalid];
			$validlist1c[$poscnt2] = $poslist1c[$lastvalid];
			$validlist1t[$poscnt2] = $poslist1t[$lastvalid];
			$validlist2o[$poscnt2] = $poslist2o[$poscnt3];
			$validlist2c[$poscnt2] = $poslist2c[$poscnt3];
			$poscnt2 += 1;

			$poslist1o[$lastvalid] = -1;
		}

		$poscnt3 += 1;
	}

	$poscnt = 0;
	$len = 0;



	while($poscnt < count($validlist1o)){
		if($validlist1t[$poscnt] == 1){
			$data = strreplace($data, $validlist1o[$poscnt], $validlist1c[$poscnt], "<ul>", $len);
			reduce($validlist1o, $len, $validlist1o[$poscnt]);
			reduce($validlist1c, $len, $validlist1o[$poscnt]);
			reduce($validlist2o, $len, $validlist1o[$poscnt]);
			reduce($validlist2c, $len, $validlist1o[$poscnt]);
			$data = strreplace($data, $validlist2o[$poscnt], $validlist2c[$poscnt], "</ul>", $len);
		reduce($validlist1o, $len, $validlist2o[$poscnt]);
			reduce($validlist1c, $len, $validlist2o[$poscnt]);
			reduce($validlist2o, $len, $validlist2o[$poscnt]);
			reduce($validlist2c, $len, $validlist2o[$poscnt]);
		}else if($validlist1t[$poscnt] == 2){
			$data = strreplace($data, $validlist1o[$poscnt], $validlist1c[$poscnt], "<ol>", $len);
			reduce($validlist1o, $len, $validlist1o[$poscnt]);
			reduce($validlist1c, $len, $validlist1o[$poscnt]);
			reduce($validlist2o, $len, $validlist1o[$poscnt]);
			reduce($validlist2c, $len, $validlist1o[$poscnt]);
			$data = strreplace($data, $validlist2o[$poscnt], $validlist2c[$poscnt], "</ol>", $len);
			reduce($validlist1o, $len, $validlist2o[$poscnt]);
			reduce($validlist1c, $len, $validlist2o[$poscnt]);
			reduce($validlist2o, $len, $validlist2o[$poscnt]);
			reduce($validlist2c, $len, $validlist2o[$poscnt]);
		}else if($validlist1t[$poscnt] == 3){
			$data = strreplace($data, $validlist1o[$poscnt], $validlist1c[$poscnt], "<ol type=a>", $len);
			reduce($validlist1o, $len, $validlist1o[$poscnt]);
			reduce($validlist1c, $len, $validlist1o[$poscnt]);
			reduce($validlist2o, $len, $validlist1o[$poscnt]);
			reduce($validlist2c, $len, $validlist1o[$poscnt]);
			$data = strreplace($data, $validlist2o[$poscnt], $validlist2c[$poscnt], "</ol>", $len);
			reduce($validlist1o, $len, $validlist2o[$poscnt]);
			reduce($validlist1c, $len, $validlist2o[$poscnt]);
			reduce($validlist2o, $len, $validlist2o[$poscnt]);
			reduce($validlist2c, $len, $validlist2o[$poscnt]);
		}else if($validlist1t[$poscnt] == 4){
			$data = strreplace($data, $validlist1o[$poscnt], $validlist1c[$poscnt], "<ol type=i>", $len);
			reduce($validlist1o, $len, $validlist1o[$poscnt]);
			reduce($validlist1c, $len, $validlist1o[$poscnt]);
			reduce($validlist2o, $len, $validlist1o[$poscnt]);
			reduce($validlist2c, $len, $validlist1o[$poscnt]);
			$data = strreplace($data, $validlist2o[$poscnt], $validlist2c[$poscnt], "</ol>", $len);
			reduce($validlist1o, $len, $validlist2o[$poscnt]);
			reduce($validlist1c, $len, $validlist2o[$poscnt]);
			reduce($validlist2o, $len, $validlist2o[$poscnt]);
			reduce($validlist2c, $len, $validlist2o[$poscnt]);
		}

		$poscnt += 1;
	}

	$data = str_replace("[*]","<li>", $data);
	return($data);
}
