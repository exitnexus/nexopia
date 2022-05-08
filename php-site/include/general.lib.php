<?
//Copyright Timo Ewalds 2004 all rights reserved.

ini_set("precision","20");

error_reporting (E_ALL);


/*

$acceptips = array(
'local' => '10.',
'timo'	=> '198.166.49.97',
//'timo2'	=> '199.126.18.213',
'newservers' => '216.234.161.192',
'newservers2' => '66.51.127.1',
);

function isPriviligedIP(){
	global $acceptips;
	$REMOTE_ADDR = getSERVERval('REMOTE_ADDR');
	foreach($acceptips as $ip)
		if(substr($REMOTE_ADDR,0,strlen($ip)) == $ip)
			return true;
	return false;
}

if(!isPriviligedIP()){
	include("game.php");
	exit;
}
//*/

$times = array('start' => gettime());



	if(empty($_SERVER['PHP_SELF']))
		$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];

	if(empty($_SERVER['HTTP_HOST']))
		$_SERVER['HTTP_HOST'] = "bad host";

	if(empty($THIS_IMG_SERVER))
		$THIS_IMG_SERVER = $_SERVER['HTTP_HOST'];


$siteStats = array();
$userData = array(	'loggedIn' => false,
					'username' => '',
					'userid' => 0,
					'premium' => false,
					'limitads' => false,
					'debug' => false);

include_once("include/config.inc.php");
include_once("include/defines.php");

include_once("include/mirrors.php");

include_once("include/mysql4.php");

//*
	$db 	= & new multiple_sql_db($databases['main'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$fastdb = & new multiple_sql_db($databases['fast'], $_SERVER['HTTP_HOST'] == $pluswwwdomain); //load balancable

	$archivedb	= & new sql_db($databases['archive']);
	$msgsdb 	= & new sql_db($databases['msgs']); //not balanced fully, check comments in messaging.php
	$commentsdb = & new sql_db($databases['comments']); //balanced, but usercommentstext is balanced based on a value not in that table (ie userid), so rebuilding it will be difficult
	$moddb  	= & new sql_db($databases['mods']);
	$filesdb	= & new sql_db($databases['files']);
	$polldb 	= & new sql_db($databases['polls']);
	$shopdb 	= & new sql_db($databases['shop']);
	$bannerdb	= & new sql_db($databases['banner']);
	$contestdb	= & new sql_db($databases['contest']);
	$weblogdb	= & new sql_db($databases['weblog']);
	$sessiondb	= & new sql_db($databases['session']); //load balancable, but anonymous users are all on one server
	$forumdb	= & new sql_db($databases['forums']);
	$statsdb	= & new sql_db($databases['stats']); //single row, could split updates then add up deltas to get the actual values
	$logdb		= & new sql_db($databases['logs']);
//	$logdb		= & new multiple_sql_db_hash($databases['logsnew']); //load balancable
	$profviewsdb= & new sql_db($databases['profviews']); //load balancable
	$profiledb	= & new sql_db($databases['profile']); //load balancable


	$dbs = array();
	$dbs['main'] 		= & $db;
	$dbs['fast'] 		= & $fastdb;
	$dbs['msgs'] 		= & $msgsdb;
	$dbs['comments']	= & $commentsdb;
	$dbs['mods'] 		= & $moddb;
	$dbs['files']		= & $filesdb;
	$dbs['poll'] 		= & $polldb;
	$dbs['shop'] 		= & $shopdb;
	$dbs['banner']		= & $bannerdb;
	$dbs['contest']		= & $contestdb;
	$dbs['weblog']		= & $weblogdb;
	$dbs['session']		= & $sessiondb;
	$dbs['forums']		= & $forumdb;
	$dbs['stats']		= & $statsdb;
	$dbs['logs']		= & $logdb;
	$dbs['profviews']	= & $profviewsdb;
	$dbs['profile']		= & $profiledb;
//	$dbs['newlogs']		= & $newlogdb;

//	$dbs['archive'] = & $archivedb; //don't backup, note it also doesn't show in the debug output

/*/
	$db1 = & new multiple_sql_db($databases['db1'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$db2 = & new multiple_sql_db($databases['db2'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$db3 = & new multiple_sql_db($databases['db3'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$db4 = & new multiple_sql_db($databases['db4'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
//	$db5 = & new multiple_sql_db($databases['db5'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$db6 = & new multiple_sql_db($databases['db6'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$db7 = & new multiple_sql_db($databases['db7'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$db8 = & new multiple_sql_db($databases['db8'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$db9 = & new multiple_sql_db($databases['db9'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
	$bannerdb = & new multiple_sql_db($databases['banner'], false);
	$archivedb = & new multiple_sql_db($databases['archive'], false);


	$db			= & $db1;
	$moddb  	= & $db2;
	$filesdb	= & $db2;
	$polldb 	= & $db2;
	$shopdb 	= & $db2;
	$contestdb 	= & $db2;
	$weblogdb 	= & $db2;
	$forumdb 	= & $db2;
	$msgsdb 	= & $db3;
	$logdb		= & $db4;
	$commentsdb	= & $db6;
	$profiledb	= & $db7;
	$profviewsdb= & $db8;
	$sessiondb 	= & $db9;
	$fastdb		= & $db9;
	$statsdb	= & $db9;

	$dbs = array();
	$dbs['db1'] = & $db1;
	$dbs['db2'] = & $db2;
	$dbs['db3'] = & $db3;
	$dbs['db4'] = & $db4;
//	$dbs['db5'] = & $db5;
	$dbs['db6'] = & $db6;
	$dbs['db7'] = & $db7;
	$dbs['db8'] = & $db8;
	$dbs['db9'] = & $db9;
	$dbs['bannerdb'] = & $bannerdb;
//	$dbs['archive'] = & $archivedb; //don't backup, note it also doesn't show in the debug output

//*/


/*
include_once("include/cache.php");
	$cache = & new cache("$sitebasedir/cache");
*/

/*
include_once("include/MemCachedClient.php");
	$memcache = & new MemCachedClient($memcacheoptions);
*/

//*
include_once("include/memcached-client.php");
	$memcache = & new memcached($memcacheoptions);
	$pagememcache = & new memcached($pagecacheoptions);

/*/
include_once("include/peclmemcached-client.php");
	$memcache = & new peclmemcached($memcacheoptions);
	$pagememcache = & new peclmemcached($pagecacheoptions);

//*/
//include_once("include/memcache.php"); //hdget is bad!
include_once("include/memcache2.php");
	$cache 		= & new cache($memcache, "$sitebasedir/cache");
	$pagecache	= & new cache($pagememcache, "$sitebasedir/cache");

	$config = $cache->hdget("config", 0, 'getConfig');

//*
include_once("include/errorlog.php");
/*/
include_once("include/errorsyslog.php");
//*/
include_once("include/msgs.php");
include_once("include/menu.php");
include_once("include/auth.php");
include_once("include/blocks.php");
include_once("include/banner6.php");
include_once("include/forums.php");
include_once("include/moderator.php");
include_once("include/abuselog.php");
include_once("include/survey.php");
include_once("include/stats.php");
include_once("include/categories.php");
include_once("include/polls.php");
include_once("include/rating.php");
include_once("include/shoppingcart.php");
include_once("include/payg.php");
include_once("include/messaging.php");
include_once("include/usernotify.php");
include_once("include/usercomments.php");
include_once("include/date.php");
include_once("include/priorities.php");
include_once("include/profileskins.php");
include_once("include/plus.php");
include_once("include/textmanip.php");
include_once("include/smtp.php");
include_once("include/timer.php");
include_once("include/filesystem.php");
include_once("include/terms.php");
include_once("include/contests.php");
include_once("include/weblog.php");

//declared after includes to allow to use all defines
	$msgs = 		& new messages();
	$banner = 		& new bannerclient( $bannerdb, $bannerservers );
	$forums = 		& new forums ( $forumdb );
	$mods = 		& new moderator( $moddb );
	$abuselog = 	& new abuselog( $moddb );
	$polls = 		& new polls( $polldb );
	$shoppingcart = & new shoppingcart( $shopdb );
	$payg = 		& new paygcards( $shopdb );
	$messaging = 	& new messaging( $msgsdb, $archivedb );
	$usernotify =   & new usernotify( $db );
	$usercomments = & new usercomments( $commentsdb, $archivedb );
	$filesystem = 	& new filesystem($filesdb, $THIS_IMG_SERVER);
	$contests =		& new contests( $contestdb );
	$weblog = 		& new weblog( $weblogdb );


//	if($enableCompression)
//		ini_set('zlib.output_compression', 'On');
//		ob_start("ob_gzhandler");

	if(!isset($config['timezone']))
		$config['timezone'] = 6;
	putenv("TZ=GMT" . $config['timezone']);

	header("Vary: Cookie"); //helps fix caching


	timeline('parse', true);

	$mirrors = $cache->hdget("mirrors", 0, 'getMirrors');

	if(!isset($forceserver)) //needed to allow pages to force the server
		$forceserver = getREQval('forceserver', 'bool');

	$cachekey = getREQval('cachekey');

	$cookiedomain = chooseRandomServer($mirrors['www'], true, 'cookie', $_SERVER['HTTP_HOST'], $forceserver); //assume $plus = true since this must be done before login

//	echo "cookiedomain: $cookiedomain, host: $_SERVER[HTTP_HOST], force: $forceserver";

	if(isset($login)){
		if(!isset($userprefs))// || isset($_REQUEST['userprefs']))// && $_REQUEST['userprefs'] == $userprefs))
			$userprefs = array();

		if(!isset($simplepage))
			$simplepage = !empty($cachekey); //equiv to (bool)$cachekey

		$userid = getCOOKIEval('userid', 'int');
		$key = getCOOKIEval('key');

		$userData = auth($userid, $key, ($login > 0), $simplepage, $userprefs);

		if($login == -1 && $userData['loggedIn'])
			die("This is only viewable by people who haven't logged in");
		if($login == 2 && !$userData['premium'])
			die("You must be a plus member to view this page");
	}

	if($cachekey){
		if(checkKey('cache-' . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "cachekey") - 1), $cachekey)){
			$page = $pagecache->get("page-cache-$cachekey");
			if($page){
				echo $page;
				if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)) debugOutput();
				exit;
			}
		}
	}


	$action = getREQval('action');


	$plusserv = ($userData['loggedIn'] && ($mods->isMod($userData['userid']) || $userData['premium']));

	if(count($_POST) == 0 && !$forceserver){
		$webserver = chooseRandomServer($mirrors['www'], $plusserv, 'domain', $_SERVER['HTTP_HOST']);

		if($webserver != "" && $webserver != $_SERVER['HTTP_HOST']){
			header("location: http://" . $webserver . $_SERVER['REQUEST_URI']);
			exit;
		}
	}

	$imgserver = ($userData['loggedIn'] ? chooseImageServer($userData['userid']) : chooseRandomServer($mirrors['image'], $plusserv, 'domain') );

	if($imgserver)
		$imgserver = "http://$imgserver";

	$config['bannerloc']		= $imgserver . $config['bannerdir'];
	$config['uploadfileloc']	= "http://users.nexopia.com" . $config['basefiledir'];
	$config['imageloc']			= $imgserver . $config['imagedir'];
	$config['smilyloc']			= $imgserver . $config['smilydir'];
	$config['imgserver']		= $imgserver;
