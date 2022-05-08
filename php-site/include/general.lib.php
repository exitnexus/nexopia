<?
// Copyright Timo Ewalds 2004 all rights reserved.

ini_set("precision","20");

error_reporting (E_ALL);

define("REQUIRE_ANY", 0);
define("REQUIRE_LOGGEDIN", 1);
define("REQUIRE_NOTLOGGEDIN", -1);
define("REQUIRE_LOGGEDIN_PLUS", 2);
define("REQUIRE_LOGGEDIN_ADMIN", 3);

$revstr = '$Revision$';
preg_match('/Revision: ([0-9]+)/', $revstr, $matches);
$reporev = $matches[1];

/*

$acceptips = array(
'local' => '10.',
//'timo' => '198.166.49.97',
//'timo2' => '199.126.18.213',
'newservers' => '216.234.161.192',
'newservers2' => '66.51.127.1',
'office' => '142.179.205.97',
'office2' => '142.179.204.201',
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
$memory = array(get_memory_usage());



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


	// call with include_once inherit_config_profile('name');
	function inherit_config_profile($profilename)
	{
		return "include/config.$profilename.inc.php";
	}

include_once("include/defines.php");
include_once("include/config.inc.php");
include_once("include/mirrors.php");

timeline('config', true);

if(isset($tidyoutput) && $tidyoutput && extension_loaded('tidy'))
	ob_start('ob_tidyhandler');

	function inherit_database_profile($name)
	{
		return "include/dbconf.$name.php";
	}

	// taken from http://ca3.php.net/manual/en/function.array-merge-recursive.php,
	// comment by (shemari75 at mixmail dot com)
	function recursive_merge_config(&$array, &$array_i) {
		// For each element of the array (key => value):
		foreach ($array_i as $k => $v) {
			// If the value itself is an array, the process repeats recursively:
			if (is_array($v)) {
				if (!isset($array[$k])) {
					$array[$k] = array();
				}
				recursive_merge_config($array[$k], $v);

			// Else, the value is assigned to the current element of the resulting array:
			} else {
				if (isset($array[$k]) && is_array($array[$k])) {
					$array[$k][0] = $v;
				} else {
					if (isset($array) && !is_array($array)) {
						$temp = $array;
						$array = array();
						$array[0] = $temp;
					}
					$array[$k] = $v;
				}
			}
		}
	}

	// this produces a really nasty crowded config array, but the important thing
	// is that everything is where it's supposed to be when the db object is initialized
	function recursive_inherit_config(&$configtree, &$subtree, $sets = array())
	{
		foreach ($sets as $key => $value)
		{
			if (!isset($subtree[$key]))
				$subtree[$key] = $value;
		}

		if (isset($subtree['inherit']) && isset($configtree[ $subtree['inherit'] ]))
		{
			$sourcename = $subtree['inherit'];
			recursive_merge_config($subtree, $configtree[$sourcename]);
		}
		foreach ($subtree as $key => &$value)
		{
			if (!is_array($value) && $key != 'inherit')
				$sets[$key] = $value;
		}
		foreach ($subtree as &$subsubtree)
		{
			if (is_array($subsubtree))
				recursive_inherit_config($configtree, $subsubtree, $sets);
		}
	}

	require_once "include/dbconf.$databaseprofile.php";
	recursive_inherit_config($databases, $databases);

	include_once("include/sql.php");
	include_once("include/multisql.php");

	if (!isset($databaselib) || !preg_match('/^(mysql|mysql4|pdo)$/', $databaselib))
		$databaselib = 'mysql';

	include_once("include/$databaselib.php");

	function init_dbo($dbinfo, &$dbs) // already constructed dbs passed to $dbs
	{
		global $pluswwwdomain;

		if ($dbinfo['type'] == 'single')
		{
			return new sql_db($dbinfo);

		} else if ($dbinfo['type'] == 'multi') {
			return new multiple_sql_db($dbinfo, $_SERVER['HTTP_HOST'] == $pluswwwdomain);

		} else if ($dbinfo['type'] == 'split') {
			$subdbos = array();
			foreach ($dbinfo['sources'] as $source)
			{
				if (is_array($source))
					$subdbos[] = init_dbo($source, $dbs);
				else if (isset($dbs[$source]))
					$subdbos[] = $dbs[$source];
			}
			return new multiple_sql_db_split($subdbos, $dbinfo['splitfunc'], $_SERVER['HTTP_HOST'] == $pluswwwdomain);
		}
		return null;
	}


	$dbs = array();
	foreach ($databases as $dbname => $dbinfo)
	{
		if (isset($dbinfo['instance']))
		{
			$dbo = init_dbo($dbinfo, $dbs);
			if ($dbo)
				$dbs[$dbname] = $dbo;
		}
	}
	extract($dbs);
timeline('dbs', true);


include_once("include/memcached-client.php");
//include_once("include/mcachewrapper.php");
//include_once("include/peclmemcachewrapper.php");
//include_once("include/peclmemcached-client.php");

	$memcache = new memcached($memcacheoptions);
	$pagememcache = new memcached($pagecacheoptions);


include_once("include/memcache2.php");
	$cache 		= new cache($memcache, "$sitebasedir/cache");
	$pagecache	= new cache($pagememcache, "$sitebasedir/cache");

timeline('cache', true);

include_once("include/general.class.php");
include_once("include/databaseobject.php");
include_once("include/msgs.php");
include_once("include/menu.php");
include_once("include/auth4.php");
include_once("include/blocks.php");
include_once("include/banner6.php");
include_once("include/forums.php");
include_once("include/moderator.php");
include_once("include/abuselog.php");
include_once("include/survey.php");
include_once("include/stats.php");
include_once("include/categories.php");
include_once("include/polls.php");
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
include_once("include/gallery.php");
include_once("include/pics.php");
include_once("include/comment.php");
include_once("include/wiki2.php");
include_once("include/sourcepictures.php");
include_once("include/template.php");
include_once("include/fckeditor.php") ;
include_once("include/html_sanitizer.php");
include_once("include/userSearch.php");
include_once('include/profileblocks.php');
include_once("include/inlineDebug.php");

timeline('parse', true);

//declared after includes to allow to use all defines
	$accounts = 	new accounts( $masterdb, $usersdb, $db );
	$useraccounts = new useraccounts( $masterdb, $usersdb );
	$auth = 		new authentication( $masterdb, $usersdb );

	$msgs = 		new messages();
	$banner = 		new bannerclient( $bannerdb, $bannerservers );
	$forums = 		new forums ( $forumdb );
	$mods = 		new moderator( $moddb );
	$abuselog = 	new abuselog( $moddb );
	$polls = 		new polls( $polldb );
	$shoppingcart = new shoppingcart( $shopdb );
	$payg = 		new paygcards( $shopdb );
	$messaging = 	new messaging( $usersdb );
	$usernotify =   new usernotify( $db );
	$usercomments = new usercomments( $usersdb );
	$filesystem = 	new filesystem($filesdb, $THIS_IMG_SERVER);
	$contests =		new contests( $contestdb );
	$weblog = 		new weblog( $usersdb );
	$galleries = 	new galleries( $usersdb );
	$wiki =		 	new wiki( $wikidb );
	$sourcepictures=new sourcepictures( $usersdb );
	$inlineDebug =	new inlineDebug();


//	if($enableCompression)
//		ini_set('zlib.output_compression', 'On');
//		ob_start("ob_gzhandler");

	if(!isset($config['timezone']))
		$config['timezone'] = 6;
//	putenv("TZ=MST");

	header("Vary: Cookie"); //helps fix caching


	timeline('init', true);

	$cachekey = getREQval('cachekey');

	if(isset($login)){
		requireLogin($login);
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

	if(!isset($forceserver))
		$forceserver = getREQval('forceserver', 'bool');

	if(count($_POST) == 0 && !$forceserver){
		$webserver = ( ($userData['loggedIn'] && ($mods->isMod($userData['userid']) || $userData['premium'])) ? $pluswwwdomain : $wwwdomain );

		if($webserver != $_SERVER['HTTP_HOST']){
			header("location: " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $webserver . $_SERVER['REQUEST_URI']);
			exit;
		}
	}

include_once("include/skin.php");

timeline('auth done');


$action = getREQval('action');

//end of general stuff


/////////////////////////////////////////////////////
////// Site Specific Functions //////////////////////
/////////////////////////////////////////////////////

function requireLogin($login = REQUIRE_LOGGEDIN, $adminpriv = "", $userid = false, $key = false) // called by default when $login is set pre-start
{
	global $simplepage, $userData, $mods, $auth;

	if(!isset($simplepage))
		$simplepage = !empty($cachekey); //equiv to (bool)$cachekey

	if (!$userid)
		$userid = getCOOKIEval('userid', 'int');
	if (!$key)
		$key = getCOOKIEval('key');

	$userData = $auth->auth($userid, $key, ($login >= REQUIRE_LOGGEDIN), $simplepage);

	if($login == REQUIRE_NOTLOGGEDIN && $userData['loggedIn'])
		die("This is only viewable by people who haven't logged in");
	if($login == REQUIRE_LOGGEDIN_PLUS && !($userData['premium'] || $mods->isAdmin($userData['userid'],$adminpriv)))
		die("You must be a plus member to view this page");
	if($login == REQUIRE_LOGGEDIN_ADMIN && !$mods->isAdmin($userData['userid'],$adminpriv))
		die("You do not have permission to see this page");

	return true;
}

function debugOutput(){
	global $userData, $debuginfousers, $times, $memory, $dbs, $cache, $config, $revstr, $inlineDebug;
	if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)){
		timeline('end');
		$total = ($times['end'] - $times['start'])/10;
		$parse = ($times['parse'] - $times['start'])/10;
		$mysql = 0;

		$outputmemory = function_exists('memory_get_usage');

		foreach($dbs as $name => $db)
			$mysql += $db->getquerytime();

		$mysql /= 10;
		$cachetime = $cache->time/10;
		$php = $total - $parse - $mysql - $cachetime;

		// first give output from inline debug, thus module specific debug messages will come before
		// global site debug messages
		echo $inlineDebug->outputItems();

		echo "<table><tr><td valign=top>";

		echo "<table>";
		echo "<tr><td class=header nowrap>Total time:</td><td class=header align=right nowrap>" . number_format($total, 2) . " ms</td></tr>";
		echo "<tr><td class=body nowrap>Parse time:</td><td class=body align=right nowrap>" . number_format($parse, 2) . " ms</td></tr>";
		echo "<tr><td class=body nowrap>Mysql time:</td><td class=body align=right nowrap>" . number_format($mysql, 2) . " ms</td></tr>";
		echo "<tr><td class=body nowrap>Memcache time:</td><td class=body align=right nowrap>" . number_format($cachetime, 2) . " ms</td></tr>";
		echo "<tr><td class=body nowrap>PHP time:</td><td class=body align=right nowrap>" . number_format($php, 2) . " ms</td></tr>";
		if($outputmemory)
			echo "<tr><td class=body nowrap>Memory usage:</td><td class=body align=right nowrap>" . number_format(get_memory_usage()/1024) . " KB</td></tr>";
		if(isset($config['showsource']) && $config['showsource'])
			echo "<tr><td class=body colspan=2 nowrap><a class=body href=/source.php?file=$_SERVER[PHP_SELF]&k=" . makeKey($_SERVER['PHP_SELF']) . ">View Source</a></td></tr>";
		echo "<tr><td class=body colspan=2 nowrap>$revstr</td></tr>";

		echo "</table>";

		echo "</td><td valign=top>";

		echo "<table>";

		$start = $times['start'];
//		unset($times['start']);
//		unset($memory['start']);

		$names = array_keys($times);
		$times = array_values($times);
		$num = count($names);
		$rows = 6;
		$cols = ceil($num/$rows);

		$subcols = 3;
		if($outputmemory)
			$subcols++;

		echo "<tr><td class=header colspan=" . ($subcols*$cols-1) . " align=center>Timeline:</td></tr>";

		$total = $rows*$cols;
		for($i = 0; $i < $total; $i++){
			$col = $i%$cols;
			$row = floor($i/$cols);

			$j = $col*$rows + $row;

			if( $i % $cols == 0)
				echo "<tr>";

			if($j < $num){
				echo "<td class=body align=right nowrap>" . number_format(($times[$j] - $start)/10, 2) . " ms</td>";
				if($outputmemory)
					echo "<td class=body align=right nowrap>" . number_format($memory[$j]/1024) . " KB</td>";
				echo "<td class=body nowrap>$names[$j]</td>";
				if($col < $cols-1)
//					echo "<td width=1 bgcolor=#000000></td>";
					echo "<td class=body nowrap>&nbsp; | &nbsp;</td>";
			}

			if($i % $cols == $cols - 1)
				echo "</tr>\n";
		}
		if($i > 0)
			echo "</tr>\n";

		echo "</table>";

		echo "</td></tr></table>";

		$headers = headers_list();

		echo "<table>";
		echo "<tr><td class=header colspan=2>HTTP Headers sent:</td></tr>";
		foreach($headers as $line){
			list($n, $v) = explode(":", $line, 2);
			echo "<tr><td class=body>$n:</td><td class=body>$v</td></tr>";
		}
		echo "</table>";

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

	$result = strtoupper(substr(base_convert(md5("$myuserid:blah:$id"), 16, 36), 0, 10));
	return $result;
}

function checkKey($id, $key, $myuserid = 0){
	return ($key == makeKey($id, $myuserid));
}

function timeline($name, $force = false){
	global $userData, $times, $memory;

	if($force || $userData['debug']){
		$times[$name] = gettime();
		$memory[] = get_memory_usage();
	}
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

function addRefreshHeaders(){
//	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
//	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");  // always modified
	header("Cache-Control: must-revalidate, proxy-revalidate, no-cache");  // HTTP/1.1
//	header("Pragma: no-cache");                          // HTTP/1.0
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
	$res = $db->prepare_query("SELECT title, date, ntext FROM news WHERE type IN ('both',?) ORDER BY date DESC", $type);

	$ret=array();
	while($line = $res->fetchrow())
		$ret[]=$line;
	return $ret;
}

function getConfig(){
	global $db;

	$res = $db->query("SELECT name,value FROM config");

	$config=array();
	while($conf = $res->fetchrow())
		$config[$conf['name']] = $conf['value'];

	return $config;
}

function getBlocks(){
	global $db;

	$res = $db->prepare_query("SELECT funcname, side FROM blocks WHERE enabled = 'y' ORDER BY priority ASC");

	$blocks = array();
	while($line = $res->fetchrow())
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
function editBox($text = "", $id = 'msg', $formid = 'editbox', $height = 200, $width = 600, $maxlength=0, $parse_bbcode = true ){
	echo editBoxStr($text, $id, $formid, $height, $width, $maxlength, $parse_bbcode);
	return;
}

function editBoxStr($text = "", $id = 'msg', $formid = 'editbox', $height = 250, $width = 700, $maxlength=0, $parse_bbcode = true ){
	global $config, $cache, $userData;

	$userData['bbcode_editor'] = true; //here to disable for all, including anonymous viewers

	$sBasePath = 'include/' ;

	$output = '';

	$oFCKeditor = new FCKeditor($id) ;
	if($oFCKeditor->IsCompatible() && !$userData['bbcode_editor'] )
	{
		$oFCKeditor->BasePath	= $sBasePath ;
		$oFCKeditor->Value		= $text ;
		$oFCKeditor->Width		= $width;
		$oFCKeditor->Height		= $height;
		$oFCKeditor->MaxLength	= $maxlength;
		$output = $oFCKeditor->Create();
	}
	else
	{
		$smilyusage = getSmilies();
//		if($maxlength > 0)
//			$maxlength = $maxlength + 200;

		$mangledtext = htmlentities(str_replace("\r","",str_replace("\n","\\n",str_replace('\\', '\\\\', $text))));

		$output = "<script>" .
			"var smileypics = new Array('" . implode("','", $smilyusage) . "');" .
			"var smileycodes = new Array('" . implode("','", array_keys($smilyusage)) . "');" .
			"var smileyloc = '$config[smilyloc]';";

		$output .= "document.write(editBox(\"$mangledtext\", true ,'$id','$formid', $maxlength, $height, $width)); </script>";

		$output .= "<noscript><textarea cols=70 rows=10 name='$id'></textarea></noscript>";
	}

	return $output;
}

function weirdmap($input){
	return $input;
	//the rest of this function is not used, ignore it.


	settype($input, 'integer');

	// relayout to make the lsb the msb approximately reversed (real msb stays where it is to avoid overflowing 31 bits into sign bit)
	$input = (($input & 0x7f000000) | (($input >> 16) & 0x000000ff) | ($input & 0x0000ff00) | (($input << 16) & 0x00ff0000));

	$part1 = 0x2AAAAAAA; //b1010 (without sign bit)
	$part2 = 0x55555555; //b0101
	$new = ($input & $part1) | (((($input & $part2) << 23) & 0x7fffffff) | (($input & $part2) >> 8) & 0x7fffffff);

	return sprintf("%x", $new);
}

function weirdunmap($input){
	return $input;
	// the rest of this function is not used, ignore it.

	settype($input, 'integer');

	$part1 = 0x2AAAAAAA; //b1010 (without sign bit)
	$part2 = 0x55555555; //b0101
	$input = ($input & $part1) | (((($input & $part2) >> 23) & 0x7fffffff) | (($input & $part2) << 8) & 0x7fffffff);

	$input = (($input & 0x7f000000) | (($input >> 16) & 0x000000ff) | ($input & 0x0000ff00) | (($input << 16) & 0x00ff0000));
//	var_dump(($input << 16) & 0x00ff0000);

	return $input;
}

/*/
function editBox($text = ""){
	global $config, $cache;

	$smilyusage = getSmilies();

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

function addComment($id,$msg,$preview="changed",$params=array(),  $parse_bbcode, $usedb='article'){
	global $userData, $articlesdb, $polldb;
	if(!$userData['loggedIn'])
		return;

	if(trim($msg)=="")
		return;

	$nmsg = html_sanitizer::sanitize($msg);
	if($parse_bbcode)
	{
		$nmsg2 = parseHTML($nmsg);
		$nmsg3 = smilies($nmsg2);
		$nmsg3 = wrap($nmsg3);
		$nmsg3 = nl2br($nmsg3);
	}
	else
		$nmsg3 = $nmsg;

	if($preview=="Preview" || ($nmsg != $nmsg2 && $preview=="changed")){
		incHeader();

		echo "Some changes have been made (be it smilies, html removal, or code to html conversions). Here is a preview of what the post will look like:<hr><blockquote>\n";

		echo $nmsg3;

		echo "</blockquote><hr>\n";

		echo "<form action=$_SERVER[PHP_SELF] method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
		echo "<table width=100% cellspacing=0>";

		echo "<input type=hidden name='id' value='" . htmlentities($id) . "'>\n";
		echo "<input type=hidden name='action' value='comment'>\n";
		echo "<input type=hidden name='db' value='" . htmlentities($usedb) . "'>\n";

		foreach($params as $k => $v)
			echo "<input type=hidden name='$k' value='" . htmlentities($v) . "'>\n";

		echo "<tr><td colspan=2><table width=100%><tr>\n";
		echo "<td class=body width=25%><input type=hidden name=parse_bbcode value=y /></td>\n";

		echo "</tr></table></td></tr>\n";

		echo "<tr><td class=header>";

		editBox($nmsg);

		echo "</td></tr>\n";
		echo "<tr><td class=header align=center><input type=submit name=postaction value=Preview> <input type=submit name=postaction value='Post' accesskey='s' onClick='checksubmit()'></td></tr>\n";

		echo "</table>";
		echo "</form>";

		incFooter();
		exit(0);
	}

	$parse_bbcode 	= $parse_bbcode ? 'y' : 'n';


	$old_user_abort = ignore_user_abort(true);
	if($usedb == 'polls'){
		$result=$polldb->prepare_query("SELECT id FROM pollcomments WHERE itemid = # && time > # && authorid = #", $id, time() - 15, $userData['userid']);
		if($result->fetchrow()) //double post
			return false;

		$polldb->prepare_query("INSERT INTO pollcomments SET itemid = #, authorid = #, time = #", $id, $userData['userid'], time());
		$insertid = $polldb->insertid();

		$polldb->prepare_query("INSERT INTO pollcommentstext SET id = #, msg = ?, nmsg = ?,  parse_bbcode = ?", $insertid, $msg, $nmsg3, $parse_bbcode);
		$polldb->prepare_query("UPDATE polls SET comments = comments+1 WHERE id = #", $id);
	}else{
		$result = $articlesdb->prepare_query("SELECT id FROM comments WHERE itemid = # && time > # && authorid = #", $id, time() - 15, $userData['userid']);
		if($result->fetchrow()) //double post
			return false;

		$articlesdb->prepare_query("INSERT INTO comments SET itemid = #, authorid = #, time = #", $id, $userData['userid'], time());
		$insertid = $articlesdb->insertid();

		$articlesdb->prepare_query("INSERT INTO commentstext SET id = #, msg = ?, nmsg = ?,  parse_bbcode = ?", $insertid, $msg, $nmsg3, $parse_bbcode);

		$articlesdb->prepare_query("UPDATE articles SET comments = comments+1 WHERE id = #", $id);
	}
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

function makeLinkBar($links, $class)
{
  	$implode = array();
  	$class = ($class? " class=$class":"");
  	foreach ($links as $link)
  	{
  		if (!empty($link[1]))
  			$implode[] = "<a href=\"{$link[1]}\"" . $class . ">{$link[0]}</a>";
  		else
  			$implode[] = "<b>{$link[0]}</b>";
	}
	return implode(" | ", $implode);
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

function getREQval($name, $type = 'string', $default = null){
	$val = (isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default);
	settype($val, $type);
	return $val;
}

function getGETval($name, $type = 'string', $default = null){
	$val = (isset($_GET[$name]) ? $_GET[$name] : $default);
	settype($val, $type);
	return $val;
}

function getPOSTval($name, $type = 'string', $default = null){
	$val = (isset($_POST[$name]) ? $_POST[$name] : $default);
	settype($val, $type);
	return $val;
}

function getCOOKIEval($name, $type = 'string', $default = null){
	$val = (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
	settype($val, $type);
	return $val;
}

function getFILEval($name){
	return (isset($_FILES[$name]) ? $_FILES[$name] : false);
}

function getSERVERval($name){
	return (isset($_SERVER[$name]) ? $_SERVER[$name] : false);
}

//meant to be used with extract: extract(setDefaults($input, $defaults));
function setDefaults($input, $defaults){
	return array_merge($defaults, array_intersect_key($input, $defaults));
}

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


function getStaticValue($id, $restricted = true, $full = false){ //default return restricted content
	global $db, $cache;

	if(!is_numeric($id)){
		$newid = $cache->get("staticpageslookup-$id");

		if(!$newid){
			$res = $db->prepare_query("SELECT id FROM staticpages WHERE name = ?", $id);
			$newid = $res->fetchfield();

			if(!$newid)
				return false;

			$cache->put("staticpageslookup-$id", $newid, 86400);
		}
		$id = $newid;
	}

	$data = $cache->get("staticpages-$id");

	if(!$data){
		$res = $db->prepare_query("SELECT content, restricted, html, autonewlines, pagewidth FROM staticpages WHERE id = #", $id);
		$data = $res->fetchrow();

		if(!$data)
			return false;

		$cache->put("staticpages-$id", $data, 86400);
	}

	if(!$restricted && $data['restricted'] == 'y') //if calling from pages.php, and it's restricted content, don't show it.
		return false;

	if($data['html'] == 'n')
		$data['content'] = parseHTML($data['content']);

	if($data['autonewlines'] == 'y')
		$data['content'] = nl2br($data['content']);

	if($full) //pages.php can do more with it as needed (page with specifically)
		return $data;
	else
		return $data['content'];
}

/////////////////////////////////////////////////////
////// PHP rewrite or general funcs /////////////////
/////////////////////////////////////////////////////

function swap(&$var1, &$var2){
	$temp = $var1;
	$var1 = $var2;
	$var2 = $temp;
}

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

function array_search_offset($needle, $haystack, $offset = 0, $strict = false){

	for($i = 0; $i < $offset; $i++)
		next($haystack);

	while(list($k, $v) = each($haystack)){
		if($strict){
			if($v === $needle)
				return $k;
		}else{
			if($v == $needle)
				return $k;
		}
	}

	return false;
}

function explode_keep($sep, $str){ //explodes only on single chars (can be an array of chars), and keeps the char at the beginning of the items
	$array = array();

	while(($pos = strpos_needle($str, $sep, 1)) !== false){
		$array[] = substr($str, 0, $pos);
		$str = substr($str, $pos);
	}
	if($str != '')
		$array[] = $str;

	return $array;
}

function strpos_needle(&$haystack, $needle, $offset = 0){
	if(!is_array($needle))
		$needle = array($needle);

	$needle = array_flip($needle);
	for($i = $offset; $i < strlen($haystack); $i++)
		if(isset($needle[$haystack{$i}]))
			return $i;

	return false;
}

function diff($str1, $str2, $sep = "\n"){ //takes 2 strings, and returns array( array( $line1, $line2), ...), and null when either of those don't exist
	$array1 = explode_keep($sep, $str1);
	$weight1 = array_count_values($array1);

	$array2 = explode_keep($sep, $str2);
	$weight2 = array_count_values($array2);

	$diff = array();

	$pos1 = 0;
	$pos2 = 0;


	while(1){

	//if we're at the end of a string, the other must all be additions.
		if($pos1 == count($array1)){
			for($i = $pos2; $i < count($array2); $i++)
				$diff[] = array(null, $array2[$i]);
			break;
		}

		if($pos2 == count($array2)){
			for($i = $pos1; $i < count($array1); $i++)
				$diff[] = array($array1[$i], null);
			break;
		}

	//get the line
		$line1 = $array1[$pos1];
		$line2 = $array2[$pos2];

	//matches
		if($line1 == $line2){
			$diff[] = array($line1, $line2);
			$pos1++;
			$pos2++;
			continue;
		}

		$i = array_search_offset($line2, $array1, $pos1+1);
		$j = array_search_offset($line1, $array2, $pos2+1);

	//if both are unique, it's a change
		if($i === false && $j === false ){ //change, not insert
			$diff[] = array($line1, $line2);
			$pos1++;
			$pos2++;
			continue;
		}

	//insert to str2
		if($i == $pos1+1){
			$diff[] = array($line1, null);
			$pos1++;
			continue;
		}

	 //insert to str1
		if($j == $pos2+1){
			$diff[] = array(null, $line2);
			$pos2++;
			continue;
		}

	//non-unique change

	//find the dominant line
		if($weight1[$line1] == 1 && $j !== false){ //ie is unique
			for($i = $pos2; $i < $j; $i++)
				$diff[] = array(null, $array2[$i]);

			$diff[] = array($line1, $line1);
			$pos1++;
			$pos2 = $j+1;
			continue;
		}

	//find the dominant line
		if($weight2[$line2] == 1 && $i !== false){ //ie is unique
			for($j = $pos1; $j < $i; $j++)
				$diff[] = array($array1[$j], null);

			$diff[] = array($line2, $line2);
			$pos2++;
			$pos1 = $i+1;
			continue;
		}

	//assume change
		$diff[] = array($line1, $line2);
		$pos1++;
		$pos2++;
	}

	return $diff;
}

function get_memory_usage(){
	if(!function_exists('memory_get_usage')){
		return 0;
	}else{
		return memory_get_usage();
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
			$str .= "<option value=\"$v\" selected> $v</option>";
		else
			$str .= "<option value=\"$v\"> $v</option>";
	}

	return $str;
}

function make_select_list_multiple( $list, $sel = array() ){
	$str = "";
	foreach($list as $k => $v){
		if(in_array($v, $sel))
			$str .= "<option value=\"$v\" selected> $v</option>";
		else
			$str .= "<option value=\"$v\"> $v</option>";
	}

	return $str;
}

function make_select_list_key( $list, $sel = null ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $k )
			$str .= "<option value=\"$k\" selected> $v</option>";
		else
			$str .= "<option value=\"$k\"> $v</option>";
	}

	return $str;
}

function make_select_list_multiple_key( $list, $sel = array() ){
	$str = "";
	foreach($list as $k => $v){
		if(in_array($k, $sel))
			$str .= "<option value=\"$k\" selected> $v</option>";
		else
			$str .= "<option value=\"$k\"> $v</option>";
	}

	return $str;
}

function make_select_list_key_key( $list, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $v )
			$str .= "<option value=\"$k\" selected> $k</option>";
		else
			$str .= "<option value=\"$k\"> $k</option>";
	}

	return $str;
}

