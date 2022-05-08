<?

	$login=1;

	require_once("include/general.lib.php");

	define("ALL_EXCEPT", 1);
	define("ONLY", 0);

	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");

	$uid = ($bannerAdmin ? getREQval('uid', 'int', $userData['userid']) : $userData['userid']);

	$scope = array('Active','Moded','All');

	$res = $banner->db->prepare_query("SELECT id FROM bannerclients WHERE userid = #" , $uid);

	$client = $res->fetchrow();

	if(!$client)
		die("You are not a banner client");
	else
		$clientid = $client['id'];

	switch ($action) {
		case "bannerstats":
			if(empty($interval))
				$interval = 3600;
			if(empty($month) || empty($day) || empty($year))
				$start = time() - $interval*30;
			else
				$start = mktime(0,0,0,$month,$day,$year);
	
			$id = getREQval('id','int');
	
			bannerStats($id, $interval, $start);	//exit
			break;
		case "transfercredits":
			transferCredits($clientid); //exit
			break;
		case "performtransfer":
			$totype = getREQval('toradio');
			$fromtype = getREQval('fromradio');
			switch ($totype) {
				case 'client';
					$toid = $clientid;
					break;
				case 'campaign':
					$toid = getREQval('selectToCampaign', 'int');
					break;
				case 'banner':
					$toid = getREQval('selectToBanner', 'int');
					break;
			}
			switch ($fromtype) {
				case 'client';
					$fromid = $clientid;
					break;
				case 'campaign':
					$fromid = getREQval('selectFromCampaign', 'int');
					break;
				case 'banner':
					$fromid = getREQval('selectFromBanner', 'int');
					break;
			}
			$amount = getREQval('transferamount', 'int');
			performTransfer($clientid, $fromtype, $fromid, $totype, $toid, $amount);
			break;
		case "editCampaign":
			editCampaign(getREQval('id', 'int')); //exit
			break;
		case "insertCampaign":
			insertCampaign(getREQval('data', 'array'));
			break;
		case "updateCampaign":
			updateCampaign(getREQval('id', 'int'), getREQval('data', 'array'));
			break;
		case "editBanner":
			editBanner(getREQval('id', 'int')); //exit
			break;
		case "insertBanner":
			insertBanner(getREQval('data', 'array'));
			break;
		case "updateBanner":
			updateBanner(getREQval('id', 'int'), getREQval('data', 'array'));
			break;
			
	}
	
	clientOverview($clientid);  //exit


function listBanners($clientid){
	global $banner;

	$res = $banner->db->prepare_query("SELECT banners.title, banners.bannersize, banners.startdate, banners.enddate, banners.views, banners.clicks, banners.id FROM banners, bannercampaigns WHERE bannercampaigns.id = banners.campaignid && bannercampaigns.clientid = ?", $clientid);

	$banners = array();
	while($line = $res->fetchrow())
		$banners[] = $line;

	sortCols($banners, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_STRING, 'bannersize');

	incHeader();

	echo "<table align=center>";

	$cols = 15;

	echo "<tr>";
	echo "<td class=header>Title</td>";
	echo "<td class=header>Size</td>";
	echo "<td class=header>Start</td>";
	echo "<td class=header>End</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Clickthru</td>";
	echo "<td class=header></td>";
	echo "</tr>";

	$classes = array('body','body2');
	$i=1;

	foreach($banners as $line){
		$i = !$i;
		echo "<tr>";
		echo "<td class=$classes[$i]>$line[title]</td>";
		echo "<td class=$classes[$i]>" . $banner->sizes[$line['bannersize']] . "</td>";
		echo "<td class=$classes[$i]>" . ($line['startdate'] > 0 ? userdate("M j, Y", $line['startdate']) : "Started") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['enddate'] > 0 ? userdate("M j, Y", $line['enddate']) : "Never")  . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($line['views']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] ? number_format($line['clicks']) : "N/A") . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] && $line['views'] ? number_format($line['clicks']/$line['views']*100, 3) . "%" : "N/A") . "</td>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=bannerstats&id=$line[id]>Stats</a></td>";

		echo "</tr>";
	}
	echo "</table>";

	incFooter();
	exit;
}


function bannerStats($id){
	global $banner;

	$interval = 86400;

	$res = $banner->db->prepare_query("SELECT time, views, clicks FROM bannerstats WHERE bannerid = ? ORDER BY time", $id);

	$stats = array();
	while($line = $res->fetchrow())
		$stats[] = $line;

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";

	echo "<tr>";
	echo "<td class=header>Time</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Click Thru</td>";
	echo "</tr>";

	$totalviews = 0;
	$totalclicks = 0;

	$preperiodviews = 0;
	$preperiodclicks = 0;

	$periodstart = "";

	foreach($stats as $line){
		$totalviews = $line['views'];
		$totalclicks = $line['clicks'];

		if(userDate("M j, Y", $line['time']) != $periodstart){
			echo "<tr>";
			echo "<td class=body>" . userDate("M j, Y", $line['time']) . "</td>";
			echo "<td class=body align=right>" . number_format($totalviews - $preperiodviews) . "</td>";
			echo "<td class=body align=right>" . number_format($totalclicks - $preperiodclicks) . "</td>";
			echo "<td class=body align=right>" . ($totalviews - $preperiodviews ? number_format(100*($totalclicks - $preperiodclicks) / ($totalviews - $preperiodviews),3) . "%" : "N/A" ) ."</td>";
			echo "</tr>";

			$preperiodviews = $totalviews;
			$preperiodclicks = $totalclicks;
			$periodstart = userDate("M j, Y", $line['time']);
		}
	}

	echo "<tr>";
	echo "<td class=header></td>";
	echo "<td class=header align=right>" . number_format($totalviews) . "</td>";
	echo "<td class=header align=right>" . number_format($totalclicks) . "</td>";
	echo "<td class=header align=right>" . ($totalviews ? number_format(100*$totalclicks / $totalviews,3) . "%" : "N/A" ) . "</td>";
	echo "</tr>";

	echo "</table>";

	incFooter();
	exit;
}

