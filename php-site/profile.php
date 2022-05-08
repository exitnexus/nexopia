<?
	$login=0;

	require_once("include/general.lib.php");

	$locations = & new category("locs");

	$namescopes = array('starts' => "Starts With", 'includes' => "Includes", 'ends' => "Ends With");

	if($action == 'advanced' && $userData['premium'])
		advancedSearch();

//vote
	if(isset($voteid) && isset($rating) && isset($time) && isset($votekey) && $config['votingenabled']){
		if(!$userData['loggedIn']){
			header("location: /login.php?referer=". urlencode($REQUEST_URI));
			exit;
		}
		if(!isset($HTTP_REFERER) || strpos($HTTP_REFERER,"profile.php")!==false && $time > time() - 600 && $time <= time() && $votekey == makeVoteKey($userData['userid'], $voteid, $time))
			votepic($voteid,$rating);
	}


	if($userData['loggedIn']){
		$sexes = $userData['defaultsex'];
		$sex = ($sexes == 'Male' ? 'm' : 'f');
		$minage = $userData['defaultminage'];
		$maxage = $userData['defaultmaxage'];
	}else{
		$sexes = array("Male","Female");
		$sex = 'b';
		$minage = 14;
		$maxage = 30;
	}

	$cache->prime(array('online',"top5f:$minage-$maxage","top5m:$minage-$maxage","new5$sex:$minage-$maxage","updt5$sex:$minage-$maxage"));


//uid
	if(!empty($uid)){
		$uid = trim($uid);

		if(!is_numeric($uid)){

			$uid = getUserId($uid);

			if(!$uid){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "User does not exist";
				incFooter();
				exit;
			}
		}

		if(empty($picnum))
			$picnum=0;

		displayUser($uid,$picnum); //exit
	}

//picid
	if(!empty($picid)){
		displayUser(0,$picid); //exit
	}

	if(!isset($sort))
		$sort = array();
	if(!isset($sort['mode'])){
		if(isset($sort['user']))
			$sort['mode']='Go';
		else
			$sort['mode']='random';
	}

//mode
	switch($sort['mode']){
		case "rate":
		case "Rate":
			updateStats(); //needed to set $siteStats[picgroups]

			$factor = 1.0;
			$where = array();

			$numpics = $siteStats['picgroups']*$config['picgroupsize'];

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14 || $sort['minage'] > 80){
				if($userData['loggedIn'])
					$sort['minage'] = $userData['defaultminage'];
				else
					$sort['minage'] = 14;
			}
			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] < 14 || $sort['maxage'] > 80){
				if($userData['loggedIn'])
					$sort['maxage'] = $userData['defaultmaxage'];
				else
					$sort['maxage'] = 80;
			}

			$minage = min($sort['minage'], $sort['maxage']);
			$maxage = $sort['maxage'] = max($sort['minage'], $sort['maxage']);
			$sort['minage'] = $minage;

			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female'&& $sort['sex'] != 'Both')){
				if($userData['loggedIn']){
					$sort['sex']=$userData['defaultsex'];
				}else{
					$sort['sex']="Both";
				}
			}
			$sex = $sort['sex'];
			$sexes = "pics$sex";
			if($sex == 'Both'){
				$sex = array("Male","Female");
				$sexes = array("picsMale","picsFemale");
			}

			$num = getNumUsersInAge($sexes, $minage, $maxage);

			$factor = (double)$num/$numpics;

			if($sort['minage'] > 10 || $sort['maxage'] < 80)
				$where[] = $db->prepare("age IN (?)", range($sort['minage'], $sort['maxage']));

			$where[] = $db->prepare("sex IN (?)", $sex);

			$desiredresults = 50; //really 1, but gotta account for the uneveness in the database

			$estimatedresults = ceil($numpics * $factor);

			$numgroups = ceil($estimatedresults / $desiredresults);
			if($numgroups > $siteStats['picgroups'])
				$numgroups = $siteStats['picgroups'];

			$estimatedpergroup = floor($estimatedresults/$numgroups);

			randomize();
			$groupnum = rand(1,$numgroups);

			$mincluster = floor($siteStats['userclusters']*($groupnum-1)/$numgroups)+1;
			$maxcluster = ceil($siteStats['userclusters']*$groupnum/$numgroups);


			extract(getPicClusterpicid($mincluster,$maxcluster));

			if($minpicid > 1 && $maxpicid > $minpicid)
				$where[] = "pics.id BETWEEN $minpicid AND $maxpicid";
			elseif($minpicid > 1)
				$where[] = "pics.id >= $minpicid";
			elseif($maxpicid > $minpicid)
				$where[] = "pics.id <= $maxpicid";

			$where[] = "vote='y'";
//			$where[] = "top IN ('y','n')";

		$time1 = gettime();

			$query = "SELECT pics.id FROM pics WHERE " . implode(" && ", $where);

			$result = $db->query($query);

		$time2 = gettime();

			$rows = $db->numrows($result);

	if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)){
		echo "<table><tr><td bgcolor=#FFFFFF><tt>";

//		$numresults = 0;

		echo " factor: $factor<br>\n";
		echo "groupnum: $groupnum, numgroups: $numgroups, rows per group: " . round($siteStats['picgroups']/$numgroups) . ", total clusters: $siteStats[picgroups]<br>\n";
		echo "estimated: $estimatedresults, desired: $desiredresults, estimated per group: $estimatedpergroup, actual: $rows<br>\n";
		echo "min cluster: $mincluster, max cluster: $maxcluster, minpicid: $minpicid, maxpicid: $maxpicid<br>\n";
		echo "$query<br>\n";
		echo "time: " . number_format(($time2-$time1)/10,4) . " milliseconds<br>\n";
		echo $db->explain($query);


		echo "<tt></td></tr></table>";
	}

			if($rows == 0){
				die("error");
			}

			$picid = $db->fetchfield(0,rand(0,$rows-1),$result);

			ratePic($picid); //exit

			break;
		case "top":
			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex'] != 'Both')){
				if($userData['loggedIn']){
					$sort['sex']=$userData['defaultsex'];
					$sexes = $sort['sex'];
				}else{
					$sort['sex']="Both";
					$sexes = array("Male","Female");
				}
			}else{
				if($sort['sex'] == 'Male' || $sort['sex'] == 'Female')
					$sexes = $sort['sex'];
				else //$sort['sex'] == 'Both'
					$sexes = array("Male","Female");
			}

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14){
				if($userData['loggedIn'])
					$sort['minage'] = $userData['defaultminage'];
				else
					$sort['minage'] = 14;
			}
			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] > 80){
				if($userData['loggedIn'])
					$sort['maxage'] = $userData['defaultmaxage'];
				else
					$sort['maxage'] = 80;
			}

			if(empty($page)) $page=0;

			$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id FROM picstop WHERE sex IN (?) && age IN (?) ORDER BY score DESC LIMIT " . ($page*$config['picsPerPage']) . ", $config[picsPerPage]", $sexes, range($sort['minage'], $sort['maxage']));

			$data = array();
			while($line = $db->fetchrow())
				$data[]=$line['id'];

