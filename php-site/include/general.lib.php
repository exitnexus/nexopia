<?
//Copyright Timo Ewalds 2004 all rights reserved.

ini_set("precision","20");

error_reporting (E_ALL);


/*

$acceptips = array(
'local' => '192.168.',
'timo'	=> '198.166.49.97');

function isPriviligedIP(){
	global $acceptips, $REMOTE_ADDR;
	foreach($acceptips as $ip)
		if(substr($REMOTE_ADDR,0,strlen($ip)) == $ip)
			return true;
	return false;
}

if(!isPriviligedIP())
//	die("Nexopia is down for an update, and will be up in a couple minutes");
	die("Nexopia is down for a backup, and will be up in a couple minutes");
//	die("One of the servers seems to have crashed, and will be up again shortly");
//	die("New servers are being added, and seem to be causing some trouble. It should be fixed shortly");

//*/

$startTime = gettime();


	if(empty($PHP_SELF))
		$PHP_SELF = $_SERVER['SCRIPT_NAME'];




if(!isset($userid))	$userid="";
if(!isset($key)) $key="";


$siteStats = array();
$userData = array('loggedIn'=>false,'username'=>'','userid'=>0, 'premium'=>false);

include_once("include/config.inc.php");
include_once("include/defines.php");

include_once("include/mysql4.php");
	$db = & new multiple_sql_db($databases);
	$fastdb = & new sql_db($databases['fast']['host'], $databases['fast']['login'], $databases['fast']['passwd'], $databases['fast']['db']);


//*
include_once("include/cache.php");
	$cache = & new cache("$sitebasedir/cache");
/*/

include_once("include/MemCachedClient.php");
	$memcache = & new MemCachedClient($memcacheoptions);

include_once("include/memcache.php");
	$cache = & new cache("$sitebasedir/cache");
//*/

	$config = $cache->hdget("config", 'getConfig');

	if(!isset($errorLogging))
		$errorLogging = $config['errorLogging'];

include_once("include/errorlog.php");
include_once("include/msgs.php");
	$msgs = & new messages();

include_once("include/menu.php");
include_once("include/auth.php");
include_once("include/blocks.php");
include_once("include/banner.php");
include_once("include/forums.php");
include_once("include/moderator.php");
	$mods = & new moderator();
include_once("include/survey.php");
include_once("include/stats.php");
include_once("include/categories.php");
include_once("include/polls.php");
include_once("include/rating.php");
include_once("include/invoice.php");
include_once("include/messaging.php");
include_once("include/date.php");
include_once("include/priorities.php");
include_once("include/mirrors.php");
include_once("include/profileskins.php");
include_once("include/plus.php");
include_once("include/textmanip.php");
include_once("include/smtp.php");
include_once("include/filesystem.php");
	$filesystem = & new filesystem($HTTP_HOST);


//	if($enableCompression)
//		ini_set('zlib.output_compression', 'On');
//		ob_start("ob_gzhandler");

	if(!isset($config['timezone']))
		$config['timezone'] = 6;
	putenv("TZ=GMT" . $config['timezone']);


	$parseTime = gettime();

	if(isset($login)){
		if($login>0)
			$userData=auth($userid,$key,true); //die if not logged in
		else
			$userData=auth($userid,$key,false);

		if($login == -1 && $userData['loggedIn'])
			die("This is only viewable by people who haven't logged in");
		if($login == 2 && !$userData['premium'])
			die("You must be a premium member to view this page");
	}

	if(!isset($action))
		$action="";

	if($userData['loggedIn'] && ($mods->isMod($userData['userid']) || $userData['premium'])){
		if(count($_POST)==0 && !isset($forceserver)){
			$webserver = chooseServer('www',true, $HTTP_HOST);

			if($webserver != "" && $webserver != $HTTP_HOST){
				header("location: http://" . $webserver . $REQUEST_URI);
				exit;
			}
		}

		$imgserver = chooseServer('image',true);

		if($mods->isMod($userData['userid']))
			$cache->prime("modsonline");
	}else{
		if(count($_POST)==0 && !isset($forceserver)){
			$webserver = chooseServer('www',false,$HTTP_HOST);

			if($webserver != "" && $webserver != $HTTP_HOST){
				header("location: http://" . $webserver . $REQUEST_URI);
				exit;
			}
		}
		$imgserver = chooseServer('image',false);
	}

	if($imgserver)
		$imgserver = "http://$imgserver";

	$config['bannerloc']		= $imgserver . $config['bannerdir'];
	$config['uploadfileloc']	= $imgserver . $config['basefiledir'];
	$config['gallerypicloc']	= $imgserver . $config['gallerypicdir'];
	$config['gallerythumbloc']	= $imgserver . $config['gallerythumbdir'];
	$config['imageloc']			= $imgserver . $config['imagedir'];
	$config['smilyloc']			= $imgserver . $config['smilydir'];
	$config['picloc']			= $imgserver . $config['picdir'];
	$config['thumbloc']			= $imgserver . $config['thumbdir'];
	$config['imgserver']		= $imgserver;