//	$config['imgserver'] = $imgserver = 'http://plus.img.nexopia.com';

include_once("include/skin.php");

timeline('auth done');


/////////////////////////////////////////////////////
////// Site Specific Functions //////////////////////
/////////////////////////////////////////////////////

function debugOutput(){
	global $userData, $debuginfousers, $times, $dbs, $cache;
	if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)){
		$times['end'] = gettime();
		$total = ($times['end'] - $times['start'])/10;
		$parse = ($times['parse'] - $times['start'])/10;
		$mysql = 0;

		foreach($dbs as $name => $db){
			if(!isset($db->insertdb)){
				$mysql += $db->time;
			}else{
				$mysql += $db->insertdb->time;
				if(isset($db->selectdb) && $db->selectdb !== $db->insertdb)
					$mysql += $db->selectdb->time;
			}
		}

		$mysql /= 10;
		$cachetime = $cache->time/10;
		$php = $total - $parse - $mysql - $cachetime;

		echo "<table><tr><td valign=top>";

		echo "<table>";
		echo "<tr><td class=header>Total time:</td><td class=header align=right>" . number_format($total, 2) . " ms</td></tr>";
		echo "<tr><td class=body>Parse time:</td><td class=body align=right>" . number_format($parse, 2) . " ms</td></tr>";
		echo "<tr><td class=body>Mysql time:</td><td class=body align=right>" . number_format($mysql, 2) . " ms</td></tr>";
		echo "<tr><td class=body>Memcache time:</td><td class=body align=right>" . number_format($cachetime, 2) . " ms</td></tr>";
		echo "<tr><td class=body>PHP time:</td><td class=body align=right>" . number_format($php, 2) . " ms</td></tr>";
		echo "</table>";

		echo "</td><td valign=top>";

		echo "<table>";

		$start = $times['start'];
		unset($times['start']);

		$names = array_keys($times);
		$times = array_values($times);
		$num = count($names);
		$rows = 6;
		$cols = ceil($num/$rows);

		echo "<tr><td class=header colspan=" . (3*$cols-1) . " align=center>Timeline:</td></tr>";

		$total = $rows*$cols;
		for($i = 0; $i < $total; $i++){
			$col = $i%$cols;
			$row = floor($i/$cols);

			$j = $col*$rows + $row;

			if( $i % $cols == 0)
				echo "<tr>";

			if($j < $num){
				echo "<td class=body align=right>" . number_format(($times[$j] - $start)/10, 2) . " ms</td><td class=body>$names[$j]</td>";
				if($col < $cols-1)
//					echo "<td width=1 bgcolor=#000000></td>";
					echo "<td class=body>&nbsp; | &nbsp;</td>";
			}

			if($i % $cols == $cols - 1)
				echo "</tr>\n";
		}
		if($i > 0)
			echo "</tr>\n";


//		foreach($times as $k => $v)
//			echo "<tr><td class=body align=right>" . number_format(($v - $times['start'])/10, 2) . " ms</td><td class=body>$k</td></tr>";
		echo "</table>";

		echo "</td></tr></table>";

		outputQueries();
	}else{
		$endTime = gettime();
		$total = ($endTime - $times['start'])/10;

		echo "<!-- Creation time: " . number_format($total,4) . " ms -->";
	}
}

function closeAllDBs(){
	global $dbs;

//would use a foreach($dbs as & $db), but that's php5 only. Not calling by reference fails.
	$names = array_keys($dbs);

	foreach($names as $name)
		$dbs[$name]->close();
}

function blank(){ //multi arg version of empty
	$args = func_get_args();
	foreach($args as $arg)
		if(empty($arg))
			return true;
	return false;
}

function makeKey($id, $myuserid = 0){
	global $userData;

	if(!$myuserid){
		if(empty($userData['userid'])){
			trigger_error("Must have a userid to generate a key", E_USER_NOTICE); //: " . var_export($userData, true)
		}else{
			$myuserid = $userData['userid'];
		}
	}

	return strtoupper(substr(base_convert(md5("$myuserid:blah:$id"), 16, 36), 0, 10));
}

function checkKey($id, $key){
	return ($key == makeKey($id));
}

function timeline($name, $force = false){
	global $userData, $times;

	if($force || $userData['debug'])
		$times[$name] = gettime();
}

function zipflush(){
	global $enableCompression;
	if($enableCompression){
		echo "<!-- ";
		for($i=0;$i<4000;$i++)
			echo chr(rand(65,126));
		echo " -->\n";
	}
	flush();
}

function capchatext($seed){
	return strtoupper(base_convert(substr(md5($seed),2,10),16,36));
}

function outputQueries(){
	global $dbs, $cache;

	foreach($dbs as $name => $db)
		$db->outputQueries($name);

	$cache->outputActions();
}

function getNews(){
	global $userData,$db;
	if($userData['loggedIn'])
		$type="inside";
	else
		$type="outside";
	$db->prepare_query("SELECT title, date, ntext FROM news WHERE type IN ('both',?) ORDER BY date DESC", $type);

	$ret=array();
	while($line = $db->fetchrow())
		$ret[]=$line;
	return $ret;
}

function getConfig(){
	global $db;

	$db->query("SELECT name,value FROM config");

	$config=array();
	while($conf = $db->fetchrow())
		$config[$conf['name']] = $conf['value'];

	return $config;
}

function getBlocks(){
	global $db;

	$db->prepare_query("SELECT funcname, side FROM blocks WHERE enabled = 'y' ORDER BY priority ASC");

	$blocks = array();
	while($line = $db->fetchrow())
		$blocks[$line['side']][] = $line['funcname'];

	return $blocks;
}