function make_select_list_col_key( $list, $col, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $k )
			$str .= "<option value=\"$k\" selected> $v[$col]</option>";
		else
			$str .= "<option value=\"$k\"> $v[$col]</option>";
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
		$str .= ">" . str_repeat("- ", $cat['depth']) . $cat['name'] . "</option>";
	}

	return $str;
}

function makeCatSelect_multiple($branch, $category = array()){
	$str="";

	foreach($branch as $cat){
		$str .= "<option value='$cat[id]'";
		if(in_array($cat['id'], $category))
			$str .= " selected";
		$str .= ">" . str_repeat("- ", $cat['depth']) . $cat['name'] . "</option>";
	}

	return $str;
}

function makeCheckBox($name, $title, $checked = false){
	return "<input type=checkbox id=\"$name\" name=\"$name\"" . ($checked ? ' checked' : '') . "><label for=\"$name\"> $title</label>";
}

/////////////////////////////////////////////////////
////// User Functions ///////////////////////////////
/////////////////////////////////////////////////////

function getFriendsListIDs($uid, $mode = USER_FRIENDS){
	global $cache, $usersdb;

	$friendids = $cache->get("friendids$mode-$uid");

	if($friendids === false){
		if($mode == USER_FRIENDS){
			$res = $usersdb->prepare_query("SELECT friendid FROM friends WHERE userid = %", $uid);

			$friendids = array();
			while($line = $res->fetchrow())
				$friendids[] = $line['friendid'];
		}else{
			$res = $usersdb->prepare_query("SELECT userid FROM friends WHERE friendid = #", $uid); //yes, all servers

			$friendids = array();
			while($line = $res->fetchrow())
				$friendids[] = $line['userid'];

		}

		$cache->put("friendids$mode-$uid", $friendids, 86400);
	}

	if(count($friendids))
		return array_combine($friendids, $friendids);
	else
		return array();
}