include_once("include/skin.php");

//echo "\n\n";


	if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)){
//		$fastdb->debug = true;
		$db->selectdb->debug = true;
		$db->insertdb->debug = true;
	}


/////////////////////////////////////////////////////
////// Site Specific Functions //////////////////////
/////////////////////////////////////////////////////

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
	global $db,$fastdb,$moddb;

	$time = 0;
	if(isset($db->insertdb))
		$time += $db->insertdb->time;
	if(isset($db->selectdb) && $db->selectdb !== $db->insertdb)
		$time += $db->selectdb->time;
	if(isset($fastdb))
		$time += $fastdb->time;

	echo "<br>Mysql time: " . number_format($time/10,4) . " milliseconds<br>";


	if(!isset($db->insertdb))
		$db->outputQueries("");

	if(isset($db->insertdb))
		$db->insertdb->outputQueries("Insert");
	if(isset($db->selectdb))
		$db->selectdb->outputQueries("Select");
	if(isset($fastdb))
		$fastdb->outputQueries("Fast");
/*	if(isset($moddb))
		$moddb->outputQueries("Mod");
*/
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

	$blocks = $cache->hdget("blocks", "getBlocks");

	if(count($blocks[$side]))
		foreach($blocks[$side] as $funcname)
			$funcname($side);
}

function editBox($text=""){
	global $config, $cache;

	$smilyusage = $cache->hdget("smilyuseage", 'getSmileyCodesByUsage');

	echo "<script>";
	echo "var smileypics = new Array('" . implode("','", $smilyusage) . "');";
	echo "var smileycodes = new Array('" . implode("','", array_keys($smilyusage)) . "');";
	echo "setSmileyLoc('$config[smilyloc]');";
	echo "document.write(editBox(\"" . htmlentities(str_replace("\r","",str_replace("\n","\\n",$text))) . "\",true));</script>";
	echo "<noscript><textarea cols=70 rows=10 name=msg></textarea></noscript>";
	return;
}

function addComment($id,$msg,$preview="changed",$params=array()){
	global $userData,$PHP_SELF,$db;

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



		echo "<form action=\"$PHP_SELF\" method=post enctype=\"application/x-www-form-urlencoded\" name=editbox>\n";
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

	if($db->numrows($result)>0){ //double post
		$db->query("UNLOCK TABLES");
		return false;
	}


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
	$base10 = ($parts[0]<<24)|($parts[1]<<16)|($parts[2]<<8)|($parts[3]);
	if($base10 >= pow(2,31))
		$base10 -= pow(2,32);
	return $base10;
}

function getip(){
	global $REMOTE_ADDR, $HTTP_X_FORWARDED_FOR;

	if(isset($HTTP_X_FORWARDED_FOR))
		return $HTTP_X_FORWARDED_FOR;
	return $REMOTE_ADDR;
}

