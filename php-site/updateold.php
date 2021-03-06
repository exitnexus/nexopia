<?

	$forceserver = true;
	$enableCompression = true;
	$login=1;
	$devutil = true;
//	$errorLogging = false;
	require_once("include/general.lib.php");

/*	if(!in_array($userData['userid'], $debuginfousers))
		die("error");
*/

	echo str_repeat(" ",400). "\n";
	set_time_limit(0);
//	ignore_user_abort(true);




	echo "\n<br>Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $startTime)/10000,4);
	echo "Run-time $dtime seconds<br>\n";


	ob_start();
	outputQueries();
//	echo "<pre>" . htmlentities(ob_get_clean()) . "</pre>";



exit;

///////////////////////////////////////////
///////////// Old Stuff //////////////////
/////////////////////////////////////////



//Find out how big the biggest galleries are.
	$users = array();
	$galleries = array();
	$pics = array();
	
	
	$res = $usersdb->query("SELECT count(*) as count FROM gallerypics GROUP BY userid ORDER BY count DESC LIMIT 5");
	while($line = $res->fetchrow())
		$users[] = $line['count'];
	
	$res = $usersdb->query("SELECT count(*) as count FROM gallerypics GROUP BY userid, galleryid ORDER BY count DESC LIMIT 5");
	while($line = $res->fetchrow())
		$pics[] = $line['count'];

	$res = $usersdb->query("SELECT count(*) as count FROM gallery GROUP BY ownerid ORDER BY count DESC LIMIT 5");
	while($line = $res->fetchrow())
		$galleries[] = $line['count'];
	
	sort($users);
	sort($galleries);
	sort($pics);
	
	echo "Users:<br>";
	echo implode(", ", $users);
	echo "<br><br>";
	echo "Gallery:<br>";
	echo implode(", ", $galleries);
	echo "<br><br>";
	echo "pics:<br>";
	echo implode(", ", $pics);

// clear out the questionable queue by re-voting for things that made it there that shouldn't have.
// really, only works if the person running this has level 6+

	$piccount = 100;
	$iter = 0;
	while(1){
		echo ++$iter . " ";

		$ids = $mods->getModItems(MOD_QUESTIONABLEPICS, $piccount, 3, 120, true);

		if(!$ids || $iter >= 100)
			break;

		$key = array('userid' => '#', 'picid' => '#');
		$res = $moddb->query("SELECT * FROM modvoteslog WHERE " . $moddb->prepare_multikey($key, $ids));

		$data = array();
		while($line = $res->fetchrow()){
			$id = "$line[userid]:$line[picid]";

			if(!isset($data[$id]))
				$data[$id] = array('total' => 0, 'accept' => 0, 'deny' => 0);

			if($line['vote'] == 'y'){
				$data[$id]['total'] += $line['points'];
				$data[$id]['accept']++;
			}else{
				$data[$id]['total'] -= $line['points'];
				$data[$id]['deny']++;
			}
		}

		$votes = array();

		foreach($data as $id => $val){
			if($val['total'] >= 6 && $val['deny'] == 0)
				$votes[$id] = 'y';
			elseif($val['total'] <= -6 && $val['accept'] == 0)
				$votes[$id] = 'n';
		}

		if($votes)
			$mods->vote($votes, MOD_QUESTIONABLEPICS);
		
		echo ": " . count($votes) . "<br>\n";
	}








// add deleted accounts entries, update stats in serverbalance

	$res = $masterdb->query("SELECT * FROM serverbalance WHERE weight > 0 && type = 6");
	$servers = $res->fetchrowset();
	$numservers = count($servers);


//set all to active (to be changed below)
	$masterdb->prepare_query("UPDATE accounts SET state = # WHERE state != #", ACCOUNT_STATE_ACTIVE, ACCOUNT_STATE_ACTIVE);


	$res = $db->query("SELECT userid FROM deletedusers ORDER BY userid");

	while($line = $res->fetchrow()){
		$id = $line['userid'];

	//insert if it doesn't exist yet
		$res2 = $masterdb->prepare_query("INSERT IGNORE INTO accounts SET id = #, type = 6, serverid = #, state = #", 
								$id, $servers[$id % $numservers]['serverid'], ACCOUNT_STATE_DELETED);

	//update if it already exists
		if($res2->affectedrows() == 0)
			$masterdb->prepare_query("UPDATE accounts SET state = # WHERE id = #", ACCOUNT_STATE_DELETED, $id);
	}


//update the counts in serverbalance
	$counts = array();

	$res = $masterdb->query("SELECT serverid, count(*) as count FROM accounts WHERE type = 6 GROUP BY serverid");
	while($line = $res->fetchrow())
		$counts[$line['serverid']]['totalaccounts'] = $line['count'];

	$res = $masterdb->prepare_query("SELECT serverid, count(*) as count FROM accounts WHERE type = 6 && state != # GROUP BY serverid", ACCOUNT_STATE_DELETED);
	while($line = $res->fetchrow())
		$counts[$line['serverid']]['realaccounts'] = $line['count'];

	foreach($counts as $serverid => $vals)
		$masterdb->prepare_query("UPDATE serverbalance SET totalaccounts = #, realaccounts = # WHERE serverid = #", $vals['totalaccounts'], $vals['realaccounts'], $serverid);


//update account status to include frozen and new
	$split = $usersdb->getSplitDBs();

	$new = array();
	$frozen = array();

	foreach($split as $mydb){
		$res = $mydb->unbuffered_query("SELECT userid, state FROM users WHERE state in ('new','frozen')");

		while($line = $res->fetchrow()){
			if($line['state'] == 'new'){
				$new[] = $line['userid'];

				if(count($new) >= 100){
					$masterdb->prepare_query("UPDATE accounts SET state = # WHERE id IN (#)", ACCOUNT_STATE_NEW, $new);
					$new = array();
				}
			}else{
				$frozen[] = $line['userid'];

				if(count($frozen) >= 100){
					$masterdb->prepare_query("UPDATE accounts SET state = # WHERE id IN (#)", ACCOUNT_STATE_FROZEN, $frozen);
					$frozen = array();
				}
			}
		}
	}

	if(count($new))
		$masterdb->prepare_query("UPDATE accounts SET state = # WHERE id IN (#)", ACCOUNT_STATE_NEW, $new);
	if(count($frozen))
		$masterdb->prepare_query("UPDATE accounts SET state = # WHERE id IN (#)", ACCOUNT_STATE_FROZEN, $frozen);




//make sure dbs are mapped correctly
	$dbs = $usersdb->getSplitDBs();


	foreach($dbs as $dbnum => $db){
		$res = $masterdb->prepare_query("SELECT id FROM accounts WHERE type = 6 && serverid = # LIMIT 100", $dbnum);
		
		while($line = $res->fetchrow())
			$ids[] = $line['id'];

		if($ids){
			$res = $db->prepare_query("SELECT count(*) FROM users WHERE userid IN (#)", $ids);
			$count = $res->fetchfield();
			
			if($count == 0)
				echo "None found on $dbnum<br>\n";
		}
	}
	
	echo "Done<br>\n";


//find other replicas of stray data

	$tables = array(
		'blog' => 'userid',
		'blogcomments' => 'bloguserid',
		'blogcommentsunread' => 'userid',
		'bloglastreadfriends' => 'userid',
		'friends' => 'userid',
		'friendscomments' => 'userid',
		'gallery' => 'ownerid',
		'gallerypending' => 'userid',
		'gallerypics' => 'userid',
		'ignore' => 'userid',
		'loginlog' => 'userid',
		'msgfolder' => 'userid',
		'msgs' => 'userid',
		'msgtext' => 'userid',
		'picbans' => 'userid',
		'pics' => 'userid',
		'picspending' => 'userid',
		'profile' => 'userid',
		'profileblocks' => 'userid',
		'profileviews' => 'userid',
		'sessions' => 'userid',
		'sourcepics' => 'userid',
		'useractivetime' => 'userid',
		'usercomments' => 'userid',
		'usercounter' => 'id',
		'userfiles' => 'userid',
		'userfileslayout' => 'userid',
		'userhitlog' => 'userid',
		'userinterests' => 'userid',
		'usernames' => 'userid',
		'userpasswords' => 'userid',
		'users' => 'userid',
		'usersearch' => 'userid',
		);


	$dbs = $usersdb->getSplitDBs();
	
	for($i = 29; $i <= 34; $i++){
		
		foreach($tables as $table => $pkey){
			$data = array();
		
			$res = $dbs[$i]->query("SELECT * FROM `$table`");
			while($line = $res->fetchrow())
				$data[$line[$pkey]][] = $line;


			if($data){
				$res = $usersdb->prepare_query("SELECT * FROM `$table` WHERE $pkey IN (%)", array_keys($data));
				while($line = $res->fetchrow()){
					$k = array_search($line, $data[$line[$pkey]]);
						
					if($k !== false)
						unset($data[$line[$pkey]][$k]);
				}
	
				foreach($data[$line[$pkey]] as $line)
					echo "Row in newusers$i:$table not found:<br>\n" . print_r($line) . "<br><br>\n";
			}
		}
	}

//script to move leftover portion of userfileslayout data after an incomplete move

	$dbobjs = $usersdb->getSplitDBs();

	foreach($dbobjs as &$dbobj){
		$res = $dbobj->query("SELECT userfileslayout.* FROM userfileslayout LEFT JOIN usernames USING (userid) WHERE usernames.userid IS NULL");
	
		while($line = $res->fetchrow()){
			$usersdb->prepare_query("INSERT IGNORE INTO userfileslayout SET userid = %, path = ?, type = ?, size = #", $line['userid'], $line['path'], $line['type'], $line['size']);
//			$dbobj->prepare_query("DELETE FROM userfileslayout WHERE userid = % && path = ?", $line['userid'], $line['path']);
		}
	}


//start of code to parse the new bannertypestats format


	$res = $bannerdb->prepare_query("SELECT * FROM bannertypestats LIMIT 10,1");
	
//	$data = array();
	while($line = $res->fetchrow()){
		$line['viewsdump'] = gzdecode($line['viewsdump']);
		$line['clicksdump'] = gzdecode($line['clicksdump']);
	
		$entry = new bannerstats();
		loadXML($entry, $line['viewsdump']);
	
		echo "<pre>";
		
//		print_r(simplexml_load_string($line['viewsdump']));
		print_r($entry);
		echo "</pre>";
		echo "<br><br><br><hr><br><br><br>";
	}


function loadXML($bannerstats, $xmlstring){
	$xml = simplexml_load_string($xmlstring);

	$bannerstats->total = (int)$xml->total[0];
	$bannerstats->starttime = (int)$xml->starttime[0];

	foreach($xml->agesex as $k => $v)
		if(is_int($k))
			$bannerstats->agesex[$k] = array(SEX_UNKNOWN => $v[SEX_UNKNOWN], 
			                                 SEX_MALE    => $v[SEX_MALE], 
			                                 SEX_FEMALE  => $v[SEX_FEMALE]);

	$locs = (array)$xml->loc;
	foreach($locs['int'] as $k => $v)
		if($v)
			$bannerstats->loc[$k] = $v;

	$interests = (array)$xml->interests;
	foreach($interests['int'] as $k => $v)
		if($v)
			$bannerstats->interests[$k] = $v;

	$hittimes = (array)$xml->hittimes;
	foreach($hittimes['int-array'] as $daynum => $day){
		$temp = (array)$day;

		foreach($temp['int'] as $hour => $hits)
			$bannerstats->hittimes[$daynum][$hour] = $hits;
	}

	$pages = (array)$xml->pages;
	foreach($pages['entry'] as $line){
		$temp = (array)$line;
		$temp2 = (array)$temp['integer'];
		$bannerstats->page[$temp['string']] = $temp2['i'];
	}
}

function XMLToArray($xml)
{
  if ($xml instanceof SimpleXMLElement) {
   $children = $xml->children();
   $return = null;
  }

  foreach ($children as $element => $value) {
   if ($value instanceof SimpleXMLElement) {
     $values = (array)$value->children();
    
     if (count($values) > 0) {
       $return[$element] = XMLToArray($value);
     } else {
       if (!isset($return[$element])) {
         $return[$element] = (string)$value;
       } else {
         if (!is_array($return[$element])) {
           $return[$element] = array($return[$element], (string)$value);
         } else {
           $return[$element][] = (string)$value;
         }
       }
     }
   }
  }
 
  if (is_array($return)) {
   return $return;
  } else {
   return $false;
  }
}



//start of a function to detect when users DOS the site
function detectDos($type, $id){ //either an ip or a userid
	global $cache, $db;

	$num = $cache->incr("dos-$type-$id");
	
	if(!$num){
		$cache->put("dos-$type-$id", 1, 60);
		return;
	}

	if($num % 30 == 0){ //update the db every 30th hit
		$res = $db->prepare_query("UPDATE detectdos SET hits = hits + 1, time = # WHERE type = ? && id = #", time(), $type, $id);
		if($res->affectedrows() == 0)
			$db->prepare_query("INSERT IGNORE detectdos SET hits = 1, time = #, type = ?, id = #", time(), $type, $id);
	}

	return $cache->get("blacklist-$type-$id");
}


//dump the usersearch tables to a file (useful to load for a search daemon)
	$fp = fopen("/home/nexopia/search.txt", 'w');
	
	$res = $usersdb->query("SELECT * FROM usersearch");
	
	$str = "";
	
	$line = $res->fetchrow();
	$str .= implode(",", array_keys($line)) . "\n";
	$str .= implode(",", $line) . "\n";
	
	$i = 0;
	
	while($line = $res->fetchrow()){
		$str .= implode(",", $line) . "\n";
		
		if(++$i >= 10000){
			fwrite($fp, $str);
			$str = "";
			$i = 0;
		}
	}
	fwrite($fp, $str);
	fclose($fp);
	
	

//memcache benchmark
	$key = "asdf";
	$val = str_repeat("blah", 1);
	$limit = 50000;
	
	$direct = new memcached(array ('servers' => array( array('localhost:11211', 1) ), 'debug' => false ) );
	$proxy  = new memcached(array ('servers' => array( array('localhost:11212', 1) ), 'debug' => false ) );


	$direct->set($key, $val, 100);

	$time1 = gettime();

	for($i = 0; $i < $limit; $i++)
		$direct->get($key);
	
	$time2 = gettime();

	for($i = 0; $i < $limit; $i++)
		$proxy->get($key);

	$time3 = gettime();

	
	echo "direct: " . number_format(($time2 - $time1)/10, 3) . " ms, " . number_format($limit*10000/($time2-$time1), 3) . " gets/s<br>";
	echo "proxy:  " . number_format(($time3 - $time2)/10, 3) . " ms, " . number_format($limit*10000/($time3-$time2), 3) . " gets/s<br>";

exit;


//a test to see which was the fastest way to access data

class test {
	public $attr;
	function __construct(){
		$this->attr = 1;
	}
}

$class = new test();
$array = array('attr' => 1);
$var = 1;

	$limit = 1000000;

	$time1 = gettime();

	for($i = 0; $i < $limit; $i++)
		$class->attr;
	
	$time2 = gettime();

	for($i = 0; $i < $limit; $i++)
		$array['attr'];

	$time3 = gettime();

	for($i = 0; $i < $limit; $i++)
		$var;

	$time4 = gettime();

	for($i = 0; $i < $limit; $i++)
		;

	$time5 = gettime();	

	echo "class: " . number_format(($time2 - $time1)/10, 3) . " ms, " . number_format($limit*10000/($time2-$time1), 3) . " gets/s<br>";
	echo "array: " . number_format(($time3 - $time2)/10, 3) . " ms, " . number_format($limit*10000/($time3-$time2), 3) . " gets/s<br>";
	echo "var:   " . number_format(($time4 - $time3)/10, 3) . " ms, " . number_format($limit*10000/($time4-$time3), 3) . " gets/s<br>";
	echo "loop:  " . number_format(($time5 - $time4)/10, 3) . " ms, " . number_format($limit*10000/($time5-$time4), 3) . " gets/s<br>";



exit;


//a css parser
	$alldata = array();

	foreach($skins as $skin => $skinname){
		$data = array();

		$file = file_get_contents($staticRoot . $config['skindir'] . "$skin/default.css");

		$rulenames = array();
		$ruledata = array();

		preg_match_all("/([-a-zA-Z0-9:.,> \t\n]*)\s*{([^\}]*)}/", $file, $matches, PREG_SET_ORDER);
		
		foreach($matches as $match){
			$rulenames = explode(',', $match[1]);
			$ruledata = explode(';', $match[2]);
			
			foreach($rulenames as $name){
				foreach($ruledata as $rule){
					if(trim($rule)){
						list($k, $v) = explode(':', $rule, 2);
						$data[strtolower(trim($name))][trim($k)] = trim($v);
					}
				}
			}
		}

//		if(!isset($data['a.forumlst:active']))	die($skin);

		$alldata[$skin] = $data;
/*
		echo "<pre>";
		print_r($data);
//		print_r($matches);
		echo "</pre>";
*/
	}

	echo "<pre>";
