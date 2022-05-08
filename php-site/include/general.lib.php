<?
// Copyright Timo Ewalds 2004 all rights reserved.


ini_set("precision","20");

error_reporting (E_ALL);

define("REQUIRE_ANY", 0);
define("REQUIRE_HALFLOGGEDIN", 0.5);
define("REQUIRE_LOGGEDIN", 1);
define("REQUIRE_NOTLOGGEDIN", -1);
define("REQUIRE_LOGGEDIN_PLUS", 2);
define("REQUIRE_LOGGEDIN_ADMIN", 3);

$revstr = '$Revision: 4965 $';
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
'officeshaw' => '68.149.19.75',
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


//most pages only serve html, so don't try to serve try to serve html if the browser expects an image
//to have a page accept anything (javascript, css, images), set $accepttype = false; before including general.lib.php
	if(!isset($accepttype))
		$accepttype = 'html';

	if($accepttype && isset($_SERVER['HTTP_ACCEPT']) && !checkAcceptType($accepttype))
		die("Bad Request Type");


	$cwd = getcwd();

$siteStats = array();
$userData = array(	'loggedIn' => false,
					'username' => '',
					'userid' => 0,
					'premium' => false,
					'limitads' => false,
					'debug' => false);

$cachekey = getREQval('cachekey');

// Simple page/auth is an attempt to lower the overhead of simple pages
//   Simple auth is true/false, default false. When true, use the cached version of prefs if possible
//   Simple page has several levels, and includes more libraries at lower simplicity levels:
//     0 - Not simple, include everything - default
//     1 - Only include enough to display the skin
//     2 - Only include the absolute basics

	if(!isset($simplepage))
		$simplepage = 0; // ($cachekey ? 1 : 0);
	if(!isset($simpleauth))
		$simpleauth = ($cachekey ? true : false);


	include("$cwd/include/config.inc.php");

	if(isset($devutil) && $devutil && (!isset($config['devutil']) || !$config['devutil'])) //is devutil, and isn't a dev site
		exit;


	includecompiled('init', array(
		"include/defines.php",
		"include/mirrors.php",
		"include/memcached-client.php",
		"include/memcache2.php",
		"include/sql.php",
		"include/sqlmirror.php",
		"include/sqlpair.php",
		"include/sqlmulti.php",
		"include/mysql.php",
		) );

	$memcache = new memcached($memcacheoptions);
	$pagememcache = new memcached($pagecacheoptions);

	$cache 		= new cache($memcache, "$sitebasedir/cache");
	$pagecache	= new cache($pagememcache, "$sitebasedir/cache");

timeline('cache', true);


	if($config['cachedbs'] && (!isset($cachedbs) || !$cachedbs))
		$dbs = $cache->hdget("dbs", 3600, 'getdbs');
	else
		$dbs = getdbs();

	extract($dbs);
	$cache->db = $db;

timeline('dbs', true);


includecompiled('basic', array(
	"include/auth4.php",
	"include/banner6.php",
	"include/msgs.php",
	"include/date.php",
	"include/google.php",
	) );
	
if($simplepage <= 1)
	includecompiled('simple', array(
		"include/stats.php",
		"include/menu.php",
		"include/moderator.php",
		"include/blocks.php",
		"include/categories.php",
		"include/archive.php",
		"include/abuselog.php",
		"include/forums.php",
		"include/messaging.php",
		"include/weblog.php",
		"include/template.php",
		"include/usercomments.php",
		"include/textmanip.php",
		"include/wiki2.php",
		"include/sitenotifications.php",
		"include/secure_form.php"
	));

if($simplepage == 0)
	includecompiled('mainset', array(
		"include/general.class.php",
		"include/databaseobject.php",
		"include/survey.php",
		"include/polls.php",
		"include/shoppingcart.php",
		"include/payg.php",
		"include/usernotify.php",
		"include/priorities.php",
		"include/profileskins.php",
		"include/plus.php",
		"include/smtp.php",
		"include/timer.php",
		"include/filesystem.php",
		"include/HTTPClient.php",
		"include/MogileFS.php",
		"include/mogfs.php",
		"include/terms.php",
		"include/quizzes.php",
		"include/gallery.php",
		"include/pics.php",
		"include/comment.php",
		"include/sourcepictures.php",
		"include/userSearch.php",
		"include/profileblocks.php",
		"include/profilehead.php",
		"include/inlineDebug.php",
//		"include/uploads.php",
		"include/typeid.php",
		"include/groups.php"
	) );