function getQueryNum(){
	global $db;

	return $db->num_queries;
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
		$age = userdate("Y") - userdate("Y",$birthday) + ((userdate("z") -userdate("z",$birthday))/365);

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

function make_select_list_key( $list, $sel = "" ){
	$str = "";
	foreach($list as $k => $v){
		if( $sel == $k )
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
		$str .= "<input type=radio name=\"$name\" value=\"$v\"";
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

function makeCatSelect(&$branch,$category=0){
	$str="";

	foreach($branch as $cat){
		$str .= "<option value='$cat[id]'";
		if($cat['id']==$category)
			$str .= " selected";
		$str .= ">";
		$str .= str_repeat("- ", $cat['depth']) . $cat['name'];
	}
	return $str;
}

function makeCheckBox($name, $title, $class='body', $checked = false){
	return "<input type=checkbox id=\"$name\" name=\"$name\"" . ($checked ? ' checked' : '') . "><label for=\"$name\" class=$class>$title</label>";
}

/////////////////////////////////////////////////////
////// User Functions ///////////////////////////////
/////////////////////////////////////////////////////

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
	$db->prepare_query("SELECT username FROM users WHERE userid = ?", $uid);

	if($db->numrows()==0)
		return false;

	return $db->fetchfield();
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

	$word = trim($word);

	$orig = $word;
	$chars = array(' ','<','>','&','%','"',"'",'`','=','@',chr(127),chr(152),chr(158),chr(160),'/','\\');
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
	if($word != $orig){
		$msgs->addMsg("Illegal characters have been removed. Changed to '$word'.");
		return false;
	}

	if(is_numeric($word)){
		$msgs->addMsg("Names must have at least one non-numeric character");
		return false;
	}

	$db->prepare_query("SELECT userid FROM users WHERE LOWER(username)=LOWER(?)", $word);
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

function isIgnored($to, $from, $scope){
	global $db, $userData, $mods;

	if($mods->isAdmin($from))
		return false;

	$db->prepare_query("SELECT onlyfriends,ignorebyage,defaultminage,defaultmaxage FROM users WHERE userid = ?", $to);
	$line = $db->fetchrow();

	if(($line['onlyfriends'] == 'both' || $line['onlyfriends']  == $scope || $line['ignorebyage'] == 'both' || $line['ignorebyage'] == $scope) && !isFriend($from,$to)){
		if($line['onlyfriends'] == 'both' || $line['onlyfriends']  == $scope)
			return true;

		if(($line['ignorebyage'] == 'both' || $line['ignorebyage'] == $scope) && ($userData['age'] < $line['defaultminage'] || $userData['age'] > $line['defaultmaxage']) )
			return true;
	}

	$db->prepare_query("SELECT userid FROM `ignore` WHERE userid IN (?,0) && ignoreid = ?", $to, $from);

	return $db->numrows() > 0;
}

function isFriend($friendid,$userid=0){
	global $userData,$db;

	if($userid==0)
		$userid=$userData['userid'];

	if($friendid == $userid)
		return true;

	static $friends = array();

	if(!isset($friends[$friendid][$userid])){
		$db->prepare_query("SELECT id FROM friends WHERE userid = ? && friendid = ?", $userid, $friendid);
		$friends[$friendid][$userid] = $db->numrows();
	}

	return $friends[$friendid][$userid];
}

function isValidEmail($email){
	global $msgs;
	if(!eregi("^[a-z0-9]+([a-z0-9_.&-]+)*@([a-z0-9.-]+)+$", $email, $regs) ){
		$msgs->addMsg("Error: '$email' isn't a valid mail address");
		return false;
	}
	elseif(!checkdnsrr($regs[2],"MX")){
		$msgs->addMsg("Error: Can't find the host '$regs[2]'");
		return false;
	}

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

	if($db->numrows()==0)
		return false;
	return true;
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
	foreach($sortlist as $n => $v)
		if($v!=""){
			$sortt=$n;
			return false;
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
	global $PHP_SELF,$sortt,$sortd,$config;
	if($href=="")
		$href=$PHP_SELF;
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
		}elseif($arg_list[$i] === SORT_REGULAR || $arg_list[$i] === SORT_NUMERIC || $arg_list[$i] === SORT_STRING || $arg_list[$i] === SORT_CASESTR){
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
				case SORT_REGULAR:	$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ($a["'.$key.'"] < $b["'.$key.'"]) ? -1 : 1;}'; 	break;
				case SORT_NUMERIC:	$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ((float)$a["'.$key.'"] < (float)$b["'.$key.'"]) ? -1 : 1;}'; 	break;
				case SORT_STRING:	$func = 'return strcmp($a["'.$key.'"],$b["'.$key.'"]);'; 		break;
				case SORT_CASESTR:	$func = 'return strcasecmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
			}
		}else{
			switch($type){
				case SORT_REGULAR:	$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ($a["'.$key.'"] < $b["'.$key.'"]) ? 1 : -1;}'; 	break;
				case SORT_NUMERIC:	$func = 'if ($a["'.$key.'"] == $b["'.$key.'"]) {return 0;}else {return ((float)$a["'.$key.'"] < (float)$b["'.$key.'"]) ? 1 : -1;}'; 	break;
				case SORT_STRING:	$func = 'return 0 - strcmp($a["'.$key.'"],$b["'.$key.'"]);'; 		break;
				case SORT_CASESTR:	$func = 'return 0 - strcasecmp($a["'.$key.'"],$b["'.$key.'"]);'; 	break;
			}
		}
		$compare = create_function('$a,$b',$func);
		mergesort($rows,$compare);
	}
}

