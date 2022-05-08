<?
	$login=0;

	require_once("include/general.lib.php");

	$locations = & new category( $db, "locs");
	$interests = & new category( $db, "interests");

	$namescopes = array('starts' => "Starts With", 'includes' => "Includes", 'ends' => "Ends With");

	if($action == 'advanced' && $userData['premium'])
		advancedSearch();

//vote
	if(isset($voteid) && isset($rating) && isset($votekey) && isset($votetime) && $config['votingenabled']){
		if(!$userData['loggedIn']){
			header("location: /login.php?referer=". urlencode($REQUEST_URI));
			exit;
		}
		$time = time();
		if((!isset($HTTP_REFERER) || strpos($HTTP_REFERER, "profile.php")!==false) && $time >= $votetime && $time - 600 <= $votetime && checkKey("$voteid:$votetime", $votekey))
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


//uid
	if($uid = getREQval('uid')){
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

		$picnum = getREQval('picnum', 'int');

		displayUser($uid,$picnum); //exit
	}

//picid
	if($picid = getREQval('picid', 'int'))
		displayUser(0, $picid); //exit


	$sort = getREQval('sort', 'array');

	if(empty($sort['mode'])){
		if(isset($sort['user']))
			$sort['mode']='Go';
		else
			$sort['mode']='random';
	}
	$page = getREQval('page', 'int');

//mode

	choosePic();

function choosePic(){
	global $sort, $userData, $config, $cache, $db, $siteStats, $debuginfousers, $locations, $interests, $page, $namescopes, $msgs;

	switch($sort['mode']){
		case "rate":
		case "Rate":

/*
		//sex
			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex'] != 'Both')){
				if($userData['loggedIn']){
					$sort['sex'] = $userData['defaultsex'];
				}else
					$sort['sex'] = "Both";
			}

			$sexes = ($sort['sex'] == 'Both' ? array('Male','Female') : array($sort['sex']) );

		//age
			if(empty($sort['minage']) || !($sort['minage'] = intval($sort['minage'])) || $sort['minage'] < 14 || $sort['minage'] > 80)
				$sort['minage'] = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);

			if(empty($sort['maxage']) || !($sort['maxage'] = intval($sort['maxage'])) || $sort['maxage'] < 14 ||$sort['maxage'] > 80)
				$sort['maxage'] = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 25);

			$minage = min($sort['minage'], $sort['maxage']);
			$maxage = $sort['maxage'] = max($sort['minage'], $sort['maxage']);
			$sort['minage'] = $minage;
*/




	//old way:
			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14 || $sort['minage'] > 80)
				$sort['minage'] = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);

			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] < 14 || $sort['maxage'] > 80)
				$sort['maxage'] = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 80);

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
			$sexes = array("$sex");
			if($sex == 'Both'){
				$sex = array("Male","Female");
				$sexes = array("Male","Female");
			}

			$users = $cache->get("votePicsByAgeSex");

			if(!$users){
				$db->query("SELECT age, sex, picsvotable FROM agesexgroups");

				$users = array();
				while($line = $db->fetchrow())
					$users[$line['age']][$line['sex']] = $line['picsvotable'];

				$cache->put("votePicsByAgeSex", $users, 3600*6);
			}


			$samplespace = array();

			for($age2 = $minage; $age2 <= $maxage; $age2++)
				foreach($sexes as $sex2)
					if(isset($users[$age2][$sex2]) && $users[$age2][$sex2])
						$samplespace["$sex2:$age2"] = $users[$age2][$sex2];


			if(!count($samplespace)){
				incHeader(true,array('incTextAdBlock','incSortBlock'));

				echo "There are no votable pictures in that age and sex range";

				if($userData['loggedIn'] && in_array($userData['userid'], $debuginfousers)){
					echo "<br>\n";
					print_r($users);
					echo "<br>\n";
					print_r($samplespace);
				}


				incFooter();
				exit;
			}

//			randomize();

			$i = 10;
			do{
				$choice = chooseWeight($samplespace);

				list($choicesex, $choiceage) = explode(":", $choice);

				$choiceid = rand(1, $users[$choiceage][$choicesex]);

				$db->prepare_query("SELECT picid FROM picsvotable WHERE sex = ? && age = # && id = #", $choicesex, $choiceage, $choiceid);
			}while(!$db->numrows() && --$i ); //shouldn't be needed

			if(!$db->numrows()){
				incHeader(true,array('incTextAdBlock','incSortBlock'));

				echo "There are no votable pictures in that age and sex range";

				incFooter();
				exit;
			}

			$picid = $db->fetchfield();

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

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14)
				$sort['minage'] = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);

			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] > 80)
				$sort['maxage'] = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 80);

			$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS id FROM picstop WHERE sex IN (?) && age IN (#) ORDER BY score DESC LIMIT " . ($page*$config['picsPerPage']) . ", $config[picsPerPage]", $sexes, range($sort['minage'], $sort['maxage']));

			$data = array();
			while($line = $db->fetchrow())
				$data[]=$line['id'];

			$db->query("SELECT FOUND_ROWS()");
			$totalrows = $db->fetchfield();

			$numpages =  ceil($totalrows / $config['picsPerPage']);
			if($page>=$numpages) $page=0;

			displayList($data,'top', $numpages); //exit

			break;
		case "searchname":
		case "Go":
			if(empty($sort['user'])){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "You must specify a username";
				incFooter();
				exit();
			}

			$result = $db->prepare_query("SELECT userid, firstpic FROM users WHERE username = ?", $sort['user']);

			if($db->numrows()){
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

			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex']!='Both')){
				if($userData['loggedIn']){
					$sort['sex']=$userData['defaultsex'];
				}else
					$sort['sex']="Both";
			}
			$sex = $sort['sex'];
			if($sex == 'Both')
				$sex = array("Male","Female");

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14)
				$sort['minage'] = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);

			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] > 80)
				$sort['maxage'] = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 30);

			$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS userid FROM newestusers WHERE sex IN (?) && age IN (#) ORDER BY id DESC LIMIT " . ($page*$config['picsPerPage']) . ", $config[picsPerPage]", $sex, range($sort['minage'], $sort['maxage']));

			$data = array();
			while($line = $db->fetchrow())
				$data[]=$line['userid'];

			$db->query("SELECT FOUND_ROWS()");
			$totalrows = $db->fetchfield();

			$numpages = ceil($totalrows / $config['picsPerPage']);
			if($page>=$numpages) $page=0;

			displayList($data,'newest', $numpages); //exit

			break;
		case "bday":

			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex']!='Both')){
				if($userData['loggedIn']){
					$sort['sex']=$userData['defaultsex'];
				}else
					$sort['sex']="Both";
			}
			$sex = $sort['sex'];
			if($sex == 'Both')
				$sex = array("Male","Female");

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14)
				$sort['minage'] = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);

			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] > 80)
				$sort['maxage'] = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 30);

			$db->prepare_query("SELECT SQL_CALC_FOUND_ROWS userid FROM bday WHERE sex IN (?) && age IN (#) LIMIT " . ($page*$config['picsPerPage']) . ", $config[picsPerPage]", $sex, range($sort['minage'], $sort['maxage']));

			$data = array();
			while($line = $db->fetchrow())
				$data[]=$line['userid'];

			$db->query("SELECT FOUND_ROWS()");
			$totalrows = $db->fetchfield();

			$numpages = ceil($totalrows / $config['picsPerPage']);
			if($page>=$numpages) $page=0;

			if($totalrows == 0){
				incHeader(true, array('incSortBlock'));
				echo "No one has their birthday today";
				incFooter();
				exit;
			}

			displayList($data,'bday', $numpages); //exit

			break;



		case "mypage":
			if($userData['loggedIn'])
				displayUser($userData['userid'], 0 );//exit
	//else random
		case "search":
		case "random":
		default: //random user


		case "New Search":
			updateStats(); //needed to set $siteStats[userstotal], $siteStats[userswithpics], $siteStats[userclusters], userswithsignpics

			$tables = array();
			$factors = array();
			$where = array();

			$tables['usersearch'] = 'usersearch';