function blocks($side){
	global $cache;

	$blocks = $cache->hdget("blocks", 0, "getBlocks");

	if(count($blocks[$side]))
		foreach($blocks[$side] as $funcname)
			$funcname($side);
}
//*
function editBox($text = ""){
	global $config, $cache;

	$smilyusage = $cache->hdget("smilyuseage", 0, 'getSmileyCodesByUsage');

	echo "<script>";
	echo "var smileypics = new Array('" . implode("','", $smilyusage) . "');";
	echo "var smileycodes = new Array('" . implode("','", array_keys($smilyusage)) . "');";
	echo "var smileyloc = '$config[smilyloc]';";
	echo "document.write(editBox(\"" . htmlentities(str_replace("\r","",str_replace("\n","\\n",$text))) . "\",true));</script>";
	echo "<noscript><textarea cols=70 rows=10 name=msg></textarea></noscript>";
	return;
}
/*/
function editBox($text = ""){
	global $config, $cache;

	$smilyusage = $cache->hdget("smilyuseage", 0, 'getSmileyCodesByUsage');

	echo "<table cellspacing=0 align=center>";
	echo "<tr><td align=center>";
		echo "<input class=body type=button class=button accesskey=b name=addbbcode0 value=' B ' style='font-weight:bold; width: 30px' onClick='bbstyle(0)'>";
		echo "<input class=body type=button class=button accesskey=i name=addbbcode2 value=' i ' style='font-style:italic; width: 30px' onClick='bbstyle(2)'>";
		echo "<input class=body type=button class=button accesskey=u name=addbbcode4 value=' u ' style='text-decoration: underline; width: 30px' onClick='bbstyle(4)'>";
		echo "<input class=body type=button class=button accesskey=q name=addbbcode6 value='Quote' style='width: 50px' onClick='bbstyle(6)'>";
		echo "<input class=body type=button class=button accesskey=p name=addbbcode8 value='Img' style='width: 40px'  onClick='bbstyle(8)'>";
		echo "<input class=body type=button class=button accesskey=w name=addbbcode10 value='URL' style='text-decoration: underline; width: 40px' onClick='bbstyle(10)'>";
		echo "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[font=' + this.options[this.selectedIndex].value + ']', '[/font]');this.selectedIndex=0\">";
			echo "<option value='0'>Font</option>";
			echo "<option value='Arial' style='font-family:Arial'>Arial</option>";
			echo "<option value='Times' style='font-family:Times'>Times</option>";
			echo "<option value='Courier' style='font-family:Courier'>Courier</option>";
			echo "<option value='Impact' style='font-family:Impact'>Impact</option>";
			echo "<option value='Geneva' style='font-family:Geneva'>Geneva</option>";
			echo "<option value='Optima' style='font-family:Optima'>Optima</option>";
		echo "</select>";
		echo "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[color=' + this.options[this.selectedIndex].value + ']', '[/color]');this.selectedIndex=0\">";
			echo "<option style='color:black; background-color: #FFFFFF' value='0'>Color</option>";
			echo "<option style='color:darkred; background-color: #DEE3E7' value='darkred'>Dark Red</option>";
			echo "<option style='color:red; background-color: #DEE3E7' value='red'>Red</option>";
			echo "<option style='color:orange; background-color: #DEE3E7' value='orange'>Orange</option>";
			echo "<option style='color:brown; background-color: #DEE3E7' value='brown'>Brown</option>";
			echo "<option style='color:yellow; background-color: #DEE3E7' value='yellow'>Yellow</option>";
			echo "<option style='color:green; background-color: #DEE3E7' value='green'>Green</option>";
			echo "<option style='color:olive; background-color: #DEE3E7' value='olive'>Olive</option>";
			echo "<option style='color:cyan; background-color: #DEE3E7' value='cyan'>Cyan</option>";
			echo "<option style='color:blue; background-color: #DEE3E7' value='blue'>Blue</option>";
			echo "<option style='color:darkblue; background-color: #DEE3E7' value='darkblue'>Dark Blue</option>";
			echo "<option style='color:indigo; background-color: #DEE3E7' value='indigo'>Indigo</option>";
			echo "<option style='color:violet; background-color: #DEE3E7' value='violet'>Violet</option>";
			echo "<option style='color:white; background-color: #DEE3E7' value='white'>White</option>";
			echo "<option style='color:black; background-color: #DEE3E7' value='black'>Black</option>";
		echo "</select>";
		echo "<select style='width: 60px' class=body onChange=\"if(this.selectedIndex!=0) bbfontstyle('[size=' + this.options[this.selectedIndex].value + ']', '[/size]');this.selectedIndex=0\">";
			echo "<option value=0>Size</option>";
			echo "<option value=1>Tiny</option>";
			echo "<option value=2>Small</option>";
			echo "<option value=3>Normal</option>";
			echo "<option value=4>Large</option>";
			echo "<option value=5>Huge</option>";
		echo "</select>";
	echo "</td>\n";

	$cols = 4;
	$rows = 6;

	echo "<td rowspan=2>";
	echo "<table cellspacing=0 cellpadding=3 border=1 style=\"border-collapse: collapse\">";
	$num = min($cols * $rows, ceil(count($smilyusage)/$cols)*$cols);
	for($i=0; $i < $num; $i++){
		list($code, $val) = each($smilyusage);
		if($i % $cols == 0)
			echo "<tr>";

		echo"<td class=body><div name=smiley$i id=smiley$i>";
		if($i < count($smilyusage))
			echo"<a href=\"javascript:emoticon('$code')\"><img src=\"$config[smilyloc]$val.gif\" alt=\"$val\" border=0></a>";
		echo "</div></td>";

		if($i % $cols == $cols - 1)
			echo "</tr>";
	}
	echo "<tr><td colspan=$cols class=body>";

	echo "<table width=100%><tr>";
	echo "<td class=body><a class=body href=\"javascript:smiliespage(-1);\">Prev</a></td>";
	echo "<td class=body align=right><a class=body href=\"javascript:smiliespage(1);\">Next</a></td>";
	echo "</tr></table>";

	echo "</td></tr>";
	echo "</table>";
	echo "</td>\n";

	echo "</tr>";
	echo "<tr><td align=center><textarea style='width: 400px' class=header cols=70 rows=10 name=msg wrap=virtual onSelect=\"storeCaret(this);\" onClick=\"storeCaret(this);\" onKeyUp=\"storeCaret(this);\">$text</textarea></td></tr>\n";
	echo "</table>\n";

	echo "<script>";
	echo "var smileypics = new Array('" . implode("','", $smilyusage) . "');";
	echo "var smileycodes = new Array('" . implode("','", array_keys($smilyusage)) . "');";
	echo "var smileyloc = '$config[smilyloc]';";
//	echo "putinnerHTML('editdiv', editBox(\"" . htmlentities(str_replace("\r","",str_replace("\n","\\n",$text))) . "\",true));";
	echo "</script>";
//	echo "<noscript><textarea cols=70 rows=10 name=msg></textarea></noscript>";
	return;
}
//*/

function addComment($id,$msg,$preview="changed",$params=array()){
	global $userData,$db;

	if(!$userData['loggedIn'])
		return;

	if(trim($msg)=="")
		return;

	$nmsg = removeHTML($msg);
	$nmsg2 = parseHTML($nmsg);
	$nmsg3 = smilies($nmsg2);
	$nmsg3 = wrap($nmsg3);
	$nmsg3 = nl2br($nmsg3);

	if($preview=="Preview" || ($nmsg != $nmsg2 && $preview=="changed")){
		incHeader();

		echo "Some changes have been made (be it smilies, html removal, or code to html conversions). Here is a preview of what the post will look like:<hr><blockquote>\n";

		echo $nmsg3;

		echo "</blockquote><hr>\n";

		echo "<table width=100% cellspacing=0>";



		echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
		echo "<input type=hidden name='id' value='$id'>\n";
		echo "<input type=hidden name='action' value='comment'>\n";

		foreach($params as $k => $v)
			echo "<input type=hidden name='$k' value='$v'>\n";

		echo "<tr><td class=header>";

		editBox($nmsg,true);

		echo "</td></tr>\n";
		echo "<tr><td class=header align=center><input type=submit name=postaction value=Preview> <input type=submit name=postaction value='Post' accesskey='s' onClick='checksubmit()'></td></tr>\n";
		echo "</form>";

		echo "</table>";

		incFooter();
		exit(0);
	}

	$old_user_abort = ignore_user_abort(true);

	$result = $db->prepare_query("SELECT id FROM comments WHERE itemid = ? && time > ? && authorid = ?", $id, time() - 15, $userData['userid']);

	if($db->numrows($result)>0) //double post
		return false;


	$db->prepare_query("INSERT INTO comments SET itemid = ?, author = ?, authorid = ?, time = ?", $id, $userData['username'], $userData['userid'], time());

	$insertid = $db->insertid();

	$db->prepare_query("INSERT INTO commentstext SET id = ?, msg = ?, nmsg = ?", $insertid, $msg, $nmsg3);

	$db->prepare_query("UPDATE articles SET comments = comments+1 WHERE id = ?", $id);

	ignore_user_abort($old_user_abort);
}



function pageList($link,$page,$numpages,$class='body'){
	global $config;

	if($numpages<=0)
		$numpages=1;

	$connector = strpos($link,"?")===false ? "?" : "&";

	$start = ($page+1>$config['pagesInList'] ? ($page+1)-$config['pagesInList'] : 1);
	$finish = ($page+$config['pagesInList']<$numpages ? ($page+1)+$config['pagesInList'] : $numpages);

	$str = array();

	if($page > 0){
		if($start > 1)
			$str[] = "<a class=$class href=$link" . $connector . "page=0>|&lt;</a>";
		$str[] = "<a class=$class href=$link" . $connector . "page=" . ($page-1) . ">&lt;</a>";
	}

	for($i=$start; $i<=$finish; $i++){
		if($i-1 == $page)
			$str[] = "[$i]";
		else
			$str[] = "<a class=$class href=$link" . $connector . "page=" . ($i-1) . ">$i</a>";
	}

	if($page < $numpages-1){
		$str[] = "<a class=$class href=$link" . $connector . "page=" . ($page+1) . ">></a>";
		if($finish < $numpages)
			$str[] = "<a class=$class href=$link" . $connector . "page=" . ($numpages-1) . ">>|</a>";
	}

	return implode(" ", $str);
}