function getFriendsList($uid, $mode = USER_FRIENDS){
	global $cache, $usersdb;

	$friendids = getFriendsListIDs($uid, $mode);

	$friends = array();

	if(count($friendids)){
		$users = getUserInfo($friendids);

		foreach($users as $user)
			if($user['state'] == 'new' || $user['state'] == 'active')
				$friends[$user['username']] = $user['userid'];

		uksort($friends,'strcasecmp');
		$friends = array_flip($friends);
	}
	return $friends;
}

function getMutualFriendsList($uid, $mode = USER_FRIENDS){

	$friends = getFriendsListIDs($uid, USER_FRIENDS);
	$friendof = getFriendsListIDs($uid, USER_FRIENDOF);


	$rows = array();

	if($mode == USER_FRIENDS){
		foreach($friends as $id)
			$rows[$id] = isset($friendof[$id]);
	}else{
		foreach($friendof as $id)
			$rows[$id] = isset($friends[$id]);

	}

	return $rows;
}

function getUserInfo($uids, $getactive = true){
	global $usersdb, $cache, $config;

	$time = time();

	$array = is_array($uids);

	if(!$array)
		$uids = array($uids);

	foreach ($uids as & $uid)
		settype($uid, 'integer');

	$users = $cache->get_multi($uids, "userinfo-");

	$missingids = array_diff($uids, array_keys($users));

	if(count($missingids)){

		$names = getUserName($missingids);

		$res = $usersdb->prepare_query("SELECT * FROM users WHERE userid IN (%)", $missingids);

		while($line = $res->fetchrow()){
			$line['username'] = $names[$line['userid']];
			$users[$line['userid']] = $line;
			$cache->put("userinfo-$line[userid]", $line, 86400*7);
		}

		$uids = array_keys($users);

		if(count($uids) == 0)
			return ($array ? array() : false);
	}

	if($getactive){
		$activetimes = $cache->get_multi($uids, "useractive-"); //don't need to set, as it is never removed, and should never be older than the above cached version

		foreach($activetimes as $uid => $activetime)
			$users[$uid]['activetime'] = $activetime;
	}

	foreach($uids as $uid){
		if($getactive)
			$users[$uid]['online'] = ($users[$uid]['activetime'] > $time - $config['friendAwayTime'] ? 'y' : 'n');
		$users[$uid]['plus'] = ($users[$uid]['premiumexpiry'] > $time);
		$users[$uid]['age'] = getAge($users[$uid]['dob']);
	}

	return ($array ? $users : array_pop($users));
}