//			$tables['usersearch'] = 'usersearch FORCE INDEX (`PRIMARY`)';
//			$tables['usersearch'] = 'usersearch FORCE INDEX (`id-age-sex`)';
			$table = 'usersearch';


		//sex
			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex'] != 'Both')){
				if($userData['loggedIn']){
					$sort['sex'] = $userData['defaultsex'];
				}else
					$sort['sex'] = "Both";
			}

			$sexes = ($sort['sex'] == 'Both' ? array('Male','Female') : array($sort['sex']) );

			$where[] = ($sort['sex'] == "Both" ? "$table.sex IN ('Male','Female')" : "$table.sex = '$sort[sex]'");

		//age
			if(empty($sort['minage']) || !($sort['minage'] = intval($sort['minage'])) || $sort['minage'] < 14 || $sort['minage'] > 80)
				$sort['minage'] = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);

			if(empty($sort['maxage']) || !($sort['maxage'] = intval($sort['maxage'])) || $sort['maxage'] < 14 ||$sort['maxage'] > 80)
				$sort['maxage'] = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 25);

			$minage = min($sort['minage'], $sort['maxage']);
			$maxage = $sort['maxage'] = max($sort['minage'], $sort['maxage']);
			$sort['minage'] = $minage;

			$where[] = $db->prepare("$table.age IN (#)", range($sort['minage'], $sort['maxage']));

			$numagesex = getNumUsersInAgeSexCol($sexes, $minage, $maxage, 'total');

			$factors['agesex'] = (double)$numagesex/$siteStats['userstotal'];


		//active
			if(!isset($sort['active']))
				$sort['active'] = 1;

			switch($sort['active']){
				case 0: //all
					$where[] = "$table.active IN (0,1,2)";
					break;

				case 2: //online
					$where[] = "$table.active = 2";

					$factors['online'] = (double)$siteStats['online']/($siteStats['userstotal']/2);//0.1; //get REAL value

					break;

				case 1:
				default://recent
					$where[] = "$table.active IN (1,2)";
					break;
			}

		//pic
			if(!isset($sort['pic']))
				$sort['pic'] = 1;

			switch($sort['pic']){
				case 0: //everyone
					$where[] = "$table.pic IN (0,1,2)";
					break;

				case 2: //verified
					$where[] = "$table.pic = 2";
					break;

				case 1:
				default: //pics
					$where[] = "$table.pic IN (1,2)";
					break;
			}


		//pic/active factor relative to age/sex
			$col = "";
			if($sort['active'] >= 1)
				$col .= "active";

			if($sort['pic'] == 1){
				$col .= "pics";
			}elseif($sort['pic'] == 2){
				$col .= "signpics";
			}

			if($col){
				$num = getNumUsersInAgeSexCol($sexes, $minage, $maxage, $col);
				$factors[$col] = ($numagesex ? (double)$num/$numagesex : 0);
			}

		//single, factore relative to age/sex
			if(isset($sort['single'])){
				$sort['single'] = 1;
				$where[] = "$table.single = 1";
				$num = getNumUsersInAgeSexCol($sexes, $minage, $maxage, 'single');
				$factors['single'] = ($numagesex ? (double)$num/$numagesex : 0);
			}else{
				$where[] = "$table.single IN (0,1)";
			}


		//sexuality, factore relative to age/sex
			if(!empty($sort['sexuality']) && ($sort['sexuality'] = intval($sort['sexuality']))){

				if(in_array($sort['sexuality'], range(1,3))){
					$where[] = $db->prepare("$table.sexuality = ?", $sort['sexuality']);

					$num = getNumUsersInAgeSexCol($sexes, $minage, $maxage, "sexuality" . $sort['sexuality']);
					$factors['sexuality'] = (double)$num/$numagesex;
				}
			}

		//loc
			if(!empty($sort['loc']) && $locations->isValidCat($sort['loc'])){
				$locbranch = $locations->makeBranch($sort['loc']);

				$locs = array();
				$locs[] = $sort['loc'];
				foreach($locbranch as $loc)
					$locs[] = $loc['id'];

				$num = getNumUsersInLocs($locs);

				$factors['loc'] = (double)$num/$siteStats['userstotal'];

				$where[] = $db->prepare("$table.loc IN (#)", $locs);
			}else{
				$sort['loc'] = 0;
			}

		//interests
			if(!empty($sort['interest']) && ($sort['interest'] = intval($sort['interest'])) && $interests->isValidCat($sort['interest'])){

				$num = $cache->get("interestnum-$sort[interest]");

				if($num === false){
					$db->prepare_query("SELECT users FROM interests WHERE id = #", $sort['interest']);
					$num = $db->fetchfield();

					$cache->put("interestnum-$sort[interest]", $num, 86400);
				}

				$factors['interests'] = (double)$num/($siteStats['userstotal']/4);

				$tables['userinterests'] = 'userinterests';

				$where[] = "userinterests.userid = usersearch.userid";
				$where[] = $db->prepare("userinterests.interestid = #", $sort['interest']);
			}else{
				$sort['interest'] = 0;
			}




/*
		//friends of friends

		//might need to remove the age/sex/loc factors
		//as they likely aren't relevant for friends
		//might be best to cache the friendslists, then base it on that.

		//disable friends search with online?
		//then do the full search (ie not range limited)
		//makes it easier to cache that way

		//part of the advanced search??

//max rows = 250^2 = 62500
//realistic max = 10000



			if($userData['loggedIn'] && !empty($sort['friends'])){
				$tables[] = "friends AS f1";
				$tables[] = "friends AS f2";

				$where[] = $db->prepare("f1.userid = #", $userData['userid']);
				$where[] = "f1.friendid = f2.userid";
				$where[] = "f2.friendid = $table.userid";
			}
*/