//	print_r($data);

			$db->query("SELECT FOUND_ROWS()");
			$totalrows = $db->fetchfield();

			$numpages =  ceil($totalrows / $config['picsPerPage']);
			if($page>=$numpages) $page=0;

			displayList($data,'top'); //exit

			break;
		case "searchname":
		case "Go":
			if(empty($sort['user'])){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "You must specify a username";
				incFooter();
				exit();
			}

			$result = $db->prepare_query("SELECT userid,firstpic FROM users WHERE username = ?", $sort['user']);

			if($db->numrows()>0){
				list($uid,$firstpic) = $db->fetchrow($result,DB_NUM);

				displayUser($uid, ($firstpic>0) ); //exit
			}else{
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "No results found. Try an advanced search on part of the username.";
				incFooter();
				exit;
			}

			break;
		case "newest":
			if(empty($page)) $page=0;

			updateStats(); //needed to set $siteStats[maxuserid]

			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex']!='Both')){
				if($userData['loggedIn']){
					$sort['sex']=$userData['defaultsex'];
				}else
					$sort['sex']="Both";
			}
			$sex = $sort['sex'];
			if($sex == 'Both')
				$sex = array("Male","Female");

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14){
				if($userData['loggedIn'])
					$sort['minage'] = $userData['defaultminage'];
				else
					$sort['minage'] = 14;
			}
			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] > 80){
				if($userData['loggedIn'])
					$sort['maxage'] = $userData['defaultmaxage'];
				else
					$sort['maxage'] = 30;
			}

			$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS userid FROM newestusers WHERE sex IN (?) && age IN (?) ORDER BY id DESC LIMIT " . ($page*$config['picsPerPage']) . ", $config[picsPerPage]", $sex, range($sort['minage'], $sort['maxage']));

			$data = array();
			while($line = $db->fetchrow())
				$data[]=$line['userid'];

			$db->query("SELECT FOUND_ROWS()");
			$totalrows = $db->fetchfield();

			$numpages = ceil($totalrows / $config['picsPerPage']);
			if($page>=$numpages) $page=0;

			displayList($data,'newest'); //exit

			break;
		case "mypage":
			if($userData['loggedIn'])
				displayUser($userData['userid'], 0 );//exit
	//else random
		case "search":
		case "random":
		default: //random user

			updateStats(); //needed to set $siteStats[totalusers], $siteStats[maxuserid], $siteStats[userswithpics], $siteStats[userclusters], userswsignpics

			$tables = array();
			$tables['users'] = "users";
			$table = "users";
			$factors = array();
			$where = array();

			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex']!='Both')){
				if($userData['loggedIn']){
					$sort['sex']=$userData['defaultsex'];
				}else
					$sort['sex']="Both";
			}

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14 || $sort['minage'] > 80){
				if($userData['loggedIn'])
					$sort['minage'] = $userData['defaultminage'];
				else
					$sort['minage'] = 14;
			}
			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] < 14 ||$sort['maxage'] > 80){
				if($userData['loggedIn'])
					$sort['maxage'] = $userData['defaultmaxage'];
				else
					$sort['maxage'] = 30;
			}

			$minage = min($sort['minage'], $sort['maxage']);
			$maxage = $sort['maxage'] = max($sort['minage'], $sort['maxage']);
			$sort['minage'] = $minage;

			if($sort['minage'] > 10 || $sort['maxage'] < 80 || $sort['sex'] != 'Both' || isset($sort['single']) || !empty($sort['sexuality']) || isset($sort['signpic'])){


				if(!empty($sort['sexuality']) && in_array($sort['sexuality'], array(1,2,3))){
					$where[] = "sexuality = '$sort[sexuality]'";
					$factors['sexuality'] = getSexualityFactor($sort['sexuality']);
				}elseif(isset($sort['single']) || !empty($sort['sexuality']) || isset($sort['signpic']))
					$where[] = "sexuality IN ('0','1','2','3')";

				if(!empty($sort['signpic'])){
					$where[] = "signpic = 'y'";
					$factors['signpic'] = (double)$siteStats['userswsignpics']/$siteStats['totalusers'];
				}elseif(isset($sort['single']) || !empty($sort['sexuality']) || isset($sort['signpic']))
					$where[] = "signpic IN ('y','n')";

				if(isset($sort['single'])){
					if($sort['sex'] == 'Both')
						$sexes = array('singleMale','singleFemale');
					else
						$sexes = array("single$sort[sex]");
					$where[] = "single = 'y'";
				}else{
					if($sort['sex'] == 'Both')
						$sexes = array('Male','Female');
					else
						$sexes = array($sort['sex']);

					if(isset($sort['single']) || !empty($sort['sexuality']) || isset($sort['signpic']))
						$where[] = "single IN ('y','n')";
				}

				$num = getNumUsersInAge($sexes, $minage,$maxage);

				$factors['agesex'] = (double)$num/$siteStats['totalusers'];

				if($sort['minage']>10 || $sort['maxage'] < 80)
					$where[] = $db->prepare("age IN (?)", range($sort['minage'], $sort['maxage']));

				if($sort['sex'] == "Both")	$where[] = "sex IN ('Male','Female')";
				else						$where[] = "sex = '$sort[sex]'";
			}

			if(!empty($sort['loc']) && $locations->isValidCat($sort['loc'])){
				$locbranch = $locations->makeBranch($sort['loc']);

				$locs=array();
				$locs[] = $sort['loc'];
				foreach($locbranch as $loc)
					$locs[] = $loc['id'];

				$num = getNumUsersInLocs($locs);

				$factors['loc'] = (double)$num/$siteStats['totalusers'];

				$where[] = "loc IN ('" . implode("','", $locs) . "')";
//				$tables[] = "users";
			}else{
				$sort['loc'] = 0;
			}

			if(empty($sort['nopics'])){
				if(!empty($sort['online']))
					$factors['pics'] = 0.9;//(double)$siteStats['activeuserswithpics']/$siteStats['totalusers'];
				else
					$factors['pics'] = (double)$siteStats['userswithpics']/$siteStats['totalusers'];
				$where[] = "firstpic >= 1";
//				$where[] = "pic = 'y'";
//				$tables['users'] = "users";
			}else{
//				$where[] = "pic IN ('n','y')";
			}