//	print_r($alldata);

	$rulecount = array();
	foreach($alldata as $skin => $data){
		foreach($data as $rulename => $ruledata){
			if(!isset($rulecount[$rulename]))
				$rulecount[$rulename] = 0;
			$rulecount[$rulename]++;
		}
	}
	
	ksort($rulecount);
	print_r($rulecount);	
	
	echo "</pre>";



//create a test table with lots of data
	$usersdb->query("CREATE TABLE IF NOT EXISTS testtable ( id int(10) unsigned NOT NULL default '0',  val char(32) NOT NULL default '') TYPE=MyISAM");
  	$usersdb->query("TRUNCATE TABLE testtable");
  
  
	for($i = 1; $i < 100000; $i++){
		if($i % 1000 == 0){
			echo "$i<br>";
			zipflush();
		}

		$query = $usersdb->prepare("INSERT INTO testtable SET id = #, val = ?", $i, md5($i));

		$usersdb->squery(5, $query);
		$usersdb->squery(207, $query);
	}

	exit;

//find all the global variables in the includes directory

	$directory = "/include/";

	$dir = opendir($docRoot . $directory);

	$files = array();
	while($item = readdir($dir))
		if(!is_dir($docRoot . $directory . $item) && strrchr($item, ".") == ".php")
			$files[] = $item;
	
	sort($files);
		
	foreach($files as $item){

		$file = file_get_contents($docRoot . $directory . $item);
		
		echo "<b>$directory$item</b><br>\n";
		
		$quote = false;
		$escape = false;
		$comment = false;
		$block = 0;
		$funchead = false;
		$line = 1;

		for($i = 0; $i < strlen($file); $i++){
			$chr = $file[$i];
			
			if($chr == "\n")
				$line++;
			
			if($comment){
				if($comment == 1 && $chr == "\n"){
					$comment = false;
				}elseif($chr == '*' && $file[$i+1] == '/'){
					$comment = false;
					$i++;
				}
			}elseif($quote){
				if(!$escape && $chr == '\\'){
					$escape = true;
				}else{
					if(!$escape && $chr == $quote) {
						$quote = false;
					}
					$escape = false;
				}
			}elseif($chr == '"' || $chr == "'"){
				$quote = $chr;
			}elseif($chr == '#'){ //ie # style comments
				$comment = 1;
			}elseif($chr == '/'){
				if($file[$i+1] == '/'){ //ie // style comments
					$comment = 1;
					$i++;
				}elseif($file[$i+1] == '*'){ //ie /* style comments
					$comment = 2;
					$i++;
				}
			}elseif($chr == '{'){
				$funchead = false;
				$block++;
			}elseif($chr == '}'){
				$block--;
			}elseif($chr == '$'){
				if($block == 0 && !$funchead)
					echo "$directory$item: $line<br>\n";
			}elseif($chr == 'f' && substr($file, $i, 8) == 'function'){
				$funchead = true;
			}
		}
	}





//compare ajax vs non-ajax requests

echo "<pre>\n";
foreach($_SERVER as $k => $v)
	if(strncmp($k, "HTTP", 4) == 0)
		echo "$k => $v\n";

//print_r($_SERVER);
echo "</pre>\n";


if(getREQval('ajax')){
	exit;
}



?>
<hr>
<div id=ajax>empty</div>


<script>

function putinnerHTML(div,str){
	if(document.all){
		document.all[div].innerHTML = str;
	}else{
		eval("document.getElementById('" + div + "').innerHTML = str;");
	}
}