//fraction of users that are likely to match this search
			$factor = 1.0;
			foreach($factors as $val)
				$factor *= $val;



			if($factor == 0){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "No results found. Please broaden your search.";

				if($userData['loggedIn'] && in_array($userData['userid'], $debuginfousers)){
					echo "<br>\n";
					print_r($sort);
					echo "<br>\n";
					print_r($factors);
				}

				incFooter();
				exit();
			}

			$desiredresults = 10; //really 1, but gotta account for the uneveness in the database
			if(!empty($sort['list']))
				$desiredresults = $config['picsPerPage'];//*1.5;

			$estimatedresults = ceil($siteStats['userstotal'] * $factor);

			$advanced = ($userData['loggedIn'] && $userData['premium'] && ((!empty($sort['namescope']) && !empty($sort['user']) && isset($namescopes[$sort['namescope']])) || ($userData['loggedIn'] && !empty($sort['friends']))));

			if(!$advanced){ //basic search
				$numgroups = ceil($estimatedresults / $desiredresults);

				$estimatedpergroup = floor((double)$estimatedresults/$numgroups);


				$page = getREQval('page', 'int', -1);

				if($page < 0 || $page - 1 > $numgroups){
					randomize();
					$groupnum = rand(1,$numgroups);
					$page = $groupnum - 1;
				}else{
					$groupnum = $page + 1;
				}
				$numpages = $numgroups;


				$minid = floor($siteStats['userstotal']*($groupnum-1)/$numgroups)+1;
				$maxid = ceil($siteStats['userstotal']*$groupnum/$numgroups);

				if($minid > 1 && $maxid < $siteStats['userstotal'])
					$where[] = "$table.id BETWEEN $minid AND $maxid";
				elseif($minid > 1)
					$where[] = "$table.id >= $minid";
				elseif($maxid > $minid)
					$where[] = "$table.id <= $maxid";

				$data = false; //simulate a cache miss

			}else{ //advanced search

/*				if($estimatedresults > 100000){
					$msgs->addMsg("Search is too broad. Please restrict your search parameters");
					advancedSearch(); //exit
				}
*/

				$cachename = "search-" . substr( base_convert(md5(serialize($sort)),16,36), 0, 12);
				$cachetime = ($sort['active'] == 2 ? 300 : 3600*3); // online -> 5min, all -> 3 hours

				$data = $cache->get($cachename);
				if($data)
					$data = explode(',', $data);

				if(!empty($sort['namescope']) && !empty($sort['user']) && in_array($sort['namescope'], array_keys($namescopes))){
					$tables['users'] = 'users';
					$where[] = "users.userid = $table.userid";
					switch($sort['namescope']){
						case "includes":	$where[] = "users.username LIKE '%" . $db->escape($sort['user']) . "%'";	break;
						case "ends": 		$where[] = "users.username LIKE '%" . $db->escape($sort['user']) . "'";		break;
						case "starts":
						default:			$where[] = "users.username LIKE '" . $db->escape($sort['user']) . "%'";		break;
					}
				}

			}


			if(!$data){
				$query = "SELECT $table.userid FROM " . implode(", ", $tables) . " WHERE " . implode(" && ", $where);

//				$db->prepare_query("INSERT INTO searchqueries SET time = #, sort = ?, query = ?", time(), str_replace("\n", '', var_export($sort, true)), $query);

				$time1 = gettime();
				$result = $db->query($query);
				$time2 = gettime();

				$data = array();
				while($line = $db->fetchrow($result))
					$data[$line['userid']]=0; //use as key to get rid of dupes from friends list search

				$data = array_keys($data); //flip to values. This creates new consecutive keys.
			}

			$numresults = count($data);
//*
	if($userData['loggedIn'] && in_array($userData['userid'], $debuginfousers)){
		echo "<table><tr><td bgcolor=#FFFFFF><tt>";


		foreach($factors as $factorname => $val)
			echo "$factorname: " . number_format($val, 5) . ", ";
		echo "factor: " . number_format($factor, 5) . "<br>\n";
		if($advanced){
			echo "estimated: $estimatedresults, found: $numresults<br>";
		}else{
			echo "groupnum: $groupnum, numgroups: $numgroups, rows per group: " . round($siteStats['userstotal']/$numgroups) . "<br>\n";
			echo "estimated: $estimatedresults, desired: $desiredresults, estimated per group: $estimatedpergroup, actual: $numresults<br>\n";
			echo "minid: $minid, maxid: $maxid<br>\n";
		}
		if(isset($query)){
			echo "$query<br>\n";
			echo "time: " . number_format(($time2-$time1)/10,4) . " milliseconds<br>\n";
			echo $db->explain($query);
		}

		echo "<tt></td></tr></table>";
	}
//*/
			if($numresults == 0){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "No results found. Please broaden your search or search again";
				incFooter();
				exit();
			}


			if(!$advanced){ //basic search
				if(!empty($sort['list'])){
					displayList($data,'users',$numpages); //exit
				}else{
					$uid = $data[rand(0,$numresults-1)];

					displayUser($uid, 0 ); //exit
				}
			}else{ // advanced search

				if(isset($query))
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

					displayList($data, 'users', $numpages); //exit
				}else{
					$uid = $data[rand(0,count($data)-1)];

					displayUser($uid, 0 ); //exit
				}
			}

			break;



	case "old search":

	die("this is the old search that doesn't work anymore");



			updateStats(); //needed to set $siteStats[userstotal], $siteStats[userswithpics], $siteStats[userclusters], userswithsignpics

			$tables = array();
			$tables['users'] = "users";
			$table = "users";
			$factors = array();
			$where = array();

			if(!$userData['loggedIn'])
				$where[] = "hideprofile = 'n'";

			if(empty($sort['sex']) || ($sort['sex'] != 'Male' && $sort['sex'] != 'Female' && $sort['sex']!='Both')){
				if($userData['loggedIn']){
					$sort['sex']=$userData['defaultsex'];
				}else
					$sort['sex']="Both";
			}

			if(empty($sort['minage']) || !is_numeric($sort['minage']) || $sort['minage'] < 14 || $sort['minage'] > 80)
				$sort['minage'] = ($userData['loggedIn'] ? $userData['defaultminage'] : 14);

			if(empty($sort['maxage']) || !is_numeric($sort['maxage']) || $sort['maxage'] < 14 ||$sort['maxage'] > 80)
				$sort['maxage'] = ($userData['loggedIn'] ? $userData['defaultmaxage'] : 30);

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
					$factors['signpic'] = (double)$siteStats['userswithsignpics']/$siteStats['userstotal'];
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

				$factors['agesex'] = (double)$num/$siteStats['userstotal'];

				if($sort['minage']>10 || $sort['maxage'] < 80)
					$where[] = $db->prepare("age IN (#)", range($sort['minage'], $sort['maxage']));

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

				$factors['loc'] = (double)$num/$siteStats['userstotal'];

//				$where[] = "loc IN ('" . implode("','", $locs) . "')";
				$where[] = $db->prepare("loc IN (#)", $locs);