//must be last option, overwrites $table
			if(!empty($sort['online'])){
				$factors['online'] = (double)$siteStats['online']/$siteStats['totalusers'];

				$where[] = "online = 'y'";

/*				if(isset($tables['users']))
					$where[] = "users.userid=online.userid";
				$tables['online'] = "online USE KEY(PRIMARY)";
				$table = "online";
*/			}

//			if(!isset($tables['online']))
//				$where[] = "activated = 'y'";

//fraction of users that are likely to match this search
			$factor = 1.0;
			foreach($factors as $val)
				$factor *= $val;



			if($factor == 0){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "No results found. Please broaden your search";
				incFooter();
				exit();
			}

			$desiredresults = 5; //really 1, but gotta account for the uneveness in the database
			if(!empty($sort['list']))
				$desiredresults = $config['picsPerPage'];//*1.5;

			$estimatedresults = ceil($siteStats['totalusers'] * $factor);

			$advanced = (!empty($sort['namescope']) && !empty($sort['user']) && in_array($sort['namescope'], array_keys($namescopes))) || ($userData['loggedIn'] && !empty($sort['friends']));


			if(!$advanced){ //basic search
				$numgroups = ceil($estimatedresults / $desiredresults);
				if($numgroups > $siteStats['userclusters'])
					$numgroups = $siteStats['userclusters'];

				$estimatedpergroup = floor($estimatedresults/$numgroups);

				if(!isset($page) || $page < 0 || $page - 1 > $numgroups){
					randomize();
					$groupnum = rand(1,$numgroups);
					$page = $groupnum - 1;
				}else{
					$groupnum = $page + 1;
				}
				$numpages = $numgroups;


				$mincluster = floor($siteStats['userclusters']*($groupnum-1)/$numgroups)+1;
				$maxcluster = ceil($siteStats['userclusters']*$groupnum/$numgroups);

				extract(getUserClusterUserid($mincluster, $maxcluster)); // returns $minuserid, $maxuserid

				if($minuserid > 1 && $maxuserid > $minuserid)
					$where[] = "$table.userid BETWEEN $minuserid AND $maxuserid";
				elseif($minuserid > 1)
					$where[] = "$table.userid >= $minuserid";
				elseif($maxuserid > $minuserid)
					$where[] = "$table.userid <= $maxuserid";

			}else{ //advanced search

				if($estimatedresults > 100000){
					$msgs->addMsg("Search is too broad. Please restrict your search parameters");
					advancedSearch(); //exit
				}

				$cachename = substr( base_convert(md5(serialize(array($minage, $maxage, $sort['loc'], $sort['sex'], (empty($sort['online']) ? 'n' : 'y'), (empty($sort['nopics']) ? 'y' : 'n'), $sort['namescope'], $sort['user'], (empty($sort['friends']) ? 'y' : 'n'),(empty($sort['signpic']) ? 'y' : 'n'), (empty($sort['single']) ? 'y' : 'n')))),16,36), 0, 12);
				$cachetime = (empty($sort['online']) ? 1800 : 300); // online -> 5min, all -> 30min

				$data = $cache->get($cachename, $cachetime);

				if($data){
					$data = explode(',', $data);
					if(!empty($sort['list'])){

						$numpages = ceil(count($data) / $config['picsPerPage']);

						if(!isset($page) || $page < 0 || $page - 1 > $numpages){
							randomize();
							$groupnum = rand(1,$numpages);
							$page = $groupnum - 1;
						}else{
							$groupnum = $page + 1;
						}

						$data = array_slice($data, $page*$config['picsPerPage'], $config['picsPerPage']);

						displayList($data,'users', $numpages); //exit
					}else{
						$uid = $data[rand(0,count($data)-1)];

						displayUser($uid, 0 ); //exit
					}
				}

				if(!empty($sort['namescope']) && !empty($sort['user']) && in_array($sort['namescope'], array_keys($namescopes))){
					switch($sort['namescope']){
						case "includes":	$where[] = "username LIKE '%" . $db->escape($sort['user']) . "%'";	break;
						case "ends": 		$where[] = "username LIKE '%" . $db->escape($sort['user']) . "'";	break;
						case "starts":
						default:			$where[] = "username LIKE '" . $db->escape($sort['user']) . "%'";	break;
					}
				}

				if($userData['loggedIn'] && !empty($sort['friends'])){
					$tables[] = "friends AS f1";
					$tables[] = "friends AS f2";

					$where[] = $db->prepare("f1.userid = ?", $userData['userid']);
					$where[] = "f1.friendid = f2.userid";
					$where[] = "f2.friendid = users.userid";
				}
			}