/////////////////////////////////////////////////////
////// General Functions ////////////////////////////
/////////////////////////////////////////////////////


//replaces ip2long, always returns unsigned values
function ip2int($ip){
	$parts = explode(".",$ip);
	if(count($parts) != 4)
		return 0;
	$int = ($parts[0]<<24)|($parts[1]<<16)|($parts[2]<<8)|($parts[3]);
	if($int >= pow(2,31))
		$int -= pow(2,32);
	return $int;
}

function getip(){
	static $ip = 0;

	if($ip)
		return $ip;

	$REMOTE_ADDR = getSERVERval('REMOTE_ADDR');
	$HTTP_X_FORWARDED_FOR = getSERVERval('HTTP_X_FORWARDED_FOR');

	if(isset($HTTP_X_FORWARDED_FOR) && substr($HTTP_X_FORWARDED_FOR, 0, 7) != "unknown"){
		$ips = explode(",", $HTTP_X_FORWARDED_FOR);

		foreach($ips as $i)
			if(isRoutableIP(trim($i)))
				$ip = trim($i);
	}

	if(!$ip)
		$ip = $REMOTE_ADDR;

	return $ip;
}

function isRoutableIP($ip){
	$parts = explode(".", $ip);
	return !(	$parts[0] == 0 || 											// 0.0.0.0/8
				$parts[0] == 10 || 											// 10.0.0.0/8
			(	$parts[0] == 172 && $parts[1] >= 16 && $parts[1] <= 31) ||	// 172.16.0.0/12
			(	$parts[0] == 192 && $parts[1] == 168) );					// 192.168.0.0/16
}

function getAge($birthday,$decimals=0) {
	if(!$decimals){
		$age = userdate("Y") - userdate("Y",$birthday);
		if(userdate("m",$birthday) > userdate("m"))
			$age--;
		elseif(userdate("m",$birthday) == userdate("m") && userdate("j",$birthday) > userdate("j"))
			$age--;

		return $age;
	}else{
		$age = userdate("Y") - userdate("Y",$birthday) + ((userdate("z") - userdate("z",$birthday))/365);

		return number_format($age,$decimals);
	}
}

function gettime(){
	list($usec, $sec) = explode(" ",microtime());
	return (10000*((float)$usec + (float)$sec));
}

function countCodeLines ($directory,$recur=true,$output=false){
	if(substr($directory,-1)=="/")
		$directory=substr($directory,0,-1);

	$totalCodeLines=0;

	$dir = opendir($directory);
	if(!$dir){
		trigger_error("can't open $directory",E_USER_NOTICE);
		return 0;
	}

	while ($item = readdir($dir)) {
		if (is_dir($directory . "/" . $item) && ($item != ".") && ($item != "..") && $recur) { //check to see if we need to walk into another directory
			$totalCodeLines += countCodeLines($directory . "/" . $item); //recursive directory walking
		} elseif (strrchr($item, ".") == ".php") { //count only php files
			$lines = file($directory . "/" . $item);

 			$fileLines=0;

			foreach ($lines as $line) {
				//count lines containg a semi-colon or
				//lines containing either an opened brace or a closed brace
				if (preg_match("/\;/", $line) || (preg_match("/\{/", $line) || preg_match("/\}/", $line))) {
					$fileLines++;
				}
			}

			if($output)
				echo $directory . "/" . $item . " : $fileLines<br>\n";

			$totalCodeLines+=$fileLines;
		}
	}
	return $totalCodeLines; //return the final count
}

function searchCode($search,$directory,$recur=true){
	if(substr($directory,-1)=="/")
		$directory=substr($directory,0,-1);

	$found = array();

	$dir = opendir($directory);

	while ($item = readdir($dir)) {
		if (is_dir($directory . "/" . $item) && ($item != ".") && ($item != "..") && $recur) { //check to see if we need to walk into another directory
			$found = array_merge($found,searchCode($search,$directory . "/" . $item)); //recursive directory walking
		} elseif (strrchr($item, ".") == ".php") { //count only php files
			$lines = file($directory . "/" . $item);

			foreach ($lines as $linenum => $line) {
				if(stristr($line,$search)!==false)
					$found[] = array('file' => $directory . "/" . $item, 'linenum' => $linenum+1);

			}
		}
	}
	return $found; //return the final count
}

function randomize(){
	if(!defined("RANDOMIZED")){
		define("RANDOMIZED", true);

		srand((double)microtime()*1000000);
	}
}


function chooseWeight($items, $int = true){ //array($id => $weight);
	$totalweight = 0;

	foreach($items as $weight)
		$totalweight += $weight;

	if($totalweight == 0)
		return false;

	if($int)
		$rand = rand(0,$totalweight-1);
	else
		$rand = (rand() / (double)getrandmax()) * $totalweight;

	foreach($items as $id => $weight){
		$rand -= $weight;
		if($rand < 0)
			return $id;
	}

	return false;
}

function & getREQval($name, $type = 'string', $default = null){
	$val = (isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default);
	settype($val, $type);
	return $val;
}

function & getGETval($name, $type = 'string', $default = null){
	$val = (isset($_GET[$name]) ? $_GET[$name] : $default);
	settype($val, $type);
	return $val;
}

function & getPOSTval($name, $type = 'string', $default = null){
	$val = (isset($_POST[$name]) ? $_POST[$name] : $default);
	settype($val, $type);
	return $val;
}

function & getCOOKIEval($name, $type = 'string', $default = null){
	$val = (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
	settype($val, $type);
	return $val;
}

function & getFILEval($name){
	return (isset($_FILES[$name]) ? $_FILES[$name] : false);
}

function & getSERVERval($name){
	return (isset($_SERVER[$name]) ? $_SERVER[$name] : false);
}

/*
function & getREQval($name, $default = ''){
	return (!isset($_REQUEST[$name]) ? $default : $_REQUEST[$name]);
}

function & getGETval($name, $default = ''){
	return (!isset($_GET[$name]) ? $default : $_GET[$name]);
}

function & getPOSTval($name, $default = ''){
	return (!isset($_POST[$name]) ? $default : $_POST[$name]);
}

function & getCOOKIEval($name, $default = ''){
	return (!isset($_COOKIE[$name]) ? $default : $_COOKIE[$name]);
}

function & getFILEval($name, $default = false){
	return (!isset($_FILES[$name]) ? $default : $_FILES[$name]);
}
*/

function arraydump($array){ //meant to take a 2 dimensional array dumped from $db->fetchrowset or equiv
	echo "<table>";

	$first = true;
	foreach($array as $k => $row){

		if($first){
			echo "<tr><td class=header></td>";

			foreach($row as $n => $v)
				echo "<td class=header align=center>$n</td>";

			echo "</tr>";

			$first = false;
		}

		echo "<tr>";
		echo "<td class=header>$k</td>";

		foreach($row as $v)
			echo "<td class=body>$v</td>";

		echo "</tr>";
	}

	echo "</table>";
}


function getStaticValue($id, $restricted = true){ //default return restricted content
	global $db, $cache;

	$data = false;//$cache->get("staticpages-$id");

	if(!$data){
		$db->prepare_query("SELECT content, restricted FROM staticpages WHERE id = #", $id);
		$data = $db->fetchrow();

		if(!$data)
			return false;

		$cache->put("staticpages-$id", $data, 86400);
	}

	if(!$restricted && $data['restricted'] == 'y') //if calling from pages.php, and it's restricted content, don't show it.
		return false;

	return $data['content'];
}

/////////////////////////////////////////////////////
////// PHP rewrite or general funcs /////////////////
/////////////////////////////////////////////////////

function strrposstr($haystack,$needle,$offset=0){
	if($offset!=0)
		$offset=strlen($haystack)-$offset;
	return strlen($haystack) - (strpos(strrev($haystack), strrev($needle),$offset) + strlen($needle));
}

function strreplace($string, $start, $end, $new, &$len){
	$tempstr = substr($string, 0, $start).$new.substr($string, $end+1);
	$len = strlen($new)-($end-$start+1);

	return($tempstr);
}

if(!function_exists('array_search')){
function array_search($needle,$stack,$strict=false){
	if($strict){
		foreach($stack as $key => $value)
			if($value===$needle)
				return $key;
	}else{
		foreach($stack as $key => $value)
			if($value==$needle)
				return $key;
	}
	return false;
}
}

if(!function_exists('str_split')){
function str_split($str, $len = 1){
	$length = strlen($str);

	if($len <= 0)
		return false;

	$ret = array();

	for($i=0; $i < $length; $i += $len)
		$ret[] = substr($str, $i, $len);

	return $ret;
}
}

if(!function_exists('array_combine')){
function array_combine($a, $b) {
   $num = count($a);
   if ($num != count($b) || $num == 0) return false;

   $a = array_values($a);
   $b = array_values($b);

   $c = array();
   for ($i = 0; $i < $num; $i++) {
       $c[$a[$i]] = $b[$i];
   }
   return $c;
}
}

function rmdirrecursive($dir){
	$handle = opendir($dir);

	while(false!==($FolderOrFile = readdir($handle))){
		if($FolderOrFile != "." && $FolderOrFile != ".."){
			if(is_dir("$dir/$FolderOrFile"))
				rmdirrecursive("$dir/$FolderOrFile");
			else
				unlink("$dir/$FolderOrFile");
		}
	}
	closedir($handle);

	return rmdir($dir);
}

function mkdirrecursive($dir){
	$dirs = explode("/", $dir);

	umask(0);

	$basedir = "/";
	foreach($dirs as $dir){
		if(!is_dir("$basedir/$dir"))
			@mkdir("$basedir/$dir",0777);
		$basedir .= "/$dir";
	}
}

/////////////////////////////////////////////////////
////// Make Select List /////////////////////////////
/////////////////////////////////////////////////////

function make_select_list( $list, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $v )
			$str .= "<option value=\"$v\" selected> $v";
		else
			$str .= "<option value=\"$v\"> $v";
	}

	return $str;
}