function getHTTPObject() {
	var xmlhttp;
	/*@cc_on
	@if (@_jscript_version >= 5)
	try {
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
	try {
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (E) {
			xmlhttp = false;
		}
	}
	@else
	xmlhttp = false;
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
		try {
			xmlhttp = new XMLHttpRequest();
		} catch (e) {
			xmlhttp = false;
		}
	}
	return xmlhttp;
}

var http = getHTTPObject();
if(http){
	http.open("GET", "http://plus.www.timo.dev.nexopia.com/update.php?ajax=true", false);
	http.send(null);
	
	putinnerHTML('ajax', http.responseText);
}else{
	putinnerHTML('ajax', 'failed');
}

</script>
<?






//benchmark different output methods
	$b = "b";
	$count = 1000;

echo "<pre>";

	$time1 = gettime();
		
	for($i = 0; $i < $count; $i++)
		echo "a $b c\n";
	
	$time2 = gettime();
	
	for($i = 0; $i < $count; $i++)
		echo 'a ' . $b . ' c' . "\n";
	
	$time3 = gettime();
	
	for($i = 0; $i < $count; $i++){
		?>a <?= $b ?> c
<?	}
	$time4 = gettime();

	for($i = 0; $i < $count; $i++)
		echo 'a ' . $b . " c\n";

	$time5 = gettime();

	echo str_repeat('a ' . $b . " c\n", $count);

	$time6 = gettime();

	$str = "";
	for($i = 0; $i < $count; $i++)
		$str .= 'a ' . $b . " c\n";
	echo $str;

	$time7 = gettime();

	for($i = 0; $i < $count; $i++)
		print('a ' . $b . " c\n");

	$time8 = gettime();	
	
echo "</pre>";

	echo "\n\n<hr>";
	echo number_format(($time2-$time1)/10,3) . " ms<br>";
	echo number_format(($time3-$time2)/10,3) . " ms<br>";
	echo number_format(($time4-$time3)/10,3) . " ms<br>";
	echo number_format(($time5-$time4)/10,3) . " ms<br>";
	echo number_format(($time6-$time5)/10,3) . " ms<br>";
	echo number_format(($time7-$time6)/10,3) . " ms<br>";
	echo number_format(($time8-$time7)/10,3) . " ms<br>";
	



//update users skin choices

	$usersdb->query("ALTER TABLE `users` ADD `skintype` ENUM( 'frames', 'normal' ) NOT NULL AFTER `skin`");

	$pairs = array(	"refreshfr",	"refresh",
					"azureframes",	'azure',
					"orangeframes",	"orange",
					"solarframes",	"solar",
					"auroraframes",	"aurora",
					"carbonframes",	"carbon",
					"pinkframes",	"pink",
					"megaleetfr",	"megaleet",
					"rushhourfr",	"rushhour",
					"greenxframes",	"greenx",
					"flowerframes",	"flowers",
					"crushframes",	"crush",
					"halloweenfr",	"halloween",
					"winterfr",		"winter",
					"newyearsfr",	"newyears",
					);

	for($i = 0; $i < 40; $i += 2){
		$name = $pairs[$i+1];
		$frames = $pairs[$i];

		$usersdb->prepare_query("UPDATE users SET skin = ?, skintype = 'frames' WHERE skin = ?", $name, $frames);
		$usersdb->prepare_query("UPDATE users SET           skintype = 'normal' WHERE skin = ?", $name);
	}


////////////////////////////////////////////////

//read old skin configs and rewrite the frames/normal in one config, commented, and with all variables.

	$data = array();
	
	foreach($skins as $name => $stuff){
		$file = file_get_contents("skins/$name/skin.php");
		
		$file = substr($file, 2, strpos($file, 'include_once')-2 ) . "\n";
	
		$file = str_replace('$skindata', '$data[\'' . $name . '\']', $file);
		
		eval($file);
	}


	for($i = 0; $i < 40; $i += 2){
		$name = $pairs[$i+1];
		$fr = $data[$pairs[$i]];
		$no = $data[$pairs[$i+1]];
	
		$no['incCenter'] = ($no['incCenter'] ? 'true' : 'false');
		$no['menuends'] = (isset($no['menuends']) ? $no['menuends'] : '');
	
		$no['valignsideheader'] = (isset($no['valignsideheader']) ? $no['valignsideheader'] : 'bottom');
	
		$fr['menugutter'] = (isset($fr['menugutter']) ? $fr['menugutter'] : '#000000');
		$fr['menuguttersize'] = (isset($fr['menuguttersize']) ? $fr['menuguttersize'] : 0);
	
		$newfile = "<?";
$newfile .= <<<END


	\$skindata = array();

//general
	\$skindata['name']        = '$name'; //name of the skin, used for ruby layer translation
	\$skindata['skinWidth']   = "$no[skinWidth]"; //width of the skin, 100% for full width, otherwise in pixels
	\$skindata['cellspacing'] = $no[cellspacing];      //spacing between the center and blocks,
	\$skindata['incCenter']   = $no[incCenter];   //have a border around the center
	\$skindata['backgroundpic'] = "$no[backgroundpic]";   //background for the whole page, only useful if borders are specified below

//borders for the full page, width and colours
	\$skindata['topBorderSize']    = 0;
	\$skindata['topBorder']        = "";
	\$skindata['leftBorderSize']   = 0;
	\$skindata['leftBorder']       = "";
	\$skindata['rightBorderSize']  = 0;
	\$skindata['rightBorder']      = "";
	\$skindata['bottomBorderSize'] = 0;
	\$skindata['bottomBorder']     = "";

//floating logo for non-frames - floats right
	\$skindata['floatinglogo'] = "$no[floatinglogo]";             //image to float
	\$skindata['floatinglogovalign'] = "$no[floatinglogovalign]"; //valign top or bottom

//non-frames header
	\$skindata['headerpic'] = "$no[headerpic]"; //name of the header background (1600xVAR, assume only 750 width visible)
	\$skindata['headerheight'] = $no[headerheight];           //height of the header

//frames header
	\$skindata['headersmall'] = "$fr[headersmall]"; //header for 800x600  users (1600x60, assume only 300x60 visible)
	\$skindata['headerbig']   = "$fr[headerbig]";   //header for 1024x768 users (1600x90, assume only 300x90 visible)
	\$skindata['headerplus']  = "$fr[headerplus]"; //header for plus     users (1600x60, assume only 750x60 visible)

//menu
	\$skindata['menupic'] = "$no[menupic]"; //background for the menus, either a image or a colour (starting with #)
	\$skindata['menuheight'] = $no[menuheight];        //height of the menu (generally the pic from above)
	\$skindata['menudivider'] = "$no[menudivider]";    //separater between menu items
	\$skindata['menuspacer'] = "$no[menuspacer]"; //separater between menus, either a image or a colour (starting with #)
	\$skindata['menuspacersize'] = $no[menuspacersize];     //size of the spacer
	\$skindata['menugutter'] = "$fr[menugutter]"; //gutter below the top menu, either a image or a colour (starting with #)
	\$skindata['menuguttersize'] = $fr[menuguttersize];     //size of the gutter
	\$skindata['menuends'] = "$no[menuends]";          //menu ends, two images, prefixed with 'left' and 'right', with this suffix

//body
	\$skindata['mainbg'] = "$no[mainbg]"; //background image for the main body, if empty or a colour, use the one from the css

//blocks
	\$skindata['sideWidth'] = $no[sideWidth];                //width of the side blocks, in pixels
	\$skindata['blockBorder'] = $no[blockBorder];                //border size of the side blocks, colour in the css
	\$skindata['blockheadpic'] = "$no[blockheadpic]"; //block head, two images, prefixed with 'left' and 'right', with this suffix
	\$skindata['blockheadpicsize'] = $no[blockheadpicsize];          //size of the image
	\$skindata['valignsideheader'] = "$no[valignsideheader]";    //valign of the text in the block (bottom or center in general)


END;
	
		$fp = fopen("include/skins/$name.php", "w");
		fwrite($fp, $newfile);
		fclose($fp);
	}
	
////////////////////////////////////////////////


//database migration script

/*
5 - 1-225000
6 - 450001-675000
7 - 900001-1125000
8 - 1350001-1575000
*/

	$serverids = array( 5 => array(1, 225000),
						6 => array(450001, 675000),
						7 => array(900001, 1125000),
						8 => array(1350001, 1575000));

	$masterdb->query("INSERT IGNORE INTO serverbalance (serverid, weight, count) VALUES (5,1,0),(6,1,0),(7,1,0),(8,1,0)");

	foreach($serverids as $serverid => $uidrange){
		$res = $masterdb->prepare_query("UPDATE accounts SET serverid = # WHERE userid BETWEEN # AND #", $serverid, $uidrange[0], $uidrange[1]);

		$count = $res->affected_rows();
		$masterdb->prepare_query("UPDATE serverbalance SET count = # WHERE serverid = #", $count, $serverid);
		$masterdb->prepare_query("UPDATE serverbalance SET count = count - # WHERE serverid = #", $count, $serverid-4);
	}

/*
	$res = $masterdb->prepare_query("SELECT serverid, count(*) as count FROM accounts GROUP BY serverid");

	while($line = $res->fetchrow())
		$masterdb->query("UPDATE serverbalance SET count = # WHERE serverid = #", $line['count'], $line['serverid']);
*/


	$res = $masterdb->unbuffered_query("SELECT id, serverid FROM accounts");
	
	while($line = $res->fetchrow())
		$cache->put("serverid-user-$line[id]", $line['serverid'], 7*24*60*60);

/*
	foreach($serverids as $serverid => $uidrange){
		for($i = $uidrange[0]; $i <= $uidrange[1]; $i++){
			$cache->put("serverid-user-$i", $serverid, 7*24*60*60);
		}
	}
*/

	$cleantables = array(
		'friends'         => 'userid',
		'profileviews'    => 'userid',
		'sessions'        => 'userid',
		'useractivetime'  => 'userid',
		'usercounter'     => 'id',
		'userinterests'   => 'userid',
		'usernames'       => 'userid',
		'users'           => 'userid',
	);
/*
	$dirty = array(
		'blog' => 'userid',
		'blogcomments' => 'bloguserid',
		'blogcommentsunread' => 'userid',
		'bloglastreadfriends' => 'userid',
		'friendscomments' => 'userid',
		'gallery' => 'ownerid',
		'gallerypending' => 'userid',
		'gallerypics' => 'userid',
		'ignore' => 'userid',
		'loginlog' => 'userid',
		'picbans' => 'userid',
		'pics' => 'userid',
		'picspending' => 'userid',
		'sourcepics' => 'userid',

		'msgarchive' => 'userid',
		'msgfolder' => 'userid',
		'msgs' => 'userid',
		'msgtext' => 'userid',
		'profile' => 'userid',
		'profileblocks' => 'userid',
		'usercomments' => 'userid',
		'usercommentsarchive' => 'userid',

		'userhitlog' => 'userid',
		'userpasswords' => 'userid',
//		'usersearch' => 'userid', //truncated
		);
*/

	$dblist = $usersdb->getSplitDBs();

	foreach($cleantables as $table => $col){
		foreach($serverids as $serverid => $uidrange){
			$dblist[$serverid - 4]->query("DELETE FROM $table WHERE $col <= $uidrange[1]");
			$dblist[$serverid    ]->query("DELETE FROM $table WHERE $col >  $uidrange[1]");
		}
	}

	for($i = 5; $i <= 8; $i++)
		$dblist[$i]->prepare_query("UPDATE stats SET hitsFemale = 0, hitsMale = 0, hitsuser = 0, hitsplus = 0, hitstotal = 0, userstotal = 0");

	rebuildStats();
	updateSpotlightList();
	updateUserIndexes();




//big analysis of plus stats
		$stats = array( 
			'userscounted' => 0, //total number of people to analyze
			'oddcases' => 0, //people who got random amounts given in some way, transfered, etc. These aren't analyzed further
	
			'curone' => 0,    //people who bought 1 month last time
			'curtwo' => 0,    //people who bought 2 months last time
			'curthree' => 0,  //people who bought 3 months last time
			'cursix' => 0,    //people who bought 6 months last time
			'curyear' => 0,   //people who bought a year last time
			'curnone' => 0,   //people who have run out
	
			'curhaveinactive' => 0, //people who have it, but are inactive
			'curnoneinactive' => 0, //people who had it, but don't anymore and are inactive
	
			'startone' => 0,  //people who bought 1 month first time
			'starttwo' => 0,  //people who bought 2 months first time
			'startthree' => 0,//people who bought 3 months first time
			'startsix' => 0,  //people who bought 6 months first time
			'startyear' => 0, //people who bought a year first time
	
			'totalone' => 0,  //number of single months bought
			'totaltwo' => 0,  //number of two months bought
			'totalthree' => 0,//number of three months bought
			'totalsix' => 0,  //number of six months bought
			'totalyear' => 0, //number of years bought
	
			'buyonce' => 0,   //people who have bought once and still have it
			'tryonce' => 0,   //people who bought, let it expire, and still don't have it
			'repeat' => 0,    //people who bought more than once
			'repeatexp' => 0, //people who bought, let it expire, and bought again.
			'repeatexpweek' => 0, //people who bought, let it expire for more than a week, and bought again.
			'repeatexpmonth' => 0, //people who bought, let it expire for more than a month, and bought again.
	
			'manyonce' => 0,  //buy one month at a time, more than once
			'trymore' => 0,   //buy one month, maybe more than once, then buy more than a month at once
			);
	
		$curtime = time();
	
		$res = $db->prepare_query("SELECT userid, time, duration FROM pluslog ORDER BY userid, time");
	
		$users = array();
		$user = array();
		$curuserid = 0;
		
		while($line = $res->fetchrow()){
			if($curuserid && $curuserid != $line['userid']){
				$users[$curuserid] = $user;
			
				if(count($users) == 1000){
					analyzegroup($users);
					$users = array();
				}
	
				$user = array();
			}
			
			$curuserid = $line['userid'];
			$user[] = $line;
		}
		$users[$curuserid] = $user;
		analyzegroup($users);
	
		echo "<pre>" . print_r($stats, true) . "</pre>";
	
	function analyzegroup($users){
		global $usersdb;
	
		$res = $usersdb->prepare_query("SELECT userid, activetime FROM useractivetime WHERE userid IN (%)", array_keys($users));
		
		$activetimes = array();
		while($line = $res->fetchrow())
			$activetimes[$line['userid']] = $line['activetime'];
	
		foreach($users as $user)
			analyze($user, (isset($activetimes[$user[0]['userid']]) ? $activetimes[$user[0]['userid']] : 0));
	}
		
	function analyze($log, $lastactive){
		global $stats, $curtime;
	
		$stats['userscounted']++;
	
		$weird = false;
	
		$expiry = 0;
		$lapsed = false;
		$lapsedweek = false;
		$lapsedmonth = false;
	
		foreach($log as $line){
			if($expiry && $expiry < $line['time'])
				$lapsed = true;
			if($expiry && $expiry+86400*7 < $line['time'])
				$lapsedweek = true;
			if($expiry && $expiry+86400*31 < $line['time'])
				$lapsedmonth = true;
			if($expiry < $line['time'])
				$expiry = $line['time'];
			$expiry += $line['duration'];
	
			switch($line['duration']){
				case 86400*31:    //one month
				case 86400*31*2:  //two months
				case 86400*31*3:  //three months
				case 86400*31*6:  //six months
				case 86400*31*12: //year
					break;
				default: //weird
					$weird = true; 
					break;
			}
		}
	
	//only process logs that are simple
		if($weird){
			$stats['oddcases']++;
			return;
		}
	
	//count number of buys
		foreach($log as $line){
			switch($line['duration']){
				case 86400*31:    $stats['totalone']++;   break; //one month
				case 86400*31*2:  $stats['totaltwo']++;   break; //two months
				case 86400*31*3:  $stats['totalthree']++; break; //three months
				case 86400*31*6:  $stats['totalsix']++;   break; //six months
				case 86400*31*12: $stats['totalyear']++;  break; //year
			}
		}
	
	
	//check first
		$first = reset($log);
		switch($first['duration']){
			case 86400*31:    $stats['startone']++;   break; //one month
			case 86400*31*2:  $stats['starttwo']++;   break; //two months
			case 86400*31*3:  $stats['startthree']++; break; //three months
			case 86400*31*6:  $stats['startsix']++;   break; //six months
			case 86400*31*12: $stats['startyear']++;  break; //year
		}
	
	//did a trial period?
		if($first['duration'] == 86400*31 && count($log) > 1){
			$more = false;
			foreach($log as $line)
				if($line['duration'] > 86400*31)
					$more = true;
	
			if($more)
				$stats['trymore']++;
			else
				$stats['manyonce']++;
		}
	
	//check current plus
		$last = end($log);
		if($expiry > $curtime){ //hasn't expired
			switch($last['duration']){
				case 86400*31:    $stats['curone']++;   break; //one month
				case 86400*31*2:  $stats['curtwo']++;   break; //two months
				case 86400*31*3:  $stats['curthree']++; break; //three months
				case 86400*31*6:  $stats['cursix']++;   break; //six months
				case 86400*31*12: $stats['curyear']++;  break; //year
			}
			if(!$lastactive || $lastactive < $curtime - 86400*7)
				$stats['curhaveinactive']++;
		}else{
			$stats['curnone']++;
			if(!$lastactive || $lastactive < $curtime - 86400*7)
				$stats['curnoneinactive']++;
		}
	
	//bought multiple?
		if(count($log) == 1){ //bought once
			if($expiry > $curtime) //hasn't expired yet
				$stats['buyonce']++;
			else // let it expire
				$stats['tryonce']++;
		}else{ //bought multiple times
			$stats['repeat']++;
			if($lapsed)
				$stats['repeatexp']++;
			if($lapsedweek)
				$stats['repeatexpweek']++;
			if($lapsedmonth)
				$stats['repeatexpmonth']++;
		}
	}
//end plus stats

//remove pending pics that have been modded, re-add moditems for the remaining pending items
	$usersdb->query("DELETE picspending FROM picspending, pics WHERE picspending.userid = pics.userid AND picspending.id = pics.id");

	$res = $usersdb->unbuffered_query($usersdb->prepare("SELECT * FROM picspending"));
	while ($res)
	{
		$ids = array();
		for ($i = 0; $i < 100; $i++)
		{
			$row = $res->fetchrow();
			if (!$row)
			{
				$res = false;
				break;
			}
			if (!isset($ids[ $row['userid'] ]))
				$ids[ $row['userid'] ] = array();
			$ids[ $row['userid'] ][] = $row['id'];
		}
		if ($ids)
			$mods->newSplitItem(MOD_PICS, $ids, true);
	}


//dump config table into a php array, with comments
	$res = $db->query("SELECT * FROM config ORDER BY name");

	$str = "\$config = array(\n";

	while($line = $res->fetchrow()){
		$str .= "\t'$line[name]' => '" . addslashes($line['value']) . "',";
		if($line['comments'])
			$str .= " //$line[comments]";
		$str .= "\n";
	}
	$str .= "\t);";

	echo "<pre>";
	echo $str;
	echo "</pre>";



//delete orphaned forumposts
	DELETE forumposts FROM forumposts LEFT JOIN forumthreads ON forumthreads.id = forumposts.threadid WHERE forumposts.threadid IS NULL


//dump userid,id of all pics
	$fh = fopen("/home/nexopia/pics.txt", "w+");

	$res = $usersdb->prepare_query("SELECT userid, id FROM pics");
	
	$buffer = "";
	
	$i = 0;
	
	while($line = $res->fetchrow()){
		$buffer .= implode(',', $line) . "\n";
		
		if(++$i % 1000 == 0){
			fwrite($fh, $buffer);
			$buffer = "";
			
			echo "$i ";
			zipflush();
		}
	}

	fwrite($fh, $buffer);
	fclose($fh);


//delete all comments from a set of users
	$idiots = array();
	//1647403, 1920846, 1025426, 1921044, 1920807, 1920368);

	$masterdb->debuglevel = 0;
	$usersdb->debuglevel = 0;
	$cache->debug = false;

	$i = 0;

	$buffer = array();
	$uid = 0;

	$userdbs = $usersdb->getSplitDBs();

	foreach($userdbs as $serverid => & $deldb){
		$res = $deldb->prepare_query("DELETE usercomments FROM usercommentsarchive, usercomments WHERE usercommentsarchive.authorid IN (#) && (usercommentsarchive.userid = usercomments.userid && usercommentsarchive.id = usercomments.id)", $idiots);
		echo "<br>Deleted: " . $res->affectedrows() . " Rows from database $serverid<br>";


		$res = $deldb->prepare_query("SELECT userid, id FROM usercommentsarchive WHERE authorid IN (#) ORDER BY userid", $idiots);

		echo "<br>Deleting: " . $res->numrows() . " Rows from cache<br>";

		while($line = $res->fetchrow()){
			if(++$i % 100 == 0){
				if($i % 1000 == 0)
					echo "<br>";
				echo "$i ";
				zipflush();
			}

			$memcache->delete("comments5-$line[userid]");
		}
	}

	echo "<br>Done: $i";
	zipflush();

//generate a whole bunch of batches for printing, output them all in csv format
	$batches = array(
						array( 'numbatches' => 150, 'value' => 5, 'batchsize' => 30),
						array( 'numbatches' => 60, 'value' => 20, 'batchsize' => 10),
						array( 'numbatches' => 60, 'value' => 30, 'batchsize' => 5),
					);

	$batcheids = array();

	foreach($batches as $batch){
		for($i = 0; $i < $batch['numbatches']; $i++){
			$batchid = $payg->generateBatch($batch['batchsize'], $batch['value']);

			$batcheids[] = $batchid;
		}
	}

	$payg->db->prepare_query("SELECT id, value, batchid, secret FROM paygcards WHERE batchid IN (#) ORDER BY batchid, id", $batcheids);

	$cards = $payg->db->fetchrowset();

	$output = "";

	foreach($cards as $card)
		$output .= "$card[value],$card[batchid]-$card[id],$card[secret]\r\n";

	echo "<pre>$output</pre>";



//track the number of messages that were sent in a mass message
	$msgsdb->query("SELECT userid FROM msgs WHERE date > 1138218000 && fromname = 'BirthdayTour' && subject = 'Nexopia Birthday Tour - This Weekend: Edmonton'");

	echo "num: " . $msgsdb->numrows() . "<br>\n";

	while($line = $msgsdb->fetchrow())
		echo "$line[userid]<br>\n";


//random queries used to update the plus and the forums
	$shopdb->query("UPDATE products SET callback = 'plusCallback' WHERE id = 1");

	$db->query("INSERT INTO pluslog (id, userid, time, duration) SELECT id, userid, time, duration FROM premiumlog ORDER BY id");



	$forumdb->prepare_query("SELECT id, categoryid FROM forums WHERE official = 'y'");

	while($line = $forumdb->fetchrow())
		echo "UPDATE forums SET categoryid = $line[categoryid] WHERE id = $line[id] && official = 'y';<br>\n";


// SELECT id, 5 AS ownerid, 1 AS priority, 'n' AS official, name FROM forums WHERE categoryid = 0 AND official = 'y';

	// move official forums with parent = 0 to category table
	$forums->db->query("INSERT INTO forumcats SELECT id, ownerid, priority, 'n' AS official, name FROM forums WHERE parent = 0 AND official = 'y'");
	$forums->db->query("UPDATE forumcats SET official = 'y' WHERE id = 31");
	$forums->db->query("DELETE FROM forums WHERE parent = 0 AND official = 'y'");
	// alter parentid INT to categoryid TINYINT, remove priority column from forums
	$forums->db->query("ALTER TABLE forums CHANGE parent categoryid INT(10) UNSIGNED NOT NULL DEFAULT '0', DROP COLUMN priority");
	// add unofficial column to forums table, set inverted to official
	$forums->db->query("ALTER TABLE forums ADD COLUMN unofficial ENUM('y', 'n') NOT NULL DEFAULT 'y'");
	$forums->db->query("UPDATE forums SET unofficial = 'y' WHERE official = 'n'");
	$forums->db->query("UPDATE forums SET unofficial = 'n' WHERE official = 'y'");
	// add by* keys to forums table
	$forums->db->query("ALTER TABLE forums ADD INDEX bytime (public,categoryid,unofficial,time,id), ADD INDEX byname (public,categoryid,official,name,id), ADD INDEX byposts (public,categoryid,unofficial,posts,id)");
	// add enum options for editablility based on time.
	$forums->db->query("ALTER TABLE `forums` CHANGE `edit` `edit` ENUM( 'n', 'y', '5', '15', '60' ) NOT NULL DEFAULT 'n'");
	// add categoryid to the foruminvite table
	$forums->db->query("ALTER TABLE foruminvite ADD COLUMN categoryid INT(10) UNSIGNED NOT NULL DEFAULT '0'");

	$db->query("ALTER TABLE `users` ADD `orderforumsby` ENUM( 'mostactive', 'mostrecent', 'alphabetic' ) DEFAULT 'mostactive' NOT NULL AFTER `onlysubscribedforums`");





//benchmark the different memcache libs at get and get_multi

include_once("include/peclmemcached-client.php");
	$peclmemcache = new peclmemcached($memcacheoptions);

	$pecl = new Memcache;
	$pecl->connect("10.0.2.5", "11211");


# $cache - wrapper around $memcache/$peclmemcache
# $memcache - php memcache client
# $peclmemcache - php wrapper around $pecl
# $pecl - pecl object



	$num = 1000;
	$n = 10;
	$key = "asdf";
	$vallength = 100;

	$val = str_repeat("1", $vallength);

	$cache->put(array(1, $key), $val, 60);

	$cache->get(array(1, $key));
	$memcache->get(array(1, $key));
	$peclmemcache->get(array(1, $key));
	$pecl->get($key);

	$time1 = gettime();

	for($i = 0; $i < $num; $i++)	$cache->get($key);//array(1, $key));

	$time2 = gettime();

	for($i = 0; $i < $num; $i++)	$memcache->get($key);//array(1, $key));

	$time3 = gettime();

	for($i = 0; $i < $num; $i++)	$peclmemcache->get($key);//array(1, $key));

	$time4 = gettime();

	for($i = 0; $i < $num; $i++)	$pecl->get($key);

	$time5 = gettime();

	echo "<br>single:<br>";
	echo "cache: " . number_format(($time2 - $time1)/10, 3) . "<br>";
	echo "memcache: " . number_format(($time3 - $time2)/10, 3) . "<br>";
	echo "peclcache: " . number_format(($time4 - $time3)/10, 3) . "<br>";
	echo "pecl: " . number_format(($time5 - $time4)/10, 3) . "<br>";


	$keys = array();
	$keys2 = array();

	$val = str_repeat("1", $vallength/$n);

	for($i = 0; $i < $n; $i++){
		$k = "$key-$i";
		$cache->put(array(1, $k), $val, 60);
		$keys[] = array(1, $k);
		$keys2[] = $k;
	}

//$num /= $n;

	$time1 = gettime();

	for($i = 0; $i < $num; $i++)	$cache->get_multi($keys2);

	$time2 = gettime();

	for($i = 0; $i < $num; $i++)	$memcache->get_multi($keys2);

	$time3 = gettime();

	for($i = 0; $i < $num; $i++)	$peclmemcache->get_multi($keys2);

	$time4 = gettime();

	for($i = 0; $i < $num; $i++)	$pecl->get($keys2);

	$time5 = gettime();
	echo "<br>multi:<br>";
	echo "cache: " . number_format(($time2 - $time1)/10, 3) . "<br>";
	echo "memcache: " . number_format(($time3 - $time2)/10, 3) . "<br>";
	echo "peclcache: " . number_format(($time4 - $time3)/10, 3) . "<br>";
	echo "pecl: " . number_format(($time5 - $time4)/10, 3) . "<br>";



//convert from single db to hash balanced db
	$tables = array("iplog" => "ip", "loginlog" => "userid", "userhitlog" => "userid");

	foreach($tables as $table => $keycol){

		$result = $logdb->query("SHOW CREATE TABLE `$table`");
		$create = $logdb->fetchfield(1,0,$result);

		$newlogdb->query(false, $create);

		$logdb->unbuffered_query("SELECT * FROM $table");

		while($line = $logdb->fetchrow()){
			$query = "INSERT INTO $table SET ";

			$parts = array();
			foreach($line as $k => $v)
				$parts[] = $newlogdb->prepare("$k = ?", $v);

			$query .= implode(", ", $parts);

			$newlogdb->query($line[$keycol], $query);
		}
	}

//get table list
	$tables = array();

	$names = array_keys($dbs);

	foreach($names as $dbname){
		$tableresult = $dbs[$dbname]->listtables();

		while(list($tname) = $dbs[$dbname]->fetchrow($tableresult, DB_NUM)){
			$result = $dbs[$dbname]->query("SHOW CREATE TABLE `$tname`");
			$output = $dbs[$dbname]->fetchfield(1,0,$result);

			$tables["$dbname.$tname"] = "-- $dbname.$tname\n$output";
		}
	}

	ksort($tables);

	echo "<pre>";
	echo implode("\n\n--------------------------------------------------------\n\n", $tables);
	echo "</pre>";


//convert tables to MyISAM
	foreach($dbs as $dbname => $optdb){
		$tableresult = $dbs[$dbname]->listtables();

		while(list($tname) = $dbs[$dbname]->fetchrow($tableresult, DB_NUM)){

			echo "$dbname.$tname";

			$result = $dbs[$dbname]->query("SHOW CREATE TABLE `$tname`");
			$output = $dbs[$dbname]->fetchfield(1,0,$result);

			if(strpos($output, "InnoDB") !== 0){
				echo " ... converting ... "; zipflush();

				$dbs[$dbname]->query("ALTER TABLE `$tname` TYPE = MyISAM");

				echo "Done";
			}

			echo "<br>\n"; zipflush();
		}
	}


//convert from old to new message database format

	//get my messages
	INSERT INTO msgsold.msgs SELECT * FROM nexopia3.msgs WHERE nexopia3.msgs.userid = 1;
	//get message headers
	INSERT INTO msgsold.msgheader SELECT nexopia3.msgheader.* FROM nexopia3.msgs, nexopia3.msgheader WHERE nexopia3.msgs.msgheaderid = nexopia3.msgheader.id && nexopia3.msgs.userid = 1;
	//get other side of my messages
	INSERT INTO msgsold.msgs
		SELECT
				othermsgs.*
			FROM
				nexopia3.msgs,
				nexopia3.msgheader,
				nexopia3.msgs as othermsgs
			WHERE
				nexopia3.msgs.userid = 1 &&
				nexopia3.msgs.msgheaderid = nexopia3.msgheader.id &&
				othermsgs.msgheaderid = nexopia3.msgheader.id &&
				othermsgs.userid != 1;



	UPDATE msgsold.msgheader SET id1 = 0, id2 = 0, reply1 = 0, reply2 = 0;
	TRUNCATE TABLE msgsnew.msgs;

	ALTER TABLE `msgsold.msgheader`
		ADD `id1` INT UNSIGNED NOT NULL ,
		ADD `id2` INT UNSIGNED NOT NULL ,
		ADD `reply1` INT UNSIGNED NOT NULL ,
		ADD `reply2` INT UNSIGNED NOT NULL ;


	ALTER TABLE `msgtext`
		ADD `date` INT NOT NULL AFTER `id` ,
		ADD `compressed` ENUM( 'n', 'y' ) NOT NULL AFTER `date` ,
		ADD `html` ENUM( 'n', 'y' ) NOT NULL AFTER `compressed` ,
		ADD INDEX ( `date` ) ,
		MAX_ROWS=4294967295 AVG_ROW_LENGTH=50;



	UPDATE msgsold.msgheader, msgsold.msgs
		SET msgsold.msgheader.id1 = msgsold.msgs.id
		WHERE msgsold.msgs.msgheaderid = msgsold.msgheader.id && userid = `to`;

	UPDATE msgsold.msgheader, msgsold.msgs
		SET msgsold.msgheader.id2 = msgsold.msgs.id
		WHERE msgsold.msgs.msgheaderid = msgsold.msgheader.id && userid = `from`;




	UPDATE	msgsold.msgheader as msgheader,
			msgsold.msgheader as replyheader,
			msgsold.msgs as msgs
		SET msgheader.reply1 = msgs.id
		WHERE
			msgheader.replyto = replyheader.id &&
			replyheader.id = msgs.msgheaderid &&
			replyheader.`to` = msgs.userid;

	UPDATE	msgsold.msgheader as msgheader,
			msgsold.msgheader as replyheader,
			msgsold.msgs as msgs
		SET msgheader.reply2 = msgs.id
		WHERE
			msgheader.replyto = replyheader.id &&
			replyheader.id = msgs.msgheaderid &&
			replyheader.`from` = msgs.userid;





	INSERT INTO msgsnew.msgs (id, userid, folder, otheruserid , `to`, toname, `from`, fromname, date, subject, msgtextid, status, othermsgid, replyto)
		SELECT msgsold.msgs.id, userid, folder, other, `to`, toname, `from`, fromname, date, subject, msgtextid,
			IF(replied = 'y', 'replied', IF(new = 'y', 'new', 'read')),
			IF(userid = `to`, id2, id1),
			IF(userid = `to`, reply2, reply1)
		FROM msgsold.msgs, msgsold.msgheader
		WHERE msgsold.msgs.msgheaderid = msgsold.msgheader.id;


	$db->prepare_query("UPDATE msgtext SET date = #, html = 'n', compressed = 'n'", time());




//get table list
	$tables = array();

	foreach($dbs as $dbname => $optdb){
		$tableresult = $dbs[$dbname]->listtables();

		while(list($tname) = $dbs[$dbname]->fetchrow($tableresult, DB_NUM)){

			echo "$dbname.$tname<br>";

			$result = $dbs[$dbname]->query("SHOW CREATE TABLE `$tname`");
			$output = $dbs[$dbname]->fetchfield(1,0,$result);

			$tables[$tname] = "-- $dbname.$tname\n$output";
		}
	}

	ksort($tables);

	echo "<pre>";
	echo implode("\n\n------------------------\n\n", $tables);
	echo "</pre>";



//fix poll moditems

	$mods->db->prepare_query("DELETE FROM moditems WHERE type = #", MOD_POLL);

	$polls->db->prepare_query("SELECT id FROM polls WHERE official = 'y' && moded = 'n'");

	$valid = array();
	while($line = $polls->db->fetchrow())
	$valid[] = $line['id'];

	if(count($valid))
		$mods->newItem(MOD_POLL,$valid);


//setup the interests table
$interestlist = "Animals/Pets
-Birds
-Cats
-Dogs
-Farm Animals
-Fish
-Horses
-Rabbits
-Reptiles
-Rodents

Art
-Acting
-Astrology
-Body Art
-Cartooning
-Cross-stitching
-DJing
-Doodling
-Drawing
-Clothing design
-Film Making
-Graphic Design
-Journal Writing
-Knitting
-Painting
-Photography
-Pottery
-Sculpture
-Sewing
-Singing
-Song Writing
-Theatre Directing
-Visiting Museums
-Web Design
-Writing

Activities
-Clubbing
-Cooking
-Current Affairs
-Drinking
-Driving
-Gambling
-Karaoke
-Listening to music
-Partying
-Poker
-Pool/Billiards
-Reading
-Shopping
-Traveling
-Volunteering

Cars
-Audio
-Car Clubs
-Domestic
-Drag Racing
-Drifting
-Formula 1
-Imports
-Modifications
-Nascar
-Offroad
-Rally
-Tuning

Computers
-Apple
-Chatrooms/IRC
-E-mail
-Gaming
-Graphics
-Hardware
-Instant Messaging
-Linux/BSD
-Programming
-Surfing the net

Outdoor
-Bird-watching
-Camping
-Fishing
-Gardening
-Going to the beach
-Hunting
-Hiking
-Backpacking
-Paddling
-Exploring
-Orienteering
-Sightseeing
-Suntanning
-Travelling

Sports
-Aerobics
-Badminton
-Baseball
-Basketball
-Bicycling
-BMX
-Body Building
-Bowling
-Boxing
-Car racing
-Cheerleading
-Cricket
-Curling
-Dance (competative)
-Figure Skating
-Fishing
-Football (American)
-Golf
-Gymnastics
-Hiking
-Hockey
-Horseback Riding
-Ice-skating
-Inline Skating
-Jogging
-Kickboxing
-Lacrosse
-Martial Arts
-Mountain Biking
-Paintball
-Pilates
-Rock Climbing
-Rollerskating
-Rowing
-Rugby
-Running
-Sailing
-Scuba
-Skateboarding
-Skiing
-Sky Diving
-Snorkeling
-Snowboarding
-Soccer
-Softball
-Surfing
-Swimming
-Tennis
-Track and Field
-Ultimate Frisbee
-Volleyball
-Water-skiing
-Weight lifting
-Windsurfing
-Wrestling
-Yoga

Movies
-Action
-Animated
-Anime
-Classic
-Comedy
-Documentaries
-Drama
-Foreign
-Historical dramas
-Horror
-Independent
-Musicals
-Psychological Thrillers
-Romantic Comedies
-Science Fiction
-Spy/Political Thrillers
-Tearjerkers
-Teen
-Westerns

Music
-Alternative
-Blues
-Brit Pop
-Classic Rock
-Classical
-Country
-Death Metal
-Drum & Bass
-Electronica
-Emo
-Folk
-Funk
-Garage
-Gospel
-Goth
-Happy Hardcore
-Hardcore
-Hip-Hop
-House
-Indie
-Industrial
-Jazz
-Lounge
-Metal
-New Wave
-Pop
-Progressive
-Punk
-R & B
-Rap
-Rapcore
-Reggae
-Rock
-Ska
-Soul
-Techno
-Trance
-World

Musical Instruments
-Acoustic guitar
-Bagpipes
-Bass guitar
-Bassoon
-Cello
-Clarinet
-Chimes
-Cornet
-Double Bass
-Electric Guitar
-Fiddle
-Flute
-French horn
-Harp
-Harmonica
-Keyboard
-Kit Drums
-Oboe
-Organ
-Other Drums
-Pan pipes
-Piano
-Recorder
-Saxophone
-Tenor Horn
-Trombone
-Trumpet
-Tuba
-Viola
-Violin

Video Games
-First person shooter
-Fighting
-Puzzles
-Racing
-Role Playing
-Simulations
-Sports
-Strategy

Reading Material
-Comic books
-Fiction
-Fantasy
-Graphic novels
-Humor
-Magazines
-Newspapers
-Mysteries
-Myths and Legends
-Non-fiction
-Poetry
-Romance
-Sci-fi


";


$db->query("TRUNCATE interests");

$parent = 0;
$id = 1;

$list = explode("\n", $interestlist);

foreach($list as $i){
	$i = trim($i);

	if($i == '')
		continue;

	if($i{0} == '-'){
		$i = substr($i, 1);
	}else{
		$parent = 0;
	}

	$i = trim($i);

	$db->prepare_query("INSERT INTO interests SET id = #, parent = #, name = ?", $id, $parent, $i);

	if($parent == 0)
		$parent = $id;

	$id++;
}


//setup the agesexgroups table
	$db->query("TRUNCATE agesexgroups");

	$sexes = array("Male","Female");
	$ages = range(14, 60);

	foreach($ages as $age)
		foreach($sexes as $sex)
			$db->prepare_query("INSERT INTO agesexgroups SET age = #, sex = ?", $age, $sex);

//set abuselog entries by profile
	$abuselog->db->prepare_query("SELECT userid, count(*) as count FROM abuselog GROUP BY userid");

	while($line = $abuselog->db->fetchrow())
		$db->prepare_query("UPDATE users SET abuses = # WHERE userid = #", $line['count'], $line['userid']);

//pull sexuality out of the profile string
	UPDATE users SET sexuality = SUBSTRING(profile, 3, 1)

//pull single status out of the profile string
	UPDATE users SET single = 1 WHERE SUBSTRING(profile, 4, 1) = 1;
	UPDATE users SET single = 1 WHERE SUBSTRING(profile, 4, 1) = 2;
	UPDATE users SET single = 1 WHERE SUBSTRING(profile, 4, 1) = 6;

// parse through benchmark numbers

$files = array("output2","output3");

echo "<table border=1 cellspacing=0><tr>";

foreach($files as $file){

echo "<td>$file<br>";

	$table = array();

	$fp = fopen("/htdocs/$file.txt", 'r');

	while($line = fgets($fp)){
		list($file, $num) = explode("\t", $line);

		list($host, $file, $cons, $tmp, $k) = explode("-", substr($file, 0, -1));

		if(empty($k))
			$k = 'n';

		$table[$file][$cons][$host][$k] = $num;
	}

	uksort($table, 'strnatcmp');

	foreach($table as $file => $table2){
		echo "$file:<br>";
		echo "<table border=1 cellspacing=0>";

		echo "<tr>";
		echo "<td>Cons</td>";
		echo "<td>10.10.12.189</td>";
		echo "<td>10.10.12.190</td>";
		echo "<td>10.10.15.2</td>";
		echo "</tr>";

		uksort($table2, 'strnatcmp');

		foreach($table2 as $cons => $table3){
			echo "<tr>";
			echo "<td>$cons</td>";

			foreach($table3 as $host => $table4)
				echo "<td>$table4[n] / $table4[k]</td>";

			echo "</tr>";

		}

		echo "</table>";
	}

	fclose($fp);
	echo "</td>";
}
echo "</tr></table>";

//check connection times

$socks = array();
$num = 800;
$ip = "192.168.0.119";

$req = "GET / HTTP/1.1
Host: $ip
User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.6) Gecko/20050317 Firefox/1.0.2
Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5
Accept-Language: en-us,en;q=0.5
Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7
Keep-Alive: 300
Connection: keep-alive
Cache-Control: max-age=0