/* friend of friend search
max rows = 250^2 = 62500
realistic max = 10000

//put in a plus only user search?
*/


			$query = "SELECT $table.userid FROM " . implode(',',$tables);
			$query .= " WHERE " . implode(" && ", $where);


$time1 = gettime();

			$result = $db->query($query);

			$data = array();
			while($line = $db->fetchrow($result))
				$data[$line['userid']]=0; //use as key to get rid of dupes from friends list search

			$data = array_keys($data); //flip to values. This creates new consecutive keys.

$time2 = gettime();

			$numresults = count($data);

	if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)){
		echo "<table><tr><td bgcolor=#FFFFFF><tt>";


		foreach($factors as $factorname => $val)
			echo "$factorname: " . number_format($val, 4) . ", ";
		echo "factor: " . number_format($factor, 4) . "<br>\n";
		if($advanced){
			echo "estimated: $estimatedresults, found: $numresults<br>";
		}else{
			echo "groupnum: $groupnum, numgroups: $numgroups, rows per group: " . round($siteStats['totalusers']/$numgroups) . ", total clusters: $siteStats[userclusters]<br>\n";
			echo "estimated: $estimatedresults, desired: $desiredresults, estimated per group: $estimatedpergroup, actual: $numresults<br>\n";
			echo "min cluster: $mincluster, max cluster: $maxcluster, minuserid: $minuserid, maxuserid: $maxuserid<br>\n";
		}
		echo "$query<br>\n";
		echo "time: " . number_format(($time2-$time1)/10,4) . " milliseconds<br>\n";
		echo $db->explain($query);


		echo "<tt></td></tr></table>";
	}

			if($numresults == 0){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "No results found. Please broaden your search or search again";
				incFooter();
				exit();
			}


			if(!$advanced){ //basic search
				if(!empty($sort['list'])){
					displayList($data,'users',$estimatedresults); //exit
				}else{
					$uid = $data[rand(0,$numresults-1)];

					displayUser($uid, 0 ); //exit
				}
			}else{ // advanced search

				$cache->put($cachename, implode(',',$data), $cachetime);

				if(!empty($sort['list'])){

					$numpages = ceil($numresults / $config['picsPerPage']);

					if(!isset($page) || $page < 0 || $page - 1 > $numpages){
						randomize();
						$groupnum = rand(1,$numpages);
						$page = $groupnum - 1;
					}else{
						$groupnum = $page + 1;
					}

					$data = array_slice($data, $page*$config['picsPerPage'], $config['picsPerPage']);

					displayList($data,'users', $numpages); //exit
				}else{
					$uid = $data[rand(0,count($data)-1)];

					displayUser($uid, 0 ); //exit
				}
			}

			break;
	}

	die("<font size=7>How did I get here???</font>");


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