timeline('parse', true);

//declared after includes to allow to use all defines
	$accounts = 	new accounts( $masterdb, $usersdb, $db );
	$useraccounts = new useraccounts( $masterdb, $usersdb );
	$auth = 		new authentication( $masterdb, $usersdb );

	$msgs = 		new messages();
	$banner = 		new bannerclient( $bannerdb, $bannerservers );
	$google = 		new googleintegration( $usersdb );

	if($simplepage <= 1){
		$mods = 		new moderator( $moddb );
		$abuselog = 	new abuselog( $moddb );
		$archive =      new archive( $usersdb );
		$forums = 		new forums ( $forumdb );
		$messaging = 	new messaging( $usersdb );
		$usercomments = new usercomments( $usersdb );
		$weblog = 		new weblog( $usersdb );
		$wiki =		 	new wiki( $wikidb );
	}
	if($simplepage == 0){
		$polls = 		new polls( $polldb );
		$shoppingcart = new shoppingcart( $shopdb );
		$payg = 		new paygcards( $shopdb );
		$usernotify =   new usernotify( $db );
		$filesystem = 	new filesystem($filesdb, $THIS_IMG_SERVER);
		$mogfs =		new mogfs($mogfs_domain, $mogfs_hosts);
		$quizzes =		new quizzes( $contestdb );
		$galleries = 	new galleries( $usersdb );
		$sourcepictures=new sourcepictures( $usersdb );
		$inlineDebug =	new inlineDebug();
		$typeid =		new typeid( $db );
		$groupmembers =	new groupmembers( $groupsdb, $usersdb );
	}

//	if($enableCompression)
//		ini_set('zlib.output_compression', 'On');
//		ob_start("ob_gzhandler");

	if(!isset($config['timezone']))
		$config['timezone'] = 6;

	date_default_timezone_set("UTC");

	@header("Vary: Cookie"); //helps fix caching
	@header("Content-Type: text/html; charset=ISO-8859-1"); //set the content type


	timeline('init', true);

	if(isset($login)){
		requireLogin($login);
	}

	if($cachekey && checkKey('cache-' . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], "cachekey") - 1), $cachekey)){
		$page = $pagecache->get("page-cache-$cachekey");
		if($page){
			echo $page;
			if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)) debugOutput();
			exit;
		}
	}

	if(!isset($forceserver))
		$forceserver = getCOOKIEval('forceserver', 'bool');

	if(count($_POST) == 0 && !$forceserver && $wwwdomain != $_SERVER['HTTP_HOST']){
		header("location: " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $wwwdomain . $_SERVER['REQUEST_URI']);
		exit;
	}

if($simplepage <= 1)
	include("$cwd/include/skin.php");


timeline('auth done');

$action = getREQval('action');

//end of general stuff