function clientOverview($clientid) {
	global $banner;
	
	$res = $banner->db->prepare_query("SELECT * FROM bannerclients WHERE id = ?", $clientid);
	$client = $res->fetchrow();
	$client['username'] = getUserName($client['userid']);
	$client['credits'] = number_format($client['credits']);
	
	$campaigns = array();
	$res = $banner->db->prepare_query("SELECT * FROM bannercampaigns WHERE clientid = ?", $clientid);
	while ($line = $res->fetchrow()) {
		$bannerres = $banner->db->prepare_query("SELECT * FROM banners WHERE campaignid = ?", $line['id']);
		$banners = array();
		$views = 0;
		$clicks = 0;
		$clickviews = 0;
		$clickthru = false;
		$moded = 0;
		while ($bannerline = $bannerres->fetchrow()) {
			$views += $bannerline['views'];
			if ($bannerline['link']) {
				$clickviews += $bannerline['views'];
				$clicks += $bannerline['clicks'];
				$clickthru = true;
			}
			if ($bannerline['moded'] == 'approved') {
				$moded++;
			}
			$bannerline['clickthru'] = ($bannerline['link'] ? ($bannerline['views'] ? number_format($bannerline['clicks']/$bannerline['views']*100, 3) : $bannerline['clicks']) : 'N/A');
			$bannerline['clicks'] = ($bannerline['link'] ? number_format($bannerline['clicks']) : 'N/A');
			$bannerline['views'] = $bannerline['views'];
			$bannerline['size'] = $banner->sizes[$bannerline['bannersize']];
			$bannerline['startdate'] = ($bannerline['startdate'] > 0 ? userdate("M j, Y", $bannerline['startdate']) : "Started");
			$bannerline['enddate'] = ($bannerline['enddate'] > 0 ? userdate("M j, Y", $bannerline['enddate']) : "Never");
			$bannerline['credits'] = number_format($bannerline['credits']);
			$banners[] = $bannerline;
		}
		sortCols($banners, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_STRING, 'bannersize');
		$line['banners'] = $banners;
		$line['moded'] = $moded . '/' . count($banners);
		$line['views'] = number_format($views);
		if ($clickthru) {
			$line['clicks'] = number_format($clicks);
			$line['clickthru'] = ($clickviews ? number_format($clicks*100/$clickviews, 3) : $clicks);
		} else {
			$line['clicks'] = 'N/A';
			$line['clickthru'] = 'N/A';
		}
		$line['startdate'] = ($line['startdate'] > 0 ? userdate("M j, Y", $line['startdate']) : "Started");
		$line['enddate'] = ($line['enddate'] > 0 ? userdate("M j, Y", $line['enddate']) : "Never");
		$line['credits'] = number_format($line['credits']);
		$campaigns[] = $line;
	}
	
	$template = new template('bannerclient/clientOverview');
	$template->set('client', $client);
	$template->set('campaigns', $campaigns);
	$template->set('classes', array('body', 'body2'));
	$template->display();
	exit;
	
}

function transferCredits($clientid) {
	global $banner;
	$res = $banner->db->prepare_query("SELECT * FROM bannerclients WHERE id = ?", $clientid);
	$client = $res->fetchrow();
	if ($client['type'] != 'payinadvance') {
		clientOverview(); //exit
	}
	$campaigns = array();
	$campaignnames = array();
	$campaignids = array();
	$res = $banner->db->prepare_query("SELECT * FROM bannercampaigns WHERE clientid = ?", $clientid);
	while ($line = $res->fetchrow()) {
		$campaignnames[$line['id']] = $line['title'];
		$campaigns[$line['id']] = $line;
		$campaignids[] = $line['id'];
	}
	
	$banners = array();
	$bannernames = array();
	$res = $banner->db->prepare_query("SELECT * FROM banners WHERE campaignid IN (?)", $campaignids);
	while ($line = $res->fetchrow()) {
		$bannernames[$line['id']] = $line['title'];
		$banners[$line['id']] = $line;
	}
	$template = new template('bannerclient/transferCredits');
	$template->set('client', $client);
	$template->set('selectCampaign', make_select_list_key($campaignnames));
	$template->set('selectBanner', make_select_list_key($bannernames));
	$template->set('banners', $banners);
	$template->set('campaigns', $campaigns);
	$template->display();
	exit;
}

