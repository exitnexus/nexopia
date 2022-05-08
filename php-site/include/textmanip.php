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


function removeHTML($str){
	$str = str_replace("<", "&lt;", $str);

	for($i = 0; $i <= 32; $i++)
		$str = str_replace("&#$i;", "", $str);

	$str = str_replace("&nbsp;", " ", $str);

	return $str;

//	return str_replace("&amp;", "&", htmlentities($str));
}

function smilies($str){
	global $db,$config, $cache;

	$smilies = getSmilies('length');

	foreach($smilies as $smile => $pic){
//		$str1 = str_replace("$smile","<img src=\"$config[smilyloc]$pic.gif\" alt=\"$smile\">",$str);

		$str1 = preg_replace("/(?i)(\s|\A|>)" . preg_quote($smile) . "(\s|\Z|<)/","\\1<img src=\"$config[smilyloc]$pic.gif\" alt=\"$smile\">\\2" ,$str);

		$str = $str1;
	}

	return $str;
}

function wrap($text,$length=50){
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

	return $text;
}

function spamfilter($msg){
	global $msgs;

	$length = strlen($msg);
	$numlines = substr_count($msg,"\n");
	$numsmileys = substr_count($msg,":");
	$numimages = substr_count($msg,"[img");

	if($length <= 1){
		$msgs->addMsg("Message is too short.");
		return false;
	}
	if($length > 15000){
		$msgs->addMsg("Message is too long.");
		return false;
	}
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

	$wordlen=0;
	$html=false;
	$total = 0;
	$upper = 0;
	$lower = 0;
	$whitespace = 0;
	$char = 0;

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

		$charval = ord($msg[$i]);
		if($charval >= ord('A') && $charval <= ord('Z'))
			$upper++;
		elseif($charval >= ord('a') && $charval <= ord('z'))
			$lower++;
		elseif(in_array($msg[$i], array(' ', "\t", "\n", "\r")))
			$whitespace++;
		else
			$char++;
	}

	if($total > 1000){
		$msgs->addMsg("Too many overly long words.");
		return false;
	}

	if($length > 500){
		if($whitespace > $length / 2){
			$msgs->addMsg("Too much white space.");
			return false;
		}
/*		if($upper > $length / 3){
			$msgs->addMsg("Turn off Capslock.");
			return false;
		}
*/
/*		if($char > $length / 2){
			$msgs->addMsg("Too many non-text characters");
			return false;
		}
*/	}

	return true;
}

function parseHTML($str){
	global $config;

	if(strpos($str,'[')===false)
		return $str;

	$pos = 0;
	$match = array();
	while ( preg_match("/\G.*?\[code\](.*?)\[\/code\]/s", $str, $match, PREG_OFFSET_CAPTURE, $pos) ) {
		$insideText = $match[1];
		$insideText = str_replace("[", "&#91;", $insideText);
		$str = substr_replace($str, $insideText, $match[1][1], strlen($match[1][0]));
		$pos = $match[1][1];
	}

	$mul = $config['maxusernamelength']; //set to a short variable just to make the regex readable

	$str = preg_replace("/(?Uis)\[comment\](.*)\[\/comment\]/", "",	$str);
	$str = preg_replace("/(?Uis)\[comment=(.*)\]/", 			"",	$str);

	$str = preg_replace_callback("/(?Uis)\[img=([^`'\"()<>;\?]{1,200}\.(jpg|jpeg|gif|png|mng|bmp))\]/", 		'forumcode_img',	$str);
	$str = preg_replace_callback("/(?Uis)\[img\]([^`'\"()<>;\?]{1,200}\.(jpg|jpeg|gif|png|mng|bmp))\[\/img\]/",	'forumcode_img',	$str);
	$str = preg_replace_callback("/(?Uis)\[url\]([^`'\"()<>]{1,500})\[\/url\]/",             'forumcode_url1',	$str);  // [url]stuff[/url] -> <a href="stuff">stuff</a>
	$str = preg_replace_callback("/(?Uis)\[url=([^`'\"()<>]{1,500})\]((?s).*)\[\/url\]/",    'forumcode_url2',	$str);
	$str = preg_replace_callback("/(?Uis)\[user=([^`'\"()<>;\?]{1,$mul})\]/",                'forumcode_user',	$str);
	$str = preg_replace_callback("/(?Uis)\[user\]([^`'\"()<>;\?]{1,$mul})\[\/user\]/",       'forumcode_user',	$str);
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