";

$time = gettime();

for($i = 1; $i <= $num; $i++){

	$time1 = gettime();
	$socks[$i] = fsockopen($ip, 80);

	$time2 = gettime();


	echo "$i: " . number_format(($time2 - $time1)/10, 3) . " ms<br>\n";

	fwrite($socks[$i], $req);
}

echo "Total: " . number_format(($time2 - $time)/10, 3) . " ms<br>\n";

echo "<br><br>\n";

for($i = 0; $i < 10; $i++){
	sleep(1);

	echo "$i ...<br>\n";
	zipflush();
}
echo "<br><br>\n";

for($i = 1; $i <= $num; $i++){
	echo "$i: " . fread($socks[$i], 256) . "<br>\n";

	fclose($socks[$i]);
}

//find search trends

	$db->unbuffered_query("SELECT sort, count(*) as count FROM searchqueries GROUP BY sort ORDER BY count DESC");
//	$db->unbuffered_query("SELECT sort, 1 as count FROM searchqueries");

	$results = array();

/*
array (
  'online' => 75499,
  'list' => 89961,
  'mode' => 115338,
  'sex' => 115338,
  'minage' => 115338,
  'maxage' => 115338,
  'loc' => 115338,
  'nopics' => 7522,
  'namescope' => 279,
  'user' => 279,
  'sexuality' => 279,
  'single' => 35,
  'friends' => 11,
)
*/

	echo "<pre>";

	$num = 0;

	while($line = $db->fetchrow()){
		$num += $line['count'];

		eval("\$sort = $line[sort];");

//		if(in_array($sort['mode'], array('Search','random')))
		$results['mode-' . $sort['mode']] += $line['count'];
		$results['sex-' . $sort['sex']] += $line['count'];

		$results["ages-$sort[minage]-$sort[maxage]"] += $line['count'];
		$results['agerange-' . str_pad($sort['maxage'] - $sort['minage'] + 1, 2, '0', STR_PAD_LEFT)] += $line['count'];

		if(isset($sort['online']))		$results['online'] += $line['count'];
		if(isset($sort['list']))		$results['list'] += $line['count'];
		if($sort['loc'])				$results['loc'] += $line['count'];
		if(isset($sort['nopics']))		$results['nopics'] += $line['count'];
		if(!empty($sort['user']))		$results['user'] += $line['count'];
		if(isset($sort['single']))		$results['single'] += $line['count'];
		if(isset($sort['friends']))		$results['friends'] += $line['count'];
	}


//  'minage' => 115338,
//  'maxage' => 115338,

	ksort($results);

	echo "<pre>";
	echo "$num rows total\n";
//	var_export($results);
//	print_r($results);

	foreach($results as $k => $v)
		if($v >= 10)
			echo "$k => $v\n";

	echo "</pre>";


//remove a day of false stats

	//1119657600
	//1119571200

	$moddb->query("SELECT type, userid, strict, dumptime FROM modhist WHERE dumptime IN (1119657600, 1119571200)");

	$data = array();

	while($line = $moddb->fetchrow())
		$data[$line['type']][$line['userid']][$line['dumptime']] = $line['strict'];

	foreach($data as $type => $data2){
		foreach($data2 as $userid => $data3){
			$num = array_pop($data3);
			$num -= array_pop($data3);

			$num = abs($num);

			if($num){
				$moddb->prepare_query("UPDATE mods SET wrong = wrong - #, strict = strict - # WHERE type = # && userid = #", $num, $num, $type, $userid);

				$moddb->prepare_query("UPDATE modhist SET wrong = wrong - #, strict = strict - # WHERE type = # && userid = # && dumptime >= 1119657600", $num, $num, $type, $userid);
			}
		}
	}
	/*
	//untested, single query version
		UPDATE mods, modhist, modhist as modhist2
		SET mods.wrong = mods.wrong - (modhist.strict - modhist2.strict),
			mods.strict = mods.strict - (modhist.strict - modhist2.strict)
		WHERE mods.type = modhist.type && mods.type = modhist2.type &&
			mods.userid = modhist.userid && mods.userid = modhist2.userid &&
			modhist.dumptime = 1119657600 && modhist2.dumptime = 1119571200
	*/


//get hit hist stats
	$statsdb->unbuffered_query("SELECT time, hitstotal, userstotal, onlineusers, onlineguests FROM statshist ORDER BY time");

	echo "<pre>";
	echo "day, date, hour, hitstotal, userstotal, onlineusers, onlineguests\n";

	$line = $statsdb->fetchrow();

	$num = 1;
	$day = 1;
	$time = $line['time'];
	$date = userdate("y/m/d", $time);

	echo "$day, " .userdate("y/m/d", $time) . ", " . userdate("H", $time) . ", $line[hitstotal], $line[userstotal], $line[onlineusers], $line[onlineguests]\n";

	while($line = $statsdb->fetchrow()){
		$time += 3600;
		if($date != userdate("y/m/d", $time)){
			$day++;
			$date = userdate("y/m/d", $time);
		}

		while($time < $line['time']){
			echo "$day, " . userdate("y/m/d", $time) . ", " . userdate("H", $time) . ", 0, 0, 0, 0\n";
			$num++;

			$time += 3600;
			if($date != userdate("y/m/d", $time)){
				$day++;
				$date = userdate("y/m/d", $time);
			}
		}

		if($time == $line['time']){
			echo "$day, " . userdate("y/m/d", $time) . ", " . userdate("H", $time) . ", $line[hitstotal], $line[userstotal], $line[onlineusers], $line[onlineguests]\n";
			$num++;
		}
	}
	echo "$num rows\n";

	echo "</pre>";