function make_select_list_multiple( $list, $sel = array() ){
	$str = "";
	foreach($list as $k => $v){
		if(in_array($v, $sel))
			$str .= "<option value=\"$v\" selected> $v";
		else
			$str .= "<option value=\"$v\"> $v";
	}

	return $str;
}

function make_select_list_key( $list, $sel = null ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $k )
			$str .= "<option value=\"$k\" selected> $v";
		else
			$str .= "<option value=\"$k\"> $v";
	}

	return $str;
}

function make_select_list_multiple_key( $list, $sel = array() ){
	$str = "";
	foreach($list as $k => $v){
		if(in_array($k, $sel))
			$str .= "<option value=\"$k\" selected> $v";
		else
			$str .= "<option value=\"$k\"> $v";
	}

	return $str;
}

function make_select_list_key_key( $list, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $v )
			$str .= "<option value=\"$k\" selected> $k";
		else
			$str .= "<option value=\"$k\"> $k";
	}

	return $str;
}

function make_select_list_col_key( $list, $col, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $k )
			$str .= "<option value=\"$k\" selected> $v[$col]";
		else
			$str .= "<option value=\"$k\"> $v[$col]";
	}

	return $str;
}

function make_radio($name, $list, $sel = "", $class = 'body'){
	$str = "";
	foreach($list as $k => $v){
		$str .= "<input type=radio name=\"$name\" value=\"$v\" id=\"$name/$k\"";
		if( $sel == $v )
			$str .= " checked";
		$str .= "><label for=\"$name/$k\" class=$class> $v</label> ";
	}

	return $str;
}

function make_radio_key($name, $list, $sel = "", $class = 'body' ){
	$str = "";
	foreach($list as $k => $v){
		$str .= "<input type=radio name=\"$name\" value=\"$k\" id=\"$name/$k\"";
		if( $sel == $k )
			$str .= " checked";
		$str .= "><label for=\"$name/$k\" class=$class> $v</label> ";
	}

	return $str;
}

function makeCatSelect($branch, $category = null){
	$str="";

	foreach($branch as $cat){
		$str .= "<option value='$cat[id]'";
		if($cat['id'] == $category)
			$str .= " selected";
		$str .= ">";
		$str .= str_repeat("- ", $cat['depth']) . $cat['name'];
	}
	return $str;
}

function makeCatSelect_multiple($branch, $category = array()){
	$str="";

	foreach($branch as $cat){
		$str .= "<option value='$cat[id]'";
		if(in_array($cat['id'], $category))
			$str .= " selected";
		$str .= ">";
		$str .= str_repeat("- ", $cat['depth']) . $cat['name'];
	}
	return $str;
}

function makeCheckBox($name, $title, $checked = false){
	return "<input type=checkbox id=\"$name\" name=\"$name\"" . ($checked ? ' checked' : '') . "><label for=\"$name\"> $title</label>";
}

/////////////////////////////////////////////////////
////// User Functions ///////////////////////////////
/////////////////////////////////////////////////////

function getFriendsList($uid){
	global $cache, $db;

	$friends = $cache->get(array($uid, "friends-$uid"));

	if($friends === false){
		$db->prepare_query("SELECT friendid, username FROM friends,users WHERE friends.userid = ? && users.userid=friendid && users.frozen = 'n'", $uid);

		$friends = array();
		while($line = $db->fetchrow())
			$friends[$line['username']] = $line['friendid'];

		uksort($friends,'strcasecmp');
		$friends = array_flip($friends);

		$cache->put(array($uid, "friends-$uid"), $friends, 86400*7);
	}
	return $friends;
}

function getUserInfo($cat,$user){
	global $db;
	if(is_numeric($user))
		$query = $db->prepare("SELECT $cat FROM users WHERE userid = ?", $user);
	else
		$query = $db->prepare("SELECT $cat FROM users WHERE username = ?", $user);

	$db->query($query);

	$line = $db->fetchrow();
	return $line[$cat];
}

function getUserName($uid){
	global $db;

	static $username = array();

	if(is_array($uid)){
		$getids = array();
		foreach($uid as $id){
			if(!isset($username[$id])){
				$getids[] = $id;
				$username[$id] = false;
			}
		}

		if(count($getids)){
			$db->prepare_query("SELECT userid, username FROM users WHERE userid IN (?)", $getids);

			while($line = $db->fetchrow())
				$username[$line['userid']] = $line['username'];
		}

		$uids = array();
		foreach($uid as $id)
			$uids[$id] = $username[$id];
		return $uids;
	}else{
		if(isset($username[$uid]))
			return $username[$uid];

		$db->prepare_query("SELECT username FROM users WHERE userid = ?", $uid);

		$username[$uid] = ($db->numrows() ? $db->fetchfield() : false);
		return $username[$uid];
	}
	return false;
}

function getUserID($username){
	global $db;

	if(is_numeric($username))
		return $username;

	$username = trim($username);
	$db->prepare_query("SELECT userid FROM users WHERE username = ?", $username);

	if($db->numrows())
		return $db->fetchfield();

	return false;
}

function userNameLegal($word){
	global $msgs,$db;

	$orig = $word;

	$word = trim($word);

//filtered, illegal chars
	$chars = array(' ','<','>','&','%','"',"'",'`','+','=','@',chr(127),chr(152),chr(158),chr(160),'/','\\');
	for($i=0;$i<40;$i++)
		$chars[] = chr($i);
	for($i=166;$i<=223;$i++)
		$chars[] = chr($i);
	for($i=240;$i<=255;$i++)
		$chars[] = chr($i);
	$word = str_replace($chars,'',$word);

	if(strlen($word)<4){
		$msgs->addMsg("Username must be at least 4 characters long.");
		return false;
	}
	if(strlen($word)>12){
		$msgs->addMsg("Username cannot be more than 12 characters long");
		return false;
	}
	if($word !== $orig){
		$msgs->addMsg("Illegal characters have been removed. Changed to '$word'.");
		return false;
	}

	if(is_numeric($word)){
		$msgs->addMsg("Names must have at least one non-numeric character");
		return false;
	}

	$db->prepare_query("SELECT userid FROM users WHERE username = ?", $word);
	if($db->numrows() >= 1){
		$msgs->addMsg("That username is already in use");
		return false;
	}

	$db->query("SELECT word,type FROM bannedwords");

	while($line = $db->fetchrow()){
		if($line['type']=='word' || $line['type']=='name'){
			if(strtolower($word) == strtolower($line['word'])){
				$msgs->addMsg("'$line[word]' isn't allowed in the username");
				return false;
			}
		}elseif($line['type']=='part'){
			if(stristr($word,$line['word'])!==false){
				$msgs->addMsg("'$line[word]' isn't allowed in the username");
				return false;
			}
		}
	}
	return true;
}

function isIgnored($to, $from, $scope, $age = 0, $ignorelistonly = false){ //$from usually = $userData['userid'];
	global $cache, $db, $mods;

	if($mods->isAdmin($from))
		return false;

	if($scope && !$ignorelistonly){
		$line = $cache->get(array($to, "userprefs-$to")); //only available when the user is online. Don't $cache->put the value from below

		if(!$line || !isset($line['onlyfriends'])){
			$db->prepare_query("SELECT onlyfriends, ignorebyage, defaultminage, defaultmaxage FROM users WHERE userid = #", $to);
			$line = $db->fetchrow();
		}

		if(($line['onlyfriends'] == 'both' || $line['onlyfriends']  == $scope) && !isFriend($from,$to))
			return true;

		if(($line['ignorebyage'] == 'both' || $line['ignorebyage'] == $scope) && $age && ($age < $line['defaultminage'] || $age > $line['defaultmaxage']) && !isFriend($from, $to))
			return true;
	}

	$ignorelist = $cache->get(array($to, "ignorelist-$to"));

	if($ignorelist === false){
		$db->prepare_query("SELECT ignoreid FROM `ignore` WHERE userid = ?", $to);

		$ignorelist = array();
		while($line = $db->fetchrow())
			$ignorelist[$line['ignoreid']] = $line['ignoreid'];

		$cache->put(array($to, "ignorelist-$to"), $ignorelist, 86400*3); //3 days
	}

	return isset($ignorelist[$from]);
}