//				$tables[] = "users";
			}else{
				$sort['loc'] = 0;
			}

			if(empty($sort['nopics'])){
				if(!empty($sort['online']))
					$factors['pics'] = 0.9;//(double)$siteStats['activeuserswithpics']/$siteStats['userstotal'];
				else
					$factors['pics'] = (double)$siteStats['userswithpics']/$siteStats['userstotal'];
				$where[] = "firstpic >= 1";
//				$where[] = "pic = 'y'";
//				$tables['users'] = "users";
			}else{
//				$where[] = "pic IN ('n','y')";
			}

//must be last option, overwrites $table
			if(!empty($sort['online'])){
				$factors['online'] = (double)$siteStats['online']/$siteStats['userstotal'];

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

			$estimatedresults = ceil($siteStats['userstotal'] * $factor);

			$advanced = ($userData['loggedIn'] && $userData['premium'] && ((!empty($sort['namescope']) && !empty($sort['user']) && isset($namescopes[$sort['namescope']])) || ($userData['loggedIn'] && !empty($sort['friends']))));

			if(!$advanced){ //basic search
				$numgroups = ceil($estimatedresults / $desiredresults);
				if($numgroups > $siteStats['userclusters'])
					$numgroups = $siteStats['userclusters'];

				$estimatedpergroup = floor($estimatedresults/$numgroups);

				if(!isset($_REQUEST['page']) || $page < 0 || $page - 1 > $numgroups){
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

				$data = false; //simulate a cache miss

			}else{ //advanced search

/*				if($estimatedresults > 100000){
					$msgs->addMsg("Search is too broad. Please restrict your search parameters");
					advancedSearch(); //exit
				}
*/
				$cachename = "search-" . substr( base_convert(md5(serialize($sort)), 16, 36), 0, 12);
				$cachetime = (empty($sort['online']) ? 1800 : 300); // online -> 5min, all -> 30min

				$data = $cache->get($cachename);

/*				if($data){
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

						displayList($data,'users', count($data)); //exit
					}else{
						$uid = $data[rand(0,count($data)-1)];

						displayUser($uid, 0 ); //exit
					}
				}
*/

				if(!empty($sort['namescope']) && !empty($sort['user']) && in_array($sort['namescope'], array_keys($namescopes))){
					switch($sort['namescope']){
						case "includes":	$where[] = "username LIKE '%" . $db->escape($sort['user']) . "%'";	break;
						case "ends": 		$where[] = "username LIKE '%" . $db->escape($sort['user']) . "'";	break;
						case "starts":
						default:			$where[] = "username LIKE '" . $db->escape($sort['user']) . "%'";	break;
					}
				}


/*
friend of friend search
max rows = 250^2 = 62500
realistic max = 10000

//put in a plus only user search?
*/
				if($userData['loggedIn'] && !empty($sort['friends'])){
					$tables[] = "friends AS f1";
					$tables[] = "friends AS f2";

					$where[] = $db->prepare("f1.userid = #", $userData['userid']);
					$where[] = "f1.friendid = f2.userid";
					$where[] = "f2.friendid = users.userid";
				}
			}


			if(!$data){
				$query = "SELECT $table.userid FROM " . implode(',',$tables) . " WHERE " . implode(" && ", $where);

//				$db->prepare_query("INSERT INTO searchqueries SET time = #, sort = ?, query = ?", time(), str_replace("\n", '', var_export($sort, true)), $query);

				$time1 = gettime();
				$result = $db->query($query);
				$time2 = gettime();

				$data = array();
				while($line = $db->fetchrow($result))
					$data[$line['userid']]=0; //use as key to get rid of dupes from friends list search

				$data = array_keys($data); //flip to values. This creates new consecutive keys.
			}

			$numresults = count($data);
//*
	if($userData['loggedIn'] && in_array($userData['userid'],$debuginfousers)){
		echo "<table><tr><td bgcolor=#FFFFFF><tt>";

		foreach($factors as $factorname => $val)
			echo "$factorname: " . number_format($val, 4) . ", ";
		echo "factor: " . number_format($factor, 4) . "<br>\n";
		if($advanced){
			echo "estimated: $estimatedresults, found: $numresults<br>";
		}else{
			echo "groupnum: $groupnum, numgroups: $numgroups, rows per group: " . round($siteStats['userstotal']/$numgroups) . ", total clusters: $siteStats[userclusters]<br>\n";
			echo "estimated: $estimatedresults, desired: $desiredresults, estimated per group: $estimatedpergroup, actual: $numresults<br>\n";
			echo "min cluster: $mincluster, max cluster: $maxcluster, minuserid: $minuserid, maxuserid: $maxuserid<br>\n";
		}
		echo "$query<br>\n";
		echo "time: " . number_format(($time2-$time1)/10,4) . " milliseconds<br>\n";
		echo $db->explain($query);

		echo "<tt></td></tr></table>";
	}
//*/
			if($numresults == 0){
				incHeader(true,array('incTextAdBlock','incSortBlock'));
				echo "No results found. Please broaden your search or search again";
				incFooter();
				exit();
			}


			if(!$advanced){ //basic search
				if(!empty($sort['list'])){
					displayList($data,'users',$numpages); //exit
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

					displayList($data, 'users', $numpages); //exit
				}else{
					$uid = $data[rand(0,count($data)-1)];

					displayUser($uid, 0 ); //exit
				}
			}

			break;
	}
}
	die("<font size=7>How did I get here???</font>");


/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////


function advancedSearch(){
	global $userData, $namescopes, $sort,$locations;

	if(!$userData['loggedIn'] || !$userData['premium'])
		return;

	$user = '';
	$namescope = 'starts';
	$loc='0';
	$sexuality = 0;
	$interest = '0';
	$active = 1;
	$pic = 1;

	if($userData['loggedIn'])	$minage = $userData['defaultminage'];
	else						$minage = 14;

	if($userData['loggedIn'])	$maxage = $userData['defaultmaxage'];
	else						$maxage = 30;

	if($userData['loggedIn'])	$sex = $userData['defaultsex'];
	else						$sex = "Both";

	if(!isset($sort) || !is_array($sort))
		$sort = array();

	extract($sort);


	$interests = & new category( $db, "interests");

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF]>";

	echo "<tr><td class=header colspan=2 align=center>Advanced User Search</td></tr>";

	echo "<tr><td class=body>Username:</td><td class=body><select class=body name=sort[namescope]>" . make_select_list_key($namescopes, $namescope) . "</select> <input class=side type=text name=sort[user] size=10 style=\"width:100px\" value='$user'></td></tr>";

	echo "<tr><td class=body>Age:</td><td class=body><input class=body name=sort[minage] value='$minage' size=1> to <input class=body name=sort[maxage] value='$maxage' size=1></td></tr>";
	echo "<tr><td class=body>Sex:</td><td class=body><select class=body name=sort[sex]><option value=Both>Both" . make_select_list(array("Male","Female"),$sex) . "</select></td></tr>";
	echo "<tr><td class=body>Location:</td><td class=body><select class=body name=sort[loc]><option value=0>Anywhere" . makeCatSelect($locations->makeBranch(),$loc) . "</select></td></tr>"; // <script src=http://images.nexopia.com/include/dynconfig/locs.js></script>
	echo "<tr><td class=body>Interests:</td><td class=body><select class=side name=sort[interest]><option value=0>Any Interests" . makeCatSelect($interests->makeBranch(), $interest) . "</select></td></tr>"; //<script src=http://images.nexopia.com/include/dynconfig/locs.js></script>
	echo "<tr><td class=body>Activity:</td><td class=body><select class=side name=sort[active]>" . make_select_list_key(array(0 => "All Users", 1 => "Active Recently", 2 => "Online"), $active) . "</select></td></tr>";
	echo "<tr><td class=body>Pictures:</td><td class=body><select class=side name=sort[pic]>" . make_select_list_key(array(0 => "All Users", 1 => "With Pictures", 2 => "With a Verified Picture"), $pic) . "</select></td></tr>";
	echo "<tr><td class=body>Sexuality:</td><td class=body><select class=body name=sort[sexuality]>" . make_select_list_key(array('Any',"Heterosexual","Homosexual","Bisexual/Open-Minded"),$sexuality) . "</select></td></tr>";


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


	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[single]', 'Single Users Only', !empty($single)) . "</td></tr>";