function performTransfer($clientid, $fromtype, $fromid, $totype, $toid, $amount) {
	global $banner, $msgs;
	$res = $banner->db->prepare_query("SELECT * FROM bannerclients WHERE id = ?", $clientid);
	$client = $res->fetchrow();
	if ($client['type'] != 'payinadvance') {
		clientOverview(); //exit
	}
	$campaigns = array();
	$campaignnames = array();
	$campaignids = array();
	$res = $banner->db->prepare_query("SELECT * FROM bannercampaigns WHERE clientid = ?", $clientid);
	while ($line = $res->fetchrow()) {
		$campaigns[$line['id']] = $line;
		$campaignids[] = $line['id'];
	}
	
	$banners = array();
	$bannernames = array();
	$res = $banner->db->prepare_query("SELECT * FROM banners WHERE campaignid IN (?)", $campaignids);
	while ($line = $res->fetchrow()) {
	$banners[$line['id']] = $line;
	}
	
	switch ($fromtype) {
		case 'client':
			if ($client['credits'] < $amount) {
				$msgs->addMsg($client['clientname'] . " does not have sufficient credits to transfer.");
				return;
			} else {
				$fromsql = "UPDATE bannerclients SET credits = credits - # WHERE id=# && credits>=#";
			}
			break;
		case 'campaign':
			if (!isset($campaigns[$fromid])) {
				$msgs->addMsg("Invalid campaign selected to transfer from.");
				return;
			} elseif ($campaigns[$fromid]['credits'] < $amount) {
				$msgs->addMsg($campaigns[$fromid]['title'] . " does not have sufficient credits to transfer.");
				return;
			} else {
				$fromsql = "UPDATE bannercampaigns SET credits=credits - # WHERE id=# && credits>=#";
			}
			break;
		case 'banner':
			if (!isset($banners[$fromid])) {
				$msgs->addMsg("Invalid banner selected to transfer from.");
				return;
			} elseif ($banners[$fromid]['credits'] < $amount) {
				$msgs->addMsg($banners[$fromid]['title'] . " does not have sufficient credits to transfer.");
				return;
			} else {
				$fromsql = "UPDATE banners SET credits=credits - # WHERE id=# && credits>=#";
			}
			break;
	}
	
	switch ($totype) {
		case 'client':
			$tosql = "UPDATE bannerclients SET credits=credits+# WHERE id=#";
			$toresult = $client['credits'] + $amount;
			break;
		case 'campaign':
			if (!isset($campaigns[$toid])) {
				$msgs->addMsg("Invalid campaign selected to transfer to.");
				return;
			} else {
				$tosql = "UPDATE bannercampaigns SET credits=credits+# WHERE id=#";
				$toresult = $campaigns[$toid]['credits'] + $amount;
			}
			break;
		case 'banner':
			if (!isset($banners[$toid])) {
				$msgs->addMsg("Invalid banner selected to transfer to.");
				return;
			} else {
				$tosql = "UPDATE banners SET credits=credits+# WHERE id=#";
				$toresult = $banners[$toid]['credits'] + $amount;
			}
			break;
	}
	
	$result = $banner->db->prepare_query($fromsql, $amount, $fromid, $amount);
	if ($result->affectedrows() > 0) {
		$banner->db->prepare_query($tosql, $amount, $toid);
		$msgs->addMsg("Transfer successful.");
		if ($fromtype == 'campaign') {
			$banner->updateCampaign($fromid);
		} elseif ($fromtype == 'banner') {
			$banner->updateBanner($fromid);
		}
		if ($totype == 'campaign') {
			$banner->updateCampaign($toid);
		} elseif ($totype == 'banner') {
			$banner->updateBanner($toid);
		}
	} else {
		$msgs->addMsg("Transfer failed.");
	}
}