function isFriend($friendid, $userid=0){
	global $userData, $db;

	if($userid==0)
		$userid=$userData['userid'];

	if($friendid == $userid)
		return true;

	$friends = getFriendsList($userid);

	return isset($friends[$friendid]);
}

function isValidEmail($email){
	global $msgs;
	if(!eregi("^[a-z0-9]+([a-z0-9_.&-]+)*@([a-z0-9.-]+)+$", $email, $regs) ){
		$msgs->addMsg("Error: '$email' isn't a valid mail address");
		return false;
	}
/*	elseif(!checkdnsrr($regs[2],"MX")){
		$msgs->addMsg("Error: Can't find the host '$regs[2]'");
		return false;
	}
*/
	return true;
}

function isEmailInUse($email){
	global $msgs,$db;

	$db->prepare_query("SELECT userid FROM users WHERE email = ?", $email);

	if($db->numrows()){
		$msgs->addMsg("Email Already in use");
		return true;
	}
	return false;
}

function isBanned($val,$type){
	global $db;

	$db->prepare_query("SELECT id FROM bannedusers WHERE $type = ?", $val);

	return $db->numrows();
}

/////////////////////////////////////////////////////
////// Sorting Functions ////////////////////////////
/////////////////////////////////////////////////////

function isValidSortt($sortlist,&$sortt,$default=""){
	foreach($sortlist as $n => $v)
		if($v!="" && $n == $sortt)
			return true;
	if($default!=""){
		$sortt=$n;
		return false;
	}
	foreach($sortlist as $n => $v){
		if($v!=""){
			$sortt=$n;
			return false;
		}
	}
}

function isValidSortd(&$sortd,$default='ASC'){
	if($sortd == 'ASC' || $sortd == 'DESC')
		return true;
	$sortd=$default;
	return false;
}

function makeSortSelect($sortlist){
	$s = array();
	foreach($sortlist as $n => $v){
		$pos = strpos($n,'.');
		if($pos!==false)
			$n = substr($n,0,$pos) . "`.`" . substr($n,$pos+1);

		if($n==$v || $v=="")
			$s[] = "`$n`";
		else
			$s[] = "$v AS `$n`";
	}
	return implode(', ',$s);
}

function makeSortTableHeader($sortlist,$name,$type,$varlist=array(),$href="",$align='left'){
	global $sortt,$sortd,$config;
	if($href=="")
		$href=$_SERVER['PHP_SELF'];
	echo "<td class=header align=$align nowrap><a class=header href=\"$href?sortd=" . ($sortt==$type ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=$type";
	foreach($varlist as $k => $v)
		echo "&$k=$v";
	echo "\">$name</a>". ($sortt==$type ? "&nbsp<img src=$config[imageloc]$sortd.png>" : "") ."</td>\n";
}


function sortCols(&$rows){
	$numargs = func_num_args();
	$arg_list = func_get_args();

	if($numargs < 2){
		trigger_error("Too few args");
	}

	$sorts = array();

	$by = "";
	$dir = SORT_ASC;
	$type = SORT_REGULAR;

	for($i = 1; $i < $numargs; $i++ ){
		if($arg_list[$i] === SORT_ASC || $arg_list[$i] === SORT_DESC){
			$dir = $arg_list[$i];
		}elseif(in_array($arg_list[$i], array(SORT_REGULAR, SORT_NUMERIC, SORT_STRING, SORT_CASESTR, SORT_NATSTR, SORT_NATCASESTR), true)){
			$type = $arg_list[$i];
		}else{
			$by = $arg_list[$i];
			$sorts[] = array($by, $dir, $type);
			$by = "";
			$dir = SORT_ASC;
			$type = SORT_REGULAR;
		}
	}

	foreach($sorts as $sort){
		list($key, $dir, $type) = $sort;
		if($dir == SORT_ASC){
			switch($type){
				case SORT_REGULAR:		$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ($a["'.$key.'"] < $b["'.$key.'"]) ? -1 : 1;}'; 	break;
				case SORT_NUMERIC:		$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ((float)$a["'.$key.'"] < (float)$b["'.$key.'"]) ? -1 : 1;}'; 	break;
				case SORT_STRING:		$func = 'return strcmp($a["'.$key.'"],$b["'.$key.'"]);'; 		break;
				case SORT_CASESTR:		$func = 'return strcasecmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
				case SORT_NATSTR:		$func = 'return strnatcmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
				case SORT_NATCASESTR:	$func = 'return strnatcasecmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
			}
		}else{
			switch($type){
				case SORT_REGULAR:		$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ($a["'.$key.'"] < $b["'.$key.'"]) ? 1 : -1;}'; 	break;
				case SORT_NUMERIC:		$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ((float)$a["'.$key.'"] < (float)$b["'.$key.'"]) ? 1 : -1;}'; 	break;
				case SORT_STRING:		$func = 'return 0 - strcmp($a["'.$key.'"],$b["'.$key.'"]);'; 		break;
				case SORT_CASESTR:		$func = 'return 0 - strcasecmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
				case SORT_NATSTR:		$func = 'return 0 - strnatcmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
				case SORT_NATCASESTR:	$func = 'return 0 - strnatcasecmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
			}
		}
		$compare = create_function('$a,$b',$func);
		mergesort($rows, $compare);
	}
}

function my_array_slice($array, $offset, $length = false, $preservekey = false){
	if($offset < 0)
		$offset = count($array) - $offset;

	if($length < 0)
		$length = count($array) - $offset - $length;
	elseif($length === false)
		$length = count($array) - $offset;

	$stopoffset = $offset + $length;

	$new = array();
	$i=0;

	foreach($array as $key => $val){
		if($i >= $offset){
			if($preservekey)
				$new[$key] = $val;
			else
				$new[] = $val;
		}
		$i++;
		if($i >= $stopoffset)
			break;
	}
	return $new;
}

function mergesort(&$array, $cmp_function = 'strcmp') {

   // Arrays of size < 2 require no action.
   if (count($array) < 2) return;
   // Split the array in half
   $halfway = count($array) / 2;
   $array1 = my_array_slice($array, 0, $halfway, true);
   $array2 = my_array_slice($array, $halfway, false, true);

   // Recurse to sort the two halves
   mergesort($array1, $cmp_function);
   mergesort($array2, $cmp_function);

   $keys1 = array_keys($array1);
   $keys2 = array_keys($array2);

   // If all of $array1 is <= all of $array2, just append them.
   if (call_user_func($cmp_function, end($array1), $array2[$keys2[0]]) < 1) {
       $array = $array1 + $array2; //array_merge($array1, $array2);
       return;
   }

   // Merge the two sorted arrays into a single sorted array
   $array = array();
   $ptr1 = $ptr2 = 0;
   while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
       if (call_user_func($cmp_function, $array1[$keys1[$ptr1]], $array2[$keys2[$ptr2]]) < 1)
           $array[$keys1[$ptr1]] = $array1[$keys1[$ptr1++]];
       else
           $array[$keys2[$ptr2]] = $array2[$keys2[$ptr2++]];
   }
   // Merge the remainder
   while ($ptr1 < count($array1)) $array[$keys1[$ptr1]] = $array1[$keys1[$ptr1++]];
   while ($ptr2 < count($array2)) $array[$keys2[$ptr2]] = $array2[$keys2[$ptr2++]];
   return;
}


/////////////////////////////////////////////////////
////// Picture Functions For New Manage Pictures/////
/////////////////////////////////////////////////////