function getUserPics($uid){
	global $usersdb, $cache;

	$pics = $cache->get("pics-$uid");

	if(!$pics){
		$res = $usersdb->prepare_query("SELECT id, description, priority FROM pics WHERE userid = %", $uid);

		$pics = array();
		while($line = $res->fetchrow())
			$pics[$line['priority']] = $line;

		ksort($pics);

		$cache->put("pics-$uid", $pics, 86400*7);
	}

	return $pics;
}

function getUserName($uids){
	global $usersdb, $masterdb, $cache;

	static $usernames = array();

	$array = is_array($uids);

	if(!$array)
		$uids = array($uids);

//already have all except these
	$missingids = array_diff($uids, array_keys($usernames));

	if(count($missingids)){
	//try to get them from memcache
		$usernames += $cache->get_multi($missingids, "username-");

		$missingids = array_diff($uids, array_keys($usernames));

	//if all else fails, grab from the db
		if(count($missingids)){
			// this could be made to not touch the masterdb unless the id is not found.
			$res = $masterdb->prepare_query("SELECT userid, username FROM usernames WHERE userid IN (#)", $missingids);

			while($line = $res->fetchrow()){
				$usernames[$line['userid']] = $line['username'];
				$cache->put("username-$line[userid]", $line['username'], 86400*7);
			}
		}

		$missingids = array_diff($uids, array_keys($usernames));

	//still couldn't find them, must not exist
		foreach($missingids as $id){
			foreach($uids as $k => $uid){
				if($uid == $id){
					unset($uids[$k]);
					break;
				}
			}
		}
	}

	$ret = array();
	foreach($uids as $id)
		$ret[$id] = $usernames[$id];

	return ($array ? $ret : array_pop($ret));
}