function mergesort(&$array, $cmp_function = 'strcmp') {
   // Arrays of size < 2 require no action.
   if (count($array) < 2) return;
   // Split the array in half
   $halfway = count($array) / 2;
   $array1 = array_slice($array, 0, $halfway);
   $array2 = array_slice($array, $halfway);
   // Recurse to sort the two halves
   mergesort($array1, $cmp_function);
   mergesort($array2, $cmp_function);
   // If all of $array1 is <= all of $array2, just append them.
   if (call_user_func($cmp_function, end($array1), $array2[0]) < 1) {
       $array = array_merge($array1, $array2);
       return;
   }
   // Merge the two sorted arrays into a single sorted array
   $array = array();
   $ptr1 = $ptr2 = 0;
   while ($ptr1 < count($array1) && $ptr2 < count($array2)) {
       if (call_user_func($cmp_function, $array1[$ptr1], $array2[$ptr2]) < 1) {
           $array[] = $array1[$ptr1++];
       }
       else {
           $array[] = $array2[$ptr2++];
       }
   }
   // Merge the remainder
   while ($ptr1 < count($array1)) $array[] = $array1[$ptr1++];
   while ($ptr2 < count($array2)) $array[] = $array2[$ptr2++];
   return;
}

////////////


//*
// $file relative to $docRoot

function masterPut($file){
	global $config, $docRoot;

//*
	global $masterserver;
	if(!file_exists($masterserver . $file))
		copy($masterserver . $file, $docRoot . $file);
/*/
//////

	$remote = fopen("http://$config[masterhttpserver]$file",'r');
	if($remote)		die("File already exists");

	$remote = fopen("ftp://$config[masterftpuser]:$config[masterftppass]@$config[masterftpserver]$file",'w');
	if(!$remote)	die("Failed");

	$local = fopen("$docRoot$file",'r');
	if(!$local)		die("Failed");

	while($buf = fread($local, 4096))
		fwrite($remote, $buf);

	fclose($remote);
	fclose($local);
//*/
}

function masterGet($file){
	global $config, $docRoot;

/*
	$remote = fopen("http://$config[masterhttpserver]$file",'r');
	if(!$remote)    die("Failed");

	$local = fopen("$docRoot$file",'w');
	if(!$local)     die("Failed");

	while($buf = fread($remote, 4096))
	        fwrite($local, $buf);

	fclose($remote);
	fclose($local);

/*/
///////

	global $masterserver;
	if(file_exists($masterserver . $file))
		copy($docRoot . $file, $masterserver . $file);

//*/
/*
//////
	$remote = fopen("ftp://$masteruser:$masterpass@$masterserver$file",'r');
	if(!$remote)    die("Failed");

	$local = fopen("$docRoot$file",'w');
	if(!$local)     die("Failed");

	while($buf = fread($remote, 4096))
	        fwrite($local, $buf);

	fclose($remote);
	fclose($local);
//*/
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
		$msgs->addMsg("Bad or currupt image.");
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

//	masterPut($thumbName);
	$filesystem->add($picName);
	$filesystem->add($thumbName);

	return true;
}