function uploadPic($uploadFile,$picID){
	global $config, $docRoot, $msgs, $userData, $filesystem;

	$picName = $config['picdir'] . floor($picID/1000) . "/" . $picID . ".jpg";
	$thumbName = $config['thumbdir'] . floor($picID/1000) . "/" . $picID . ".jpg";

	$size = @GetImageSize($uploadFile);
	if ( !$size ){
		$msgs->addMsg("Could not open picture");
		return false;
	}

	include_once("include/JPEG.php");

	$jpeg =& new JPEG($uploadFile);

	$description = $jpeg->getExifField("ImageDescription");

	if(!empty($description) && substr($description,0,strlen($config['picText'])) == $config['picText']){
		$userid = substr($description,strlen($config['picText'])+1);
		if(!empty($userid) && $userid != $userData['userid']){
			$msgs->addMsg("You have been banned from uploading this image");
			return false;
		}
	}

	if($size[2] == 2)
	    $sourceImg = @ImageCreateFromJPEG($uploadFile);
	elseif($size[2] == 3)
	    $sourceImg = @ImageCreateFromPNG($uploadFile);
	else{
		$msgs->addMsg("Wrong or unknown image type. Only JPG and PNG are supported");
		return false;
	}

	if(!$sourceImg){
		$msgs->addMsg("Bad or corrupt image.");
		return false;
	}

	$aspectRat = (float)($size[0] / $size[1]);

	if($config['maxPicWidth']>0 && $config['maxPicHeight'] >0 && $size[0] > $config['maxPicWidth'] && $size[1] > $config['maxPicHeight']){
		$ratio = (float)($config['maxPicWidth'] / $config['maxPicHeight']);
		if($ratio < $aspectRat){
			$picX = $config['maxPicWidth'];
			$picY = $config['maxPicWidth'] / $aspectRat;
		}else{
			$picY = $config['maxPicHeight'];
			$picX = $config['maxPicHeight'] * $aspectRat;
		}
	}elseif($config['maxPicWidth'] >0 && $size[0]>$config['maxPicWidth']){
		$picX = $config['maxPicWidth'];
		$picY = $config['maxPicWidth'] / $aspectRat;
	}elseif($config['maxPicHeight'] > 0 && $size[1]>$config['maxPicHeight']){
		$picY = $config['maxPicHeight'];
		$picX = $config['maxPicHeight'] * $aspectRat;
	}else{
		$picX = $size[0];
		$picY = $size[1];
	}

	$picX = ceil($picX);
	$picY = ceil($picY);

	if($picX < $config['minPicWidth'] || $picY < $config['minPicHeight']){
		$msgs->addMsg("Picture is too small");
		return false;
	}

	if(!$config['gd2']){
		$destImg = ImageCreate($picX, $picY );
		ImageCopyResized($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $size[0], $size[1]);
	}else{
		$destImg = ImageCreateTrueColor($picX, $picY );
		ImageCopyResampled($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $size[0], $size[1]);
	}


	$white = ImageColorClosest($destImg, 255, 255, 255);
	$black = ImageColorClosest($destImg, 0, 0, 0);


	$padding = 3;
	$border = 1;
	$offset = 10;
	$textwidth = (strlen($config['picText'])*7)-1; // 6 pixels per character, 1 pixel space, 3 pixels padding
	$textheight = 14;

	ImageRectangle($destImg,$picX-$textwidth-$offset-$border*2-$padding*2,$picY-$textheight-$offset-$border,$picX-$offset,$picY-$offset,$black);
	ImageFilledRectangle($destImg,$picX-$textwidth-$offset-$border-$padding*2,$picY-$textheight-$offset,$picX-$border-$offset,$picY-$border-$offset,$white);
	ImageString ($destImg, 3, $picX-$textwidth-$offset-$padding, $picY-$textheight-$offset, $config['picText'], $black);

	imagejpeg($destImg, $docRoot . $picName,80);


//put in exif info
	$jpeg =& new JPEG($docRoot . $picName);

	$jpeg->setExifField("ImageDescription", "$config[picText]:$userData[userid]");
	$jpeg->save();

	if($config['thumbWidth']>0 && $config['thumbHeight'] >0 && $size[0] > $config['thumbWidth'] && $size[1] > $config['thumbHeight']){
		$ratio = (float)($config['thumbWidth'] / $config['thumbHeight']);
		if($ratio < $aspectRat){
			$newX = $config['thumbWidth'];
			$newY = $config['thumbWidth'] / $aspectRat;
		}else{
			$newY = $config['thumbHeight'];
			$newX = $config['thumbHeight'] * $aspectRat;
		}
	}elseif($config['thumbWidth'] >0 && $size[0]>$config['thumbWidth']){
		$newX = $config['thumbWidth'];
		$newY = $config['thumbWidth'] / $aspectRat;
	}elseif($config['thumbHeight'] > 0 && $size[1]>$config['thumbHeight']){
		$newY = $config['thumbHeight'];
		$newX = $config['thumbHeight'] * $aspectRat;
	}else{
		$newX = $size[0];
		$newY = $size[1];
	}

	if(!$config['gd2']){
		$thumbImg = ImageCreate($newX, $newY );
		ImageCopyResized($thumbImg, $destImg, 0,0,0,0, $newX, $newY, $picX, $picY);
	}else{
		$thumbImg = ImageCreateTrueColor($newX, $newY );
	    ImageCopyResampled($thumbImg, $destImg, 0,0,0,0, $newX, $newY, $picX, $picY);
	}

	imagejpeg($thumbImg, $docRoot . $thumbName, 80);

	$jpeg =& new JPEG($docRoot . $thumbName);

	$jpeg->setExifField("ImageDescription", "$config[picText]:$userData[userid]");
	$jpeg->save();

	$filesystem->add($picName);
	$filesystem->add($thumbName);

	return true;
}

function addPic($userfile,$vote,$description, $signpic){
	global $userData,$msgs,$db,$config, $docRoot, $mods;

	if(!file_exists($userfile)){
		$msgs->addMsg("You must upload a file. If you tried, the file might be too big (1mb max).");
		return false;
	}

	$md5 = md5_file($userfile);

	$db->prepare_query("SELECT times FROM picbans WHERE md5 = ? && userid IN (0,#)", $md5, $userData['userid']);

	if($db->numrows()){
		$times = $db->fetchfield();

		if($times > 1){
			$msgs->addMsg("This picture has been banned");
			return false;
		}
	}

	$db->prepare_query("SELECT itemid FROM picspending WHERE md5 = ? && itemid = #", $md5, $userData['userid']);

	if($db->numrows()){
		$msgs->addMsg("You already uploaded this picture");
		return false;
	}

	$db->prepare_query("INSERT INTO picspending SET itemid = #, vote = ?, description = ?, md5 = ?, signpic = ?, time = #", $userData['userid'], ($vote ? 'y' : 'n'), removeHTML(trim(str_replace("\n", ' ', $description))), $md5, ($signpic ? 'y' : 'n'), time());

	$picID = $db->insertid();

	umask(0);

	if(!is_dir($docRoot . $config['picdir'] . floor($picID/1000)))
		@mkdir($docRoot . $config['picdir'] . floor($picID/1000),0777);
	if(!is_dir($docRoot . $config['thumbdir'] . floor($picID/1000)))
		@mkdir($docRoot . $config['thumbdir'] . floor($picID/1000),0777);

	if(uploadPic($userfile, $picID)){
		$mods->newItem(MOD_PICS,$picID,$userData['premium']);

		$msgs->addMsg("Picture uploaded successfully.");
	}else{
		$db->prepare_query("DELETE FROM picspending WHERE id = #", $picID);
	}
}

function removePic($id){
	global $msgs,$config,$db, $mods, $filesystem, $docRoot, $cache;

	$db->begin();
//	$db->query("LOCK TABLES pics WRITE");

	$db->prepare_query("SELECT itemid FROM pics WHERE id = ?", $id);

	if($db->numrows()==0){
//		$db->query("UNLOCK TABLES");
		$db->rollback();
		return;
	}

	$line = $db->fetchrow();

	setMaxPriority($db, "pics", $id, "itemid = '$line[itemid]'");

	$db->prepare_query("DELETE FROM pics WHERE id = ?", $id);

//	$db->query("UNLOCK TABLES");
	$db->commit();

	$cache->remove(array($line['itemid'],"pics-$line[itemid]"));

	$db->prepare_query("DELETE FROM abuse WHERE type = ? && itemid = ?", MOD_PICABUSE, $id);

	$mods->deleteItem(MOD_PICABUSE,$id);

	$picName = $config['picdir'] . floor($id/1000) . "/" . $id . ".jpg";
	$thumbName = $config['thumbdir'] . floor($id/1000) . "/" . $id . ".jpg";

	$filesystem->delete($picName);
	$filesystem->delete($thumbName);

	if(file_exists($docRoot . $picName))
			@unlink($docRoot . $picName);
	if(file_exists($docRoot . $thumbName))
			@unlink($docRoot . $thumbName);

	$msgs->addMsg("Picture Deleted");
}


function removePicPending($ids, $deletemoditem = true){
	global $msgs,$config,$db,$mods, $filesystem, $docRoot;

	if(!is_array($ids))
		$ids = array($ids);

	$db->prepare_query("DELETE FROM picspending WHERE id IN (?)", $ids);

	if($deletemoditem)
		$mods->deleteItem(MOD_PICS,$ids);

	foreach($ids as $id){
		$picName = $config['picdir'] . floor($id/1000) . "/" . $id . ".jpg";
		$thumbName = $config['thumbdir'] . floor($id/1000) . "/" . $id . ".jpg";

		$filesystem->delete($picName);
		$filesystem->delete($thumbName);

		if(file_exists($docRoot . $picName))
				unlink($docRoot . $picName);
		if(file_exists($docRoot . $thumbName))
				unlink($docRoot . $thumbName);
	}

	if(count($ids) > 1)
		$msgs->addMsg(count($ids) . " Pictures Deleted");
	else
		$msgs->addMsg("Picture Deleted");
}