function getUserID($uids){ // $uids = (optionally an array of) usernames
	global $masterdb, $usersdb, $cache;

	static $userids = array(); // username => userid

	$array = is_array($uids);

	if(!$array)
		$uids = array($uids);

	foreach($uids as $k => $uname){
		$uname = strtolower(trim($uname));

		if(is_numeric($uname)) //if it's already a userid, just add it to the list of userids
			$userids[$uname] = $uname;

		$uids[$k] = $uname;
	}

//already have all except these
	$missingids = array_diff($uids, array_keys($userids));

	if(count($missingids)){
	//try to get them from memcache
		$userids += $cache->get_multi($missingids, "username2userid-");

		$missingids = array_diff($uids, array_keys($userids));

	//if all else fails, grab from the db
		if(count($missingids)){
			$res = $masterdb->prepare_query("SELECT userid, username FROM usernames WHERE live = 'y' && username IN (?)", $missingids);

			while($line = $res->fetchrow()){
				$userids[strtolower($line['username'])] = $line['userid'];
				$cache->put("username2userid-" . strtolower($line['username']), $line['userid'], 86400*7);
			}
		}

		$missingids = array_diff($uids, array_keys($userids));

	//still couldn't find them, must not exist
		foreach($missingids as $id)
			if(($k = array_search($id, $uids)) !== false)
				unset($uids[$k]);
	}

	$ret = array();
	foreach($uids as $id)
		$ret[$id] = $userids[$id];

	if($array)
		return $ret;

	if(count($ret))
		return array_pop($ret);
	else
		return false;
}