function addPic($userfile,$vote,$description, $signpic){
	global $userData,$msgs,$db,$config, $docRoot, $masterserver, $SERVER_NAME, $mods;

	if(!isset($userfile) || $userfile== "none")
		return false;

	if(!file_exists($userfile)){
		$msgs->addMsg("You must upload a file");
		return false;
	}

	$md5 = md5_file($userfile);

	$db->prepare_query("SELECT times FROM picbans WHERE md5 = ? && userid IN (0,?)", $md5, $userData['userid']);

	if($db->numrows()){
		$times = $db->fetchfield();

		if($times > 1){
			$msgs->addMsg("This picture has been banned");
			return false;
		}
	}

	$db->prepare_query("SELECT itemid FROM picspending WHERE md5 = ? && itemid = ?", $md5, $userData['userid']);

	if($db->numrows()){
		$msgs->addMsg("You already uploaded this picture");
		return false;
	}

	$query = $db->prepare("INSERT INTO picspending SET itemid = ?, vote = ?, description = ?, md5 = ?, signpic = ?", $userData['userid'], $vote, removeHTML($description), $md5, ($signpic ? 'y' : 'n'));
	$db->query($query);

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
		$query = $db->prepare("DELETE FROM picspending WHERE id = ?", $picID);
		$db->query($query);
	}
}

function removePic($id){
	global $masterserver,$msgs,$config,$db,$mirrors,$SERVER_NAME, $mods, $filesystem;

	$db->query("LOCK TABLES pics WRITE");

	$db->prepare_query("SELECT itemid FROM pics WHERE id = ?", $id);

	if($db->numrows()==0)
		return;

	$line = $db->fetchrow();

	setMaxPriority($id,"pics","itemid = '$line[itemid]'");

	$db->prepare_query("DELETE FROM pics WHERE id = ?", $id);

	$db->query("UNLOCK TABLES");

	$db->prepare_query("DELETE FROM abuse WHERE type = ? && itemid = ?", MOD_PICABUSE, $id);

	$mods->deleteItem(MOD_PICABUSE,$id);

	$picName = $config['picdir'] . floor($id/1000) . "/" . $id . ".jpg";
	$thumbName = $config['thumbdir'] . floor($id/1000) . "/" . $id . ".jpg";

	$filesystem->delete($picName);
	$filesystem->delete($thumbName);

	if(file_exists($docRoot . $picName))
			unlink($docRoot . $picName);
	if(file_exists($docRoot . $thumbName))
			unlink($docRoot . $thumbName);

	$msgs->addMsg("Picture Deleted");
}


function removePicPending($ids, $deletemoditem = true){
	global $masterserver,$msgs,$config,$db,$mirrors,$SERVER_NAME, $mods, $filesystem;

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
	global $db;

	$db->prepare_query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id WHERE users.userid IN (?)", $uids);
}




/////////////////////////////////////////////////////
////// Gallery Picture Functions ////////////////////
/////////////////////////////////////////////////////

function uploadGalleryPic($uploadFile,$picID){
	global $config, $docRoot, $masterserver, $msgs, $userData, $filesystem;

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
		$msgs->addMsg("Bad or currupt image.");
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

function addGalleryPic($userfile,$cat,$description){
	global $userData, $msgs, $db, $config, $masterserver, $docRoot, $mods;

	if(!isset($userfile) || $userfile== "none")
		return false;

	if(!file_exists($userfile)){
		$msgs->addMsg("You must upload a file");
		return false;
	}

	$priority = getMaxPriority("gallery",$db->prepare("userid = ? && category = ?", $userData['userid'], $cat));
	$db->prepare_query("INSERT INTO gallery SET userid = ?, category = ?, description = ?, priority = ?", $userData['userid'], $cat, $description, $priority);

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
	global $masterserver,$msgs,$config,$db,$mods,$filesystem;

	$db->query("LOCK TABLES gallery WRITE");

	$db->prepare_query("SELECT userid,category FROM gallery WHERE id = ?", $id);

	if($db->numrows()==0){
		$db->query("UNLOCK TABLES");
		return;
	}

	$line = $db->fetchrow();

	setMaxPriority($id,"gallery","userid = '$line[userid]' && category = '$line[category]'");

	$db->prepare_query("DELETE FROM gallery WHERE id = ?", $id);

	$db->query("UNLOCK TABLES");

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