/////////////////////////////////////////////////////
////// Database Init Functions //////////////////////
/////////////////////////////////////////////////////

	function getdbs(){
		global $databaseprofile, $databases, $cwd;


		include("$cwd/include/dbconf.$databaseprofile.php");
		recursive_inherit_config($databases, $databases);

		$dbs = array();
		foreach ($databases as $dbname => $dbinfo){
			if (isset($dbinfo['instance'])){
				$dbo = init_dbo($dbinfo, $dbs);
				if($dbo)
					$dbs[$dbname] = $dbo;
			}
		}

		return $dbs;
	}

	function inherit_database_profile($name){
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
	function recursive_inherit_config(&$configtree, &$subtree, $sets = array()){
		foreach ($sets as $key => $value)
			if (!isset($subtree[$key]))
				$subtree[$key] = $value;

		if (isset($subtree['inherit']) && isset($configtree[ $subtree['inherit'] ])){
			$sourcename = $subtree['inherit'];
			recursive_merge_config($subtree, $configtree[$sourcename]);
		}

		foreach ($subtree as $key => &$value)
			if (!is_array($value) && $key != 'inherit')
				$sets[$key] = $value;

		foreach ($subtree as &$subsubtree)
			if (is_array($subsubtree))
				recursive_inherit_config($configtree, $subsubtree, $sets);
	}

	function init_dbo($dbinfo, &$dbs) // already constructed dbs passed to $dbs
	{
		global $pluswwwdomain;

		if ($dbinfo['type'] == 'single'){
			return new sql_db($dbinfo);

		} else if ($dbinfo['type'] == 'multi') {
			return new multiple_sql_db($dbinfo, $_SERVER['HTTP_HOST'] == $pluswwwdomain);

		} else if ($dbinfo['type'] == 'pair') {
			return new pair_sql_db($dbinfo);

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


/////////////////////////////////////////////////////
////// Site Specific Functions //////////////////////
/////////////////////////////////////////////////////

function checkAcceptType($allowed){
	$accept = explode(",", $_SERVER['HTTP_ACCEPT']);

	foreach($accept as $type){
		$type = trim($type);

		if(strpos($type, ';') === false && ($type == '*/*' || strpos($type, $allowed) !== false))
			return true;
	}

	return false;
}

function includecompiled($name, $includes){
	global $config, $sitebasedir, $cwd;

	if($config['cacheincludes']){
		if(!file_exists("$sitebasedir/cache/compiled-$name.php")){
			$str = "<?\n\n";
			foreach($includes as $file)
				$str .= "/****************************\n * $file\n ****************************/\n//" . substr(file_get_contents("$cwd/$file"), 2) . "\n\n\n\n";

			file_put_contents("$sitebasedir/cache/compiled-$name.php", $str);
		}
		include("$sitebasedir/cache/compiled-$name.php");
	}else{
		foreach($includes as $file)
			include("$cwd/$file");
	}
}

function requireLogin($login = REQUIRE_LOGGEDIN, $adminpriv = "", $userid = false, $key = false) // called by default when $login is set pre-start
{
	global $simpleauth, $userData, $mods, $auth;

	if (!$userid)
		$userid = getCOOKIEval('userid', 'int');
	if (!$key)
		$key = getCOOKIEval('key');

	$userData = $auth->auth($userid, $key, ($login >= REQUIRE_HALFLOGGEDIN), $simpleauth);

	$msg = "";

	if($login == REQUIRE_NOTLOGGEDIN && $userData['loggedIn'])
		$msg = "This is only viewable by people who haven't logged in";
	if($login == REQUIRE_LOGGEDIN && !$userData['loggedIn'])
		$msg = "This page will only be usable when you've activated. Please check your email.";
	if($login == REQUIRE_LOGGEDIN_PLUS && !($userData['premium'] || $mods->isAdmin($userData['userid'],$adminpriv)))
		$msg = "You must be a plus member to view this page";
	if($login == REQUIRE_LOGGEDIN_ADMIN && !$mods->isAdmin($userData['userid'],$adminpriv))
		$msg = "You do not have permission to see this page";

	if($msg){
		global $skins, $cwd;

		if(!isset($skins))
			include("$cwd/include/skin.php");

		incHeader();
		echo $msg;
		incFooter();
		exit;
	}

	return true;
}

function debugOutput($force = false){
	global $userData, $debuginfousers, $times, $memory, $dbs, $cache, $config, $revstr, $inlineDebug;
	if($force || ($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers))){
		timeline('end', true);
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
		//echo $inlineDebug->outputItems();

	//debug out wrapper table, that has the element to unhide the rest
		echo "<table>";
		echo "<tr><td class=header2><a class=header2 href=# onClick=\"el=document.getElementById('debugout').style; el.display=(el.display=='none'?'':'none'); return false;\">Show/Hide Debug Output</a></td></tr>";
		echo "<tr><td class=header2 id=debugout style=\"display: none\">";


		echo "<table><tr><td valign=top>";

	//summary
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

	//timeline
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

		echo "</table>";

		echo "</td></tr></table>";

		$headers = headers_list();

	//http headers
		echo "<table>";
		echo "<tr><td class=header colspan=2>HTTP Headers sent:</td></tr>";
		foreach($headers as $line){
			list($n, $v) = explode(":", $line, 2);
			echo "<tr><td class=body>$n:</td><td class=body>$v</td></tr>";
		}
		echo "</table>";

	//database and memcache queries
		outputQueries();

		echo "</td></tr></table>";
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

	$result = strtoupper(substr(md5("$myuserid:blah:$id"), 0, 10));
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

//	$blocks = $cache->hdget("blocks", 0, "getBlocks");

	$blocks = array( 'r' => array( 'incMsgBlock', 'incFriendsBlock', 'incModBlock', 'incSubscribedThreadsBlock') );

	if(count($blocks[$side]))
		foreach($blocks[$side] as $funcname)
			$funcname($side);
}

function editBox($text = "", $id = 'msg', $formid = 'editbox', $height = 200, $width = 600, $maxlength = 0){
	echo editBoxStr($text, $id, $formid, $height, $width, $maxlength);
}

function editBoxStr($text = "", $id = 'msg', $formid = 'editbox', $height = 200, $width = 600, $maxlength = 0){
	global $config;

	$smilyusage = getSmilies();

	$mangledtext = htmlentities(str_replace("\r","",str_replace("\n","\\n",str_replace('\\', '\\\\', $text))));

	$output = "<script>" .
		"var smileypics = new Array('" . implode("','", $smilyusage) . "');" .
		"var smileycodes = new Array('" . implode("','", array_keys($smilyusage)) . "');" .
		"var smileyloc = '$config[smilyloc]';";

	$output .= "document.write(editBox(\"$mangledtext\", true ,'$id','$formid', $maxlength, $height, $width)); </script>";

	$output .= "<noscript><textarea cols=70 rows=10 name='$id'></textarea></noscript>";

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
				$parts[0] == 127 || 										// 127.0.0.0/8
			(	$parts[0] == 172 && $parts[1] >= 16 && $parts[1] <= 31) ||	// 172.16.0.0/12
			(	$parts[0] == 192 && $parts[1] == 168) );					// 192.168.0.0/16
}

function getAge($birthday,$decimals=0) {
	if(!$decimals){
		static $year = null;
		static $month = null;
		static $day = null;

		if($year === null)
			list($year, $month, $day) = explode(" ", gmdate("Y m j"));


		list($byear, $bmonth, $bday) = explode(" ", gmdate("Y m j", $birthday));

		$age = $year - $byear;
		if($bmonth > $month || ($bmonth == $month && $bday > $day))
			$age--;

		return $age;
	}else{
		$age = gmdate("Y") - gmdate("Y",$birthday) + ((gmdate("z") - gmdate("z",$birthday))/365);

		return number_format($age,$decimals);
	}
}

function gettime(){
	return microtime(true)*10000;
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

function getREQval($name, $type = 'string', $default = null, $allowremote = null){
	if(isset($_POST[$name]))
		return getInputType($_POST, $name, $type, $default, ($allowremote === null ? false : $allowremote));
	else
		return getInputType($_GET, $name, $type, $default, ($allowremote === null ? true : $allowremote));
}

function getGETval($name, $type = 'string', $default = null, $allowremote = true){
	return getInputType($_GET, $name, $type, $default, $allowremote);
}

function getPOSTval($name, $type = 'string', $default = null, $allowremote = false){
	return getInputType($_POST, $name, $type, $default, $allowremote);
}

function getREQarray($vals, $prefix = '', $allowremote = null){
	if($prefix){
		if(isset($_POST[$prefix][$name]))
			return getInputArray($_POST[$prefix], $vals, ($allowremote === null ? false : $allowremote));
		else
			return getInputArray($_GET[$prefix], $vals, ($allowremote === null ? true : $allowremote));
	}else{
		if(isset($_POST[$name]))
			return getInputArray($_POST, $vals, ($allowremote === null ? false : $allowremote));
		else
			return getInputArray($_GET, $vals, ($allowremote === null ? true : $allowremote));
	}
}

function getGETarray($vals, $prefix = '', $allowremote = true){
	if($prefix)
		return getInputArray($_GET[$prefix], $vals, $allowremote);
	else
		return getInputArray($_GET, $vals, $allowremote);
}

function getPOSTarray($vals, $prefix = '', $allowremote = true){
	if($prefix)
		return getInputArray($_POST[$prefix], $vals, $allowremote);
	else
		return getInputArray($_POST, $vals, $allowremote);
}

function isValidPost(){
	if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER']){
		$host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];

		if(strncmp($_SERVER['HTTP_REFERER'], $host, strlen($host)) != 0)
			return false;
	}
	return true;
}

function getInputType(& $INPUT, $name, $type, $default, $allowremote){
	if(!$allowremote && !isValidPost())
		unset($INPUT[$name]);

	$val = (isset($INPUT[$name]) ? $INPUT[$name] : $default);
	settype($val, $type);
	return $val;
}

function getInputArray(& $INPUT, $vals, $allowremote){
	if(!$allowremote && !isValidPost())
		return $vals;

	$ret = array();
	foreach($vals as $k => $v){
		if(isset($INPUT[$k])){
			$ret[$k] = $INPUT[$k];
			settype($ret[$k], gettype($v));
		}else{
			$ret[$k] = $v;
		}
	}

	return $ret;
}

function getCOOKIEval($name, $type = 'string', $default = null){
	return getInputType($_COOKIE, $name, $type, $default, true);
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

function gzdecode($string){
	return gzinflate(substr($string, 10));
}

//use a setCookie wrapper, so on php 5.2+ the http only flag works, but doesn't fail on earlier versions.
function set_cookie($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $HTTPOnly = false) {
	if(PHP_VERSION > 5.2)
		setCookie($name, $value, $expire, $path, $domain, $secure, $HTTPOnly);
	else
		setCookie($name, $value, $expire, $path, $domain, $secure);
}

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

if(!function_exists('date_default_timezone_set')){
function date_default_timezone_set($tz){
	if(getenv("TZ") != $tz)
		putenv("TZ=$tz");
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

function array_add_max( & $array, $val, $max){
	$array[] = $val;

	$count = count($array);
	if($count > $max){
		end($array);
		unset($array[key($array) - floor($max/2)]);
		reset($array);
	}
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
			$str .= "<option value=\"" . htmlentities($v) . "\" selected> " . htmlentities($v) . "</option>";
		else
			$str .= "<option value=\"" . htmlentities($v) . "\"> " . htmlentities($v) . "</option>";
	}

	return $str;
}

function make_select_list_multiple( $list, $sel = array() ){
	$str = "";
	foreach($list as $k => $v){
		if(in_array($v, $sel))
			$str .= "<option value=\"" . htmlentities($v) . "\" selected> " . htmlentities($v) . "</option>";
		else
			$str .= "<option value=\"" . htmlentities($v) . "\"> " . htmlentities($v) . "</option>";
	}

	return $str;
}

function make_select_list_key( $list, $sel = null ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $k )
			$str .= "<option value=\"" . htmlentities($k) . "\" selected> " . htmlentities($v) . "</option>";
		else
			$str .= "<option value=\"" . htmlentities($k) . "\"> " . htmlentities($v) . "</option>";
	}

	return $str;
}

function make_select_list_multiple_key( $list, $sel = array() ){
	$str = "";
	foreach($list as $k => $v){
		if(in_array($k, $sel))
			$str .= "<option value=\"" . htmlentities($k) . "\" selected> " . htmlentities($v) . "</option>";
		else
			$str .= "<option value=\"" . htmlentities($k) . "\"> " . htmlentities($v) . "</option>";
	}

	return $str;
}

function make_select_list_key_key( $list, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $v )
			$str .= "<option value=\"" . htmlentities($k) . "\" selected> " . htmlentities($k) . "</option>";
		else
			$str .= "<option value=\"" . htmlentities($k) . "\"> " . htmlentities($k) . "</option>";
	}

	return $str;
}