//	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[friends]', 'Friends of Friends Only', !empty($friends)) . "</td></tr>";
	echo "<tr><td class=body colspan=2>" . makeCheckBox('sort[list]', 'Show List', !empty($list)) . "</td></tr>";
	echo "<tr><td class=body colspan=2 align=center><input class=body type=submit name=sort[mode] value=Search></td></tr>";

	echo "</form></table>";

	incFooter();
	exit;
}

function displayUser($uid,$picid=0){ // either userid and picnum, or userid or picid
	global $config, $sort, $userData, $skindir, $db, $cache, $mods, $locations;

	if($uid==0 && $picid==0)
		return false;
	if($uid==0){ //gives picid
		$result = $db->prepare_query("SELECT itemid, priority FROM pics WHERE id = #", $picid);

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


//get info
	$db->prepare_query("SELECT userid, username, frozen, dob, age, sex, loc, email, showbday, jointime, showjointime, activetime, showactivetime, profileupdatetime, premiumexpiry, showpremium, signpic, enablecomments, journalentries, gallery, onlyfriends, firstpic, profile, views, online, hideprofile, abuses FROM users WHERE userid = #", $uid);
	$user = $db->fetchrow();

	if(!$user || ($user['frozen'] == 'y' && !($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))){
		incHeader(true,array('incTextAdBlock','incSortBlock'));
		echo "No results Found";
		incFooter();
		exit;
	}

	$user['plus'] = $user['premiumexpiry'] > time();

//ignored user
	if($user['plus'] && $user['hideprofile'] =='y' && (!$userData['loggedIn'] || isIgnored($uid, $userData['userid'], '', 0, true))){
		incHeader();

		if($userData['loggedIn'])
			echo "This user is ignoring you.";
		else
			echo "You must <a class=body href=login.php?referer=profile.php?uid=$uid>login</a> to see this user's profile.";

		incFooter();
		exit;
	}

//update profile views
	if($userData['loggedIn']){
		$set = "";

		global $profviewsdb;

		if($userData['userid'] != $uid){
			$profviewsdb->prepare_query($uid, "INSERT IGNORE INTO profileviews SET hits = 1, time = #, userid = #, viewuserid = #", time(), $uid, $userData['userid']);
			if($profviewsdb->affectedrows()){
				$set = "views = views + 1";
				$user['views']++;
			}else{
				$profviewsdb->prepare_query($uid, "UPDATE profileviews SET hits = hits + 1, time = # WHERE userid = # && viewuserid = #", time(), $uid, $userData['userid']);
			}

			$profviewsdb->close();

		}elseif($userData['newcomments']){ // $userData['userid'] == $uid
			$set = "newcomments = '0'";
			$userData['newcomments']=0;
			$cache->put(array($userData['userid'], "newcomments-$userData[userid]"), 0, $config['maxAwayTime']);
		}
		if($set != "")
			$db->prepare_query("UPDATE users SET $set WHERE userid = #", $uid);
	}

//get profile text
	$user2 = $cache->get(array($uid, "profile-$uid")); //changes aren't noticed

	if(!$user2){
		global $profiledb;

		$profiledb->prepare_query("SELECT msn, icq, yahoo, aim, about, likes, dislikes, skin FROM profile WHERE userid = #", $uid);
		$user2 = $profiledb->fetchrow();

		$user2['nabout'] = nl2br(wrap(parseHTML(smilies($user2['about']))));
		$user2['nlikes'] = nl2br(wrap(parseHTML(smilies($user2['likes']))));
		$user2['ndislikes'] = nl2br(wrap(parseHTML(smilies($user2['dislikes']))));

		unset($user2['about'], $user2['likes'], $user2['dislikes']);

		$cache->put(array($uid, "profile-$uid"), $user2, 86400*7);
	}

	$user += $user2;
	unset($user2);

	$friends = getFriendsList($uid);

	if($userData['loggedIn'] && $userData['premium'] && $userData['userid'] != $uid && count($friends))
		$myfriends = getFriendsList($userData['userid']);

	$isFriend = $userData['loggedIn'] && (isset($friends[$userData['userid']]) || $userData['userid']==$user['userid']);

//start output
	incHeader(600,array('incSortBlock','incSkyAdBlock','incTopGirls','incTopGuys','incNewestMembersBlock','incRecentUpdateProfileBlock'));

	if($user['plus'] && $user['skin']){
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

		$skindata = $cache->get(array($user['skin'], "profileskin-$user[skin]"));

		if(!$skindata){
			$db->prepare_query("SELECT data FROM profileskins WHERE id = #", $user['skin']);
			$skindata = decodeSkin($db->fetchfield());

			$cache->put(array($user['skin'], "profileskin-$user[skin]"), $skindata, 86400*7);
		}


echo <<<END
<style>

td.body			{ background-color: #$skindata[bodybg]; color: #$skindata[bodytext]; font-family: arial; font-size: 8pt}
a.body:active,
a.body:link,
a.body:visited	{ color: #$skindata[bodylink]; font-family: arial; font-size: 8pt }
a.body:hover	{ color: #$skindata[bodyhover]; font-family: arial; font-size: 8pt }

td.body2		{ background-color: #$skindata[bodybg2]; color: #$skindata[bodytext]; font-family: arial; font-size: 8pt}

td.header			{ background-color: #$skindata[headerbg]; color: #$skindata[headertext]; font-family: arial; font-size: 8pt}
a.header:active,
a.header:link,
a.header:visited	{ color: #$skindata[headerlink]; font-family: arial; font-size: 8pt }
a.header:hover		{ color: #$skindata[headerhover]; font-family: arial; font-size: 8pt }

td.online		{ background-color: #$skindata[bodybg]; color: #$skindata[online]; font-family: arial; font-size: 16pt; font-weight: bolder}
td.offline		{ background-color: #$skindata[bodybg]; color: #$skindata[offline]; font-family: arial; font-size: 16pt; font-weight: bolder}

</style>
END;

	}

echo "<script src=$config[imgserver]/skins/profile.js></script>";

echo "<table border=0 width=100% align=center cellspacing=0 cellpadding=0>\n";

	$cols=2;
	if($user['enablecomments']=='y')
		$cols++;
	if(	$user['journalentries'] == WEBLOG_PUBLIC ||
		($user['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($user['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		$cols++;
	if($user['plus'] && ($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend)))
		$cols++;

	$width = 100.0/$cols;

	echo "<tr>";
	echo "<td class=body colspan=2>";
	echo "<table width=100%>";
	echo "<td class=header align=center width=$width%><a class=header href=\"profile.php?uid=$user[userid]\"><b>Profile</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=\"usercomments.php?id=$user[userid]\"><b>Comments</b></a></td>";
	if($user['plus'] && ($user['gallery']=='anyone' || ($user['gallery']=='loggedin' && $userData['loggedIn']) || ($user['gallery']=='friends' && $isFriend)))
		echo "<td class=header align=center width=$width%><a class=header href=\"gallery.php?uid=$user[userid]\"><b>Gallery</b></a></td>";
	if(	$user['journalentries'] == WEBLOG_PUBLIC ||
		($user['journalentries'] == WEBLOG_LOGGEDIN && $userData['loggedIn']) ||
		($user['journalentries'] == WEBLOG_FRIENDS && $isFriend))
		echo "<td class=header align=center width=$width%><a class=header href=weblog.php?uid=$user[userid]><b>Blog</a></td>";
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

	echo "<tr><td class=header align=center width=$width%><a class=header href=\"friends.php?action=add&id=$user[userid]" . ($userData['loggedIn'] ? "&k=" . makekey($user['userid']) : '' ) . "\"><b>Add as Friend</b></a></td>";
	if(!$ignored);
		echo "<td class=header align=center width=$width%><a class=header href=\"messages.php?action=write&to=$user[userid]\"><b>Send Message</b></a></td>";
	if($user['enablecomments']=='y')
		echo "<td class=header align=center width=$width%><a class=header href=usercomments.php?id=$user[userid]#reply><b>Add Comment</a></td>";
	echo "<td class=header align=center width=$width%><a class=header href=reportabuse.php?type=" . MOD_USERABUSE . "&id=$user[userid]><b>Report Abuse</b></a></td></tr>";
	echo "</table>";


	echo "</td></tr>";

echo 	"<tr>\n";
echo 		"<td valign=top width=80 class=body>\n";


echo 			"<table border=0 width=100% height=100%>\n";

//Status
	echo "<tr><td align=center class=" . ($user['online']=='y' ? "online>Online" : "offline>Offline") . "<br></td></tr>";

	echo "<tr><td class=body align=center><b>" . number_format($user['views']) . " Hits</b><br>";
	if($user['showpremium'] == 'y' && $user['plus'])
		echo "<a class=body href=product.php?id=1>Plus Member</a><br>";
	if($user['signpic'] == 'y')
		echo "<a class=body href=faq.php?cat=1&q=66>Verified User</a><br>";
	if($mods->isAdmin($uid, 'visible'))
		echo "Administrator<br>";
	echo "&nbsp;</td></tr>";


//admin
	if($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')){
		echo "<tr><td class=header><b>Admin</b></td></tr>";
		echo "<tr><td class=body>";

		if($user['frozen'] == 'y')
			echo "<b>Account Frozen!</b><br><br>";

		global $abuselog;

//		$abuselog->db->prepare_query("SELECT count(*) FROM abuselog WHERE userid = #", $uid);
//		$abuseentries = $abuselog->db->fetchfield();

		echo "<a class=body href=/adminuser.php?type=userid&search=$uid>User Search</a><br>";
		echo "<a class=body href=/adminuserips.php?uid=$uid&type=userid>IP Search</a><br>";
		if($mods->isAdmin($userData['userid'],"loginlog"))
			echo "<a class=body href=/adminloginlog.php?col=user&val=$uid>Logins</a><br>";
		echo "<a class=body href=/adminabuselog.php?uid=" . urlencode($user['username']) . ">Abuse: $user[abuses]</a><br>";
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
	if($userData['loggedIn'] && $userData['premium'] && $userData['userid'] != $uid){
		$friendsInCommon = 0;
		foreach($friends as $friendid => $username){
			if(isset($myfriends[$friendid])){
				echo "<a class=body href=profile.php?uid=$friendid><b>$username</b></a><br>";
				$friendsInCommon++;
			}else{
				echo "<a class=body href=profile.php?uid=$friendid>$username</a><br>";
			}
		}
	}else{
		foreach($friends as $friendid => $username)
			echo "<a class=body href=profile.php?uid=$friendid>$username</a><br>";
	}
	echo "</td></tr>";

//End Friends list

echo 			"</table>";
echo 		"</td>\n";
echo 		"<td class=body valign=top>\n";
echo 			"<table border=0 cellspacing=0 width=100%>\n";
echo 				"<tr>";
echo 					"<td class=body align=center>";

//Name, Age, Sex,vote


	echo "<font size=3><b>$user[username] ($user[age] year old $user[sex])</b></font>";



//End Name, Age, Sex,vote

echo 					"</td>";
echo 				"</tr>\n";
echo 				"<tr>";
echo 					"<td align=center class=body>\n";

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
//		echo "setPicLoc(\"$config[picloc]\");";

		$pics = $cache->get(array($uid, "pics-$uid"));

		if(!$pics){
			$db->prepare_query("SELECT id,description, priority FROM pics WHERE itemid = #", $uid);

			$pics = array();
			while($line = $db->fetchrow())
				$pics[$line['priority']] = $line;

			ksort($pics);

			$cache->put(array($uid, "pics-$uid"), $pics, 86400*7);
		}

		foreach($pics as $line)
			echo "addPic('$line[id]','http://" . chooseImageServer($line['id']) . $config['picdir'] . floor($line['id']/1000) . "/$line[id].jpg','" . addslashes($line['description']) . "',0);";

		echo "changepic(" . ($picnum-1) . ");";
		echo "</script>";
	}else{
		echo "<script>setUserid($uid);</script>";
		echo "No pic available";
	}



//End Pic

echo 					"</td>";
echo 				"</tr>\n";
echo 				"<tr>";
echo 					"<td class=body valign=top>\n";

//profile

	$classes = array('body2','body');
	$i=0;

	echo "<table border=0 width=100%>\n";

	echo "<tr><td colspan=2 class=header align=center><b>Basics</b></td></tr>\n";

	echo "<tr><td class=" . $classes[$i = !$i] . " width=30%><b>Username:</b></td><td class=" . $classes[$i] . " width=70%>$user[username]</td></tr>\n";
	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Age:</b></td><td class=" . $classes[$i] . ">$user[age]</td></tr>\n";
	if($user['showbday']=='y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Date of Birth:</b></td><td class=" . $classes[$i] . ">" . gmdate("F j, Y",$user['dob']) ."</td></tr>\n";
	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Sex:</b></td><td class=" . $classes[$i] . ">$user[sex]</td></tr>\n";
//	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Location:</b></td><td class=" . $classes[$i] . ">" . $locations->getCatName($user['loc']) . "</td></tr>\n";

	$locs = $locations->makeroot($user['loc']);

	$locnames = array();
	foreach($locs as $loc)
		$locnames[] = $loc['name'];
	array_shift($locnames); //get rid of Home

	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Location:</b></td><td class=" . $classes[$i] . ">" . implode(" > ", $locnames) . "</td></tr>\n";

	if($userData['loggedIn'] && $userData['premium'] && $userData['userid'] != $uid)
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Friends in Common:</b></td><td class=" . $classes[$i] . ">$friendsInCommon</td></tr>\n";

	if($user['showjointime'] == 'y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Join Date:</b></td><td class=" . $classes[$i] . ">" . userdate("M j, Y g:i:s a",$user['jointime']) . "</td></tr>\n";

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
			}elseif($deltat < 3600*1.5){
				echo "1 hour ago";
			}elseif($deltat < 86400){
				echo round($deltat/3600) . " hours ago";
			}elseif($deltat < 86400*30){
				echo round($deltat/86400) . " days ago";
			}else{
				$months = round($deltat/(86400*30.5));
				echo "$months month" . ($months > 1 ? "s" : "") . " ago";
			}
		}
		echo "</td></tr>\n";
	}

	if($userData['loggedIn']){
		if($mods->isAdmin($userData['userid'],'listusers')){
			echo "<tr><td colspan=2 class=header align=center><b>Admin</b></td></tr>\n";

			echo "<tr><td class=" . $classes[$i = !$i] . "><b>Send Email:</b></td><td class=" . $classes[$i] . "><a class=body href=\"mailto:$user[email]\">$user[email]</a></td></tr>\n";
			if($user['plus']){
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>Plus Time remaining:</b></td><td class=" . $classes[$i] . ">" . number_format(($user['premiumexpiry'] - time())/86400,2) . " Days</td></tr>\n";
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>Plus Expiry Date:</b></td><td class=" . $classes[$i] . ">" . userDate("F j, Y, g:i a", $user['premiumexpiry']) . "</td></tr>\n";
			}
		}

		if(!empty($user['icq']) || !empty($user['msn']) || !empty($user['yahoo']) || !empty($user['aim'])){

			echo "<tr><td colspan=2 class=header align=center><b>Contact</b></td></tr>\n";

			if($isFriend || $mods->isAdmin($userData['userid'],'listusers')){
				if(!empty($user['icq']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>ICQ:</b></td><td class=" . $classes[$i] . ">$user[icq]</td></tr>\n";
				if(!empty($user['msn']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>MSN:</b></td><td class=" . $classes[$i] . ">$user[msn]</td></tr>\n";
				if(!empty($user['yahoo']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>Yahoo:</b></td><td class=" . $classes[$i] . ">$user[yahoo]</td></tr>\n";
				if(!empty($user['aim']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>AIM:</b></td><td class=" . $classes[$i] . ">$user[aim]</td></tr>\n";
			}else{
				if(!empty($user['icq']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>ICQ:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
				if(!empty($user['msn']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>MSN:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
				if(!empty($user['yahoo']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>Yahoo:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
				if(!empty($user['aim']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>AIM:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
			}
		}
	}



	global $profile;

	$prof = decodeProfile($user['profile']);

	$first = true;
	foreach($profile as $qnum => $val){
		if($prof[$qnum] != '0'){
			if($first){
				$first = false;
				echo "<tr><td colspan=2 class=header align=center><b>Profile</b></td></tr>\n";
			}
			echo "<tr><td class=" . $classes[$i = !$i] . "><b>$val[question]:</b></td><td class=" . $classes[$i] . ">" . $val['answers'][$prof[$qnum]] . "</td></tr>\n";
		}
	}






	$userinterests = $cache->get(array($uid, "userinterests-$uid"));

	if($userinterests === false){
		$db->prepare_query("SELECT interestid FROM userinterests WHERE userid = #", $uid);

		$userinterests = array();
		while($line = $db->fetchrow())
			$userinterests[] = $line['interestid'];

		$userinterests = implode(',', $userinterests); //could be blank

		$cache->put(array($uid, "userinterests-$uid"), $userinterests, 86400);
	}

	if($userinterests){
		$userinterests = explode(',', $userinterests);

		echo "<tr><td colspan=2 class=header align=center><b>Interests</b></td></tr>\n";


		global $interests;

		$cats = $interests->makebranch(); //only main categories

		$first = true;
		$subcats = array();
		foreach($cats as $item){
			if(!in_array($item['id'], $userinterests))
				continue;

			if($item['depth'] == 1){
				if(!$first)
					echo implode(", ", $subcats) . "</td></tr>";
				$first = false;
				$subcats = array();

				echo "<tr><td class=" . $classes[$i = !$i] . " colspan=2>";
				echo "<b>$item[name]:</b> ";
			}else{
				$subcats[] = $item['name'];
			}
		}
		echo implode(", ", $subcats) . "</td></tr>";

/*
		$first = true;
		$cats = $interests->makebranch(0,1); //only main categories

		foreach($cats as $item){
			echo "<tr><td class=" . $classes[$i = !$i] . " colspan=2><b>$item[name]:</b> ";

			$subcats = $interests->makebranch($item['id'],1); //only main categories

			for($i = 0; $i < $total; $i++)
				if(in_array($i,
				echo $subcats[$i]['name'];

			echo "</td></tr>\n";
		}
*/
	}






	if($user['nabout']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>About Me</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[nabout]</td></tr>\n";
	}

	if($user['nlikes']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>Likes</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[nlikes]</td></tr>\n";
	}

	if($user['ndislikes']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>Dislikes</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[ndislikes]</td></tr>\n";
	}

	echo "</table>\n";



//End profile

echo 					"</td>";
echo 				"</tr>";
echo 			"</table>\n";

echo 		"</td>";
echo 	"</tr>\n";

//start comments
	if($user['enablecomments']=='y'){

echo 	"<tr>";
echo 		"<td colspan=3 class=body>\n";




	echo "<table width=100% cellpadding=3>";
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=usercomments.php?id=$user[userid]>Add/Display Comments</a></b></td></tr>\n";

	if($userData['loggedIn']){
		$comments = $cache->get(array($uid, "comments5-$uid"));

		if($comments === false){
			global $usercomments;
			$usercomments->db->prepare_query("SELECT author, authorid, usercomments.time, nmsg FROM usercomments, usercommentstext WHERE itemid = # && usercomments.id = usercommentstext.id ORDER BY usercomments.id DESC LIMIT 5", $uid);

			$comments = array();
			while($line = $usercomments->db->fetchrow())
				$comments[] = $line;

			$cache->put(array($uid, "comments5-$uid"), $comments, 86400);
		}

		if(count($comments)){
			foreach($comments as $line){
				echo "<tr><td class=header>By: ";

				if($line['authorid'])	echo "<a class=header href=profile.php?uid=$line[authorid]>$line[author]</a>";
				else					echo "$line[author]";

				echo "</td><td class=header>Date: " . userdate("M j, Y g:i:s a",$line['time']) . "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class=body colspan=2>";

				echo $line['nmsg'] . "&nbsp;";

				echo "</td></tr>\n";
		//		echo "<tr><td colspan=2 class=header2>&nbsp;</td></tr>";
			}
		}else{
			echo "<tr><td class=body colspan=2 align=center>No Comments</td>\n";
		}
	}else{
		echo "<tr><td class=body colspan=2 align=center>You must be logged in to see comments.</td>\n";
	}
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=usercomments.php?id=$user[userid]>Add/Display Comments</a></b></td></tr>\n";
	echo "</table>\n";


echo 		"</td>";
echo 	"</tr>";
	}
//End comments

echo "</table>\n";



	incFooter();
	exit;
}

function ratePic($picid){
	global $sort,$db,$config,$skindir, $userData;

//	$db->prepare_query("SELECT users.userid, username, users.age, users.sex, description FROM users,pics WHERE pics.id = # && users.userid=pics.itemid && pics.vote = 'y'", $picid);
	$db->prepare_query("SELECT itemid as userid, age, sex, description FROM pics WHERE id = # && vote = 'y'", $picid);

	if(!$db->numrows())
		choosePic(); //pic or user deleted, or no longer votable. Find a new pic

	$user = $db->fetchrow();

	$user['username'] = getUserName($user['userid']);

//start output
	incHeader(true,array('incTextAdBlock','incSortBlock','incPrevVoteBlock'));


	echo "<script src=$config[imgserver]/skins/profile.js></script>";
	echo "<script>";

	$sortvals = array();
	foreach($sort as $k => $v)
		$sortvals[] = "sort[$k]=$v";
	$sortprefix = implode("&", $sortvals);

	$votetime = time();
	$votekey = 0;
	if($userData['loggedIn'])
		$votekey = makeKey("$picid:$votetime");

	echo "setVoteLink(\"/profile.php?$sortprefix&votekey=$votekey&votetime=$votetime\");";
//	echo "setSkinDir(\"$skindir\");";

	echo "addPic('$picid','','1');";

	echo "selectedpic=0;";
	echo "</script>";


	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]>";
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

	echo "<a class=body href=profile.php?picid=$picid><img src=http://" . chooseImageServer($picid) . $config['picdir'] . floor($picid/1000) . "/$picid.jpg border=0></a><br>";
	echo $user['description'];
	echo "<br><br></td></tr>";


	echo "</table>";

	incFooter();
	exit;
}


function displayList($list,$mode,$numpages=0){ // array of userid's,sort array
	global $config, $sort, $page, $sortlist, $sortt, $sortd, $db, $locations, $profiledb;


	$rows = array();
	$uids = array();
	$taglines = array();

	if(count($list)){
		$query = "SELECT users.userid, username, users.age, users.sex, loc, firstpic, online";
		if($mode == 'top')
			$query .= ", pics.id as picid, pics.score FROM pics, users WHERE pics.id IN (#) && users.userid = pics.itemid && frozen = 'n'";
		else // mode = users,random,newest,bday
			$query .= " FROM users WHERE userid IN (#) && frozen = 'n'";

		$db->prepare_query($query, $list);

		while($line = $db->fetchrow()){
			$rows[] = $line;
			$uids[$line['userid']] = $line['userid'];
		}


		$profiledb->prepare_query($uids, "SELECT userid, ntagline FROM profile WHERE userid IN (#)", $uids);

		while($line = $profiledb->fetchrow())
			$taglines[$line['userid']] = $line['ntagline'];

		if(count($rows)){
			if($mode=='top')
				sortCols($rows, SORT_DESC, SORT_NUMERIC, 'score');
			else
				sortCols($rows, SORT_ASC, SORT_CASESTR, 'username');
		}
	}


	incHeader(0,array('incTextAdBlock','incSortBlock'));

	if($mode!='users' && $mode!='random' && $mode!='top' && $mode!='newest' && $mode!='bday')
		die("bad mode");

	$cols = 6;
	if($config['votingenabled'] && $mode=='top')
		$cols++;

	echo "<table width=600 cellspacing=1 cellpadding=2 align=center>\n";

	if($mode == 'top' || $mode == 'newest' || $mode == 'bday'){
		echo "<form action=$_SERVER[PHP_SELF]>";
		echo "<input type=hidden name=sort[mode] value=$sort[mode]>";
		echo "<tr><td class=header colspan=$cols align=center>";
		if($mode == 'top')		echo "Top ";
		if($mode == 'newest')	echo "Newest ";
		echo "<select class=body name=sort[sex]>" . make_select_list(array("Male","Female"),$sort['sex']) . "</select>";
		echo " Age <input class=body type=text name=sort[minage] size=1 value=$sort[minage]>";
		echo " to <input class=body type=text name=sort[maxage] size=1 value=$sort[maxage]>";
		echo " <input class=body type=submit value=' Go '>";
		echo "</td></tr>";
		echo "</form>";

		if(count($list) == 0){
			echo "<tr><td class=body colspan=$cols align=center>";
			echo "No results found in that age range.";
			echo "</td></tr>";
			echo "</table>";

			incFooter();
			exit;
		}
	}

	echo "<tr>\n";
		echo "<td class=header width=$config[thumbWidth]></td>\n";
		echo "<td class=header>Username</td>\n";
		echo "<td class=header align=center>Age</td>\n";
		echo "<td class=header align=center>Sex</td>\n";
		echo "<td class=header>Location</td>\n";
		echo "<td class=header align=center>Online</td>\n";
		if($config['votingenabled'] && $mode=='top')
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
			echo "<img src=\"http://" . chooseImageServer($user['firstpic']) . $config['thumbdir'] . floor($user['firstpic']/1000) . "/$user[firstpic].jpg\" border=0></a>";
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
		if($config['votingenabled'] && $mode=='top'){
			echo "<td class=body align=center>";
			if($user['firstpic']>0)
				echo scoreCurve($user['score']);
			echo "</td>";
		}
		echo "</tr>";
		echo "<tr><td class=body colspan=" . ($cols-1) . " valign=top>" . $taglines[$user['userid']] . "<br>&nbsp;</td></tr>\n";
	}

	echo "<tr><td class=header colspan=$cols align=right>";

	foreach($sort as $k => $v)
		$params[] = "sort[$k]=$v";
	$params = implode("&", $params);

	echo "Page: " . pageList("$_SERVER[PHP_SELF]?$params", $page, $numpages, 'header');

	echo "</td></tr>";

	echo "</table>\n";

	incFooter();
	exit;
}