//get hit hist stats
	$statsdb->query("SELECT time, hitstotal, userstotal, onlineusers, onlineguests FROM statshist ORDER BY time");

	echo "<pre>";
	echo "date, hour, hitstotal, userstotal, onlineusers, onlineguests\n";

	while($line = $statsdb->fetchrow())
		echo userdate("y/m/d", $line['time']) . ", " . userdate("H", $line['time']) . ", $line[hitstotal], $line[userstotal], $line[onlineusers], $line[onlineguests]\n";

	echo "</pre>";

//skin update
	$time = time();
	$db->prepare_query("UPDATE users SET skin = 'azureframes' WHERE skin = 'azure' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'orangeframes' WHERE skin = 'orange' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'solarframes' WHERE skin = 'solar' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'auroraframes' WHERE skin = 'aurora' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'carbonframes' WHERE skin = 'carbon' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'pinkframes' WHERE skin = 'pink' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'megaleetfr' WHERE skin = 'megaleet' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'rushhourfr' WHERE skin = 'rushhour' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'greenxframes' WHERE skin = 'greenx' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'crushframes' WHERE skin = 'crush' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'flowerframes' WHERE skin = 'pink2' && premiumexpiry < #", $time);
	$db->prepare_query("UPDATE users SET skin = 'flowers' WHERE skin = 'pink2' && premiumexpiry >= #", $time);

//re-add article mod items
	$db->prepare_query("SELECT id FROM articles WHERE moded = 'n'");

	while($line = $db->fetchrow())
		$mods->newItem(MOD_ARTICLE, $line['id']);

//test forms that look and act links, but use POST instead of GET
	incHeader();

echo "<pre>";
print_r($_POST);
echo "</pre>";