function make_select_list_col_key( $list, $col, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $k )
			$str .= "<option value=\"" . htmlentities($k) . "\" selected> " . htmlentities($v[$col]) . "</option>";
		else
			$str .= "<option value=\"" . htmlentities($k) . "\"> " . htmlentities($v[$col]) . "</option>";
	}

	return $str;
}

function make_radio($name, $list, $sel = "", $class = 'body'){
	$str = "";
	foreach($list as $k => $v){
		$str .= "<input type=radio name=\"$name\" value=\"" . htmlentities($v) . "\" id=\"$name/" . htmlentities($k) . "\"";
		if( $sel == $v )
			$str .= " checked";
		$str .= "><label for=\"$name/" . htmlentities($k) . "\" class=$class> " . htmlentities($v) . "</label> ";
	}

	return $str;
}

function make_radio_key($name, $list, $sel = "", $class = 'body' ){
	$str = "";
	foreach($list as $k => $v){
		$str .= "<input type=radio name=\"$name\" value=\"" . htmlentities($k) . "\" id=\"$name/" . htmlentities($k) . "\"";
		if( $sel == $k )
			$str .= " checked";
		$str .= "><label for=\"$name/" . htmlentities($k) . "\" class=$class> " . htmlentities($v) . "</label> ";
	}

	return $str;
}