function getSpotlight(){
	global $usersdb, $db, $cache, $messaging;

	$spotlightmax = $cache->get("spotlightmax");

	if(!$spotlightmax){
		$res = $db->query("SELECT count(*) FROM spotlight");

		$spotlightmax = $res->fetchfield();

		$cache->put("spotlightmax", $spotlightmax, 86400);
	}

	randomize();

	$i = 20;

	while(--$i){
		$res = $db->prepare_query("SELECT userid FROM spotlight WHERE id = #", rand(1,$spotlightmax));
		$spotlight = $res->fetchrow();

		if(!$spotlight)
			continue;

		$userid = $spotlight['userid'];

		$user = getUserInfo($userid);

		if($user['state'] != 'active' || !$user['plus'])
			continue;

		$pics = getUserPics($userid);

		if(count($pics) == 0)
			continue;

		$pic = $pics[array_rand($pics)]['id'];

		$ret = array(	'userid' => $userid,
						'username' => $user['username'],
						'pic' => $pic,
						'age' => $user['age'],
						'sex' => $user['sex']
					);

		break;
	}

	if($i){

		$db->prepare_query("INSERT INTO spotlighthist SET userid = #, pic = #, time = #", $ret['userid'], $ret['pic'], time());

		$messaging->deliverMsg($ret['userid'], "Spotlight", "You've been spotlighted!", 0, "Nexopia", 0);

		return $ret;
	}else{
		return 0;
	}
}