function editCampaign($id = 0) {
	global $mods, $banner, $usersdb, $configdb, $msgs, $userData;

	$defaults = array(ALL_EXCEPT => 'All except', ONLY => 'Only');
	$sexes = array(SEX_UNKNOWN => "Unknown", SEX_MALE => "Male", SEX_FEMALE => "Female");

	if ($id) {
		$allowed = false;
		$result = $banner->db->prepare_query("SELECT clientid FROM bannercampaigns WHERE id = #", $id);
		if ($line = $result->fetchrow()) {
			$result = $banner->db->prepare_query("SELECT type, userid FROM bannerclients WHERE id = #", $line['clientid']);
			if ($line = $result->fetchrow()) {
				if ($line['userid'] == $userData['userid'] && $line['type'] == 'payinadvance') {
					$allowed = true;
				}
			}
		}
		if (!$allowed && !$mods->isAdmin($userData['userid'], "listbanners")) { //attempt to edit someone elses campaign
			$msgs->addMsg("You do not have permission to edit this campaign.");
			return;
		}
	}
	
	$clientid		= 0;
	$viewsperday	= 0;
	$clicksperday	= 0;
	$payrate		= 0;
	$maxviews		= 0;
	$maxclicks		= 0;
	$startmonth		= 0;
	$startday		= 0;
	$startyear		= 0;
	$endmonth		= 0;
	$endday			= 0;
	$endyear		= 0;
	$title			= "";

	$sex			= array();
	$agedefault		= ONLY;
	$age 			= array();
	$locdefault		= ONLY;
	$loc			= array();
	$interests		= array();
	$pagedefault	= ONLY;
	$page			= '';
	$paytype		= 0;
	$viewsperuser	= 0;
	$freqtype		= 3; //days
	$freqperiod		= 0;
	$enabled		= 'y';
	$refresh		= 30;
	$edit = false;
	
	if($id){
		$res = $banner->db->prepare_query("SELECT * FROM bannercampaigns WHERE id = #", $id);
		if($line = $res->fetchrow())
		{
			extract($line);

			if($startdate){
				$startmonth		= userdate("m", $startdate);
				$startday		= userdate("j", $startdate);
				$startyear		= userdate("Y", $startdate);
			}
			if($enddate){
				$endmonth		= userdate("m", $enddate);
				$endday			= userdate("j", $enddate);
				$endyear		= userdate("Y", $enddate);
			}

			if($sex == ''){
				$sex = array();
			}else{
				$sex = explode(',', $sex);
			}

			if($age == ''){
				$age = array();
			}else{
				$age = explode(',', $age);
				$agedefault = (isset($age[0]) && $age[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($loc == ''){
				$loc = array();
			}else{
				$loc = explode(',', $loc);
				$locdefault = (isset($loc[0]) && $loc[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($page != ''){
				$page = explode(',', $page);
				if(isset($page[0]) && $page[0] == '0'){
					$pagedefault = ALL_EXCEPT;
					unset($page[0]);
				}else{
					$pagedefault = ONLY;
				}
				$page = implode("\n", $page);
			}

			if($interests == ''){
				$interests = array();
			}else{
				$interests = explode(',', $interests);
			}
			if ($limitbyperiod%86400 == 0) {
				$freqtype = 3; //days
				$freqperiod = $limitbyperiod/86400;
			} elseif ($limitbyperiod%3600 == 0) {
				$freqtype = 2; //hours
				$freqperiod = $limitbyperiod/3600;
			} elseif ($limitbyperiod%60 == 0) {
				$freqtype = 1; //minutes
				$freqperiod = $limitbyperiod/60;
			} else {
				$freqtype = 0; //seconds
				$freqperiod = $limitbyperiod;
			}
			
			if($allowedtimes != '') {
				$timetable = new timetable($allowedtimes);
				$allowedTimesTable = $timetable->getTimeTable();
			}
		}
	}

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$template = new template('bannerclient/editCampaign');
	if (!isset($allowedTimesTable)) $allowedTimesTable = '';
	$template->set('allowedTimesTable', $allowedTimesTable);
	$template->set('classes', array('body', 'body2'));
	$template->set('edit', $edit);
	$template->set('id', $id);

	$template->set('checkEnabled', makeCheckBox('data[enabled]','Enabled', $enabled == 'y'));
	$template->set('title', $title);


	$template->set('selectSex', make_select_list_multiple_key($sexes, $sex));
	$template->set('selectAgeType', make_select_list_key($defaults, $agedefault));
	$template->set('selectAgeRange', make_select_list_multiple(range(14,65), $age));

	$locations = new category( $configdb, "locs");
	$template->set('selectLocationType', make_select_list_key($defaults, $locdefault));
	$template->set('selectLocations', makeCatSelect_multiple($locations->makeBranch(), $loc));
	$template->set('selectPagesType', make_select_list_key($defaults, $pagedefault));
	$template->set('page', $page);

	$interestcats = new category( $usersdb, "interests");
	$template->set('selectInterests', makeCatSelect_multiple($interestcats->makeBranch(), $interests));

	$template->set('payrate', $payrate);
	$template->set('selectPayType', make_select_list_key(array(BANNER_CPM => "Per View", BANNER_CPC => "Per Click"), $paytype));

	$template->set('maxviews', $maxviews);
	$template->set('maxclicks', $maxclicks);
	$template->set('viewsperday', $viewsperday);
	$template->set('clicksperday', $clicksperday);

	$template->set('viewsperuser', $viewsperuser);
	$template->set('selectFrequencyPeriod', make_select_list_key(array("Seconds", "Minutes", "Hours", "Days"), $freqtype));
	$template->set('freqperiod', $freqperiod);

	$template->set('allowedtimes', (isset($allowedtimes) ? $allowedtimes : ''));
	$template->set('refresh', $refresh);
	$template->set('selectNewBannerType', make_select_list_key($banner->types));
	$template->set('selectStartMonth', make_select_list_key($months, $startmonth));
	$template->set('selectStartDay', make_select_list(range(1,31), $startday));
	$template->set('selectStartYear', make_select_list(range(userdate("Y"),userdate("Y")+1), $startyear));
	$template->set('selectEndMonth', make_select_list_key($months, $endmonth));
	$template->set('selectEndDay', make_select_list(range(1,31), $endday));
	$template->set('selectEndYear', make_select_list(range(userdate("Y"),userdate("Y")+1), $endyear));
	
	$template->display();
	exit;
}

//this is only for payinadvance banner clients to add their own campaigns
//look in admin banners for more general campaign insertion
function insertCampaign($data){
	global $banner, $msgs, $docRoot, $config, $userData;

	$clientid = 0;
	
	$result = $banner->db->prepare_query("SELECT type, id FROM bannerclients WHERE userid = #", $userData['userid']);
	if ($line = $result->fetchrow()) {
		if ($line['type'] == 'payinadvance') {
			$clientid = $line['id'];
		}
	}
	
	if (!$clientid) { //can't create campaigns if you're not a banner client
		$msgs->addMsg("You are not a banner client.");
		return;
	}
	
	$timetable = new timetable($data['allowedtimes']);
	$data['allowedtimes']=$timetable->allowedtimes; //construction automatically cleans input
	
	if (isset($timetable->invalidRanges) && is_array($timetable->invalidRanges)) {
		foreach ($timetable->invalidRanges as $range) {
			$msgs->addMsg("$range is an invalid day/time range.");
		}
	}


	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? 0 : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? 0 : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$sex		= (!isset($data['sexes'])		|| !count($data['sexes'])		? "" : implode(",", $data['sexes']));
	$age		= (!isset($data['ages'])		|| !count($data['ages'])		? "" : ($data['agedefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['ages'])); //if it includes anon, start with 0. This is all except
	$loc		= (!isset($data['locs'])		|| !count($data['locs'])		? "" : ($data['locdefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['locs']));
	$page		= (!isset($data['pages'])		|| !strlen($data['pages'])		? "" : ($data['pagedefault']		== ALL_EXCEPT ? "0," : "") . implode(",", array_map('trim', explode("\n", $data['pages']))));
	$interests	= (!isset($data['interests'])	|| !strlen($data['interests'])	? "" : implode(",", $data['interests']));

	switch ($data['freqtype']) {
		case 0:
			$limitbyseconds = $data['freqperiod'];
			break;
		case 1:
			$limitbyseconds = $data['freqperiod']*60;
			break;
		case 2:
			$limitbyseconds = $data['freqperiod']*60*60;
			break;
		case 3:
			$limitbyseconds = $data['freqperiod']*60*60*24;
			break;
	}

	$set[] = "clientid = #"; 		$params[] = $clientid;
	$set[] = "maxviews = #"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = #"; 		$params[] = $data['maxclicks'];
	$set[] = "viewsperday = #"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = #";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = #";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyperiod = #";	$params[] = $limitbyseconds;
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = #"; 		$params[] = $start;
	$set[] = "enddate = #"; 		$params[] = $end;
	$set[] = "payrate = #"; 		$params[] = $data['payrate'];
	$set[] = "paytype = #"; 		$params[] = $data['paytype'];
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "dateadded = #"; 		$params[] = time();
	$set[] = "age = ?"; 			$params[] = $age;
	$set[] = "sex = ?"; 			$params[] = $sex;
	$set[] = "loc = ?"; 			$params[] = $loc;
	$set[] = "page = ?"; 			$params[] = $page;
	$set[] = "interests = ?"; 		$params[] = $interests;
	$set[] = "allowedtimes = ?";	$params[] = $data['allowedtimes'];
	$set[] = "enabled = ?"; 		$params[] = (isset($data['enabled']) ? 'y' : 'n');
	$set[] = "refresh = #"; 		$params[] = $data['refresh'];
	
	$banner->db->prepare_array_query("INSERT INTO bannercampaigns SET " . implode(", ", $set), $params);

	$id = $banner->db->insertid();

	$banner->addCampaign($id);

	$msgs->addMsg("Campaign Added");
}

//this is only for payinadvance banner clients to update their own campaigns
//look in admin banners for more general campaign updates
function updateCampaign($id, $data){
	global $banner, $msgs, $docRoot, $config, $mods, $userData;

	$clientid = 0;
	$allowed = false;
	$result = $banner->db->prepare_query("SELECT clientid FROM bannercampaigns WHERE id = #", $id);
	if ($line = $result->fetchrow()) {
		$clientid = $line['clientid'];
		$result = $banner->db->prepare_query("SELECT type, userid FROM bannerclients WHERE id = #", $line['clientid']);
		if ($line = $result->fetchrow()) {
			if ($line['userid'] == $userData['userid'] && $line['type'] == 'payinadvance') {
				$allowed = true;
			}
		}
	}
	if (!$allowed && !$mods->isAdmin($userData['userid'], "listbanners")) { //attempt to edit someone elses campaign
		$msgs->addMsg("You do not have permission to edit this campaign.");
		return;
	}
	
	$timetable = new timetable($data['allowedtimes']);
	$data['allowedtimes']=$timetable->allowedtimes; //construction automatically cleans input
	
	if (isset($timetable->invalidRanges) && is_array($timetable->invalidRanges)) {
		foreach ($timetable->invalidRanges as $range) {
			$msgs->addMsg("$range is an invalid day/time range.");
		}
	}


	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? 0 : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? 0 : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$sex		= (!isset($data['sexes'])		|| !count($data['sexes'])		? "" : implode(",", $data['sexes']));
	$age		= (!isset($data['ages'])		|| !count($data['ages'])		? "" : ($data['agedefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['ages'])); //if it includes anon, start with 0. This is all except
	$loc		= (!isset($data['locs'])		|| !count($data['locs'])		? "" : ($data['locdefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['locs']));
	$page		= (!isset($data['pages'])		|| !strlen($data['pages'])		? "" : ($data['pagedefault']		== ALL_EXCEPT ? "0," : "") . implode(",", array_map('trim', explode("\n", $data['pages']))));
	$interests	= (!isset($data['interests'])	|| !strlen($data['interests'])	? "" : implode(",", $data['interests']));

	switch ($data['freqtype']) {
		case 0:
			$limitbyseconds = $data['freqperiod'];
			break;
		case 1:
			$limitbyseconds = $data['freqperiod']*60;
			break;
		case 2:
			$limitbyseconds = $data['freqperiod']*60*60;
			break;
		case 3:
			$limitbyseconds = $data['freqperiod']*60*60*24;
			break;
	}

	$set[] = "clientid = #"; 		$params[] = $clientid;
	$set[] = "maxviews = #"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = #"; 		$params[] = $data['maxclicks'];
	$set[] = "viewsperday = #"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = #";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = #";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyperiod = #";	$params[] = $limitbyseconds;
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = #"; 		$params[] = $start;
	$set[] = "enddate = #"; 		$params[] = $end;
	$set[] = "payrate = #"; 		$params[] = $data['payrate'];
	$set[] = "paytype = #"; 		$params[] = $data['paytype'];
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "dateadded = #"; 		$params[] = time();
	$set[] = "age = ?"; 			$params[] = $age;
	$set[] = "sex = ?"; 			$params[] = $sex;
	$set[] = "loc = ?"; 			$params[] = $loc;
	$set[] = "page = ?"; 			$params[] = $page;
	$set[] = "interests = ?"; 		$params[] = $interests;
	$set[] = "allowedtimes = ?";	$params[] = $data['allowedtimes'];
	$set[] = "enabled = ?"; 		$params[] = (isset($data['enabled']) ? 'y' : 'n');
	$set[] = "refresh = #"; 		$params[] = $data['refresh'];
	
	$params[] = $id;
	
	$banner->db->prepare_array_query("UPDATE bannercampaigns SET " . implode(", ", $set) . " WHERE id = #", $params);

	$banner->updateCampaign($id);

	$msgs->addMsg("Campaign Updated");
}

function editBanner($id = 0){
	global $banner, $userData, $mods, $usersdb, $configdb;

	$client = 0;
	
	if ($id) {
		$allowed = false;
		
		$result = $banner->db->prepare_query("SELECT campaignid FROM banners WHERE id = #", $id);
		if ($line = $result->fetchrow()) {
			$result = $banner->db->prepare_query("SELECT clientid FROM bannercampaigns WHERE id = #", $line['campaignid']);
			if ($line = $result->fetchrow()) {
				$client = $line['clientid'];
				$result = $banner->db->prepare_query("SELECT type, userid FROM bannerclients WHERE id = #", $line['clientid']);
				if ($line = $result->fetchrow()) {
					if ($line['userid'] == $userData['userid'] && $line['type'] == 'payinadvance') {
						$allowed = true;
					}
				}
			}
		}
		if (!$allowed && !$mods->isAdmin($userData['userid'], "listbanners")) { //attempt to edit someone elses campaign
			$msgs->addMsg("You do not have permission to edit this banner.");
			return;
		}
	} else {
		$result = $banner->db->prepare_query("SELECT id FROM bannerclients WHERE userid = #", $userData['userid']);
		if ($line = $result->fetchrow()) {
			$client = $line['id'];
		} else {
			$msgs->addMsg("You do not have permission to create a banner.");
			return;
		}
	}
	

	$defaults = array(ALL_EXCEPT => 'All except', ONLY => 'Only');
	$sexes = array(SEX_UNKNOWN => "Unknown", SEX_MALE => "Male", SEX_FEMALE => "Female");
	
	$bannersize		= "";
	$campaignid		= 0;
	$freqtype		= 3; //days
	$freqperiod		= 0;
	$viewsperday	= 0;
	$clicksperday	= 0;
	$payrate		= -1;
	$maxviews		= 0;
	$maxclicks		= 0;
	$startmonth		= 0;
	$startday		= 0;
	$startyear		= 0;
	$endmonth		= 0;
	$endday			= 0;
	$endyear		= 0;
	$title			= "";
	$link			= "";
	$alt			= "";

	$sex			= array();
	$agedefault		= ONLY;
	$age 			= array();
	$locdefault		= ONLY;
	$loc			= array();
	$interests		= array();
	$pagedefault	= ONLY;
	$page			= '';
	$paytype		= BANNER_INHERIT;
	$viewsperuser	= 0;
	
	$enabled		= 'y';

	$refresh = -1;

	if($id){
		$res = $banner->db->prepare_query("SELECT * FROM banners WHERE id = #", $id);

		if($line = $res->fetchrow())
		{
			extract($line);

			if($startdate){
				$startmonth		= userdate("m", $startdate);
				$startday		= userdate("j", $startdate);
				$startyear		= userdate("Y", $startdate);
			}
			if($enddate){
				$endmonth		= userdate("m", $enddate);
				$endday			= userdate("j", $enddate);
				$endyear		= userdate("Y", $enddate);
			}

			if($sex == ''){
				$sex = array();
			}else{
				$sex = explode(',', $sex);
			}

			if($age == ''){
				$age = array();
			}else{
				$age = explode(',', $age);
				$agedefault = (isset($age[0]) && $age[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($loc == ''){
				$loc = array();
			}else{
				$loc = explode(',', $loc);
				$locdefault = (isset($loc[0]) && $loc[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($page != ''){
				$page = explode(',', $page);
				if(isset($page[0]) && $page[0] == '0'){
					$pagedefault = ALL_EXCEPT;
					unset($page[0]);
				}else{
					$pagedefault = ONLY;
				}
				$page = implode("\n", $page);
			}

			if($interests == ''){
				$interests = array();
			}else{
				$interests = explode(',', $interests);
			}

			if ($limitbyperiod%86400 == 0) {
				$freqtype = 3; //days
				$freqperiod = $limitbyperiod/86400;
			} elseif ($limitbyperiod%3600 == 0) {
				$freqtype = 2; //hours
				$freqperiod = $limitbyperiod/3600;
			} elseif ($limitbyperiod%60 == 0) {
				$freqtype = 1; //minutes
				$freqperiod = $limitbyperiod/60;
			} else {
				$freqtype = 0; //seconds
				$freqperiod = $limitbyperiod;
			}
			
			if($allowedtimes != '') {
				$timetable = new timetable($allowedtimes);
				$allowedTimesTable = $timetable->getTimeTable();
			}
		}
	}


	//get a list of campaigns so we can create a selection box with them
	$res = $banner->db->prepare_query("SELECT id, title FROM bannercampaigns WHERE clientid = #", $client);
	$campaigns = array();
	while($line = $res->fetchrow())
		$campaigns[$line['id']]=$line['title'];
	uasort($campaigns, 'strcasecmp');

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	$template = new template('bannerclient/editBanner');

	if($id){
		$template->set('displayBanner', $banner->getBannerID($id));
	}
	
	$template->set('id', $id);
	$template->set('selectCampaign', make_select_list_key($campaigns, $campaignid));
	$template->set('selectBannerSize', make_select_list_key($banner->sizes, $bannersize));
	$template->set('checkEnabled', makeCheckBox('data[enabled]','Enabled', $enabled == 'y'));
	$template->set('title', $title);
	$template->set('link', $link);
	$template->set('alt', (isset($bannertype) && $bannertype == BANNER_TEXT ? "" : $alt));
	$template->set('description', (isset($bannertype) && $bannertype == BANNER_TEXT ? $alt : ""));
	$template->set('headline', (isset($bannertype) && $bannertype == BANNER_TEXT ? $image: ""));
	if (!isset($allowedTimesTable)) $allowedTimesTable = '';
	$template->set('allowedTimesTable', $allowedTimesTable);
	
	if($id){
		$template->set('bannersize', $bannersize);
	}

	$template->set('selectSex', make_select_list_multiple_key($sexes, $sex));
	$template->set('selectAgeType', make_select_list_key($defaults, $agedefault));
	$template->set('selectAgeRange', make_select_list_multiple(range(14,65), $age));
	$locations = new category( $configdb, "locs");
	$template->set('selectLocationType', make_select_list_key($defaults, $locdefault));
	$template->set('selectLocations', makeCatSelect_multiple($locations->makeBranch(), $loc));
	$template->set('selectPagesType', make_select_list_key($defaults, $pagedefault));
	$template->set('page', $page);
	$interestcats = new category( $usersdb, "interests");
	$template->set('selectInterests', makeCatSelect_multiple($interestcats->makeBranch(), $interests));
	$template->set('allowedtimes', (isset($allowedtimes) ? $allowedtimes : ''));
	
	$template->set('refresh', $refresh);

	$template->set('payrate', $payrate);
	$template->set('selectPayType', make_select_list_key(array(BANNER_INHERIT => "Inherit", BANNER_CPM => "Per View", BANNER_CPC => "Per Click"), $paytype));

	$template->set('maxviews', $maxviews);
	$template->set('maxclicks', $maxclicks);
	$template->set('viewsperday', $viewsperday);
	$template->set('clicksperday', $clicksperday);

	$template->set('viewsperuser', $viewsperuser);
	$template->set('freqperiod', $freqperiod);
	$template->set('selectFrequencyPeriod', make_select_list_key(array("Seconds", "Minutes", "Hours", "Days"), $freqtype));

	$template->set('selectStartMonth', make_select_list_key($months, $startmonth));
	$template->set('selectStartDay', make_select_list(range(1,31), $startday));
	$template->set('selectStartYear', make_select_list(range(userdate("Y"),userdate("Y")+1), $startyear));
	$template->set('selectEndMonth', make_select_list_key($months, $endmonth));
	$template->set('selectEndDay', make_select_list(range(1,31), $endday));
	$template->set('selectEndYear', make_select_list(range(userdate("Y"),userdate("Y")+1), $endyear));
	$template->set('BANNER_TEXT', BANNER_TEXT);
	$template->set('BANNER_IMAGE', BANNER_IMAGE);
	$template->set('bannertype', $bannertype);
	$template->display();
	exit;
}

function insertBanner($data){
	global $mods, $userData, $banner, $msgs, $docRoot, $config, $previousaction;

	$allowed = false;
	if(!empty($data['campaignid'])){
		$result = $banner->db->prepare_query("SELECT clientid FROM bannercampaigns WHERE id = #", $data['campaignid']);
		if ($line = $result->fetchrow()) {
			$clientid = $line['clientid'];
			$result = $banner->db->prepare_query("SELECT type, userid FROM bannerclients WHERE id = #", $line['clientid']);
			if ($line = $result->fetchrow()) {
				if ($line['userid'] == $userData['userid'] && $line['type'] == 'payinadvance') {
					$allowed = true;
				}
			}
		}
	}
	if (empty($data['campaignid']) || (!$allowed && !$mods->isAdmin($userData['userid'], "listbanners"))) {
		$msgs->addMsg("You cannot create banners in this campaign.");
		return;
	}
	$timetable = new timetable($data['allowedtimes']);
	$data['allowedtimes']=$timetable->allowedtimes; //construction automatically cleans input
	
	if (isset($timetable->invalidRanges) && is_array($timetable->invalidRanges)) {
		foreach ($timetable->invalidRanges as $range) {
			$msgs->addMsg("$range is an invalid day/time range.");
		}
	}

	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? 0 : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? 0 : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$sex		= (!isset($data['sexes'])		|| !count($data['sexes'])		? "" : implode(",", $data['sexes']));
	$age		= (!isset($data['ages'])		|| !count($data['ages'])		? "" : ($data['agedefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['ages'])); //if it includes anon, start with 0. This is all except
	$loc		= (!isset($data['locs'])		|| !count($data['locs'])		? "" : ($data['locdefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['locs']));
	$page		= (!isset($data['pages'])		|| !strlen($data['pages'])		? "" : ($data['pagedefault']		== ALL_EXCEPT ? "0," : "") . implode(",", array_map('trim', explode("\n", $data['pages']))));
	$interests	= (!isset($data['interests'])	|| !strlen($data['interests'])	? "" : implode(",", $data['interests']));

	switch ($data['freqtype']) {
		case 0:
			$limitbyseconds = $data['freqperiod'];
			break;
		case 1:
			$limitbyseconds = $data['freqperiod']*60;
			break;
		case 2:
			$limitbyseconds = $data['freqperiod']*60*60;
			break;
		case 3:
			$limitbyseconds = $data['freqperiod']*60*60*24;
			break;
	}

	//$set[] = "clientid = ?"; 		$params[] = $data['clientid'];
	$set[] = "bannersize = #"; 		$params[] = $data['bannersize'];
	$set[] = "bannertype = #"; 		$params[] = $data['bannertype'];
	$set[] = "maxviews = #"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = #"; 		$params[] = $data['maxclicks'];
	$set[] = "viewsperday = #"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = #";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = #";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyperiod = #";	$params[] = $limitbyseconds;
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = #"; 		$params[] = $start;
	$set[] = "enddate = #"; 		$params[] = $end;
	$set[] = "payrate = #"; 		$params[] = $data['payrate'];
	$set[] = "paytype = #"; 		$params[] = $data['paytype'];
	$set[] = "moded = ?"; 			$params[] = 'unchecked';
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "image = ?"; 			$params[] = ($data['bannertype'] == BANNER_TEXT ? $data['headline'] : "");
	$set[] = "link = ?"; 			$params[] = (isset($data['link']) ? $data['link'] : "");
	$set[] = "alt = ?"; 			$params[] = ($data['bannertype'] == BANNER_TEXT ? $data['description'] : (isset($data['alt']) ? $data['alt'] : ""));
	$set[] = "dateadded = #"; 		$params[] = time();
	$set[] = "age = ?"; 			$params[] = $age;
	$set[] = "sex = ?"; 			$params[] = $sex;
	$set[] = "loc = ?"; 			$params[] = $loc;
	$set[] = "page = ?"; 			$params[] = $page;
	$set[] = "interests = ?"; 		$params[] = $interests;
	$set[] = "allowedtimes = ?";	$params[] = $data['allowedtimes'];
	$set[] = "enabled = ?"; 		$params[] = (isset($data['enabled']) ? 'y' : 'n');
	$set[] = "refresh = #"; 		$params[] = 30;
	$set[] = "campaignid = #"; 		$params[] = $data['campaignid'];
	
	$banner->db->prepare_array_query("INSERT INTO banners SET " . implode(", ", $set), $params);

	$id = $banner->db->insertid();
	$uploadedBanner = getFILEval('uploadbanner');
	$target_path = $docRoot . $config['bannerdir'] . $id . '.jpg';
	if (!empty($uploadedBanner['tmp_name'])) {
		if(!move_uploaded_file($uploadedBanner['tmp_name'], $target_path)) {
			$msgs->addMsg("Banner upload failed, please try again.");
		} else {
			$msgs->addMsg('Upload successful.');
		}
	}

	$banner->addBanner($id);

	$msgs->addMsg("Banner Added");

}



function updateBanner($id, $data){
	global $mods, $userData, $banner, $msgs, $config, $docRoot, $previousaction;

	$client = 0;
	$uploadedBanner = getFILEval('uploadbanner');
	
	//check that we can edit the banner
	$allowed = false;
	$result = $banner->db->prepare_query("SELECT campaignid FROM banners WHERE id = #", $id);
	if ($line = $result->fetchrow()) {
		$result = $banner->db->prepare_query("SELECT clientid FROM bannercampaigns WHERE id = #", $line['campaignid']);
		if ($line = $result->fetchrow()) {
			$client = $line['clientid'];
			$result = $banner->db->prepare_query("SELECT type, userid FROM bannerclients WHERE id = #", $line['clientid']);
			if ($line = $result->fetchrow()) {
				if ($line['userid'] == $userData['userid'] && $line['type'] == 'payinadvance') {
					$allowed = true;
				}
			}
		}
	}
	if (!$allowed && !$mods->isAdmin($userData['userid'], "listbanners")) { //attempt to edit someone elses campaign
		$msgs->addMsg("You do not have permission to edit this banner.");
		return;
	}
	//check that the banner has access to the target campaign
	$allowed = false;
	if(!empty($data['campaignid'])){
		$result = $banner->db->prepare_query("SELECT clientid FROM bannercampaigns WHERE id = #", $data['campaignid']);
		if ($line = $result->fetchrow()) {
			$client = $line['clientid'];
			$result = $banner->db->prepare_query("SELECT type, userid FROM bannerclients WHERE id = #", $line['clientid']);
			if ($line = $result->fetchrow()) {
				if ($line['userid'] == $userData['userid'] && $line['type'] == 'payinadvance') {
					$allowed = true;
				}
			}
		}
	}
	if (empty($data['campaignid']) || (!$allowed && !$mods->isAdmin($userData['userid'], "listbanners"))) {
		$msgs->addMsg("You cannot create banners in this campaign.");
		return;
	}
	
	$timetable = new timetable($data['allowedtimes']);
	$data['allowedtimes']=$timetable->allowedtimes; //construction automatically cleans input
	
	if (isset($timetable->invalidRanges) && is_array($timetable->invalidRanges)) {
		foreach ($timetable->invalidRanges as $range) {
			$msgs->addMsg("$range is an invalid day/time range.");
		}
	}
	
	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? 0 : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? 0 : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$sex		= (!isset($data['sexes'])		|| !count($data['sexes'])		? "" : implode(",", $data['sexes']));
	$age		= (!isset($data['ages'])		|| !count($data['ages'])		? "" : ($data['agedefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['ages'])); //if it includes anon, start with 0. This is all except
	$loc		= (!isset($data['locs'])		|| !count($data['locs'])		? "" : ($data['locdefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['locs']));
	$page		= (!isset($data['pages']) 		|| !strlen($data['pages'])		? "" : ($data['pagedefault']		== ALL_EXCEPT ? "0," : "") . implode(",", array_map('trim', explode("\n", $data['pages']))));
	$interests	= (!isset($data['interests'])	|| !strlen($data['interests'])	? "" : implode(",", $data['interests']));

	switch ($data['freqtype']) {
		case 0:
			$limitbyseconds = $data['freqperiod'];
			break;
		case 1:
			$limitbyseconds = $data['freqperiod']*60;
			break;
		case 2:
			$limitbyseconds = $data['freqperiod']*60*60;
			break;
		case 3:
			$limitbyseconds = $data['freqperiod']*60*60*24;
			break;
	}

	$set[] = "bannersize = #"; 		$params[] = $data['bannersize'];
	$set[] = "bannertype = #"; 		$params[] = BANNER_IMAGE;
	$set[] = "maxviews = #"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = #"; 		$params[] = $data['maxclicks'];
	$set[] = "viewsperday = #"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = #";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = #";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyperiod = #";	$params[] = $limitbyseconds;
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = #"; 		$params[] = $start;
	$set[] = "enddate = #"; 		$params[] = $end;
	$set[] = "payrate = #"; 		$params[] = $data['payrate'];
	$set[] = "paytype = #"; 		$params[] = $data['paytype'];
	if (!empty($uploadedBanner['tmp_name'])) {
	$set[] = "moded = ?"; 			$params[] = 'unchecked';
	}
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "image = ?"; 			$params[] = ($data['bannertype'] == BANNER_TEXT ? $data['headline'] : "");
	$set[] = "link = ?"; 			$params[] = (isset($data['link']) ? $data['link'] : "");
	$set[] = "alt = ?"; 			$params[] = ($data['bannertype'] == BANNER_TEXT ? $data['description'] : (isset($data['alt']) ? $data['alt'] : ""));
	$set[] = "dateadded = #"; 		$params[] = time();
	$set[] = "age = ?"; 			$params[] = $age;
	$set[] = "sex = ?"; 			$params[] = $sex;
	$set[] = "loc = ?"; 			$params[] = $loc;
	$set[] = "page = ?"; 			$params[] = $page;
	$set[] = "interests = ?"; 		$params[] = $interests;
	$set[] = "allowedtimes = ?";	$params[] = $data['allowedtimes'];
	$set[] = "enabled = ?"; 		$params[] = (isset($data['enabled']) ? 'y' : 'n');
	$set[] = "refresh = #"; 		$params[] = $data['refresh'];
	$set[] = "campaignid = #"; 		$params[] = $data['campaignid'];

	$params[] = $id;

	$banner->db->prepare_array_query("UPDATE banners SET " . implode(", ", $set) . " WHERE id = #", $params);

	$banner->updateBanner($id);

	
	$target_path = $docRoot . $config['bannerdir'] . $id . '.jpg';
	if (!empty($uploadedBanner['tmp_name'])) {
		if(!move_uploaded_file($uploadedBanner['tmp_name'], $target_path)) {
			$msgs->addMsg("Banner upload failed, please try again.");
		} else {
			$msgs->addMsg('Upload successful.');
		}
	}

	$msgs->addMsg("Banner Updated");
}