//.foobar { padding: 0; background: transparent; border: 0;  color: #FE9800; text-decoration:none; font-family: arial; font-size: 8pt }
?>
<style>
form { display: inline; margin: 0; }
.foobar			{ padding: 0; background: transparent; margin: 0; line-height: 1em; vertical-align: text-baseline; border: 0; color: #FE9800; text-decoration:none; font-family: arial; font-size: 8pt; cursor: pointer; }
.foobar:hover	{ padding: 0; background: transparent; margin: 0; line-height: 1em; vertical-align: text-baseline; border: 0; color: #CCCCCC; text-decoration:none; font-family: arial; font-size: 8pt; cursor: pointer; }
</style>

<form action=update.php method=post><input type=hidden name=id value=1><input type=hidden name=action value=add><input class=foobar type=submit value='Add as Friend'></form><br>
<a class=body href=update.php?action=add&id=1>Add as Friend</a>
<?

	incFooter();

//show all abuse reports in the past 2 weeks
	$db->prepare_query("SELECT abuse.itemid, abuse.userid, username, reason, time, age, sex, firstpic FROM abuse, users WHERE users.userid=abuse.userid && abuse.type = ? && abuse.time >= ?", MOD_PICABUSE, time() - 86400*14);

	$ids = array();
	$abuses = array();
	while($line = $db->fetchrow()){
		$abuses[$line['itemid']][] = $line;
		$ids[] = $line['itemid'];
	}

	$rows = array();
	$db->prepare_query("SELECT pics.id, users.userid, username, pics.age, pics.sex, description FROM users,pics WHERE users.userid=pics.itemid && pics.id IN (?)", $ids);

	while($line = $db->fetchrow()){
		$rows[$line['id']] = $line;
		unset($ids[$line['id']]);
	}

	incHeader(750);

	echo "<table cellpadding=3>";

	$picloc = "http://www.nexopia.com" . $config['picdir'];

	foreach($rows as $line){
		if($line['sex']=='Female') 	$bgcolor = '#FFAAAA';
		else						$bgcolor = '#AAAAFF';

		echo "<tr><td class=body valign=top align=center style=\"background-color: $bgcolor\"><img src=$picloc" . floor($line['id']/1000) . "/$line[id].jpg>";
		echo "<br>$line[description]";
		echo "</td>";


		echo "<td class=body valign=top style=\"background-color: $bgcolor\">";
		echo "<a class=body href=/users/". urlencode($line['username']) ." target=_new>$line[username]</a><br>";
		echo "Age: $line[age]<br>";
		echo "Sex: <b>$line[sex]</b><br><br>";

		echo "<br><br>";
		echo "<a class=body href=messages.php?action=write&to=$line[userid]>Send Message</a><br><br>";
		if($mods->isAdmin($userData['userid'],'listusers')){
			echo "<a class=body href=/adminuser.php?type=userid&search=$line[userid]>User Search</a><br>";
			echo "<a class=body href=/adminuserips.php?uid=$line[userid]>IP Search</a><br>";
			echo "<a class=body href=/adminabuselog.php?uid=$line[username]>Abuse</a><br>";
			if($mods->isAdmin($userData['userid'],"editprofile"))
				echo "<a class=body href=/manageprofile.php?uid=$line[userid]>Profile</a><br>";
			if($mods->isAdmin($userData['userid'],"editpictures"))
				echo "<a class=body href=/managepicture.php?uid=$line[userid]>Pictures</a><br>";
		}

		echo "</td></tr>";

		if(isset($abuses[$line['id']])){

			echo "<tr><td class=body colspan=2>";

			echo "<table border=0 width=100%>";
			foreach($abuses[$line['id']] as $abuse){
				echo "<tr><td class=body2 valign=top width=100>";
				echo "<a class=body href=/users/". urlencode($abuse['username']) .">$abuse[username]</a><br>";
				if($abuse['firstpic'])
					echo "<img src=$config[thumbloc]" . floor($abuse['firstpic']/1000) . "/$abuse[firstpic].jpg><br>";
				echo "Age $abuse[age] - $abuse[sex]<br>";
				echo "<a class=body href=messages.php?action=write&to=$abuse[userid]>Send Message</a><br>";
				echo "</td><td class=body2 valign=top>";
				echo "<b>" . userDate("F j, Y", $abuse['time']) . "<br><br>";
				echo "$abuse[reason]<br><br></td></tr>";
			}
			echo "</table>";
			echo "</td></tr>";
		}

		echo "<tr><td colspan=2>&nbsp;<br>&nbsp;</td></tr>\n";
	}

	echo "</table>";

	incFooter();



//stress test banner server
	$num = 15;

	$con = 100;

	$bench = array();


	for($i = 0; $i < $con; $i++)
		$bench[$i] = new bannerclient( $bannerdb, BANNER_ADDR . ":" . BANNER_PORT );

	$a = 1;

	while(1){

		$time1 = gettime();

		for($i = 0; $i < $num; $i++){
			for($j = 0; $j < $con; $j++)
				$bench[$j]->getbanner(BANNER_BANNER);

		}
		$time2 = gettime();

		echo "$a - $num runs: " . number_format(($time2 - $time1)/10, 3) . " - " . number_format($con*$num*10000/($time2 - $time1),3) . " per sec<br>";
		zipflush();

		for($j = 0; $j < $con; $j++)
			$bench[$j]->disconnect();

		$a++;
	}

//fix journal entries entry
	$db->query("UPDATE users SET journalentries = 0");

	$db2->query("SELECT userid, MIN(scope) as minscope FROM blog GROUP BY userid");

	while($line = $db2->fetchrow())
		if($line['minscope'])
			$db->prepare_query("UPDATE users SET journalentries = ? WHERE userid = ?", $line['minscope'], $line['userid']);


//dump messages

	$table = "msgdump4";

	function viewDumpedMsgs2($sortbyuser = false){
		global $db, $table;

		$db->begin();

		$db->prepare_query("SELECT $table.*, IF(`to`=$table.userid, toname, fromname) AS username, IF(`to`=$table.userid, `from`, `to`) AS other, touser.age as toage, touser.sex as tosex, fromuser.age as fromage, fromuser.sex as fromsex FROM $table LEFT JOIN users AS touser ON `to` = touser.userid LEFT JOIN users AS fromuser ON `from` = fromuser.userid ORDER BY " . ($sortbyuser ? "other, " : "") . "date");

		$output = "dumping " . $db->numrows() . " messages";
		$output .= "\n\n------------------------------------------------------------\n\n";

		while($line = $db->fetchrow()){
			$output .= "Msg id:  $line[id]\n";
			$output .= "User:    $line[username]\n";
			$output .= "Folder:  " . ($line['to'] == $line['userid'] ? "Inbox" : "Outbox") . "\n";
			$output .= "To:      " . str_pad($line['toname'], 12) . " ($line[to]) age: $line[toage], sex: $line[tosex]\n";
			$output .= "From:    " . str_pad($line['fromname'], 12) . " ($line[from]) age: $line[fromage], sex: $line[fromsex]\n";
			$output .= "Date:    " . userDate("D M j, Y g:i a", $line['date']) . "\n";
			$output .= "Subject: $line[subject]\n";
			$output .= "$line[msg]";

			$output .= "\n\n------------------------------------------------------------\n\n";
		}

		$db->commit();

		echo "<pre>$output</pre>";
	}



echo "<pre>";
$timer = new timer("current");


	$uids = array(444211);

//current
	$db->prepare_query("INSERT IGNORE INTO $table SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgs.userid IN (?)", $uids);


//from backups
	$dates = array("2004.11.28","2004.12.05","2004.12.12","2004.12.19","2004.12.26","2005.01.09");

	foreach($dates as $date){
		$dbname = "nexopia" . implode("",explode(".", $date));
		$localdb = new sql_db(	"localhost",
									"root",
									'pRlUvi$t',
									$dbname);

echo $timer->lap("$date - from");


		$localdb->prepare_query("SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgheader.from IN (?)", $uids);

		while($line = $localdb->fetchrow())
			$db->prepare_query("INSERT IGNORE INTO $table (`" . implode("`,`", array_keys($line)) . "`) VALUES (?)", $line);


echo $timer->lap("$date - to");

		$localdb->prepare_query("SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgheader.to IN (?)", $uids);

		while($line = $localdb->fetchrow())
			$db->prepare_query("INSERT IGNORE INTO $table (`" . implode("`,`", array_keys($line)) . "`) VALUES (?)", $line);


		$localdb->close();
	}


echo $timer->stop();

echo "</pre>";

	viewDumpedMsgs2();



//uninvite non/ex-mods from admin/mod forums
	$db->prepare_query("DELETE foruminvite FROM foruminvite LEFT JOIN mods ON mods.userid = foruminvite.userid WHERE foruminvite.forumid IN (?) && mods.id IS NULL", array(26, 203, 139));


//memcache testing

include_once("include/memcached-client.php");
	$phpmemcache = new memcached($memcacheoptions);

include_once("include/peclmemcached-client.php"); //include dl("memcached.so");
	$phppeclmemcache = new peclmemcached($memcacheoptions);

	$peclmemcache = new Memcache;
	$peclmemcache->connect('localhost', 11211);

	$num = 2000;
	echo "$num runs<br><br>";

/*
//php memcache
	$phpmemcache->set("test", 1, 60);

	$time = gettime();
	for($i=0; $i < $num; $i++)
		$phpmemcache->get("test");
//		$phpmemcache->set("test", 1, 60);
	$time2 = gettime();

	$phpmemcache->delete("test");

	echo "time: " . ($time2 - $time)/10 . " ms<br>";
	echo "each: " . (($time2 - $time)/10)/$num . " ms<br>";
	echo "<br>";
*/


//php pecl memcache
echo "<b>pecl wrapper</b><br>";

	$key = array(0,"test2");
	$key = "test2";

	$phppeclmemcache->set($key, 1, 60);

	$time = gettime();
	for($i=0; $i < $num; $i++)
		$phppeclmemcache->get($key);
//		$phppeclmemcache->set("test2", 1, 60);
	$time2 = gettime();

	$phppeclmemcache->delete($key);

	echo "time: " . number_format(($time2 - $time)/10,3) . " ms<br>";
	echo "each: " . number_format((($time2 - $time)/10)/$num,3) . " ms<br>";
	echo "rate: " . number_format((1000/((($time2 - $time)/10)/$num)),3) . " ps<br>";
	echo "<br>";



echo "<b>pure pecl</b><br>";
	$peclmemcache->set("test3", 1, false, 60);

	$time = gettime();
	for($i=0; $i < $num; $i++)
		$peclmemcache->get("test3");
//		$peclmemcache->set("test3", 1, false, 60);
	$time2 = gettime();

	$peclmemcache->delete("test3");

	echo "time: " . number_format(($time2 - $time)/10,3) . " ms<br>";
	echo "each: " . number_format((($time2 - $time)/10)/$num,3) . " ms<br>";
	echo "rate: " . number_format((1000/((($time2 - $time)/10)/$num)),3) . " ps<br>";
	echo "<br>";


/*
	$size = "468x60";

	$time = gettime();

	for($i=0; $i < 100; $i++)
		$ret = $banner->calculateWeightings2($size);

	print_r($ret);

	echo "time: " . (gettime() - $time)/10 . " ms";
*/


//$memcache = new Memcache;
//$memcache->connect('localhost', 11211) or die ("Could not connect");


/*

if($cache->get("friends") === false)
	echo "false";

print_r($cache->get("friends-1"));

$cache->put("test", 1, 60);
echo "get: " . $cache->get("test") . "<br>\n";
echo "incr: " . $cache->incr("test") . "<br>\n";
echo "get: " . $cache->get("test") . "<br>\n";
echo "incr: " . $cache->incr("test") . "<br>\n";
echo "get: " . $cache->get("test") . "<br>\n";
$cache->remove("test");
//*/

//$cache->remove("spotlight");


//updateSpotlightList();
/*

$memcacheoptions = array(
	"servers" => array("192.168.0.160:11211"),
	"debug" => false,
	"compress" => 0);

include_once("include/MemCachedClient.php");
	$memcache = new MemCachedClient($memcacheoptions);


*/


// reset votes
$time = 1095701531 - 86400*5;

$db->query("LOCK TABLES pics WRITE, votehist WRITE");

$db->query("UPDATE pics SET  score = 0, votes = 0, v1 = 0, v2 = 0, v3 = 0, v4 = 0, v5 = 0, v6 = 0, v7 = 0, v8 = 0, v9 = 0, v10 = 0");

$result = $db->prepare_query("SELECT picid, vote FROM votehist WHERE time >= ? && blocked = 'n' ORDER BY picid", $time);

echo "redoing " . $db->numrows() . " votes<br>\n";

$picid = 0;
$num = 0;

$i=0;
while(list($id, $score) = $db->fetchrow($result, DB_NUM)){

	if($id != $picid){
		$picid = $id;
		$num = 0;
	}else{
		$num++;
	}
	if($num >= 3)
		continue;

	$db->prepare_query("UPDATE pics SET score = (((score*votes)+$score)/(votes+1)), votes = votes+1 , v$score=v$score+1 WHERE id = ?", $id);
	$i++;
	if($i % 1000 == 0){
		echo "$i<br>\n";
		zipflush();
	}
}

$db->query("UNLOCK TABLES");

//dump users messages
//including those from others inbox/outbox and backups
	function viewDumpedMsgs2($sortbyuser = false){
		global $db;

		$table = "msgdump2";

		$db->begin();

		$db->prepare_query("SELECT $table.*, IF(`to`=$table.userid, toname, fromname) AS username, IF(`to`=$table.userid, `from`, `to`) AS other, touser.age as toage, touser.sex as tosex, fromuser.age as fromage, fromuser.sex as fromsex FROM $table LEFT JOIN users AS touser ON `to` = touser.userid LEFT JOIN users AS fromuser ON `from` = fromuser.userid ORDER BY " . ($sortbyuser ? "other, " : "") . "date");

		$output = "dumping " . $db->numrows() . " messages";
		$output .= "\n\n------------------------------------------------------------\n\n";

		while($line = $db->fetchrow()){
			$output .= "Msg id:  $line[id]\n";
			$output .= "User:    $line[username]\n";
			$output .= "Folder:  " . ($line['to'] == $line['userid'] ? "Inbox" : "Outbox") . "\n";
			$output .= "To:      " . str_pad($line['toname'], 12) . " ($line[to]) age: $line[toage], sex: $line[tosex]\n";
			$output .= "From:    " . str_pad($line['fromname'], 12) . " ($line[from]) age: $line[fromage], sex: $line[fromsex]\n";
			$output .= "Date:    " . userDate("D M j, Y g:i a", $line['date']) . "\n";
			$output .= "Subject: $line[subject]\n";
			$output .= "$line[msg]";

			$output .= "\n\n------------------------------------------------------------\n\n";
		}

		$db->commit();

		echo "<pre>$output</pre>";
	}

	viewDumpedMsgs2();


		$uids = 319711;

	//current
		$db->prepare_query("INSERT IGNORE INTO msgdump2 SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgs.userid IN (?)", $uids);

	//nov 8
		$localdb = new sql_db(	"192.168.0.100",
									"root",
									'pRlUvi$t',
									"enternexusnov8");

		$localdb->prepare_query("SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgheader.from IN (?)", $uids);

		while($line = $localdb->fetchrow())
			$db->prepare_query("INSERT IGNORE INTO msgdump2 (`" . implode("`,`", array_keys($line)) . "`) VALUES (?)", $line);

		$localdb->prepare_query("SELECT msgs.id, msgs.userid, msgheader.to, msgheader.toname, msgheader.from, msgheader.fromname, msgheader.date, msgheader.subject, msgtext.msg FROM msgs,msgheader,msgtext WHERE msgheader.id=msgs.msgheaderid && msgheader.msgtextid=msgtext.id && msgheader.to IN (?)", $uids);

		while($line = $localdb->fetchrow())
			$db->prepare_query("INSERT IGNORE INTO msgdump2 (`" . implode("`,`", array_keys($line)) . "`) VALUES (?)", $line);


		$localdb->close();

//uninvite non/ex-mods from admin/mod forums
	$db->prepare_query("DELETE foruminvite FROM foruminvite LEFT JOIN mods ON mods.userid = foruminvite.userid WHERE foruminvite.forumid IN (?) && mods.id IS NULL", array(26, 203, 139));


//remod signpics
	$result = $db->query("SELECT id FROM pics WHERE signpic = 'y'");

	while($line = $db->fetchrow($result))
		$mods->newItem(MOD_SIGNPICS,$line['id']);

	$db->prepare_query("UPDATE pics SET signpic = 'n' WHERE signpic = 'y'");

// reset votes
	$time = 1095701531 - 86400*5;

	$db->query("LOCK TABLES pics WRITE, votehist WRITE");

	$db->query("UPDATE pics SET  score = 0, votes = 0, v1 = 0, v2 = 0, v3 = 0, v4 = 0, v5 = 0, v6 = 0, v7 = 0, v8 = 0, v9 = 0, v10 = 0");

	$result = $db->prepare_query("SELECT picid, vote FROM votehist WHERE time >= ? && blocked = 'n' ORDER BY picid", $time);

	echo "redoing " . $db->numrows() . " votes<br>\n";

	$picid = 0;
	$num = 0;

	$i=0;
	while(list($id, $score) = $db->fetchrow($result, DB_NUM)){

		if($id != $picid){
			$picid = $id;
			$num = 0;
		}else{
			$num++;
		}
		if($num >= 3)
			continue;

		$db->prepare_query("UPDATE pics SET score = (((score*votes)+$score)/(votes+1)), votes = votes+1 , v$score=v$score+1 WHERE id = ?", $id);
		$i++;
		if($i % 1000 == 0){
			echo "$i<br>\n";
			zipflush();
		}
	}

	$db->query("UNLOCK TABLES");


// set single,sexuality status
//	$db->query("UPDATE users SET single = 'y' WHERE profile LIKE '___1_'");
//	$db->query("UPDATE users SET sexuality = SUBSTRING(profile,3,1)");

// UPDATE users,profile SET profile.icq=users.icq, profile.msn = users.msn, profile.yahoo = users.yahoo, profile.aim = users.aim WHERE profile.userid = users.userid


//move msgs to new method
//	$db->query("ALTER TABLE `msgs` RENAME `msgsold`");

	$db->query("INSERT INTO msgs SELECT id,userid,id, IF(userid=`to`, `from`, `to`), folder,mark FROM msgsold");
	$db->query("INSERT INTO msgheader SELECT id, id, `to`,`toname`,`from`,fromname,date,subject,new,'n',0 FROM msgsold");

	$result = $db->query("SELECT id, msg FROM msgtext");

	while($line = $db->fetchrow($result)){
		$hash = pack("H*",md5($line['msg']));

		$db->prepare_query("SELECT id FROM msgtext WHERE hash = ?", $hash);

		if($db->numrows()){
			$id = $db->fetchfield();
			$db->prepare_query("DELETE FROM msgtext WHERE id = ?", $line['id']);

			$db->prepare_query("UPDATE msgheader SET msgtextid = ? WHERE id = ?", $id, $line['id']);
		}else{
			$db->prepare_query("UPDATE msgtext SET hash = ? WHERE id = ?", $hash, $line['id']);
		}
	}

//fix modtypes
	$modtypes = array(
		'pics' => MOD_PICS,
		'signpics' => MOD_SIGNPICS,
		'picabuse' => MOD_PICABUSE,
		'forumpost' => MOD_FORUMPOST,
		'forumrank' => MOD_FORUMRANK,
		'galleryabuse' => MOD_GALLERYABUSE,
		'userabuse' => MOD_USERABUSE,
		'banners' => MOD_BANNER,
		'articles' => MOD_ARTICLE );


foreach($modtypes as $old => $new){

	$db->prepare_query("UPDATE mods SET type2 = ? WHERE type = ?", $new, $old);
	$db->prepare_query("UPDATE moditems SET type2 = ? WHERE type = ?", $new, $old);
	$db->prepare_query("UPDATE abuse SET type2 = ? WHERE type = ?", $new, $old);
}





//check for dupe hashes with different msgs
	$db->unbuffered_query("SELECT hash, msg FROM msghash2 ORDER BY hash");

	$rows = 0;
	$hashes = 0;
	$thishash = 0;
	$maxhash = 0;

	$middle = gettime();

	echo "querytime: " . number_format(($middle - $startTime)/10000,4) . "<br>\n";
	zipflush();

	$last = $db->fetchrow();

	while($line = $db->fetchrow()){
		$rows++;

		if($last['hash'] == $line['hash']){
			$thishash++;
			if($thishash > $maxhash)
				$maxhash = $thishash;
			if($last['msg'] != $line['msg'])
				die("dupe hash found, $rows records checked, $hashes found, $maxhash max");
		}else{
			$last = $line;
			$hashes++;
			$thishash = 0;
		}

		if($rows % 1000 == 0){
			echo "$rows rows, $hashes hashes, time: " . number_format((gettime() - $middle)/10000,4) . ", $maxhash max<br>\n";
			zipflush();
		}
	}

//resend activations
	$db->query("SELECT userid, username, activatekey, email FROM users WHERE activated='n'");

	echo $db->numrows() . " emails to send<br>\n";

	$i=0;

	while($line = $db->fetchrow()){
		$i++;

$message="Thanks for joining $config[title]! (http://$wwwdomain)

Believe it or not, we're just as bored as you.  So we figured we'd do something about it, and ended up with Nexopia.com.  In a nutshell, it's a customizable online community, with user interests kept in mind.  Rather then just have the traditional online community where you can surf around, rate pictures, and message all the people you think are cute, we're constantly incorporating thoughts and ideas that come from you!

All you have to do now is activate your account by clicking on this link:
http://$wwwdomain/activate.php?username=" . urlencode($line['username']) . "&actkey=$line[activatekey]

If that link doesn't work, go to http://$wwwdomain/activate.php and put in this information:
	Username: $line[username]
	Key:      $line[activatekey]


Thanks and enjoy,
The $config[title] Team

$wwwdomain


Please do not respond to this email. Always use the Contact section of our website instead.";

		$subject="Activate your account at $wwwdomain.";


		echo "$i: $line[email]<br>\n";

		if(!smtpmail("$line[username] <$line[email]>", $subject, $message, "From: $config[title] <no-reply@$emaildomain>"))
			echo "<br><br><b>$line[email] FAILED</b><br><br>";

	}

$msgs->display();


//detect and delete dupes in pollvotes
	$query = "LOCK TABLES pollvotes WRITE";
	$db->query($query);

	$query = "CREATE TEMPORARY TABLE asdf SELECT id,pollid,userid FROM pollvotes";
	$db->query($query);

	echo "created temp";
	zipflush();

	$query = "SELECT userid, pollid, count(*) as count FROM asdf GROUP BY CONCAT( pollid, userid ) HAVING count > 1";
	$result = $db->query($query);

	echo "selected";
	zipflush();

	echo "<table>";
	echo "<td><td>userid</td><td>forumid</td><td>count</td><td>deleted</td></tr>";
	while($line = $db->fetchrow($result)){
		$query = "DELETE FROM pollvotes WHERE userid='$line[userid]' && pollid='$line[pollid]' LIMIT " . ($line['count'] - 1);
		$db->query($query);
		echo "<td><td>$line[userid]</td><td>$line[pollid]</td><td>$line[count]</td><td>" . $db->affectedrows() . "</td></tr>";
	}
	echo "</table>";

	echo "dupes deleted";
	zipflush();

	$query = "ALTER TABLE `forumupdated` DROP INDEX `pollid` , ADD UNIQUE `userid` ( `pollid` , `userid` ) ";
	$db->query($query);

	$query = "UNLOCK TABLES";
	$db->query($query);


//check tables
	$slowtime = $db->slowtime;
	$db->slowtime = 120*10000; //2 minutes
	$tableresult = $db->listtables();

    while (list($name) = $db->fetchrow($tableresult,DB_NUM)) {

		echo "Starting table $name ... ";
		zipflush();

		$time1 = time();

		$result = $db->query("CHECK TABLE `$name` MEDIUM");
		$check = $db->fetchrow($result);

		if($check['Msg_text']!='OK' && $check['Msg_text']!='Table is already up to date'){
			echo "check msg: $check[Msg_type] : $check[Msg_text], repairing ... ";

			$result = $db->query("REPAIR TABLE `$name`");
			$repair = $db->fetchrow($result);

			if($repair['Msg_text']!='OK')
				die("Couldn't repair table $name\n<br>");
		}

		$time2 = time();

		echo "Finished in " . ($time2 - $time1) . " secs<br>";
		zipflush();
	}
	$db->slowtime = $slowtime;

/*
//restore old forums
	$db->query("SELECT id FROM forums WHERE official='y'");

	$forums = array();

	while($line = $db->fetchrow())
		$forums[] = $line['id'];

	echo implode(",", $forums);


//	31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30

INSERT IGNORE INTO enternexus.forumthreads SELECT enternexusbackupaug6.forumthreads.* FROM enternexusbackupaug6.forumthreads WHERE enternexusbackupaug6.forumthreads.forumid IN (31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30);
INSERT IGNORE INTO enternexus.forumposts SELECT enternexusbackupaug6.forumposts.* FROM enternexusbackupaug6.forumposts,enternexusbackupaug6.forumthreads WHERE enternexusbackupaug6.forumposts.threadid = enternexusbackupaug6.forumthreads.id && enternexusbackupaug6.forumthreads.forumid IN (31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30);
INSERT IGNORE INTO enternexus.forummods SELECT enternexusbackupaug6.forummods.* FROM enternexusbackupaug6.forummods WHERE enternexusbackupaug6.forummods.forumid IN (31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30);
INSERT IGNORE INTO enternexus.forummute SELECT enternexusbackupaug6.forummute.* FROM enternexusbackupaug6.forummute WHERE enternexusbackupaug6.forummute.forumid IN (31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30);
INSERT IGNORE INTO enternexus.forummute SELECT enternexusbackupaug6.forummute.* FROM enternexusbackupaug6.forummute WHERE enternexusbackupaug6.forummute.forumid IN (31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30);
INSERT IGNORE INTO enternexus.forummutereason SELECT enternexusbackupaug6.forummutereason.* FROM enternexusbackupaug6.forummute;
INSERT IGNORE INTO enternexus.forumread SELECT enternexusbackupaug6.forumread.* FROM enternexusbackupaug6.forumread,enternexusbackupaug6.forumthreads WHERE enternexusbackupaug6.forumread.threadid = enternexusbackupaug6.forumthreads.id && enternexusbackupaug6.forumthreads.forumid IN (31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30);
INSERT IGNORE INTO enternexus.forumupdated SELECT enternexusbackupaug6.forumupdated.* FROM enternexusbackupaug6.forumupdated WHERE enternexusbackupaug6.forumupdated.forumid IN (31,20,986,5,1,37,38,23,34,35,4,21,39,7,18,33,17,6,9,29,111,24,67,69,8,3,2,32,30);
*/

//add moditems for all pending pics that got lost
	$db->query("SELECT picspending.id FROM picspending LEFT JOIN moditems ON picspending.id=moditems.itemid && moditems.type='pics' WHERE moditems.id IS NULL");

	$ids = array();
	while($line = $db->fetchrow())
		$ids[] = "($line[id],'pics')";

	$db->prepare_query("INSERT IGNORE INTO moditems (itemid,type) VALUES ?", $ids);

//dump users messages
	$uid = 97904;
	$db->prepare_query("INSERT IGNORE INTO msgdump SELECT msgs.*,msgtext.msg FROM msgs,msgtext WHERE msgs.id=msgtext.id && msgs.userid = ?", $uid);


//reset all premium expiries based on the premiumlog.
	$basedate = gmmktime(7,0,0,6,1,2004); // end of trial period
	$curdate = time();

	$users = array();
	$db->query("SELECT userid,duration,time FROM premiumlog ORDER BY id ASC");

	while($line = $db->fetchrow()){
		if(!isset($users[$line['userid']]))
			$users[$line['userid']] = $basedate;

		if($users[$line['userid']] < $line['time'])
			$users[$line['userid']] = $line['time'];

		$users[$line['userid']] += $line['duration'];
	}

	$db->query("UPDATE users SET premiumexpiry = 0");
	foreach($users as $uid => $expiry)
		if($expiry > $curdate)
			$db->prepare_query("UPDATE users SET premiumexpiry = ? WHERE userid = ?", $expiry, $uid);

//set number of non-private journal entries each user has
	$db->query("SELECT userid,scope, count(*) as count FROM weblog WHERE scope != 'private' GROUP BY userid, scope");

	$ids = array();
	while($line = $db->fetchrow()){
		if(!isset($ids[$line['userid']]))
			$ids[$line['userid']] = $line['scope'];
		if($line['scope'] == "public")
			$ids[$line['userid']] = "public";
	}

	$db->query("UPDATE users SET journalentries = 'none'");
	foreach($ids as $uid => $permission)
		$db->prepare_query("UPDATE users SET journalentries = ? WHERE userid = ?", $permission, $uid);

//set number of non-private gallery entries each user has

	$db->prepare_query("SELECT userid,permission FROM gallerycats");

	$ids = array();

	while($line = $db->fetchrow()){
		if(!isset($ids[$line['userid']]))
			$ids[$line['userid']] = "none";
		switch($line['permission']){
			case "anyone":
				$ids[$line['userid']] = "anyone";
				break;
			case "loggedin":
				if($ids[$line['userid']] != "anyone")
					$ids[$line['userid']] = "loggedin";
				break;
			case "friends":
				if($ids[$line['userid']] != "loggedin" && $ids[$line['userid']] != "anyone")
					$ids[$line['userid']] = "friends";
		}
	}

	$db->query("UPDATE users SET gallery = 'none'");
	foreach($ids as $uid => $permission)
		$db->prepare_query("UPDATE users SET gallery = ? WHERE userid = ?", $permission, $uid);

//update firstpicture of gallerycats
	$db->query("UPDATE gallerycats LEFT JOIN gallery ON gallerycats.id=gallery.category && gallery.priority=1 SET gallerycats.firstpicture = gallery.id");

//delete non-premium gallery pics
	$db->prepare_query("DELETE gallery FROM gallery,users WHERE gallery.userid=users.userid && users.premiumexpiry < ?", time());
	$db->prepare_query("DELETE gallerycats FROM gallerycats,users WHERE gallerycats.userid=users.userid && users.premiumexpiry < ?", time());

//delete gallery items associated with deleted gallerycats
	$db->query("DELETE gallery FROM gallery LEFT JOIN gallerycats ON gallery.category = gallerycats.id WHERE gallerycats.id IS NULL");

//retag all pics with exif info
	include_once('include/JPEG.php');
	$start = 550899;
	$end = 0;

	$db->prepare_query("SELECT id,itemid FROM pics WHERE id >= ? ORDER BY id ASC", $start);

	$pics = array();
	while($line = $db->fetchrow()){
		$pics[$line['id']] = $line['itemid'];
		$end = $line['id'];
	}

	$db->freeresult();

	echo "pics selected, start: $start, end: $end, count: " . count($pics) . "<br>\n";
	zipflush();

	$errors = array();

	$jpeg = new JPEG('');

	$picnum = 1;

	for($i=$start; $i <= $end; $i++){
		$picID = $i;

		$picName = $docRoot . $config['picdir'] . floor($picID/1000) . "/" . $picID . ".jpg";
		$thumbName = $docRoot . $config['thumbdir'] . floor($picID/1000) . "/" . $picID . ".jpg";

		if($i % 50 == 1){
//			zipflush();
			$db->query("SELECT 1 as keepalive");
		}

		echo "$i: ";
		if(!isset($pics[$i])){ // delete
			if(file_exists($picName)){
				unlink($picName);
				echo "deleteing pic ... ";
			}
			if(file_exists($thumbName)){
				unlink($thumbName);
				echo "deleteing thumb ... ";
			}
			echo "done<br>\n";
			continue;
		}

		$userid = $pics[$i];

		echo "$picnum: Starting picture ... ";
		$picnum++;

		$size = @GetImageSize($picName);
		if (!$size){
			echo "<b>Could not open picture $picID, removed</b><br>\n";
			$errors[$picID] = "Could not open picture, removed";
			removePic($picID);
			continue;
		}

		$jpeg->open($picName);

		$description = $jpeg->getExifField("ImageDescription");

		if(!empty($description) && substr($description,0,strlen($config['picText'])) == $config['picText']){
			echo "Already tagged<br>\n";
			continue;
		}

		if($size[2] == 2)
		    $destImg = @ImageCreateFromJPEG ($picName);
		elseif($size[2] == 3)
		    $destImg = @ImageCreateFromPNG ($picName);
		else{
			echo "<b>Wrong or unknown image type</b><br>\n";
			$errors[$picID] = "Wrong or unknown image type";
			continue;
		}

		if(!$destImg){
			echo "<b>Bad or currupt image</b><br>\n";
			$errors[$picID] = "Bad or currupt image";
			continue;
		}

		$aspectRat = (float)($size[0] / $size[1]);
		$picX = $size[0];
		$picY = $size[1];

		$white = ImageColorClosest($destImg, 255, 255, 255);
		$black = ImageColorClosest($destImg, 0, 0, 0);


		$padding = 3;
		$border = 1;
		$offset = 10;
		$textwidth = (strlen($config['picText'])*7)-1; // 6 pixels per character, 1 pixel space
		$textheight = 14;

		ImageRectangle($destImg,$picX-$textwidth-$offset-$border*2-$padding*2,$picY-$textheight-$offset-$border,$picX-$offset,$picY-$offset,$black);
		ImageFilledRectangle($destImg,$picX-$textwidth-$offset-$border-$padding*2,$picY-$textheight-$offset,$picX-$border-$offset,$picY-$border-$offset,$white);
		ImageString ($destImg, 3, $picX-$textwidth-$offset-$padding, $picY-$textheight-$offset, $config['picText'], $black);

		imagejpeg($destImg, $picName, 80);

		$jpeg->open($picName);

		$jpeg->setExifField("ImageDescription", "$config[picText]:$userid");
		$jpeg->save();


		echo "thumb ... ";

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

		imagejpeg($thumbImg, $thumbName, 80);

		$jpeg->open($thumbName);

		$jpeg->setExifField("ImageDescription", "$config[picText]:$userid");
		$jpeg->save();

		echo "finished<br>\n";
	}

	echo "<table border=1 cellspacing=0>";
	echo "<tr><td colspan=2 align=center>Errors</td></tr>";
	foreach($errors as $id => $error)
		echo "<tr><td>$id</td><td>$error</td></tr>";
	echo "</table>";


//fix article commentnum's
	$db->query("SELECT id FROM articles WHERE id > 1000 && moded='y'");
	$rows = array();
	while($line = $db->fetchrow())
		$rows[] = $line['id'];
	foreach($rows as $id){
		$db->prepare_query("SELECT id,commentnum FROM comments WHERE itemid = ? ORDER BY id ASC",$id);

		$rows = array();
		while($line = $db->fetchrow())
			$rows[$line['id']] = $line['commentnum'];


		$db->prepare_query("UPDATE articles SET comments = ?, nextcomment = ? WHERE id = ?", count($rows), count($rows)+2, $id);


		$i=1;
		foreach($rows as $id => $commentnum){
			if($i != $commentnum)
				$db->prepare_query("UPDATE comments SET commentnum = ? WHERE id = ?", $i, $id);
			$i++;
		}
	}

//set trial period
	$time = gmmktime(7,0,0,6,1,2004);
	$db->prepare_query("UPDATE users SET premiumexpiry = ? WHERE premiumexpiry < ?", $time, $time);


//undelete a forum from a backup
	$db->prepare_query("INSERT INTO enternexus.forums SELECT enternexusmay9.forums.* FROM enternexusmay9.forums WHERE enternexusmay9.forums.id IN (?)",$forums);
	$db->prepare_query("INSERT INTO enternexus.forumthreads SELECT enternexusmay9.forumthreads.* FROM enternexusmay9.forumthreads WHERE enternexusmay9.forumthreads.forumid IN (?)", $forums);
	$db->prepare_query("INSERT INTO enternexus.forumposts SELECT enternexusmay9.forumposts.* FROM enternexusmay9.forumposts,enternexusmay9.forumthreads WHERE enternexusmay9.forumposts.threadid = enternexusmay9.forumthreads.id && enternexusmay9.forumthreads.forumid IN (?)", $forums);


//delete friend links that no longer exist
//	DELETE friends FROM friends LEFT JOIN users ON friends.userid = users.userid WHERE users.userid IS NULL
//	DELETE friends FROM friends LEFT JOIN users ON friends.friendid = users.userid WHERE users.userid IS NULL


//set default age/sex
	$db->query("UPDATE users SET defaultminage = floor(age/2+7), defaultmaxage = ceil(3*age/2-7), defaultsex = 'Female' WHERE sex='Male'");
	$db->query("UPDATE users SET defaultminage = floor(age/2+7), defaultmaxage = ceil(3*age/2-5), defaultsex = 'Male' WHERE sex='Female'");


//undelete threads

	$db->prepare_query("SELECT threadid FROM forummodlog WHERE userid = ? && forumid = ? && time > ?", 15361, 111, time()-3600*15);

	$threadids = array();
	while($line = $db->fetchrow())
		$threadids[] = $line['threadid'];

	$db->prepare_query("INSERT IGNORE INTO forumthreads SELECT * FROM forumthreadsdel WHERE id IN (?)", $threadids);
	$db->prepare_query("INSERT IGNORE INTO forumposts SELECT * FROM forumpostsdel WHERE threadid IN (?)", $threadids);

	$db->prepare_query("DELETE FROM forumthreadsdel WHERE id IN (?)", $threadids);
	$db->prepare_query("DELETE FROM forumpostsdel WHERE threadid IN (?)", $threadids);


//delete friends that aren't friends back
	$uid = 50179;
	$db->prepare_query("SELECT friendid FROM friends WHERE userid = ?", $uid);

	$friendids = array();
	while($line = $db->fetchrow())
		$friendids[$line['friendid']] = $line['friendid'];

	$db->prepare_query("SELECT userid FROM friends WHERE userid IN (?) && friendid = ?", $friendids, $uid);

	while($line = $db->fetchrow())
		unset($friendids[$line['userid']]);

	$db->prepare_query("DELETE FROM friends WHERE userid = ? && friendid IN (?)", $uid, $friendids);




//invite all mods to pic mods/mod chat forums
	//INSERT INTO foruminvite (userid,forumid) SELECT mods.userid, 139 FROM mods LEFT JOIN foruminvite ON mods.userid = foruminvite.userid WHERE foruminvite.id IS NULL && foruminvite.forumid = 139;
	//INSERT INTO foruminvite (userid,forumid) SELECT mods.userid, 203 FROM mods LEFT JOIN foruminvite ON mods.userid = foruminvite.userid WHERE foruminvite.id IS NULL && foruminvite.forumid = 203

//delete invites to pic mod and mod chat forums for people that aren't mods
	$db->query("DELETE FROM foruminvite USING foruminvite LEFT JOIN mods ON foruminvite.userid = mods.userid WHERE mods.id IS NULL && foruminvite.forumid IN (203,139)");

//add moditems for all non-moded pics
	$query = "SELECT id FROM enternexusoriginal.pics WHERE moded='n'";
	$db->query($query);

	if($db->numrows() == 0)
		die("none");

	$ids = array();
	while($line = $db->fetchrow())
		$ids[] = "($line[id],'picabuse')";

	$query = "INSERT IGNORE INTO moditems (itemid,type) VALUES " . implode(",", $ids);
	$db->query($query);

//delete moditems that don't exist
	$db->query("SELECT moditems.id FROM moditems LEFT JOIN picspending ON moditems.itemid=picspending.id WHERE picspending.id IS NULL");

	$ids = array();

	while($line = $db->fetchrow())
		$ids[] = $line['id'];

	$db->prepare_query("DELETE FROM moditems WHERE id IN (?)", $ids);


//delete old user comments (6 weeks)
	$group = 0;

	$endtime = time() + 3600*4;

	while($group < 60000){
		$db->prepare_query("SELECT id FROM usercomments WHERE time <  ? && id BETWEEN ? AND ?", time()-86400*42, $group*100, ($group+1)*100 );

		$deleteids = array();
		while($line = $db->fetchrow()){
			$deleteids[] = $line['id'];
		}

		echo "$group ";
		$db->prepare_query("DELETE FROM usercomments WHERE id IN (?)", $deleteids);
		$db->prepare_query("DELETE FROM usercommentstext WHERE id IN (?)", $deleteids);

		$group++;
		zipflush();
		usleep(1000);
		if(time() > $endtime){
			echo "4 hours is up";
			break;
		}
	}

//reset firstpic for everyone
	$db->query("UPDATE users LEFT JOIN pics ON users.userid=pics.itemid && pics.priority=1 SET users.firstpic = pics.id");

//reset nummessages for everyone
	$db->query("LOCK TABLES users WRITE, msgs WRITE");

	$db->query("UPDATE users SET newmsgs = 0");
	$db->query("SELECT count(*) as count,userid FROM msgs WHERE new = 'y' GROUP BY userid");

	$data = array();
	while($line = $db->fetchrow())
		$data[$line['count']][] = $line['userid'];

	foreach($data as $count => $users)
		$db->prepare_query("UPDATE users SET newmsgs = ? WHERE userid IN (?)", $count, $users);

	$db->query("UNLOCK TABLES");


//copy to new database

	$db1 = "enternexus";
	$db2 = "enternexus2";

	$result = mysql_list_tables("enternexus");

	$i=0;

	while(list($name) = $db->fetchrow($result,DB_NUM)){
		$i++;
//		if($i <= 44)
//			continue;

	echo "Starting $name .. ";
	zipflush();

	$time1 = time();

		$result2 = $db->query("CHECK TABLE `$name` MEDIUM");
		$check = $db->fetchrow($result2);

		if($check['Msg_text']!='OK' && $check['Msg_text']!='Table is already up to date'){

			echo "check msg: $check[Msg_type] : $check[Msg_text], repairing (" . (time() - $time1) . "s) .. ";
			zipflush();

			$result3 = $db->query("REPAIR TABLE `$name`");
			$repair = $db->fetchrow($result3);

			if($repair['Msg_text']!='OK')
				die("Couldn't repair database $name\n<br>");
		}else{
			echo "Checked (" . (time() - $time1) . "s) .. ";
		}

		$result2 = $db->query("SHOW CREATE TABLE `$name`");
		$creation = $db->fetchfield(1,0,$result2);




		$creation2 = str_replace("`$name`", "`$db2`.`$name`", $creation);

//		$db->query("DROP `$db2`.`$name`");

		$db->query($creation2);

$time1 = time();

		$db->query("INSERT INTO `$db2`.`$name` SELECT * FROM `$db1`.`$name`");


		echo "Finished .. " . (time() - $time1) . " seconds<br>\n";
		zipflush();
	}


//
	/*
	UPDATE sessions SET activetime = activetime / 10000, logintime = logintime / 10000
	UPDATE users SET activetime = activetime / 10000, jointime = jointime / 10000;
	ALTER TABLE `userstats` RENAME `userhitlog`
	UPDATE userhitlog SET activetime = activetime / 10000
	*/

//UPDATE users,userstats SET users.activetimeold = userstats.activetime,users.ipold = userstats.ip,users.hitsold = userstats.hits WHERE users.userid = userstats.userid

//change table types
	$result = mysql_list_tables("enternexus");

	while(list($name) = $db->fetchrow($result,DB_NUM))
		$db->query("ALTER TABLE `$name` TYPE = INNODB");

//fix forums priorities
	$query = "SELECT id, parent FROM forums WHERE official='y' ORDER BY parent, priority";
	$db->query($query);

	$data = array();
	while($line = $db->fetchrow())
		$data[$line['parent']][] = $line['id'];

	foreach($data as $parent => $children){
		foreach($children as $priority => $id){
			$db->prepare_query("UPDATE forums SET priority = ? WHERE id = ?", $priority + 1, $id);
		}
	}

//delete moditems that don't have a corresponding picture
	$query = "SELECT moditems.id FROM moditems LEFT JOIN pics ON pics.id = moditems.itemid WHERE moditems.type = 'pics' && pics.id IS NULL";
	$result = $db->query($query);

	while($line = $db->fetchrow($result))
		$db->query("DELETE FROM moditems WHERE id = '$line[id]'");


//delete old pictures
	$opendir = "/home/enternexus/public_html/users";
//	$opendir = "/htdocs/rankme/public_html/users";


	$listing = `cd $opendir; ls -1 | grep jpg`;

	$files = array();
	while(1){
		$pos = strpos($listing, '.jpg');
		if($pos === false)
			break;
		$files[] = substr($listing,0,$pos+4);
		$listing = substr($listing,$pos+5);
	}

	foreach($files as $file){
		if(substr($file,-4)=='.jpg'){
			echo "$file ";
			unlink($opendir . "/" . $file);
		}
	}

	$opendir = "/home/enternexus/public_html/users/thumbs";
//	$opendir = "/htdocs/rankme/public_html/users/thumbs/";

	$listing = `cd $opendir; ls -1 | grep jpg`;

	$files = array();
	while(1){
		$pos = strpos($listing, '.jpg');
		if($pos === false)
			break;
		$files[] = substr($listing,0,$pos+4);
		$listing = substr($listing,$pos+5);
	}

	foreach($files as $file){
		if(substr($file,-4)=='.jpg'){
			echo "$file ";
			unlink($opendir . "/" . $file);
		}
	}

//create profile for all users without profiles (from a signup disconnect)
	$query = "SELECT users.userid FROM users LEFT JOIN profile ON users.userid = profile.userid WHERE profile.userid IS NULL";
	$db->query($query);

	$uids = array();
	while($line = $db->fetchrow())
		$uids[] = "($line[userid])";

	echo count($uids);

	if(count($uids)){
		$query = "INSERT INTO profile (userid) VALUES " . implode(',', $uids);
		$db->query($query);
	}

//add moditems for all non-moded pics
	$query = "SELECT id FROM pics WHERE moded='n'";
	$db->query($query);

	if($db->numrows() == 0)
		die("none");

	$ids = array();
	while($line = $db->fetchrow())
		$ids[] = "($line[id])";

	$query = "INSERT IGNORE INTO moditems (itemid) VALUES " . implode(",", $ids);
	$db->query($query);

//numerate article comments
	$db->query("LOCK TABLES comments WRITE, commentstext WRITE, commentsold READ");

	$query = "INSERT INTO comments SELECT id, itemid,0,authorid, author, time FROM commentsold WHERE type='articles'";
	$db->query($query);
	$query = "INSERT INTO commentstext SELECT id, msg, nmsg FROM commentsold WHERE type='articles'";
	$db->query($query);

	echo "inserted";
	zipflush();


	$db->query("LOCK TABLES comments WRITE, articles WRITE");

	$query = "SELECT id, itemid FROM comments ORDER BY itemid ASC, id ASC";
	$db->query($query);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[$line['itemid']][] = $line['id'];

	echo "selected";
	zipflush();


	foreach($rows as $id => $ids){
		$query = $db->prepare("UPDATE articles SET nextcomment = ? WHERE id = ?", count($ids)+1, $id);
		$db->query($query);
	}

	echo "user updated";
	zipflush();


	$cols = array();

	foreach($rows as $user){
		$i=1;
		foreach($user as $id){
			$cols[$i][] = $id;
			$i++;
		}
	}

	echo count($cols) . " groups ";

	echo "numerated";
	zipflush();

	$rows = array();

	foreach($cols as $num => $col){
		$ids = array();
		foreach($col as $i => $id)
			$ids[floor($i/250)][] = $id;

		foreach($ids as $group)
			$db->query($db->prepare("UPDATE comments SET commentnum = $num WHERE id IN (?)", $group));

		echo $num;
		zipflush();
	}

	$db->query("UNLOCK TABLES");



//numerate user comments
	$db->query("LOCK TABLES usercomments WRITE, usercommentstext WRITE, commentsold READ");

	$query = "INSERT INTO usercomments SELECT id, itemid,0,authorid, author, time FROM commentsold WHERE type='users'";
	$db->query($query);
	$query = "INSERT INTO usercommentstext SELECT id, msg, nmsg FROM commentsold WHERE type='users'";
	$db->query($query);

	echo "inserted";
	zipflush();


	$db->query("LOCK TABLES usercomments WRITE");

	$query = "SELECT id, itemid FROM usercomments ORDER BY itemid ASC, id ASC";
	$db->query($query);

	$rows = array();
	while($line = $db->fetchrow())
		$rows[$line['itemid']][] = $line['id'];

	echo "selected";
	zipflush();

	foreach($rows as $userid => $ids){
		$query = $db->prepare("UPDATE users SET nextcomment = ? WHERE userid = ?", count($ids)+1, $userid);
		$db->query($query);
	}

	echo "user updated";
	zipflush();

	$cols = array();

	foreach($rows as $user){
		$i=1;
		foreach($user as $id){
			$cols[$i][] = $id;
			$i++;
		}
	}

	echo "numerated";
	zipflush();

	$rows = array();

	foreach($cols as $num => $col){
		$ids = array();
		foreach($col as $i => $id)
			$ids[floor($i/250)][] = $id;

		foreach($ids as $group)
			$db->query($db->prepare("UPDATE usercomments SET commentnum = $num WHERE id IN (?)", $group));

		echo $num;
		zipflush();
	}
	$db->query("UNLOCL TABLES");

//update picture locations
	$query = "SELECT id FROM pics";
	$db->query($query);

	$ids = array();
	while($line = $db->fetchrow())
		$ids[] = $line['id'];

	$db->close();

	umask(0);
	$i=0;
	foreach($ids as $id){
		if(!is_dir("$docRoot/users/" . floor($id/1000)))
			@mkdir("$docRoot/users/" . floor($id/1000),0777);
		if(!is_dir("$docRoot/users/thumbs/" . floor($id/1000)))
			@mkdir("$docRoot/users/thumbs/" . floor($id/1000),0777);

		if(file_exists($docRoot . "/users/$id.jpg"))
			rename($docRoot . "/users/$id.jpg", $docRoot . "/users/" . floor($id/1000) . "/$id.jpg");
		if(file_exists($docRoot . "/users/thumbs/$id.jpg"))
			rename($docRoot . "/users/thumbs/$id.jpg", $docRoot . "/users/thumbs/" . floor($id/1000) . "/$id.jpg");
		$i++;
		if($i % 1000 == 0){
			echo "$i<br>\n";
			zipflush();
		}
	}

//hits by user and ip log
	$query = "INSERT IGNORE INTO userhitlog SELECT userid, ip, activetime, hits FROM users";
	$db->query($query);

//detect and delete dupes in forumupdated
	$query = "LOCK TABLES forumupdated WRITE";
	$db->query($query);

	$query = "CREATE TEMPORARY TABLE asdf SELECT id,forumid,userid FROM forumupdated";
	$db->query($query);

	echo "created temp";
	zipflush();

	$query = "SELECT SQL_BIG_RESULT userid, forumid, count(*) as count FROM asdf GROUP BY CONCAT( forumid, userid ) HAVING count > 1";
	$result = $db->query($query);

	echo "selected";
	zipflush();

	echo "<table>";
	echo "<td><td>userid</td><td>forumid</td><td>count</td><td>deleted</td></tr>";
	while($line = $db->fetchrow($result)){
		$query = "DELETE FROM forumupdated WHERE userid='$line[userid]' && forumid='$line[forumid]' LIMIT " . ($line['count'] - 1);
		$db->query($query);
		echo "<td><td>$line[userid]</td><td>$line[forumid]</td><td>$line[count]</td><td>" . $db->affectedrows() . "</td></tr>";
	}
	echo "</table>";

	echo "dupes deleted";
	zipflush();

	$query = " ALTER TABLE `forumupdated` DROP INDEX `userid` , ADD UNIQUE `userid` ( `forumid` , `userid` ) ";
	$db->query($query);

	$query = "UNLOCK TABLES";
	$db->query($query);

//detect and delete dupes in forumread
	$query = "LOCK TABLES forumread WRITE";
	$db->query($query);

	$query = "CREATE TEMPORARY TABLE asdf SELECT id,threadid,userid FROM forumread";
	$db->query($query);

	echo "created temp";
	zipflush();

	$query = "SELECT SQL_BIG_RESULT userid, threadid, count(*) as count FROM asdf GROUP BY CONCAT( threadid, userid ) HAVING count > 1";
	$result = $db->query($query);

	echo "selected";
	zipflush();

	echo "<table>";
	echo "<td><td>userid</td><td>threadid</td><td>count</td><td>deleted</td></tr>";
	while($line = $db->fetchrow($result)){
		$query = "DELETE FROM forumread WHERE userid='$line[userid]' && threadid='$line[threadid]' LIMIT " . ($line['count'] - 1);
		$db->query($query);
		echo "<td><td>$line[userid]</td><td>$line[threadid]</td><td>$line[count]</td><td>" . $db->affectedrows() . "</td></tr>";
	}
	echo "</table>";


	echo "dupes deleted";
	zipflush();

	$query = "ALTER TABLE `forumread` DROP INDEX `threadid` , ADD UNIQUE `threadid` ( `threadid` , `userid` )";
	$db->query($query);

	$query = "UNLOCK TABLES";
	$db->query($query);




//move friends comments to friendscomments table
	$query = "INSERT IGNORE INTO friendscomments SELECT friends.id,friends2.comment FROM friends,friends2,friendscomments WHERE friends.id=friends2.id && friends2.comment != ''";
	$db->query($query);


//move profile text to profile table
	$query = "INSERT INTO profile SELECT userid,likes,nlikes,dislikes,ndislikes,about,nabout,signiture,nsigniture FROM users";
	$db->query($query);


//move message text to msgtext table
	$query = "INSERT INTO msgtext SELECT id,msg FROM msgs WHERE id < 2307087";
	$db->query($query);



// set top scores
	$query = "SELECT id,score FROM pics,users WHERE activated='y' && numpics > 0 && pics.itemid=users.userid && moded='y' && vote='y' && sex = 'Female' && votes >= '$config[minVotesTop10]' ORDER  BY score DESC LIMIT 95";
	$result = $db->query($query);

	$score = 0;
	$top = array();
	while($line = $db->fetchrow($result)){
		$top[] = $line['id'];
		$score = $line['score'];
	}

	$query = "UPDATE config SET value='$score' WHERE name='minScoreTop10Female'";
	$db->query($query);

	$query = "SELECT id,score FROM pics,users WHERE activated='y' && numpics > 0 && pics.itemid=users.userid && moded='y' && vote='y' && sex = 'Male' && votes >= '$config[minVotesTop10]' ORDER  BY score DESC LIMIT 95";
	$result = $db->query($query);

	$score = 0;
	while($line = $db->fetchrow($result)){
		$top[] = $line['id'];
		$score = $line['score'];
	}

	$query = "UPDATE config SET value='$score' WHERE name='minScoreTop10Male'";
	$db->query($query);

	$query = "LOCK TABLES pics WRITE";
	$db->query($query);

	$query = "UPDATE pics SET top='n'";
	$db->query($query);

	$query = "UPDATE pics SET top='y' WHERE id IN ('" . implode("','", $top) . "')";
	$db->query($query);

	$query = "UNLOCK TABLES";
	$db->query($query);




//fix iplog

	$query = "SELECT ip,count(ip) FROM iplog GROUP BY ip HAVING count(ip)>1";
	$result = mysql_query($query);

	$ips = array();
	while($line = mysql_fetch_assoc($result))
		$ips[] = $line['ip'];

	$query = "DELETE FROM iplog WHERE ip IN ('" . implode("','",$ips) . "')";
	mysql_query($query);



//fix msgs toname
	$query = "SELECT userid,username FROM users";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result))
		mysql_query("UPDATE msgs SET toname='$line[username]' WHERE userid='$line[userid]'");

	echo "Update Complete<br>\n";
	$endTime = gettime();
	$dtime = number_format(($endTime - $startTime)/10000,4);
	echo "Run-time $dtime seconds";