function setFirstPic($uids){
	global $db, $cache;

	$db->prepare_query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id WHERE users.userid IN (#)", $uids);

	if(is_array($uids))
		foreach($uids as $uid)
			$cache->remove(array($uid, "pics-$uid"));
	else
		$cache->remove(array($uids, "pics-$uids"));
}


/////////////////////////////////////////////////////
////// Gallery Picture Functions ////////////////////
/////////////////////////////////////////////////////

function uploadGalleryPic($uploadFile,$picID){
	global $config, $docRoot, $msgs, $userData, $filesystem;

	$picName = $config['gallerypicdir'] . floor($picID/1000) . "/" . $picID . ".jpg";
	$thumbName = $config['gallerythumbdir'] . floor($picID/1000) . "/" . $picID . ".jpg";

	$size = @GetImageSize($uploadFile);
	if ( !$size ){
		$msgs->addMsg("Could not open picture");
		return false;
	}

	include_once("include/JPEG.php");

	$jpeg =& new JPEG($uploadFile);

	$description = $jpeg->getExifField("ImageDescription");

	if(!empty($description) && substr($description,0,strlen($config['picText'])) == $config['picText']){
		$userid = substr($description,strlen($config['picText'])+1);
		if(!empty($userid) && $userid != $userData['userid']){
			$msgs->addMsg("You have been banned from uploading this image");
			return false;
		}
	}

	if($size[2] == 2)
	    $sourceImg = @ImageCreateFromJPEG($uploadFile);
//	elseif($size[2] == 3)
//	    $sourceImg = @ImageCreateFromPNG($uploadFile);
	else{
		$msgs->addMsg("Wrong or unknown image type. Only JPG and PNG are supported");
		return false;
	}

	if(!$sourceImg){
		$msgs->addMsg("Bad or corrupt image.");
		return false;
	}

	$aspectRat = (float)($size[0] / $size[1]);

	if($config['maxGalleryPicWidth']>0 && $config['maxGalleryPicHeight'] >0 && $size[0] > $config['maxGalleryPicWidth'] && $size[1] > $config['maxGalleryPicHeight']){
		$ratio = (float)($config['maxGalleryPicWidth'] / $config['maxGalleryPicHeight']);
		if($ratio < $aspectRat){
			$picX = $config['maxGalleryPicWidth'];
			$picY = $config['maxGalleryPicWidth'] / $aspectRat;
		}else{
			$picY = $config['maxGalleryPicHeight'];
			$picX = $config['maxGalleryPicHeight'] * $aspectRat;
		}
	}elseif($config['maxGalleryPicWidth'] >0 && $size[0]>$config['maxGalleryPicWidth']){
		$picX = $config['maxGalleryPicWidth'];
		$picY = $config['maxGalleryPicWidth'] / $aspectRat;
	}elseif($config['maxGalleryPicHeight'] > 0 && $size[1]>$config['maxGalleryPicHeight']){
		$picY = $config['maxGalleryPicHeight'];
		$picX = $config['maxGalleryPicHeight'] * $aspectRat;
	}else{
		$picX = $size[0];
		$picY = $size[1];
	}

	$picX = ceil($picX);
	$picY = ceil($picY);

	if($picX < $config['minPicWidth'] || $picY < $config['minPicHeight']){
		$msgs->addMsg("Picture is too small");
		return false;
	}


	if(!$config['gd2']){
		$destImg = ImageCreate($picX, $picY );
		ImageCopyResized($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $size[0], $size[1]);
	}else{
		$destImg = ImageCreateTrueColor($picX, $picY );
		ImageCopyResampled($destImg, $sourceImg, 0,0,0,0, $picX, $picY, $size[0], $size[1]);
	}


	$white = ImageColorClosest($destImg, 255, 255, 255);
	$black = ImageColorClosest($destImg, 0, 0, 0);


	$padding = 3;
	$border = 1;
	$offset = 10;
	$textwidth = (strlen($config['picText'])*7)-1; // 6 pixels per character, 1 pixel space, 3 pixels padding
	$textheight = 14;

	ImageRectangle($destImg,$picX-$textwidth-$offset-$border*2-$padding*2,$picY-$textheight-$offset-$border,$picX-$offset,$picY-$offset,$black);
	ImageFilledRectangle($destImg,$picX-$textwidth-$offset-$border-$padding*2,$picY-$textheight-$offset,$picX-$border-$offset,$picY-$border-$offset,$white);
	ImageString ($destImg, 3, $picX-$textwidth-$offset-$padding, $picY-$textheight-$offset, $config['picText'], $black);

	imagejpeg($destImg, $docRoot . $picName,80);


//put in exif info
	$jpeg =& new JPEG($docRoot . $picName);

	$jpeg->setExifField("ImageDescription", "$config[picText]:$userData[userid]");
	$jpeg->save();

	if($config['thumbWidth']>0 && $config['thumbHeight'] >0 && $size[0] > $config['thumbWidth'] && $size[1] > $config['thumbHeight']){
		$ratio = (float)($config['thumbWidth'] / $config['thumbHeight']);
		if($ratio < $aspectRat){
			$newX = $config['thumbWidth'];
			$newY = $config['thumbWidth'] / $aspectRat;
		}else{
			$newY = $config['thumbHeight'];
			$newX = $config['thumbHeight'] * $aspectRat;
		}
	}elseif($config['thumbWidth'] >0 && $size[0]>$config['thumbWidth']){
		$newX = $config['thumbWidth'];
		$newY = $config['thumbWidth'] / $aspectRat;
	}elseif($config['thumbHeight'] > 0 && $size[1]>$config['thumbHeight']){
		$newY = $config['thumbHeight'];
		$newX = $config['thumbHeight'] * $aspectRat;
	}else{
		$newX = $size[0];
		$newY = $size[1];
	}

	if(!$config['gd2']){
		$thumbImg = ImageCreate($newX, $newY );
		ImageCopyResized($thumbImg, $destImg, 0,0,0,0, $newX, $newY, $picX, $picY);
	}else{
		$thumbImg = ImageCreateTrueColor($newX, $newY );
	    ImageCopyResampled($thumbImg, $destImg, 0,0,0,0, $newX, $newY, $picX, $picY);
	}

	imagejpeg($thumbImg, $docRoot . $thumbName, 80);

	$jpeg =& new JPEG($docRoot . $thumbName);

	$jpeg->setExifField("ImageDescription", "$config[picText]:$userData[userid]");
	$jpeg->save();

	$filesystem->add($picName);
	$filesystem->add($thumbName);

	return true;
}

function addGalleryPic($userfile, $cat, $description){
	global $userData, $msgs, $db, $config, $docRoot, $mods;

	if(!file_exists($userfile)){
		$msgs->addMsg("You must upload a file. If you tried, the file might be too big (1mb max).");
		return false;
	}

	$priority = getMaxPriority($db, "gallery",$db->prepare("userid = ? && category = ?", $userData['userid'], $cat));
	$db->prepare_query("INSERT INTO gallery SET userid = ?, category = ?, description = ?, priority = ?", $userData['userid'], $cat, removeHTML(trim(str_replace("\n", ' ', $description))), $priority);

	$picID = $db->insertid();

	umask(0);

	if(!is_dir($docRoot . $config['gallerypicdir'] . floor($picID/1000)))
		@mkdir($docRoot . $config['gallerypicdir'] . floor($picID/1000),0777);
	if(!is_dir($docRoot . $config['gallerythumbdir'] . floor($picID/1000)))
		@mkdir($docRoot . $config['gallerythumbdir'] . floor($picID/1000),0777);

	if(uploadGalleryPic($userfile, $picID)){
		$mods->newItem(MOD_GALLERY,$picID);
		$msgs->addMsg("Picture uploaded successfully.");
	}else{
		$db->prepare_query("DELETE FROM gallery WHERE id = ?", $picID);
	}
}

function removeGalleryPic($id){
	global $msgs, $config, $db, $mods, $filesystem, $docRoot;

//	$db->query("LOCK TABLES gallery WRITE");
	$db->begin();

	$db->prepare_query("SELECT userid,category FROM gallery WHERE id = ?", $id);

	if($db->numrows()==0){
//		$db->query("UNLOCK TABLES");
		$db->rollback();
		return;
	}

	$line = $db->fetchrow();

	setMaxPriority($db, "gallery", $id, "userid = '$line[userid]' && category = '$line[category]'");

	$db->prepare_query("DELETE FROM gallery WHERE id = ?", $id);

//	$db->query("UNLOCK TABLES");
	$db->commit();

	$mods->deleteItem(MOD_GALLERYABUSE,$id);

	$picName = $config['gallerypicdir'] . floor($id/1000) . "/" . $id . ".jpg";
	$thumbName = $config['gallerythumbdir'] . floor($id/1000) . "/" . $id . ".jpg";

	if(file_exists($docRoot . $picName))
			unlink($docRoot . $picName);
	if(file_exists($docRoot . $thumbName))
			unlink($docRoot . $thumbName);

	$filesystem->delete($picName);
	$filesystem->delete($thumbName);

	$msgs->addMsg("Picture Deleted");
}

function setFirstGalleryPic($uid,$cat){
	global $db;

	$db->prepare_query("SELECT id FROM gallery WHERE userid = ? && category = ? && priority = 1", $uid, $cat);
	if($db->numrows() == 0)
		$id = 0;
	else
		$id = $db->fetchfield();

	$db->prepare_query("UPDATE gallerycats SET firstpicture = ? WHERE userid = ? && id = ?", $id, $uid, $cat);
}