function advancedSearch(){
	global $PHP_SELF, $userData, $namescopes, $sort,$locations;

	if(!$userData['loggedIn'] || !$userData['premium'])
		return;

	$user = '';
	$namescope = 'starts';
	$loc='0';
	$sexuality = 0;

	if($userData['loggedIn'])	$minage = $userData['defaultminage'];
	else						$minage = 14;

	if($userData['loggedIn'])	$maxage = $userData['defaultmaxage'];
	else						$maxage = 30;

	if($userData['loggedIn'])	$sex = $userData['defaultsex'];
	else						$sex = "Both";

	if(!isset($sort) || !is_array($sort))
		$sort = array();

	extract($sort);

	$locations = & new category("locs");

	incHeader();

	echo "<table align=center><form action=$PHP_SELF>";

	echo "<tr><td class=header colspan=2 align=center>Advanced User Search</td></tr>";

	echo "<tr><td class=body>Username:</td><td class=body><select class=body name=sort[namescope]>" . make_select_list_key($namescopes, $namescope) . "</select> <input class=side type=text name=sort[user] size=10 style=\"width:100px\" value='$user'></td></tr>";

	echo "<tr><td class=body>Age:</td><td class=body><input class=body name=sort[minage] value='$minage' size=1> to <input class=body name=sort[maxage] value='$maxage' size=1></td></tr>";
	echo "<tr><td class=body>Sex:</td><td class=body><select class=body name=sort[sex]><option value=Both>Both" . make_select_list(array("Male","Female"),$sex) . "</select></td></tr>";
	echo "<tr><td class=body>Sexuality:</td><td class=body><select class=body name=sort[sexuality]>" . make_select_list_key(array('Any',"Heterosexual","Homosexual","Bisexual/Open-Minded"),$sexuality) . "</select></td></tr>";
	echo "<tr><td class=body>Location:</td><td class=body><select class=body name=sort[loc]><option value=0>Anywhere" . makeCatSelect($locations->makeBranch(),$loc) . "</select></td></tr>"; // <script src=http://images.nexopia.com/include/dynconfig/locs.js></script>

	if(!empty($loc)){
		$branch = $locations->makeBranch();
		$i=1;
		foreach($branch as $cat){
			if($cat['id']==$loc)
				break;
			$i++;
		}
		echo "<script> document.profilesort['sort[loc]'].selectedIndex = $i; </script>";
	}


	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[single]', 'Single and Looking Users Only', 'body', !empty($single)) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[friends]', 'Friends of Friends Only', 'body', !empty($friends)) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[signpic]', 'Only Users with a Sign Picture', 'body', !empty($signpic)) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[online]', 'Online Users Only', 'body', !empty($online)) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[nopics]', 'Include Users Without Pictures', 'body', !empty($nopics)) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[list]', 'Show List', 'body', !empty($list)) . "</td></tr>";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=sort[mode] value=Search></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function displayUser($uid,$picid=0){ // either userid and picnum, or userid or picid
	global $PHP_SELF,$config,$sort,$userData,$skindir,$db,$cache, $mods, $locations;

	if($uid==0 && $picid==0)
		return false;
	if($uid==0){ //gives picid
		$query = "SELECT itemid,priority FROM pics WHERE id='$picid'";
		$result = $db->query($query);

		if($db->numrows($result)==0){
			incHeader(true,array('incTextAdBlock','incSortBlock'));
			echo "No results Found";
			incFooter();
			exit;
		}

		list($uid,$picnum) = $db->fetchrow($result,DB_NUM);

	}else{ //gives userid, and possibly picnum
		$picnum=max($picid,1);
	}

//$uid,$picnum are set now;


	if($userData['loggedIn']){
		$set = "";

		if($userData['userid'] != $uid){
			$db->prepare_query("INSERT IGNORE INTO profileviews SET hits = 1, time = ?, userid = ?, viewuserid = ?", time(), $uid, $userData['userid']);
			if($db->affectedrows()){
				$set = "views = views + 1";
			}else{
				$db->prepare_query("UPDATE profileviews SET hits = hits + 1, time = ? WHERE userid = ? && viewuserid = ?", time(), $uid, $userData['userid']);
			}
		}elseif($userData['newcomments']){ // $userData['userid'] == $uid
			$set = "newcomments = '0'";
			$userData['newcomments']=0;
		}
		if($set != "")
			$db->prepare_query("UPDATE users SET $set WHERE userid = ?", $uid);
	}


//get info
	$query = "SELECT users.userid, username, frozen, dob, sex, loc, email, showbday, showemail, jointime, activetime, showjointime, showactivetime, premiumexpiry, showpremium, signpic, enablecomments, journalentries, gallery, onlyfriends, msn, icq, yahoo, aim, firstpic, profile, nlikes, ndislikes, nabout, views, online, profile.skin FROM users, profile WHERE users.userid='$uid' && users.userid=profile.userid";
	$result = $db->query($query);
	$user = $db->fetchrow($result);

	if(!$user || ($user['frozen'] == 'y' && !$mods->isAdmin($userData['userid'],'listusers'))){
		incHeader(true,array('incTextAdBlock','incSortBlock'));
		echo "No results Found";
		incFooter();
		exit;
	}

	$db->prepare_query("SELECT friendid,username FROM friends,users WHERE friends.userid = ? && users.userid=friendid", $user['userid']);

	$friends = array();
	while($line = $db->fetchrow())
		$friends[$line['username']] = $line['friendid'];

	uksort($friends,'strcasecmp');

	$isFriend = $userData['loggedIn'] && (isset($friends[$userData['userid']]) || $userData['userid']==$user['userid']);

//start output
	incHeader(600,array('incTextAdBlock','incSortBlock','incTopGirls','incTopGuys','incNewestMembersBlock','incRecentUpdateProfileBlock'));

	if($user['premiumexpiry'] > time() && $user['skin']){
/*
$skindata = array(
'headerbg' => "A1570E",
'headertext' => "FFFFFF",
'headerlink' => "FFFFFF",
'headerhover' => "CCCCCC",

'bodybg' => "666666",
'bodybg2' => "333333",
'bodytext' => "FFFFFF",
'bodylink' => "FE9800",
'bodyhover' => "CCCCCC",

'votelink' => "CCCCCC",
'votehover' => "FFFFFF",

'online' => "00AA00",
'offline' => "FF0000");*/


		$db->prepare_query("SELECT data FROM profileskins WHERE id = ?", $user['skin']);
		$skindata = decodeSkin($db->fetchfield());


echo <<<END
<style>
a.header:active,
a.header:link,
a.header:visited{ color: #$skindata[headerlink]; font-family: arial; font-size: 8pt }
a.header:hover	{ color: #$skindata[headerhover]; font-family: arial; font-size: 8pt }
td.header		{ background-color: #$skindata[headerbg]; color: #$skindata[headertext]; font-family: arial; font-size: 8pt}

a.body:active,
a.body:link,
a.body:visited	{ color: #$skindata[bodylink]; font-family: arial; font-size: 8pt }
a.body:hover	{ color: #$skindata[bodyhover]; font-family: arial; font-size: 8pt }
td.body			{ background-color: #$skindata[bodybg]; color: #$skindata[bodytext]; font-family: arial; font-size: 8pt}
td.body2		{ background-color: #$skindata[bodybg2]; color: #$skindata[bodytext]; font-family: arial; font-size: 8pt}

td.online		{ color: #$skindata[online]; font-family: arial; font-size: 16pt; font-weight: bolder}
td.offline		{ color: #FF00$skindata[offline]00; font-family: arial; font-size: 16pt; font-weight: bolder}

</style>
END;
/*
a.vote:active,
a.vote:link,
a.vote:visited	{ color: #$skindata[votelink]; font-family: arial; font-size: 15pt; font-weight: bolder }
a.vote:hover	{ color: #$skindata[votehover]; font-family: arial; font-size: 15pt; font-weight: bolder }
*/

	}

echo "<script src=$config[imgserver]/skins/profile.js></script>";

echo "<table border=0 width=600 align=center cellspacing=0 cellpadding=0>\n";

	$cols=2;
	if($user['enablecomments']=='y')
		$cols++;
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
		$cols++;
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		$cols++;

	$width = 100.0/$cols;

	echo "<tr>";
	echo "<td class=body colspan=2>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$user[userid]\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$user[userid]\"><b>Comments</b></a></td>";
	if($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$user[userid]\"><b>Gallery</b></a></td>";
	if($user['journalentries'] == 'public' || ($user['journalentries']=='friends' && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?id=$user[userid]><b>Journal</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"friends.php?uid=$user[userid]\"><b>Friends</b></a></td></tr>";
	echo "</table>";
	echo "</td></tr>";


	echo "<tr><td class=body colspan=2>";

	$ignored = true;
	$cols = 2;
	if($user['onlyfriends']!='y' || ($userData['loggedIn'] && ($isFriend || $mods->isAdmin($userData['userid'],'listusers')))){
		$ignored = false;
		$cols++;
	}
	if($user['enablecomments']=='y')
		$cols++;

	$width = 100.0/$cols;

	echo "<table border=0 width=100%>";

	echo "<tr><td class=header align=center width=$width%><a class=header href=\"friends.php?action=add&id=$user[userid]\"><b>Add as Friend</b></a></td>";
	if(!$ignored);
		echo "<td class=header align=center width=$width%><a class=header href=\"messages.php?action=write&to=$user[userid]\"><b>Send Message</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=usercomments.php?id=$user[userid]#reply><b>Add Comment</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=\"javascript: reportabuse()\"><b>Report Abuse</b></a></td></tr>";
	echo "</table>";


	echo "</td></tr>";

echo "	<tr>\n";
echo "		<td valign=top width=80 class=body>\n";


echo "			<table border=0 width=100% height=100%>\n";

//Status
	echo "<tr><td align=center class=" . ($user['online']=='y' ? "online>Online" : "offline>Offline") . "<br></td></tr>";

	echo "<tr><td class=body align=center><b>$user[views] Hits</b><br>";
	if($user['showpremium'] == 'y' && $user['premiumexpiry'] > time())
		echo "Plus Member<br>";
	if($user['signpic'] == 'y')
		echo "Verified User<br>";
	echo "&nbsp;</td></tr>";


//admin
	if($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')){
		echo "<tr><td class=header><b>Admin</b></td></tr>";
		echo "<tr><td class=body>";

		if($user['frozen'] == 'y')
			echo "<b>Account Frozen!</b><br><br>";

		echo "<a class=body href=/adminuser.php?search=$uid&type=userid>User Search</a><br>";
		echo "<a class=body href=/adminuserips.php?uid=$uid>IP Search</a><br>";
		if($mods->isAdmin($userData['userid'],"editprofile"))
			echo "<a class=body href=/manageprofile.php?uid=$uid>Profile</a><br>";
		if($mods->isAdmin($userData['userid'],"editpictures"))
			echo "<a class=body href=/managepicture.php?uid=$uid>Pictures</a><br>";
		if($mods->isAdmin($userData['userid'],"editpreferences"))
			echo "<a class=body href=/prefs.php?uid=$uid>Prefs</a><br>";


		echo "<br></td></tr>";
	}


//friendslist
	echo "<tr><td class=header><b>Friends</b></td></tr>";
	echo "<tr><td class=body>";
	foreach($friends as $username => $friendid)
		echo "<a class=body href=profile.php?uid=$friendid>$username</a><br>";
	echo "</td></tr>";

//End Friends list

echo "			</table>\n";
echo "		</td>\n";
echo "		<td class=body valign=top>\n";
echo "			<table border=0 cellspacing=0 width=100%>\n";
echo "				<tr>\n";
echo "					<td class=body align=center>\n";

//Name, Age, Sex,vote


	echo "<font size=3><b>$user[username] (" . getAge($user['dob']) . " year old $user[sex])</b></font>";



//End Name, Age, Sex,vote

echo "					</td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "					<td align=center class=body>\n";

//Pic


	if($user['firstpic']){



		echo "<table width=100% height=100% border=0><tr><td class=body align=center valign=center>";

		echo "<div id=votediv name=votediv></div>";
		echo "<img name=userpic id=userpic>";
		echo "<div id=picdesc name=picdesc></div>";
		echo "</td></tr><tr><td class=body align=center valign=bottom>";
		echo "<div id=piclinks name=piclinks></div>";

		echo "</td></tr></table>";

		echo "<script>";

		$sortprefix="uid=$uid&picnum=$picnum";
		if(is_array($sort)){
			foreach($sort as $k => $v)
				if($k != 'mode')
					$sortprefix .= "&sort[$k]=$v";
		}

//		echo "setVoteLink(\"profile.php?$sortprefix&votekey=" . time() . "\");";
		echo "setSkinDir(\"$skindir\");";
		echo "setPicLoc(\"$config[picloc]\");";

		$db->prepare_query("SELECT id,description, priority FROM pics WHERE itemid = ?", $uid);

		$pics = array();
		while($line = $db->fetchrow())
			$pics[$line['priority']] = $line;

		ksort($pics);

		foreach($pics as $line)
			echo "addPic('$line[id]','" . addslashes($line['description']) . "',0);";

		echo "changepic(" . ($picnum-1) . ");";
		echo "</script>";
	}else{
		echo "<script>setUserid($uid);</script>";
		echo "No pic available";
	}



//End Pic

echo "					</td>\n";
echo "				</tr>\n";
echo "				<tr>\n";
echo "					<td class=body valign=top>\n";

//profile

	$classes = array('body2','body');
	$i=0;

	echo "<table border=0 width=100%>";

	echo "<tr><td colspan=2 class=header align=center><b>Basics</b></td></tr>";

	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Username:</b></td><td class=" . $classes[$i] . ">$user[username]</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Age:</b></td><td class=" . $classes[$i] . ">" . getAge($user['dob']) ."</td></tr>";
	if($user['showbday']=='y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Date of Birth:</b></td><td class=" . $classes[$i] . ">" . gmdate("F j, Y",$user['dob']) ."</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Sex:</b></td><td class=" . $classes[$i] . ">$user[sex]</td></tr>";
	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Location:</b></td><td class=" . $classes[$i] . ">" . $locations->getCatName($user['loc']) . "</td></tr>";

	if($user['showjointime'] == 'y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Join Date:</b></td><td class=" . $classes[$i] . ">" . userdate("M j, Y g:i:s a",$user['jointime']) . "</td></tr>";

	if($user['showactivetime'] == 'y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers'))){
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Active Time:</b></td><td class=" . $classes[$i] . ">";
		if($user['online'] == 'y'){
			echo "User is online";
		}elseif($user['activetime'] == 0){
				echo "Never";
		}else{
			$deltat = time() - $user['activetime'];
			if($deltat < 3600){
				echo "Within the past hour";
			}elseif($deltat < 86400){
				echo round($deltat/3600) . " hours ago";
			}elseif($deltat < 86400*30){
				echo round($deltat/86400) . " days ago";
			}else{
				echo round($deltat/(86400*30.5)) . " months ago";
			}
		}
		echo "</td></tr>";
	}

	if($userData['loggedIn']){
		if($mods->isAdmin($userData['userid'],'listusers')){
			echo "<tr><td colspan=2 class=header align=center><b>Admin</b></td></tr>";

/*			global $fastdb;
			$fastdb->prepare_query("SELECT activetime FROM useractivetime WHERE userid = ?", $uid);
			$activetime = $fastdb->fetchfield();
			echo "<tr><td class=" . $classes[$i = !$i] . "><b>Active Time:</b></td><td class=" . $classes[$i] . ">" . ($activetime == 0 ? "Never" : userdate("M j, Y g:i:s a",$activetime)) . "</td></tr>";
*/
			echo "<tr><td class=" . $classes[$i = !$i] . "><b>Send Email:</b></td><td class=" . $classes[$i] . "><a class=body href=\"mailto:$user[email]\">$user[email]</a></td></tr>";
			if($user['premiumexpiry'] > time()){
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>Plus Time remaining:</b></td><td class=" . $classes[$i] . ">" . number_format(($user['premiumexpiry'] - time())/86400,2) . " Days</td></tr>";
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>Plus Expiry Date:</b></td><td class=" . $classes[$i] . ">" . userDate("F j, Y, g:i a", $user['premiumexpiry']) . "</td></tr>";
			}
		}

		if(!empty($user['icq']) || !empty($user['msn']) || !empty($user['yahoo']) || !empty($user['aim'])){

			echo "<tr><td colspan=2 class=header align=center><b>Contact</b></td></tr>";

			if(!empty($user['icq']))
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>ICQ:</b></td><td class=" . $classes[$i] . ">$user[icq]</td></tr>";
			if(!empty($user['msn']))
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>MSN:</b></td><td class=" . $classes[$i] . ">$user[msn]</td></tr>";
			if(!empty($user['yahoo']))
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>Yahoo:</b></td><td class=" . $classes[$i] . ">$user[yahoo]</td></tr>";
			if(!empty($user['aim']))
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>AIM:</b></td><td class=" . $classes[$i] . ">$user[aim]</td></tr>";
		}
	}



	global $profile;

	$prof = decodeProfile($user['profile']);

	$first = true;
	foreach($profile as $qnum => $val){
		if($prof[$qnum]!=0){
			if($first){
				$first = false;
				echo "<tr><td colspan=2 class=header align=center><b>Profile</b></td></tr>";
			}
			echo "<tr><td class=" . $classes[$i = !$i] . "><b>$val[question]:</b></td><td class=" . $classes[$i] . ">" . $val['answers'][$prof[$qnum]-1] . "</td></tr>\n";
		}
	}

	if($user['nabout']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>About Me</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[nabout]</td></tr>";
	}

	if($user['nlikes']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>Likes</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[nlikes]</td></tr>";
	}

	if($user['ndislikes']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>Dislikes</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[ndislikes]</td></tr>";
	}

	echo "</table>";



//End profile

echo "					</td>";
echo "				</tr>";
echo "			</table>";

echo "		</td>\n";
echo "	</tr>\n";

//start comments
	if($user['enablecomments']=='y'){

echo "	<tr>\n";
echo "		<td colspan=3 class=body>\n";




	echo "<table width=100% cellpadding=3>";
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=usercomments.php?id=$user[userid]>Add/Display Comments</a></b></td></tr>";

	$db->prepare_query("SELECT author, authorid, time, nmsg FROM usercomments, usercommentstext WHERE itemid = ? && usercomments.id = usercommentstext.id ORDER BY usercomments.id DESC LIMIT 5", $user['userid']);

	if($db->numrows()==0)
		echo "<tr><td class=body colspan=2 align=center>No Comments</td>";
	$comments = array();
	while($line = $db->fetchrow())
		$comments[] = $line;

	foreach($comments as $line){
		echo "<tr><td class=header>By: ";

		if($line['authorid'])	echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
		else					echo "$line[author]";

		echo "</td><td class=header>Date: " . userdate("M j, Y g:i:s a",$line['time']) . "</td>";
		echo "</tr>";

		echo "<tr>";
		echo "<td class=body colspan=2>";

		echo $line['nmsg'] . "&nbsp;";

		echo "</td></tr>";
//		echo "<tr><td colspan=2 class=header2>&nbsp;</td></tr>";
	}
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=usercomments.php?id=$user[userid]>Add/Display Comments</a></b></td></tr>";
	echo "</table>";


echo "		</td>\n";
echo "	</tr>\n";
	}
//End comments

echo "</table>\n";



	incFooter();
	exit;
}

function makeVoteKey($userid, $picid, $time){
	return md5("$userid:$picid:$time:salt");
}

function ratePic($picid){
	global $sort,$db,$config,$skindir,$PHP_SELF, $userData;

	$db->prepare_query("SELECT users.userid,username,users.age,users.sex,description FROM users,pics WHERE pics.id = ? && users.userid=pics.itemid", $picid);

	if($db->numrows()==0)
		die("User does not exist");

	$user = $db->fetchrow();

//start output
	incHeader(true,array('incTextAdBlock','incSortBlock','incPrevVoteBlock'));


	echo "<script src=$config[imgserver]/skins/profile.js></script>";
	echo "<script>";

	$sortvals = array();
	foreach($sort as $k => $v)
		$sortvals[] = "sort[$k]=$v";
	$sortprefix = implode("&", $sortvals);


	$time = time();
	$votekey = 0;
	if($userData['loggedIn'])
		$votekey = makeVoteKey($userData['userid'], $picid, $time);

	echo "setVoteLink(\"/profile.php?$sortprefix&time=$time&votekey=$votekey\");";
	echo "setSkinDir(\"$skindir\");";

	echo "addPic('$picid','','1');";

	echo "selectedpic=0;";
	echo "</script>";


	echo "<table align=center>";

	echo "<form action=$PHP_SELF>";
	echo "<tr><td class=body align=center>Rate: <select class=body name=sort[sex]>" . make_select_list(array("Male","Female"),$sort['sex']) . "</select> Age <input class=body type=text name=sort[minage] size=1 value=$sort[minage]> to <input class=body type=text name=sort[maxage] size=1 value=$sort[maxage]> <input class=body type=submit name=sort[mode] value=Rate></td></tr>";
	echo "</form>";
	echo "<tr><td class=body>&nbsp;</td></tr>";

	echo "<tr><td class=body align=center>";
	echo "<font size=3><b>$user[username] ($user[age] year old $user[sex])</b></font>";

	echo "</td></tr>";
	echo "<tr><td class=body align=center>";

	echo "<script> document.write(votelinks()); </script>";

	echo "</td></tr>";
	echo "<tr><td class=body align=center>";

	echo "<a class=body href=profile.php?picid=$picid><img src=$config[picloc]" . floor($picid/1000) . "/$picid.jpg border=0></a><br>";
	echo $user['description'];
	echo "<br><br></td></tr>";


	echo "</table>";

	incFooter();
	exit;
}


function displayList($list,$mode,$estimated=0){ // array of userid's,sort array
	global $PHP_SELF,$config,$sort,$page,$numpages,$sortlist,$sortt,$sortd,$db,$locations;

	incHeader(0,array('incTextAdBlock','incSortBlock'));

	if($mode!='users' && $mode!='random' && $mode!='top' && $mode!='newest')
		die("bad mode");

	$cols = 6;
	if($config['votingenabled'])
		$cols++;

	echo "<table width=600 cellspacing=1 cellpadding=2 align=center>\n";

	if($mode == 'top' || $mode == 'newest'){
		echo "<form action=$PHP_SELF>";
		echo "<input type=hidden name=sort[mode] value=$sort[mode]>";
		echo "<tr><td class=header colspan=$cols align=center>";
		if($mode == 'top')		echo "Top ";
		if($mode == 'newest')	echo "Newest ";
		echo "<select class=body name=sort[sex]>" . make_select_list(array("Male","Female"),$sort['sex']) . "</select> Age <input class=body type=text name=sort[minage] size=1 value=$sort[minage]> to <input class=body type=text name=sort[maxage] size=1 value=$sort[maxage]>";
		echo " <input class=body type=submit value=' Go '>";
		echo "</td></tr>";
		echo "</form>";
	}


	$query = "SELECT users.userid,username,users.age,users.sex,loc,firstpic,online,pics.score, profile.ntagline ";
	if($mode=='top')
		$query .= ",pics.id as picid FROM pics,users,profile WHERE pics.id IN (?) && users.userid=pics.itemid && users.userid=profile.userid";
	else // mode = users,random,newest
		$query .= "FROM users,profile LEFT JOIN pics ON users.firstpic > 0 && pics.id=users.firstpic WHERE users.userid IN (?) && users.userid=profile.userid";

	$db->prepare_query($query, $list);

	$rows = array();

	while($user = $db->fetchrow())
		$rows[] = $user;

	if(count($rows)){
		if($mode=='top')
			sortCols($rows, SORT_DESC, SORT_NUMERIC, 'score');
		else
			sortCols($rows, SORT_ASC, SORT_CASESTR, 'username');
	}

	echo "<tr>\n";
		echo "<td class=header width=$config[thumbWidth]></td>\n";
		echo "<td class=header>Username</td>\n";
		echo "<td class=header align=center>Age</td>\n";
		echo "<td class=header align=center>Sex</td>\n";
		echo "<td class=header>Location</td>\n";
		echo "<td class=header align=center>Online</td>\n";
		if($config['votingenabled'])
			echo "<td class=header align=center>Score</td>\n";
	echo "</tr>\n";

	foreach($rows as $user){
		echo "<tr><td class=body rowspan=2 valign=top>";
		if($user['firstpic']>0){
			if(isset($user['picid']))
				$user['firstpic'] = $user['picid'];
			if($mode=='users')
				echo "<a class=body href=\"profile.php?uid=$user[userid]\">";
			else
				echo "<a class=body href=\"profile.php?picid=$user[firstpic]\">";
			echo "<img src=\"$config[thumbloc]" . floor($user['firstpic']/1000) . "/$user[firstpic].jpg\" border=0></a>";
		}else
			echo "No pic";
		echo "</td>";
		echo "<td class=body height=25><a class=body href=\"profile.php?uid=$user[userid]\"><b>$user[username]</b></a>";
		echo "<td class=body align=center>$user[age]</td>";
		echo "<td class=body align=center>$user[sex]</td>";
		echo "<td class=body>" . $locations->getCatName($user['loc']) . "</td>";
		echo "<td class=body align=center>";
		if($user['online']=='y')
			echo "<b>Online</b>";
		echo "</td>";
		if($config['votingenabled']){
			echo "<td class=body align=center>";
			if($user['firstpic']>0)
				echo scoreCurve($user['score']);
			echo "</td>";
		}
		echo "</tr>";
		echo "<tr><td class=body colspan=" . ($cols-1) . " valign=top>$user[ntagline] <br>&nbsp;</td></tr>\n";
	}

	echo "<tr><td class=header colspan=$cols align=right>";

	foreach($sort as $k => $v)
		$params[] = "sort[$k]=$v";
	$params = implode("&", $params);

	if($mode == 'random')
		echo "Showing a random " . count($list) . " of an estimated $estimated results. <a class=header href=$PHP_SELF?$params>Search Again</a>";
	else
		echo "Page: " . pageList("$PHP_SELF?$params",$page,$numpages,'header');

	echo "</td></tr>";

	echo "</table>\n";

	incFooter();
	exit;
}