//update the stats on forumthreads
	$query = "SELECT id FROM forumthreads";
	$threadsresult = mysql_query($query);

	while($line = mysql_fetch_assoc($threadsresult)){
		$result2 = mysql_query("SELECT id FROM forumposts WHERE threadid='$line[id]' ORDER BY id ASC LIMIT 1") or die(mysql_error());
		$firstpost = mysql_result($result2,0);

		$result2 = mysql_query("SELECT id,author,authorid,time FROM forumposts WHERE threadid='$line[id]' ORDER BY id DESC LIMIT 1") or die(mysql_error());
		list($lastpost,$lastauthor,$lastauthorid,$time) = mysql_fetch_array($result2);

		$result2 = mysql_query("SELECT count(*) FROM forumposts WHERE threadid='$line[id]'") or die(mysql_error());
		$posts = mysql_result($result2,0)-1;

		mysql_query("UPDATE forumthreads SET firstpost='$firstpost',lastpost='$lastpost',lastauthor='$lastauthor',lastauthorid='$lastauthorid',time='$time',posts='$posts' WHERE id='$line[id]'") or die(mysql_error());
	}

//update stats on forums
	$query = "SELECT id FROM forums";
	$result = mysql_query($query);

	$forums = array();
	while($line = mysql_fetch_assoc($result))
		$forums[$line['id']] = array('threads'=>0, 'posts'=>0);

	$query = "SELECT posts,forumid FROM forumthreads";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		$forums[$line['forumid']]['threads']++;
		$forums[$line['forumid']]['posts']+=$line['posts']+1;
	}

	foreach($forums as $forumid => $info){
		$query = "UPDATE forums SET threads = '$info[threads]', posts='$info[posts]' WHERE id='$forumid'";
		mysql_query($query) or die(mysql_error());
	}