function userNameLegal($word){
	global $msgs, $db, $config;

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

	if($word !== $orig){
		$msgs->addMsg("Illegal characters have been removed. Changed to '$word'.");
		return false;
	}

	if(strlen($word) < $config['minusernamelength']){
		$msgs->addMsg("Username must be at least $config[minusernamelength] characters long.");
		return false;
	}
	if(strlen($word) > $config['maxusernamelength']){
		$msgs->addMsg("Username cannot be more than $config[maxusernamelength] characters long");
		return false;
	}

	if(is_numeric($word)){
		$msgs->addMsg("Names must have at least one non-numeric character");
		return false;
	}

	if(getUserID($word) !== false){
		$msgs->addMsg("That username is already in use");
		return false;
	}

	$res = $db->query("SELECT word,type FROM bannedwords");

	while($line = $res->fetchrow()){
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
	global $cache, $usersdb, $mods;

	if($mods->isAdmin($from))
		return false;

	if($scope && !$ignorelistonly){
		$line = getUserInfo($to, false);

		if(($line['onlyfriends'] == 'both' || $line['onlyfriends'] == $scope) && !isFriend($from,$to))
			return true;

		if(($line['ignorebyage'] == 'both' || $line['ignorebyage'] == $scope) && $age && ($age < $line['defaultminage'] || $age > $line['defaultmaxage']) && !isFriend($from, $to))
			return true;
	}

	$ignorelist = $cache->get("ignorelist-$to");

	if($ignorelist === false){
		$res = $usersdb->prepare_query("SELECT ignoreid FROM `ignore` WHERE userid = %", $to);

		$ignorelist = array();
		while($line = $res->fetchrow())
			$ignorelist[$line['ignoreid']] = $line['ignoreid'];

		$cache->put("ignorelist-$to", $ignorelist, 86400*3); //3 days
	}

	return isset($ignorelist[$from]);
}

function isFriend($friendid, $userid=0){
	global $userData, $db;

	if($userid==0)
		$userid=$userData['userid'];

	if($friendid == $userid)
		return true;

	$friends = getFriendsListIDs($userid);

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
	global $msgs, $masterdb;

	$res = $masterdb->prepare_query("SELECT userid FROM useremails WHERE email = ?", $email);

	if($res->fetchrow()){
		$msgs->addMsg("Email Already in use");
		return true;
	}
	return false;
}

function isBanned($val){
	global $db;

	$res = $db->prepare_query("SELECT date FROM bannedusers WHERE banned = ?", $val);

	return $res->fetchrow() != false;
}

/////////////////////////////////////////////////////
////// Sorting Functions ////////////////////////////
/////////////////////////////////////////////////////

function isValidSortt($sortlist, &$sortt, $default = ""){
	foreach($sortlist as $n => $v)
		if($v && $n == $sortt)
			return true;
	if($default){
		$sortt = $default;
		return false;
	}
	foreach($sortlist as $n => $v){
		if($v){
			$sortt = $n;
			return false;
		}
	}
}

function isValidSortd(&$sortd, $default = 'ASC'){
	if($sortd == 'ASC' || $sortd == 'DESC')
		return true;
	$sortd = $default;
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

function makeSortTableHeader($name, $type, $varlist=array(), $href="", $align='left'){
	global $sortt,$sortd,$config;
	if($href=="")
		$href=$_SERVER['PHP_SELF'];
	$str = "<td class=header align=$align nowrap><a class=header href=\"$href?sortd=" . ($sortt==$type ? ($sortd=="ASC" ? "DESC" : "ASC") : $sortd). "&sortt=$type";
	foreach($varlist as $k => $v)
		$str .= "&$k=$v";
	$str .= "\">$name</a>". ($sortt==$type ? "&nbsp<img src=$config[imageloc]$sortd.png>" : "") ."</td>\n";
	return $str;
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

	$first = true;
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

		if($first){ //use the first time only as the built in php sorts are not stable (ie if they have equal value, they may randomly change order)
			uasort($rows, $compare);
			$first = false;
		}else{
			mergesort($rows, $compare);
		}
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