function makeCatSelect($branch, $category = null){
	$str="";

	$prefix = array();

	foreach($branch as $cat){
		if(!isset($prefix[$cat['depth']]))
			$prefix[$cat['depth']] = str_repeat("- ", $cat['depth']);

		$str .= "<option value='$cat[id]'";
		if($cat['id'] == $category)
			$str .= " selected";
		$str .= ">" . $prefix[$cat['depth']] . $cat['name'] . "</option>";
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
//*
	//Good version
		$users = getUserInfo($friendids, false);

		foreach($users as $user)
			if($user['state'] == 'new' || $user['state'] == 'active')
				$friends[$user['username']] = $user['userid'];

		uksort($friends,'strcasecmp');
		$friends = array_flip($friends);
/*/
	//shows frozen users, but faster
		$friends = getUserName($friendids);
		uasort($friends, 'strcasecmp');
//*/

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

	if($getactive){
		$temp = $cache->get_multi_multi($uids, array("userinfo-", "useractive-"));

		$users = isset($temp['userinfo-']) ? $temp['userinfo-'] : array();
		$activetimes = isset($temp['useractive-']) ? $temp['useractive-'] : array();
	}else{
		$users = $cache->get_multi($uids, "userinfo-");
	}

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

//don't need to set, as it is never removed, and should never be older than the above cached version
	if($getactive){
		foreach($activetimes as $uid => $activetime)
			if(isset($users[$uid]))
				$users[$uid]['activetime'] = $activetime;
	}

	foreach($users as & $user){
		$user['online'] = (($user['activetime'] > $time - $config['friendAwayTime']) ? 'y' : 'n');
		$user['plus'] = ($user['premiumexpiry'] > $time);
		$user['age'] = getAge($user['dob']);
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

function getUserIDByEmail($emailAddress)
{
	global $masterdb, $usersdb, $cache;
	
	$res = $masterdb->prepare_query("SELECT userid FROM useremails WHERE active = 'y' && email = ?", $emailAddress);
	
	$uid = false;
	
	while($line = $res->fetchrow()){
		$uid = $line['userid'];
	}
	
	if(is_numeric($uid))
		return $uid;
	else
		return false;
}

function getSpotlight(){
	global $usersdb, $db, $cache, $messaging, $config;

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

		$messaging->deliverMsg($ret['userid'], "Spotlight", "You've been spotlighted with this picture:\n[img=$config[thumbloc]" . floor($ret['userid']/1000) . "/$ret[userid]/$ret[pic].jpg]", 0, "Nexopia", 0);

		return $ret;
	}else{
		return 0;
	}
}

function getSpotlightHist($limit = 10){
	global $db;

	$res = $db->prepare_query("SELECT * FROM spotlighthist ORDER BY time DESC LIMIT 1, #", $limit); //skip the first since it is likely still live

	$users = array();

	while($line = $res->fetchrow())
		$users[$line['userid']] = $line;

	$userinfo = getUserInfo(array_keys($users));

	foreach($users as $userid => & $user){
		$user['username'] = $userinfo[$userid]['username'];
		$user['age'] = $userinfo[$userid]['age'];
		$user['sex'] = $userinfo[$userid]['sex'];
	}

	return $users;
}


//returns 0 if you're not ignored, 1 if it's friends only, 2 if it's by age or explicit
function isIgnored($to, $from, $scope, $age = 0, $ignorelistonly = false){ // usually $from = $userData['userid'];
	global $cache, $usersdb, $mods;

	if($mods->isAdmin($from))
		return 0;

	if($scope && !$ignorelistonly){
		$line = getUserInfo($to, false);

		if(($line['onlyfriends'] == 'both' || $line['onlyfriends'] == $scope) && !isFriend($from,$to))
			return 1;

		if(($line['ignorebyage'] == 'both' || $line['ignorebyage'] == $scope) && $age && ($age < $line['defaultminage'] || $age > $line['defaultmaxage']) && !isFriend($from, $to))
			return 2;
	}

	$ignorelist = $cache->get("ignorelist-$to");

	if($ignorelist === false){
		$res = $usersdb->prepare_query("SELECT ignoreid FROM `ignore` WHERE userid = %", $to);

		$ignorelist = array();
		while($line = $res->fetchrow())
			$ignorelist[$line['ignoreid']] = $line['ignoreid'];

		$cache->put("ignorelist-$to", $ignorelist, 86400*3); //3 days
	}

	return (isset($ignorelist[$from]) ? 2 : 0);
}

function isFriend($friendid, $userid=0, $selfAutomaticallyCounts=true){
	global $userData, $db;

	if($userid==0)
		$userid=$userData['userid'];

	if($friendid == $userid && $selfAutomaticallyCounts)
		return true;

	$friends = getFriendsListIDs($userid);

	return isset($friends[$friendid]);
}

function isFriendOfFriend($friendOfFriendID, $userid=0, $selfAutomaticallyCounts=true)
{
	global $userData, $db;
	
	if ($userid==0)
		$userid=$userData['userid'];
	
	if ($friendOfFriendID == $userid && $selfAutomaticallyCounts)
		return true;
	
	if ($friendOfFriendID == null)
		return false;
	
	$userFriends = getFriendsListIDs($userid);
	if (sizeof($userFriends) > 0)
	{
		foreach($userFriends as $userFriend)
		{
			if (isFriend($friendOfFriendID, $userFriend, $selfAutomaticallyCounts))
			{
				return true;
			}
		}
	}
	
	return false;
}


function isVisibleTo($userid, $viewerid, $visibility)
{
	global $userData;
	
	if (!$userData['loggedIn'])
	{
		return false;
	}
	
	if (($visibility != VISIBILITY_NONE && $viewerid == $userid) ||
		($visibility == VISIBILITY_FRIENDS && isFriend($viewerid, $userid, false)) ||
		($visibility == VISIBILITY_FRIENDS_OF_FRIENDS && isFriendOfFriend($viewerid, $userid, false)) ||
		$visibility == VISIBILITY_ALL)
	{
		return true;
	}
	else
	{
		return false;
	}
}


function isValidEmail($email){
	global $msgs;
	if(!eregi("^[a-z0-9]+([a-z0-9_.&-]+)*@([a-z0-9.-]+)+$", $email, $regs) ){
		$msgs->addMsg("Error: '" . htmlentities($email) . "' isn't a valid mail address");
		return false;
	}
	elseif(!checkdnsrr($regs[2],"MX") && !checkdnsrr($regs[2], "A")){
		$msgs->addMsg("Error: Can't find the host '$regs[2]'");
		return false;
	}
	
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
	if(count($rows) <= 1)
		return;

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

	$sorts = array_reverse($sorts);

	$func = "";
	foreach($sorts as $sort){
		list($key, $dir, $type) = $sort;
		if($dir == SORT_ASC){
			switch($type){
				case SORT_REGULAR:		$func .= 'if ($a["' . $key.'"] != $b["' . $key.'"]) {return ($a["' . $key . '"] < $b["' . $key . '"]) ? -1 : 1;}'; break;
				case SORT_NUMERIC:		$func .= 'if ($a["' . $key.'"] != $b["' . $key.'"]) {return ((float)$a["' . $key . '"] < (float)$b["' . $key . '"]) ? -1 : 1;}'; break;
				case SORT_STRING:		$func .= '$tmp = strcmp($a["'.$key.'"],$b["'.$key.'"]);        if($tmp != 0) return $tmp;'; break;
				case SORT_CASESTR:		$func .= '$tmp = strcasecmp($a["'.$key.'"],$b["'.$key.'"]);    if($tmp != 0) return $tmp;'; break;
				case SORT_NATSTR:		$func .= '$tmp = strnatcmp($a["'.$key.'"],$b["'.$key.'"]);     if($tmp != 0) return $tmp;'; break;
				case SORT_NATCASESTR:	$func .= '$tmp = strnatcasecmp($a["'.$key.'"],$b["'.$key.'"]); if($tmp != 0) return $tmp;'; break;
			}
		}else{
			switch($type){
				case SORT_REGULAR:		$func .= 'if ($a["' . $key.'"] != $b["' . $key.'"]) {return ($a["' . $key . '"] < $b["' . $key . '"]) ? 1 : -1;}'; break;
				case SORT_NUMERIC:		$func .= 'if ($a["' . $key.'"] != $b["' . $key.'"]) {return ((float)$a["' . $key . '"] < (float)$b["' . $key . '"]) ? 1 : -1;}'; break;
				case SORT_STRING:		$func .= '$tmp = strcmp($a["'.$key.'"],$b["'.$key.'"]);        if($tmp != 0) return 0 - $tmp;'; break;
				case SORT_CASESTR:		$func .= '$tmp = strcasecmp($a["'.$key.'"],$b["'.$key.'"]);    if($tmp != 0) return 0 - $tmp;'; break;
				case SORT_NATSTR:		$func .= '$tmp = strnatcmp($a["'.$key.'"],$b["'.$key.'"]);     if($tmp != 0) return 0 - $tmp;'; break;
				case SORT_NATCASESTR:	$func .= '$tmp = strnatcasecmp($a["'.$key.'"],$b["'.$key.'"]); if($tmp != 0) return 0 - $tmp;'; break;
			}
		}
	}
	$func .= 'return 0;';

	$compare = create_function('$a,$b',$func);
	uasort($rows, $compare);
}

// Use like this: post_process_queue("Video::Video", "embed", []);
// $className - the ruby class name of the object you are reporting
// $func - the name of the ruby-side function you want to call
// $params - the params to the function defined on the ruby side
function post_process_queue($className, $func, $params){
	global $db, $processqueuedb, $typeid, $config;

	$class_typeid = $typeid->getTypeID($className);

	$cluster = $config['queue_cluster'];
	$serialized_data = serialize($params);
	$time = time();

	$unique = MD5($func . $serialized_data . $time);
	$processqueuedb->prepare_query("INSERT IGNORE INTO `postprocessqueue` ( `id` , `time` , `module` , `func` , `params` , `unique` , `expiry` , `lock` , `owner` , `cluster` , `status` )
						VALUES ('', #, #, ?, ?, ?, #, 0, '', ?, 'queued')",
						$time, $class_typeid, $func, "php$serialized_data", $unique, ($time + 86400), $cluster);
}

// Use like this: enqueue("Observable::Status", "create", $uid, []);
// $className - the ruby class name of the object you are reporting
// $event_type - the type of event you are reporting on the object.
//                The defaults are 0=create, 1=edit
// $uid - the user for whom this event is being reported (the event owner)
// $params - an array containing the primary key for the column in which
//        the object is stored.  The object must exist already, ruby will
//        attempt to retrieve it.
function enqueue($className, $event_type, $uid, $params){
	global $db, $processqueuedb, $typeid, $config;

	$observable_typeid = $typeid->getTypeID("ObservationsModule");
	$class_typeid = $typeid->getTypeID($className);

	$time = time();

	$func = 'create_event';
	$cluster = $config['queue_cluster'];
	$serialized_data = serialize(array($class_typeid, $event_type, $uid, $time, $params));

	$unique = MD5($func . $serialized_data . $time);
	$processqueuedb->prepare_query("INSERT IGNORE INTO `postprocessqueue` ( `id` , `time` , `module` , `func` , `params` , `unique` , `expiry` , `lock` , `owner` , `cluster` , `status` )
						VALUES ('', #, #, ?, ?, ?, #, 0, '', ?, 'queued')",
						$time, $observable_typeid, $func, "php$serialized_data", $unique, ($time + 86400), $cluster);
}