//fix user forumposts
	mysql_query("UPDATE users SET forumposts='0'");


	$result = mysql_query("SELECT authorid,count(*) as count FROM forumposts WHERE authorid!=0 GROUP BY authorid");

	while($line = mysql_fetch_assoc($result)){
		mysql_query("UPDATE users SET posts = '$line[count]' WHERE userid = '$line[authorid]'");
	}




//update an old set of forumthreads
	$query = "SELECT id FROM forumthreads";
	$threadsresult = mysql_query($query);

	while($line = mysql_fetch_assoc($threadsresult)){
		$result2 = mysql_query("SELECT id FROM forumposts WHERE threadid='$line[id]' ORDER BY id ASC LIMIT 1") or die(mysql_error());
		$firstpost = mysql_result($result2,0);

		$result2 = mysql_query("SELECT id,author,authorid,time FROM forumposts WHERE threadid='$line[id]' ORDER BY id DESC LIMIT 1") or die(mysql_error());
		list($lastpost,$lastauthor,$lastauthorid,$time) = mysql_fetch_array($result2);

		$result2 = mysql_query("SELECT count(*) FROM forumposts WHERE threadid='$line[id]'") or die(mysql_error());
		$posts = mysql_result($result2,0);

		mysql_query("UPDATE forumthreads SET firstpost='$firstpost',lastpost='$lastpost',lastauthor='$lastauthor',lastauthorid='$lastauthorid',time='$time' WHERE id='$line[id]'") or die(mysql_error());
	}


//add firstpost and lastpost to forumthreads
	$query = "SELECT id FROM forumthreads";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		$result2 = mysql_query("SELECT id FROM forumposts WHERE threadid='$line[id]' ORDER BY id ASC LIMIT 1");
		$firstpost = mysql_result($result2,0);

		$result2 = mysql_query("SELECT id FROM forumposts WHERE threadid='$line[id]' ORDER BY id DESC LIMIT 1");
		$lastpost = mysql_result($result2,0);

		mysql_query("UPDATE forumthreads SET firstpost='$firstpost',lastpost='$lastpost' WHERE id='$line[id]'");
	}


//change activation key method
	$query = "SELECT userid FROM users WHERE activated='n'";
	$result = mysql_query($query);

	echo mysql_num_rows($result);

	while($line = mysql_fetch_assoc($result)){
		$uid=$line['userid'];

		$key = makekey();

		mysql_query("UPDATE users SET activated = 'n',activatekey='$key' WHERE userid = '$uid'");


		$result2 = mysql_query("SELECT username,email FROM users WHERE userid='$uid'");
		$line = mysql_fetch_assoc($result2);


		$message="To activate your account at http://$wwwdomain click on the following link or copy it into your webbrowser: http://$wwwdomain/account/activate?username=" . urlencode($line['username']) . "&key=$key";
		$subject="Activate your account at $wwwdomain.";


		@mail("$line[username] <$line[email]>", $subject, $message, "From: $config[title] <no-reply@$emaildomain>") or die("Error sending email");
	}


//add usernames to sessions table
	$query = "SELECT sessions.userid,users.username FROM sessions,users WHERE sessions.userid=users.userid";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result))
		mysql_query("UPDATE sessions SET username='$line[username]' WHERE userid='$line[userid]'");


//set number of new msgs each user has
	$query = "SELECT userid FROM users";
	$result = mysql_query($result);

	while(list($id) = mysql_fetch_assoc($result))
		setNumNewMsgs($id);

//change non-logged in users from having userid=0 to NULL, so count(userid) doesn't include non-logged in users
	$query = "UPDATE sessions SET userid=NULL WHERE userid='0'";
	mysql_result($result);



//preparse all the forum sigs,likes,dislikes,about
	$query = "SELECT userid,signiture,likes,dislikes,about FROM users";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		$nsig = nl2br(wrap(parseHTML(smilies($line['signiture']))));
		$nabout = nl2br(wrap(parseHTML(smilies(cleanHTML(censor($line['about']))))));
		$nlikes = nl2br(wrap(parseHTML(smilies(cleanHTML(censor($line['likes']))))));
		$ndislikes = nl2br(wrap(parseHTML(smilies(cleanHTML(censor($line['dislikes']))))));

		sqlSafe(&$nsig,&$nabout,&$nlikes,&$ndislikes);

		mysql_query("UPDATE users SET nsigniture = '$nsig', nlikes='$nlikes', nabout='$nabout', ndislikes='$ndislikes' WHERE userid='$line[userid]'");
	}

//preparse all the forum sigs,likes,dislikes,about
	$query = "SELECT userid,signiture,likes,dislikes,about FROM users";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		$nsig = nl2br(wrap(parseHTML(smilies($line['signiture']))));
		$nabout = nl2br(wrap(parseHTML(smilies(cleanHTML(censor($line['about']))))));
		$nlikes = nl2br(wrap(parseHTML(smilies(cleanHTML(censor($line['likes']))))));
		$ndislikes = nl2br(wrap(parseHTML(smilies(cleanHTML(censor($line['dislikes']))))));

		sqlSafe(&$nsig,&$nabout,&$nlikes,&$ndislikes);

		mysql_query("UPDATE users SET nsigniture = '$nsig', nlikes='$nlikes', nabout='$nabout', ndislikes='$ndislikes' WHERE userid='$line[userid]'");
	}

//preparse all the forum posts
	$query = "SELECT id,msg FROM forumposts WHERE nmsg = ''";
	$result = mysql_query($query);

	echo mysql_num_rows($result) . " rows to go<br>\n";

	while($line = mysql_fetch_assoc($result)){
		$nmsg = nl2br(wrap(parseHTML(smilies($line['msg']))));

		sqlSafe(&$nmsg);

		mysql_query("UPDATE forumposts SET nmsg = '$nmsg' WHERE id='$line[id]'");
	}

//update friends list
	$query = "SELECT * FROM friends";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		mysql_query("INSERT INTO friendsnew SET userid='$line[user1]',friendid='$line[user2]'");

		if($line['twoway']=='y')
			mysql_query("INSERT INTO friendsnew SET userid='$line[user2]',friendid='$line[user1]'");
	}

//preparse all articles
	$query = "SELECT id,text FROM articles";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		$nmsg = nl2br(parseHTML(smilies($line['text'])));

		sqlSafe(&$nmsg);

		mysql_query("UPDATE articles SET ntext = '$nmsg' WHERE id='$line[id]'");
	}

//preparse all comments
	$query = "SELECT id,msg FROM comments";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		$nmsg = nl2br(wrap(parseHTML(smilies($line['msg']))));

		sqlSafe(&$nmsg);

		mysql_query("UPDATE comments SET nmsg = '$nmsg' WHERE id='$line[id]'");
	}



//set first pic to speed thumb showing
	$query = "SELECT userid FROM users WHERE numpics>0";
	$result = mysql_query($query);

	while($line = mysql_fetch_assoc($result)){
		$query = "SELECT id FROM pics WHERE itemid='$line[userid]' && type='users' ORDER BY priority ASC LIMIT 1";
		$result2 = mysql_query($query);
		$id=mysql_result($result2,0);

		$query = "UPDATE users SET firstpic = '$id' WHERE userid = '$line[userid]'";
		mysql_query($query);
	}


//add last post user to thread data
	$query = "SELECT id FROM forumthreads";
	$result = mysql_query($query);

	$data = array();
	while($line = mysql_fetch_assoc($result)){
		$query = "SELECT author,authorid FROM forumposts WHERE threadid='$line[id]' ORDER BY time DESC LIMIT 1";
		$threadresult = mysql_query($query);
		$threadline = mysql_fetch_assoc($threadresult);

		$query = "UPDATE forumthreads SET lastauthor = '$threadline[author]', lastauthorid='$threadline[authorid]' WHERE id='$line[id]'";
		mysql_query($query);
	}

//update deleted account method
	$query = "SELECT userid FROM users";
	$result = mysql_query($query);

	$data = array();
	while($line = mysql_fetch_assoc($result))
		$data[] = $line['userid'];

	$highest = nextAuto('users');

	for($uid=1;$uid<$highest;$uid++){
		if(!in_array($uid,$data)){
			mysql_query("UPDATE forumposts SET authorid='0' WHERE authorid='$uid'");
			mysql_query("UPDATE forumthreads SET authorid='0' WHERE authorid='$uid'");
			mysql_query("UPDATE msgs SET `from`='0' WHERE `from`='$uid'");
			mysql_query("UPDATE comments SET authorid='0' WHERE authorid='$uid'");
			mysql_query("UPDATE shedule SET authorid='0' WHERE authorid='$uid'");
			mysql_query("UPDATE articles SET authorid='0' WHERE authorid='$uid'");
		}
	}


