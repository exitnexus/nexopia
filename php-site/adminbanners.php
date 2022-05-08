<?

	$login=1;

	require_once("include/general.lib.php");

	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");
	
	if(!$bannerAdmin)
		die("You don't have permission to see this");

	//assume $banner exists

	define("ALL_EXCEPT", 1);
	define("ONLY", 0);

	$defaults = array(ALL_EXCEPT => 'All except', ONLY => 'Only');
	$sexes = array(SEX_UNKNOWN => "Unknown", SEX_MALE => "Male", SEX_FEMALE => "Female");

	$scope = array('Active', 'All');
	$size = getREQval('size', 'int');
	$type = getREQval('type', 'int');
	$clientid = getREQval('clientid', 'int');
	$all = getREQval('all', 'int', 1);
	$campaignid = getREQval('campaignid', 'int');
	$previousaction = getREQval('previousaction');
	$filterData = array('size' => $size,
						'type' => $type,
						'clientid' => $clientid,
						'all' => $all,
						'campaignid' => $campaignid);
	$data = getREQval('data', 'array');

	switch($action){
		case "Add Campaign":
		case "addcampaign":
			addCampaign($id = getREQval('id', 'int'), $data); //exit
			break;
		case "editcampaign":
			if($id = getREQval('id', 'int'))
				addCampaign($id);
		case "insertcampaign";
			if($data) {
				insertCampaign($data);
			}
			break;
		case "updatecampaign":
			if(($id = getREQval('id', 'int')) && $data)
				updateCampaign($id, $data);
			break;
		case "listbanners":
			listBanners($size, $type, $clientid, $all, $campaignid); //exit
		case "listcampaigns":
			listCampaigns(); //exit
		case "viewbanner":
			if($id = getREQval('id', 'int'))
				viewBanner($id); //exit
			break;
		case "editbanner":
			if($id = getREQval('id', 'int'))
				addBanner(0,$id);
//			editBanner($id); //exit
			break;
		case "updatebanner":
			if(($id = getREQval('id', 'int')) && $data)
				updateBanner($id, $data);
			break;

		case "deletebanner":
			if($id = getREQval('id', 'int'))
				deleteBanner($id);
			break;
		case "deletecampaign":
			if($id = getREQval('id', 'int'))
				deleteCampaign($id);
			break;

		case "bannerstats":
			$id = getREQval('id', 'int');
			$month = getREQval('month', 'int');
			$day = getREQval('day', 'int');
			$year = getREQval('year', 'int');
			$action2 = getREQval('action2');

			if(blank($month, $day, $year)){
				$start = usermktime(0,0,0,userdate("n"),userdate("j"),userdate("Y")); //today
			}else{
				if($action2 == '<--')
					$day--;
				elseif($action2 == '-->')
					$day++;
				$start = userMkTime(0, 0, 0, $month, $day, $year);
			}

			bannerStats($id, $start);	//exit

		case "addbanner":
		case "Add Banner":
			if(($newtype = getREQval('newtype', 'int')) && isset($banner->types[$newtype])) {
				$data = getREQval('data', 'array');
				addBanner($newtype, 0, $data);	//exit
			}
			break;

		case "insertbanner":
			if($data)
				insertBanner($data);
			break;

		case "listclients":
			listClients();	//exit

		case "addclient":
			editClient(0);	//exit

		case "Add Client":
		case "insertclient":
			updateClient(0);
			listClients();	//exit

		case "editclient":
			if($id = getREQval('id', 'int'))
				editClient($id); //exit
			break;

		case "updateclient":
		case "Update Client":
			$id = getREQval('id', 'int');
			updateClient($id);
			listClients();	//exit

		case "deleteclient":
			if($id = getREQval('id', 'int'))
				deleteClient($id);
			listClients();	//exit
			
		case "creditclient":
			if($id = getREQval('id', 'int'))
				creditClient($id); //exit

		case "Update Credits":
			if($id = getREQval('id', 'int')) {
				$credits = getREQval('credits', 'int');
				updateCredits($id, $credits); 
			}
			listClients(); //exit

		case "typestats":
			$size = getREQval('size', 'int', BANNER_BANNER);
			$month = getREQval('month', 'int');
			$day = getREQval('day', 'int');
			$year = getREQval('year', 'int');
			$action2 = getREQval('action2');

			if(blank($month, $day, $year)){
				$start = floor(time() / 86400) * 86400; //round down to the nearest day
			}else{
				if($action2 == '<--')
					$day--;
				elseif($action2 == '-->')
					$day++;
				$start = userMkTime(0, 0, 0, $month, $day, $year);
			}
			bannerTypeStats($size, $start);
	}
	//if we break and had a previous action we should return to that page
	//this occurs after things like banner adds and deletes that can
	//be initiated from multiple pages
	switch ($previousaction) {
		case "addcampaign":
		case "editcampaign":
			addCampaign($data['campaignid']); //exit
			break;
		case "listbanners":
			listBanners($size, $type, $clientid, $all, $campaignid);
			break;
	}

	listClients();


function listBanners($size = false, $type = false, $clientid = false, $all = false, $campaignid = false){
	global $banner, $scope, $filterData, $config;

	$params = array();
	$where = array("1");

	if($size){
		$where[] = "bannersize = #";
		$params[] = $size;
	}
	if($type){
		$where[] = "bannertype = #";
		$params[] = $type;
	}
	switch($all){
		case 1:
			break;
		case 0:
		default:
			$where[] = "enabled = 'y'";
			$where[] = "(enddate >= # || enddate = 0)";
			$params[] = time();
	}

	//collect information from the clients table
	$res = $banner->db->query("SELECT id, clientname FROM bannerclients");
	$clients = array();
	while($line = $res->fetchrow())
		$clients[$line['id']] = $line['clientname'];
	uasort($clients, 'strcasecmp');

	//collect information from the bannercampaigns table
	$res = $banner->db->query("SELECT id, clientid, title, enabled, enddate FROM bannercampaigns");
	$clientids = array();
	$campaignnames = array();
	$campaignenabled = array();
	while($line = $res->fetchrow()) {
		$clientids[$line['id']] = $line['clientid'];
		$campaignnames[$line['id']] = $line['title'];
		$campaignenabled[$line['id']] = ($line['enabled'] == 'y' && 
										($line['enddate'] == 0 || $line['enddate'] >= time()) );
	}
	uasort($campaignnames, 'strcasecmp');

	//collect information from the bannerstats table
	$endstatstime = usermktime(0, 0, 0, userdate('n'), userdate('d'), userdate('Y'));
	$res = $banner->db->prepare_query("SELECT bannerid, views, clicks, passbacks FROM bannerstats WHERE time >= # && time <= # ORDER BY time ASC", $endstatstime-86400, $endstatstime);
	$stats = array();
	while($line = $res->fetchrow()){
		if(!isset($stats[$line['bannerid']]))
			$stats[$line['bannerid']] = array('start' => $line);

		$stats[$line['bannerid']]['end'] = $line;
	}


	$maxviewsperday = 0;
	$totalviews = 0;
	$totalclicks = 0;
	$totalpassbacks = 0;
	$totalyestviews = 0;
	$totalyestclicks = 0;
	$totalyestpassbacks = 0;
	$classes = array('body','body2');
	$banners = array();

	//construct our array of data for the template, this will hold all
	//relevant information about the banners
	$res = $banner->db->prepare_array_query("SELECT * FROM banners WHERE " . implode(" && ", $where), $params);
	while($line = $res->fetchrow()){
		if ($clientid) {
			if ($clientid != $clientids[$line['campaignid']]) {
				continue;
			}
		}
		if (!($all >= 1)) { #hide banners disabled by the campaigns status
			if (!$campaignenabled[$line['campaignid']]) {
				continue;
			}
		}
		if ($campaignid) {
			if ($campaignid != $line['campaignid']) {
				continue;
			}
		}
		$line['clientname'] = $clients[$clientids[$line['campaignid']]];
		$line['campaignname'] = $campaignnames[$line['campaignid']];

		//do all total calculations before we start modifying the line
		$statviews = (isset($stats[$line['id']]) ? $stats[$line['id']]['end']['views'] - $stats[$line['id']]['start']['views'] : 0 );
		$statclicks = (isset($stats[$line['id']]) ? $stats[$line['id']]['end']['clicks'] - $stats[$line['id']]['start']['clicks'] : 0 );
		$statpassbacks = (isset($stats[$line['id']]) ? $stats[$line['id']]['end']['passbacks'] - $stats[$line['id']]['start']['passbacks'] : 0 );
		$maxviewsperday += $line['viewsperday'];
		$totalviews += $line['views']/* - $line['passbacks']*/;
		$totalclicks += $line['clicks'];
		$totalpassbacks += $line['passbacks'];
		$totalyestviews += $statviews/* - $statpassbacks*/;
		$totalyestclicks += $statclicks;
		$totalyestpassbacks += $statpassbacks;

		switch ($line['paytype']) {
			case BANNER_CPM:
				$line['paytype'] = "CPM";
				break;
			case BANNER_CPC:
				$line['paytype'] = "CPC";
				break;
			case BANNER_INHERIT:
				$line['paytype'] = "Inherited";
				break;
		}
		if ($line['payrate'] < 0) {
			$line['payrate'] = 'Inherited';
		} else {
			$line['payrate'] = $line['payrate'] . ' c';
		}
		$line['viewsperday'] = ($line['enabled'] == 'y' ? ($line['viewsperday'] ? number_format($line['viewsperday']) : "unlim" ) : 'disabled');
		if ($line['viewsperuser']) {
			if ($line['limitbyperiod']%86400 == 0) {
				$freqtype = 'd'; //days
				$freqperiod = $line['limitbyperiod']/86400;
			} elseif ($line['limitbyperiod']%3600 == 0) {
				$freqtype = 'h'; //hours
				$freqperiod = $line['limitbyperiod']/3600;
			} elseif ($line['limitbyperiod']%60 == 0) {
				$freqtype = 'm'; //minutes
				$freqperiod = $line['limitbyperiod']/60;
			} else {
				$freqtype = 's'; //seconds
				$freqperiod = $line['limitbyperiod'];
			}
			$line['viewsperuser'] = number_format($line['viewsperuser']) . "/$freqperiod$freqtype";
		} else {
			$line['viewsperuser'] = "";
		}
		$line['maxviews'] = ($line['maxviews'] ? number_format($line['maxviews']) : "unlim");
		$line['startdate'] = ($line['startdate'] > 0 ? userdate("M j", $line['startdate']) : "");
		$line['enddate'] = ($line['enddate'] > 0 ? userdate("M j", $line['enddate']) : "");
		
		$line['clickthru'] = ($line['link'] && $line['views'] ? number_format(($line['clicks']/$line['views'])*100, 3) . "%" : "N/A");
		$line['views'] = number_format($line['views']/* - $line['passbacks']*/);
		$line['clicks'] = ($line['link'] ? number_format($line['clicks']) : "N/A");
		$line['passbacks'] = number_format($line['passbacks']);
		
		$line['yestviews'] = number_format($statviews/* - $statpassbacks*/);
		$line['yestclicks'] = ($line['link'] ? number_format($statclicks) : "N/A");
		$line['yestpassbacks'] = number_format($statpassbacks);

		$banners[] = $line;
	}
	sortCols($banners, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_CASESTR, 'campaignname', SORT_ASC, SORT_CASESTR, 'clientname', SORT_ASC, SORT_STRING, 'bannersize');


	$cols = 27;

	$template = new template('admin/adminbanners/listBanners');
	$template->set('jsloc', "$config[jsloc]sorttable.js");
	$menu_template = new template('admin/adminbanners/menu');
	$menu_template->setMultiple($filterData);
	$template->set('menu', $menu_template->toString());
	$template->set('cols', $cols);
	$template->setMultiple($filterData);
	$template->set('selectCampaign', make_select_list_key($campaignnames, $campaignid));
	$template->set('selectBannerSize', make_select_list_key($banner->sizes, $size));
	$template->set('selectBannerType', make_select_list_key($banner->types, $type));
	$template->set('selectClientID', make_select_list_key($clients, $clientid));
	$template->set('selectScope', make_select_list_key($scope, $all));
	$template->set('classes', $classes);
	$template->set('banners', $banners);
	$template->set('banner', $banner);
	$template->set('maxviewsperday', number_format($maxviewsperday));
	$template->set('totalviews', number_format($totalviews));
	$template->set('totalclicks', number_format($totalclicks));
	$template->set('totalclickthru',  ($totalviews ? number_format($totalclicks/$totalviews*100, 3) . "%" : "N/A"));
	$template->set('totalpassbacks', number_format($totalpassbacks));
	$template->set('totalyestviews',  number_format($totalyestviews));
	$template->set('totalyestclicks', number_format($totalyestclicks));
	$template->set('totalyestpassbacks', number_format($totalyestpassbacks));
	$template->set('selectNewBannerType', make_select_list_key($banner->types));
	$template->display();
	exit;
}
#list clients list campaigns banners campaign stats banner stats type stats

function listCampaigns(){
	global $banner, $scope, $filterData, $config;

	$params = array();
	$where = array("1");

	if ($filterData['clientid']) {
		$where[] = "clientid = #";
		$params[] = $filterData['clientid'];
	}
	switch($filterData['all']){
		case 1:
			break;
		case 0:
		default:
			$where[] = "enabled = 'y'";
			$where[] = "(enddate >= # || enddate = 0)";
			$params[] = time();
	}

	//collect information about what clients we have
	$res = $banner->db->query("SELECT id, clientname FROM bannerclients");
	$clients = array();
	while($line = $res->fetchrow())
		$clients[$line['id']] = $line['clientname'];
	uasort($clients, 'strcasecmp');

	//Get our list of campaigns and add clientname to it before we sort it
	$res = $banner->db->prepare_array_query("SELECT * FROM bannercampaigns WHERE " . implode(" && ", $where), $params);
	$campaigns = array();
	while($line = $res->fetchrow()){
		$line['clientname'] = $clients[$line['clientid']];
		$campaigns[] = $line;
		$bannercount[$line['id']] = 0;
		$totalbannercount[$line['id']] = 0;
	}
	sortCols($campaigns, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_CASESTR, 'clientname');

	$endstatstime = usermktime(0, 0, 0, userdate('n'), userdate('d'), userdate('Y'));
	$res = $banner->db->prepare_query("SELECT bannerid, views, clicks, passbacks FROM bannerstats WHERE time >= # && time <= # ORDER BY time ASC", $endstatstime-86400, $endstatstime);
	$stats = array();
	while($line = $res->fetchrow()){
		if(!isset($stats[$line['bannerid']]))
			$stats[$line['bannerid']] = array('start' => $line);

		$stats[$line['bannerid']]['end'] = $line;
	}
	//How many banners per campaign?
	$banner->db->prepare_query("SELECT * FROM banners");
	
	$bannercount = array();
	$totalbannercount = array();
	while ($line = $banner->db->fetchrow()) {
		if ($line['enabled'] == 'y') {
			$bannerslist[$line['campaignid']][$line['id']] = $line;
			if (!isset($bannercount[$line['campaignid']])) $bannercount[$line['campaignid']] = 0;
			$bannercount[$line['campaignid']]++;
		}
		if (!isset($totalbannercount[$line['campaignid']])) $totalbannercount[$line['campaignid']] = 0;
		$totalbannercount[$line['campaignid']]++;
	}

	$template = new template('admin/adminbanners/listCampaigns');
	$template->set('jsloc', "$config[jsloc]sorttable.js");
	$menu_template = new template('admin/adminbanners/menu');
	$menu_template->setMultiple($filterData);
	$template->set('menu', $menu_template->toString());
	$template->set('bannercount', $bannercount);
	$template->set('totalbannercount', $totalbannercount);
	$cols = 24;
	$template->set('cols', $cols);

	$template->set('selectClientID', make_select_list_key($clients, $filterData['clientid']));
	$template->set('selectScope', make_select_list_key($scope, $filterData['all']));
	$template->setMultiple($filterData);
	$classes = array('body','body2');
	$template->set('classes', $classes);
	$template->set('campaigns', $campaigns);

	$maxviewsperday = 0;
	$totalviews = 0;
	$totalclicks = 0;
	$totalpassbacks = 0;

	$totalyestviews = 0;
	$totalyestclicks = 0;
	$totalyestpassbacks = 0;

	$views = array();
	$clicks = array();
	$clickthru = array();
	$passbacks = array();
	$yestviews = array();
	$yestclicks = array();
	$yestpassbacks = array();
	$enabled = array();
	$paytype = array();
	$viewsperday = array();
	$viewsperuser = array();
	$maxviews = array();
	$startdate = array();
	$enddate = array();
	
	$j = -1;
	foreach($campaigns as $line){
		$j++;

		$statviews = 0;
		if (isset($bannerslist[$line['id']])) {
			foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
				if (isset($stats[$bannerid])) {
					$statviews += $stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'];
				}
			}
		}
		$statclicks = 0;
		if (isset($bannerslist[$line['id']])) {
			foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
				if (isset($stats[$bannerid])) {
					$statclicks += $stats[$bannerid]['end']['clicks'] - $stats[$bannerid]['start']['clicks'];
				}
			}
		}
		$statpassbacks = 0;
		if (isset($bannerslist[$line['id']])) {
			foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
				if (isset($stats[$bannerid])) {
					$statpassbacks += $stats[$bannerid]['end']['passbacks'] - $stats[$bannerid]['start']['passbacks'];
				}
			}
		}
		
		$enabled[$j] = $line['enabled'] == 'y' ? "Yes" : "No";
		$paytype[$j] = ($line['paytype'] == BANNER_CPC ? "CPC" : "CPM");
		$viewsperday[$j] = ($line['enabled'] == 'y' ? ($line['viewsperday'] ? number_format($line['viewsperday']) : "unlim" ) : 'disabled');
		if ($line['viewsperuser']) {
			if ($line['limitbyperiod']%86400 == 0) {
				$freqtype = 'd'; //days
				$freqperiod = $line['limitbyperiod']/86400;
			} elseif ($line['limitbyperiod']%3600 == 0) {
				$freqtype = 'h'; //hours
				$freqperiod = $line['limitbyperiod']/3600;
			} elseif ($line['limitbyperiod']%60 == 0) {
				$freqtype = 'm'; //minutes
				$freqperiod = $line['limitbyperiod']/60;
			} else {
				$freqtype = 's'; //seconds
				$freqperiod = $line['limitbyperiod'];
			}
			$viewsperuser[$j] = number_format($line['viewsperuser']) . "/$freqperiod$freqtype";
		} else {
			$viewsperuser[$j] = "";
		}
		

		$maxviews[$j] = ($line['maxviews'] ? number_format($line['maxviews']) : "unlim");
		$startdate[$j] = ($line['startdate'] > 0 ? userdate("M j", $line['startdate']) : "");
		$enddate[$j] = ($line['enddate'] > 0 ? userdate("M j", $line['enddate']) : "");

		$views[$j] = 0;
		if (isset($bannerslist[$line['id']])) {
			foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
				$views[$j] += $bannerline['views'];
			}
		}
		$unformattedviews = $views[$j];
		$views[$j] = number_format($views[$j]);
		$clicks[$j] = "N/A";
		if (isset($bannerslist[$line['id']])) {
			foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
			if ($bannerline['link'] && $clicks[$j] == "N/A") {
					$clicks[$j] = 0;
				}
				if ($clicks[$j] !== "N/A") {
					$clicks[$j] += $bannerline['clicks'];
				}
			}
		}
		if ($clicks[$j] !== "N/A") {
			$clickthru[$j] =  ($views[$j] ? number_format($clicks[$j]/$unformattedviews*100, 3) . '%' : "Undefined");
			$clicks[$j] = number_format($clicks[$j]);
		} else {
			$clickthru[$j] = "N/A";
		}
		$passbacks[$j] = 0;
		if (isset($bannerslist[$line['id']])) {
			foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
				$passbacks[$j] += $bannerline['passbacks'];
			}
		}
		$passbacks[$j] = number_format($passbacks[$j]);

		$yestviews[$j] = number_format($statviews);
		$yestclicks[$j] = ($clicks[$j] !== "N/A" ? number_format($statclicks) : "N/A");
		$yestpassbacks[$j] = number_format($statpassbacks);

		if (isset($bannerslist[$line['id']]) && is_array($bannerslist[$line['id']])) {
			foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
				$totalviews += $bannerline['views'];
				$totalclicks += $bannerline['clicks'];
				$totalpassbacks += $bannerline['passbacks'];
			}
		}
		$maxviewsperday += $line['viewsperday'];

		
		$totalyestviews += $statviews;
		$totalyestclicks += $statclicks;
		$totalyestpassbacks += $statpassbacks;
		
	}
	$template->set('views', $views);
	$template->set('clicks', $clicks);
	$template->set('clickthru', $clickthru);
	$template->set('passbacks', $passbacks);

	$template->set('yestviews', $yestviews);
	$template->set('yestclicks', $yestclicks);
	$template->set('yestpassbacks', $yestpassbacks);

	$template->set('maxviewsperday', number_format($maxviewsperday));
	$template->set('totalviews', number_format($totalviews));
	$template->set('totalclicks', number_format($totalclicks));
	$template->set('totalpassbacks', number_format($totalpassbacks));
	$template->set('totalyestviews', number_format($totalyestviews));
	$template->set('totalyestclicks', number_format($totalyestclicks));
	$template->set('totalyestpassbacks', number_format($totalyestpassbacks));
	
	$template->set('banner', $banner);
	$template->set('enabled', $enabled);
	$template->set('paytype', $paytype);
	$template->set('viewsperday', $viewsperday);
	$template->set('viewsperuser', $viewsperuser);
	$template->set('maxviews', $maxviews);
	$template->set('startdate', $startdate);
	$template->set('enddate', $enddate);
	$template->display();
	exit;
}

function addCampaign($id = 0, $data = array()) {
	global $banner, $defaults, $sexes, $usersdb, $configdb, $filterData, $userData;


	$clientid		= 0;
	$minviewsperday = 0;
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
	$cols = 2;
	$targeting_exists = false;
	
	if($id){
		//This means we'll be displaying a list of banners in the campaign
		$edit = true;
		$cols = 11;
		$res = $banner->db->prepare_query("SELECT * FROM banners WHERE campaignid = #", $id);
		while ($line = $res->fetchrow()) {
			switch ($line['paytype']) {
				case BANNER_CPM:
				$line['paytype'] = "CPM";
				break;
				case BANNER_CPC:
				$line['paytype'] = "CPC";
				break;
				case BANNER_INHERIT:
				$line['paytype'] = "Inherited";
				break;
			}
			if ($line['payrate'] < 0) {
				$line['payrate'] = "Inherited";
			} else {
				$line['payrate'] = $line['payrate'] . ' c';
			}
			$line['type'] = $banner->types[$line['bannertype']];
			$line['size'] = $banner->sizes[$line['bannersize']];

			$line['viewsperday'] = ($line['enabled'] == 'y' ? ($line['viewsperday'] ? number_format($line['viewsperday']) : "unlim" ) : 'disabled');
			$line['minviewsperday'] = ($line['enabled'] == 'y' ? number_format($line['minviewsperday'])  : 'disabled');
			
			if ($line['viewsperuser']) {
				if ($line['limitbyperiod']%86400 == 0) {
					$freqtype = 'd'; //days
					$freqperiod = $line['limitbyperiod']/86400;
				} elseif ($line['limitbyperiod']%3600 == 0) {
					$freqtype = 'h'; //hours
					$freqperiod = $line['limitbyperiod']/3600;
				} elseif ($line['limitbyperiod']%60 == 0) {
					$freqtype = 'm'; //minutes
					$freqperiod = $line['limitbyperiod']/60;
				} else {
					$freqtype = 's'; //seconds
					$freqperiod = $line['limitbyperiod'];
				}
				$line['viewsperuser'] = number_format($line['viewsperuser']) . "/$freqperiod$freqtype";
			} else {
				$line['viewsperuser'] = "";
			}
			$line['maxviews'] = ($line['maxviews'] ? number_format($line['maxviews']) : "unlim");
			$line['startdate'] = ($line['startdate'] > 0 ? userdate("M j", $line['startdate']) : "");
			$line['enddate'] = ($line['enddate'] > 0 ? userdate("M j", $line['enddate']) : "");
			$banners[] = $line;
		}

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
				$targeting_exists = true;
				$sex = explode(',', $sex);
			}

			if($age == ''){
				$age = array();
			}else{
				$targeting_exists = true;
				$age = explode(',', $age);
				$agedefault = (isset($age[0]) && $age[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($loc == ''){
				$loc = array();
			}else{
				$targeting_exists = true;
				$loc = explode(',', $loc);
				$locdefault = (isset($loc[0]) && $loc[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($page != ''){
				$targeting_exists = true;
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
				$targeting_exists = true;
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
				$targeting_exists = true;
				$timetable = new timetable($allowedtimes);
				$allowedTimesTable = $timetable->getTimeTable();
			}
		}
	}else{
		if(count($data))
			extract($data);
	}
		
	//We need client names in order to make a select box with them
	$res = $banner->db->query("SELECT id, clientname FROM bannerclients");
	while($line = $res->fetchrow())
		$clients[$line['id']]=$line['clientname'];
	uasort($clients, 'strcasecmp');

	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

	$template = new template('admin/adminbanners/addCampaign');
	if (!isset($allowedTimesTable)) $allowedTimesTable = '';
	$template->set('allowedTimesTable', $allowedTimesTable);
	$template->set('classes', array('body', 'body2'));
	if (!isset($banners)) $banners = array();
	$template->set('banners', $banners);
	$template->set('edit', $edit);
	$template->set('cols', $cols);
	$template->set('id', $id);
	$template->set('display_targeting', ($targeting_exists || !$edit));
	
	if (!$id) $selectClient = make_select_list_key($clients, $filterData['clientid']);
	else $selectClient = make_select_list_key($clients, $clientid);
	$template->set('selectClient', $selectClient);
	$template->set('checkEnabled', makeCheckBox('data[enabled]','Enabled', $enabled == 'y'));
	if (!isset($pagedominance)) $pagedominance = 'n';
	$template->set('checkPageDominance', makeCheckBox('data[pagedominance]','Enabled', $pagedominance == 'y'));
	$template->set('enabled', $enabled == 'y');
	$template->set('title', $title);


	$template->set('selectSex', make_select_list_multiple_key($sexes, $sex));
	$template->set('selectAgeType', make_select_list_key($defaults, $agedefault));
	$template->set('selectAgeRange', make_select_list_multiple(range(14,65), $age));

	// $locations = new category( $configdb, "locs");
	$template->set('selectLocationType', make_select_list_key($defaults, $locdefault));
	$template->set('selectLocations', locationAutocomplete_multiple($loc, "data[locs]"));
	$template->set('selectPagesType', make_select_list_key($defaults, $pagedefault));
	$template->set('page', $page);

	$interestcats = new category( $configdb, "interests");
	$template->set('selectInterests', makeCatSelect_multiple($interestcats->makeBranch(), $interests));

	$template->set('payrate', $payrate);
	$template->set('selectPayType', make_select_list_key(array(BANNER_CPM => "CPM", BANNER_CPC => "CPC"), $paytype));

	$template->set('maxviews', $maxviews);
	$template->set('minviewsperday', $minviewsperday);
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
	$template->setMultiple($filterData);
	$template->display();
	exit;
}

function insertCampaign($data){
	global $banner, $msgs, $docRoot, $config;

	if(empty($data['clientid'])){
		$msgs->addMsg("Bad Client");
		addCampaign(0, $data); //exit
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

	$set[] = "clientid = #"; 		$params[] = $data['clientid'];
	$set[] = "maxviews = #"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = #"; 		$params[] = $data['maxclicks'];
	$set[] = "minviewsperday = #"; 	$params[] = $data['minviewsperday'];
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
	$set[] = "pagedominance = ?"; 	$params[] = (isset($data['pagedominance']) ? 'y' : 'n');
	$set[] = "refresh = #"; 		$params[] = $data['refresh'];
	
	$banner->db->prepare_array_query("INSERT INTO bannercampaigns SET " . implode(", ", $set), $params);
	
	$id = $banner->db->insertid();

	$banner->addCampaign($id);

	$msgs->addMsg("Campaign Added");
	addCampaign($id);
}

function updateCampaign($id, $data){
	global $banner, $msgs, $config, $docRoot;

	if(empty($data['clientid'])){
		$msgs->addMsg("Bad Client");
		addCampaign(0, $data); //exit
	}
	
	$res = $banner->db->prepare_query("SELECT * FROM bannercampaigns WHERE id = #", $id);
	if ($line = $res->fetchrow()) {
		$previouslyenabled = $line['enabled'] == 'y';
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

	$set[] = "clientid = #"; 		$params[] = $data['clientid'];
	$set[] = "maxviews = #"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = #"; 		$params[] = $data['maxclicks'];
	$set[] = "minviewsperday = #"; 	$params[] = $data['minviewsperday'];
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
	$set[] = "pagedominance = ?"; 	$params[] = (isset($data['pagedominance']) ? 'y' : 'n');
	$set[] = "refresh = #"; 		$params[] = $data['refresh'];

	$params[] = $id;
	
	if (isset($data['enabled']) && !$previouslyenabled) {
		$banner->db->prepare_query("UPDATE banners SET enabled = 'y' WHERE campaignid = #", $id);
	}
	
	$banner->db->prepare_array_query("UPDATE bannercampaigns SET " . implode(", ", $set) . " WHERE id = #", $params);

	$banner->updateCampaign($id);

	$msgs->addMsg("Campaign Updated");
	addCampaign($id);
}


function addBanner($bannertype, $id = 0, $data = array()){
	global $banner, $defaults, $sexes, $usersdb, $configdb, $filterData, $previousaction;

	$bannersize		= "";
	$campaignid		= $filterData['campaignid'];
	$freqtype		= 3; //days
	$freqperiod		= 0;
	$viewsperday	= 0;
	$minviewsperday	= 0;
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
	$image			= "";
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

	$targeting_exists = false;
	
	$refresh = -1;

	$cols = 2;

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
				$targeting_exists = true;
				$sex = explode(',', $sex);
			}

			if($age == ''){
				$age = array();
			}else{
				$targeting_exists = true;
				$age = explode(',', $age);
				$agedefault = (isset($age[0]) && $age[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($loc == ''){
				$loc = array();
			}else{
				$targeting_exists = true;
				$loc = explode(',', $loc);
				$locdefault = (isset($loc[0]) && $loc[0] == '0' ? ALL_EXCEPT : ONLY);
			}

			if($page != ''){
				$targeting_exists = true;
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
				$targeting_exists = true;
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
				$targeting_exists = true;
				$timetable = new timetable($allowedtimes);
				$allowedTimesTable = $timetable->getTimeTable();
			}
		}
	}else{
		if(count($data))
			extract($data);
	}


	//get a list of campaigns so we can create a selection box with them
	$res = $banner->db->query("SELECT id, title FROM bannercampaigns");
	while($line = $res->fetchrow())
		$campaigns[$line['id']]=$line['title'];
	uasort($campaigns, 'strcasecmp');

	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

	$template = new template('admin/adminbanners/addBanner');

	if($id){
		$template->set('displayBanner', $banner->getBannerID($id));
	}
	//$template->setMultiple($filterData);
	
	$template->set('display_targeting', ($targeting_exists || !$id));
	$template->set('cols', $cols);
	$template->set('id', $id);
	$template->set('bannertype', $bannertype);
	$template->set('selectCampaign', make_select_list_key($campaigns, $campaignid));
	$template->set('selectBannerSize', make_select_list_key($banner->sizes, $bannersize));
	$template->set('checkEnabled', makeCheckBox('data[enabled]','Enabled', $enabled == 'y'));
	$template->set('title', $title);
	$template->set('image', $image);
	$template->set('link', $link);
	$template->set('alt', $alt);
	$template->set('previousaction', $previousaction);
	if (!isset($allowedTimesTable)) $allowedTimesTable = '';
	$template->set('allowedTimesTable', $allowedTimesTable);
	
	$template->set('BANNER_IMAGE', ($bannertype == BANNER_IMAGE));
	$template->set('BANNER_FLASH', ($bannertype == BANNER_FLASH));
	$template->set('BANNER_IFRAME', ($bannertype == BANNER_IFRAME));
	$template->set('BANNER_HTML', ($bannertype == BANNER_HTML));
	$template->set('BANNER_TEXT', ($bannertype == BANNER_TEXT));
	
	if($id){
		$template->set('bannersize', $bannersize);
	}

	$template->set('selectSex', make_select_list_multiple_key($sexes, $sex));
	$template->set('selectAgeType', make_select_list_key($defaults, $agedefault));
	$template->set('selectAgeRange', make_select_list_multiple(range(14,65), $age));
	// $locations = new category( $configdb, "locs");
	$template->set('selectLocationType', make_select_list_key($defaults, $locdefault));
	$template->set('selectLocations', locationAutocomplete_multiple($loc, "data[locs]"));
	$template->set('selectPagesType', make_select_list_key($defaults, $pagedefault));
	$template->set('page', $page);
	$interestcats = new category( $configdb, "interests");
	$template->set('selectInterests', makeCatSelect_multiple($interestcats->makeBranch(), $interests));
	$template->set('allowedtimes', (isset($allowedtimes) ? $allowedtimes : ''));
	
	$template->set('refresh', $refresh);

	$template->set('payrate', $payrate);
	$template->set('selectPayType', make_select_list_key(array(BANNER_INHERIT => "Inherit", BANNER_CPM => "CPM", BANNER_CPC => "CPC"), $paytype));

	$template->set('maxviews', $maxviews);
	$template->set('maxclicks', $maxclicks);
	$template->set('minviewsperday', $minviewsperday);
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
	$template->setMultiple($filterData);

	$template->display();
	exit;
}

function insertBanner($data){
	global $banner, $msgs, $previousaction;

	if(empty($data['campaignid'])){
		$msgs->addMsg("Bad Campaign");
		addBanner($data['bannertype'], 0, $data); //exit
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
	$set[] = "minviewsperday = #"; 	$params[] = $data['minviewsperday'];
	$set[] = "viewsperday = #"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = #";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = #";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyperiod = #";	$params[] = $limitbyseconds;
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = #"; 		$params[] = $start;
	$set[] = "enddate = #"; 		$params[] = $end;
	$set[] = "payrate = #"; 		$params[] = $data['payrate'];
	$set[] = "paytype = #"; 		$params[] = $data['paytype'];
	$set[] = "moded = ?"; 			$params[] = 'approved';
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "image = ?"; 			$params[] = (isset($data['image']) ? $data['image'] : "");
	$set[] = "link = ?"; 			$params[] = (isset($data['link']) ? $data['link'] : "");
	$set[] = "alt = ?"; 			$params[] = (isset($data['alt']) ? $data['alt'] : "");
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

	$banner->db->prepare_array_query("INSERT INTO banners SET " . implode(", ", $set), $params);

	$id = $banner->db->insertid();

	uploadBanner($id, getFILEval('uploadbanner'), 'image');
	uploadBanner($id, getFILEval('uploadflashbanner'), 'alt');

	$banner->addBanner($id);

	$msgs->addMsg("Banner Added");

}

function updateBanner($id, $data){
	global $banner, $msgs, $previousaction;

	if(empty($data['campaignid'])){
		$msgs->addMsg("Bad Campaign");
		addBanner($data['bannertype'], 0, $data); //exit
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

	//$res = $banner->db->prepare_query("SELECT clientid FROM bannercampaigns WHERE id = ?", $data['campaignid']);
	//$line = $res->fetchrow();

	//$set[] = "clientid = ?"; 		$params[] = $line['clientid'];
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
	$set[] = "bannertype = #"; 		$params[] = $data['bannertype'];
	$set[] = "maxviews = #"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = #"; 		$params[] = $data['maxclicks'];
	$set[] = "minviewsperday = #"; 	$params[] = $data['minviewsperday'];
	$set[] = "viewsperday = #"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = #";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = #";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyperiod = #";	$params[] = $limitbyseconds;
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = #"; 		$params[] = $start;
	$set[] = "enddate = #"; 		$params[] = $end;
	$set[] = "payrate = #"; 		$params[] = $data['payrate'];
	$set[] = "paytype = #"; 		$params[] = $data['paytype'];
	$set[] = "moded = ?"; 			$params[] = 'approved';
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "image = ?"; 			$params[] = (isset($data['image']) ? $data['image'] : "");
	$set[] = "link = ?"; 			$params[] = (isset($data['link']) ? $data['link'] : "");
	$set[] = "alt = ?"; 			$params[] = (isset($data['alt']) ? $data['alt'] : "");
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

	uploadBanner($id, getFILEval('uploadbanner'), 'image');
	uploadBanner($id, getFILEval('uploadflashbanner'), 'alt');

	$banner->updateBanner($id);

	$msgs->addMsg("Banner Updated");
}

function uploadBanner($id, $file, $col, $softlimit = 50, $hardlimit = 100){
	global $config, $banner, $msgs, $mogfs;

	if(empty($file) || empty($file['tmp_name']) || !$file['size'])
		return true;

	if($softlimit && $softlimit*1024 < $file['size']){
		$msgs->addMsg("Uploaded file is bigger than the soft size limit of $softlimit KB.");
	}

	if($hardlimit && $hardlimit*1024 < $file['size']){
		$msgs->addMsg("Uploaded file is bigger than the hard size limit of $hardlimit KB. Upload failed.");
		return false;
	}

	$res = $banner->db->prepare_query("SELECT $col FROM banners WHERE id = #", $id);
	$curimage = $res->fetchfield();

	$ver = 0;
	$tail = "";
	if($curimage){
		if(preg_match("/(\d+)-(\d+).([a-zA-Z]+)(\?.*)/", $curimage, $matches)){ //auto-generated names
			$ver = $matches[2];
			$tail = $matches[4];
		}elseif(preg_match("/([-.a-zA-Z]+)(\?.*)/", $curimage, $matches)){
			$tail = $matches[2];
		}
	}

	$fileending = substr($file['name'], strrpos($file['name'], '.') + 1);

	do{
		$ver++;
		$newfilename = "$id-$ver.$fileending";
	}while($mogfs->test(FS_BANNERS, $newfilename));

	$banner->db->prepare_query("UPDATE banners SET $col = ? WHERE id = #", $newfilename . $tail, $id);
	
	$data = file_get_contents($file['tmp_name']);
	if($mogfs->add(FS_BANNERS, $newfilename, $data))
		$msgs->addMsg('Upload successful.');
	else
		$msgs->addMsg("Banner upload failed, please try again or specify a banner link.");
}

function deleteBanner($id){
	global $banner, $docRoot, $config;

	$banner->db->prepare_query("DELETE FROM banners WHERE id = #", $id);
	$banner->db->prepare_query("DELETE FROM bannerstats WHERE bannerid = #", $id);

	$banner->deleteBanner($id);
}

//delete campaign and all banners it contains
function deleteCampaign($id){
	global $banner, $docRoot, $config;
	$result = $banner->db->prepare_query("SELECT id FROM banners WHERE campaignid = #", $id);
	while ($line = $result->fetchrow()) {
		$banner->db->prepare_query("DELETE FROM bannerstats WHERE bannerid = #", $line['id']);
	}
	$banner->db->prepare_query("DELETE FROM banners WHERE campaignid = #", $id);
	$banner->db->prepare_query("DELETE FROM bannercampaigns WHERE id = #", $id);

	$banner->deleteCampaign($id);
}


function bannerStats($id, $start){
	global $banner;

	$end = $start + 86400;

	$res = $banner->db->prepare_query("SELECT time, views, clicks, passbacks FROM bannerstats WHERE bannerid = # && time >= # && time <= # ORDER BY time", $id, $start, $end);

	$stats = array();
	while($line = $res->fetchrow())
		$stats[] = $line;

	$month= userdate("n",$start);
	$day  = userdate("j",$start);
	$year = userdate("Y",$start);


	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]><tr><td class=header colspan=9 align=center>";
	preserveFilterData();
	echo "<input type=submit class=body name=action2 value=\"<--\">";
	echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months, $month) . "</select>";
	echo "<select class=body name=\"day\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
	echo "<select class=body name=\"year\"><option value=0>Year" . make_select_list(array_reverse(range(2002,date("Y"))),$year) . "</select>";
	echo "<input type=hidden name=action value=bannerstats>";
	echo "<input type=hidden name=id value=$id>";
	echo "<input class=body type=submit name=action2 value=Go>";
	echo "<input type=submit class=body name=action2 value=\"-->\">";

	echo "</td></tr></form>";

	echo "<tr>";
	echo "<td class=header>Time</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Click Thru</td>";
	echo "<td class=header>Passbacks</td>";
	echo "<td class=header>PB Rate</td>";
	echo "<td class=header>Total Views</td>";
	echo "<td class=header>Total Clicks</td>";
	echo "<td class=header>Total Click Thru</td>";

	echo "</tr>";

	$last = false;
	$first = false;
	foreach($stats as $line){
		if($last){
			echo "<tr>";
			echo "<td class=body>" . userDate("M j, Y, g:i a", $last['time']) . "</td>";
			echo "<td class=body align=right>" . number_format($line['views'] - $last['views']) . "</td>";
			echo "<td class=body align=right>" . number_format($line['clicks'] - $last['clicks']) . "</td>";
			echo "<td class=body align=right>" . (($line['views'] - $last['views']) ? number_format(100*($line['clicks'] - $last['clicks']) / ($line['views'] - $last['views']),3) . "%" : "N/A" ) ."</td>";
			echo "<td class=body align=right>" . number_format($line['passbacks'] - $last['passbacks']) . "</td>";
			echo "<td class=body align=right>" . (($line['views'] - $last['views']) ? number_format(($line['passbacks'] - $last['passbacks'])/($line['views'] - $last['views'])*100, 2) . "%" : "N/A") . "</td>";
			echo "<td class=body align=right>" . number_format($last['views']) . "</td>";
			echo "<td class=body align=right>" . number_format($last['clicks']) . "</td>";
			echo "<td class=body align=right>" . ($last['views'] ? number_format(100*$last['clicks'] / $last['views'],3) . "%" : "N/A" ) ."</td>";
			echo "</tr>";
		}else{
			$first = $line;
		}
		$last = $line;
	}
	if($first && $last){
		echo "<tr>";
		echo "<td class=header></td>";
		echo "<td class=header align=right>" . number_format($last['views'] - $first['views']) . "</td>";
		echo "<td class=header align=right>" . number_format($last['clicks'] - $first['clicks']) . "</td>";
		echo "<td class=header align=right>" . (($last['views'] - $first['views']) ? number_format(100*($last['clicks'] - $first['clicks']) / ($last['views'] - $first['views']),3) . "%" : "N/A" ) ."</td>";
		echo "<td class=header align=right>" . number_format($last['passbacks'] - $first['passbacks']) . "</td>";
		echo "<td class=header align=right>" . number_format(($last['passbacks'] - $first['passbacks'])/($last['views'] - $first['views'])*100, 2) . "%</td>";

		echo "<td class=header colspan=3></td>";
		echo "</tr>";
	}

	echo "</table>";

	incFooter();
	exit;
}

function array_merge_recursive_sum($array, $second){
	foreach($second as $key => $value){
		if($value === null)
			$value = array();

		if(is_array($value)){
			if(!isset($array[$key]))
				$array[$key] = array();

			$array[$key] = array_merge_recursive_sum($array[$key], $value);
		}else{
			if(!isset($array[$key]))
				$array[$key] = 0;

			$array[$key] += $value;
		}
	}
	return $array;
}

function bannerTypeStats($size, $date = false){
	global $banner, $configdb;


	$res = $banner->db->prepare_query("SELECT * FROM bannertypestats WHERE size = # && time >= # && time <= #", $size, $date, $date + 86400);

	$views = array();
	$clicks = array();

	while($line = $res->fetchrow()){
		$views = array_merge_recursive_sum($views, unserialize(gzuncompress($line['viewsdump'])));
		$clicks = array_merge_recursive_sum($clicks, unserialize(gzuncompress($line['clicksdump'])));
	}

	$month= userdate("n",$date);
	$day  = userdate("j",$date);
	$year = userdate("Y",$date);


	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	preserveFilterData();
	echo "<tr><td class=header align=center colspan=4>";
	echo "<input type=submit class=body name=action2 value=\"<--\">";
	echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months, $month) . "</select>";
	echo "<select class=body name=\"day\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
	echo "<select class=body name=\"year\"><option value=0>Year" . make_select_list(array_reverse(range(2002,date("Y"))),$year) . "</select>";
	echo "<select class=body name=size>" . make_select_list_key($banner->sizes, $size) . "</select>";
	echo "<input type=hidden name=action value=typestats>";
	echo "<input class=body type=submit name=action2 value=Go>";
	echo "<input type=submit class=body name=action2 value=\"-->\">";
	echo "</td></tr>";
	echo "</form>";

	if(!isset($views['total'])){
		echo "<tr><td class=body colspan=4>No data for that date</td></tr>";
	}else{
		echo "<tr><td class=header colspan=4>Stats: " . number_format($views['total']) . " / " . number_format($clicks['total']) . "</td></tr>";

		echo "<tr>";
		if(isset($views['agesex']))
			echo "<td class=header>Age+Sex</td>";
		if(isset($views['loc']))
			echo "<td class=header>Locs</td>";
		if(isset($views['page']))
			echo "<td class=header>Pages</td>";
		if(isset($views['interests']))
			echo "<td class=header>Interests</td>";
		echo "</tr>";

		echo "<tr>";

	//age+sex
		if(isset($views['agesex'])){
			echo "<td class=body valign=top>";

				echo "<table>";

				echo "<tr>";
				echo "<td class=header align=center>Age</td>";
				echo "<td class=header align=center>Male</td>";
				echo "<td class=header align=center>Female</td>";
				echo "</tr>";

				$tvm = 0;
				$tcm = 0;
				$tvf = 0;
				$tcf = 0;

				if($views['agesex'][0][0]){
					echo "<tr>";
					echo "<td class=header align=right>N/A</td>";
					echo "<td class=body align=center colspan=2>" . number_format($views['agesex'][0][0]) . " / " . number_format($clicks['agesex'][0][0]) . "</td>";
					echo "</tr>";
				}

				foreach($views['agesex'] as $age => $sexes){
					if( $views['agesex'][$age][SEX_MALE] == 0 &&
						$clicks['agesex'][$age][SEX_FEMALE] == 0 &&
						$views['agesex'][$age][SEX_MALE] == 0 &&
						$clicks['agesex'][$age][SEX_FEMALE] == 0)
							continue;

					echo "<tr>";
					echo "<td class=header align=right>$age</td>";
					echo "<td class=body align=right>" . number_format($views['agesex'][$age][SEX_MALE]) . " / " . number_format($clicks['agesex'][$age][SEX_MALE]) . "</td>";
					echo "<td class=body align=right>" . number_format($views['agesex'][$age][SEX_FEMALE]) . " / " . number_format($clicks['agesex'][$age][SEX_FEMALE]) . "</td>";
					echo "</tr>";

					$tvm += $views['agesex'][$age][SEX_MALE];
					$tcm += $clicks['agesex'][$age][SEX_MALE];
					$tvf += $views['agesex'][$age][SEX_FEMALE];
					$tcf += $clicks['agesex'][$age][SEX_FEMALE];
				}

				echo "<tr><td class=header align=right>Total:</td><td class=header align=right>" . number_format($tvm) . " / " . number_format($tcm) . "</td><td class=header align=right>" . number_format($tvf) . " / " . number_format($tcf) . "</td></tr>";
				echo "<tr><td class=header align=right></td><td class=header align=center colspan=2>" . number_format($tvm+$tvf+$views['agesex'][0][0]) . " / " . number_format($tcm+$tcf+$clicks['agesex'][0][0]) . "</td></tr>";
				echo "</table>";

			echo "</td>";
		}

	//locs
		if(isset($views['loc'])){
			echo "<td class=body valign=top>";

				echo "<table>";

				echo "<tr>";
				echo "<td class=header align=center>Locs</td>";
				echo "<td class=header align=center>Views / Clicks</td>";
				echo "</tr>";

				global $usersdb;
				$locations = new category( $configdb, "locs");

				arsort($views['loc']);

				$tv = 0;
				$tc = 0;

				foreach($views['loc'] as $loc => $val){
					if($views['loc'][$loc] == 0)
						continue;

					echo "<tr>";
					echo "<td class=header align=right>" . ($loc ? $locations->getCatName($loc) : "N/A") . "</td>";
					echo "<td class=body align=right>" . number_format($views['loc'][$loc]) . " / " . (isset($clicks['loc'][$loc]) ? number_format($clicks['loc'][$loc]) : 0 ) . "</td>";
					echo "</tr>";

					$tv += $views['loc'][$loc];
					$tc += $clicks['loc'][$loc];
				}

				echo "<tr><td class=header align=right>Total:</td><td class=header align=right>" . number_format($tv) . " / " . number_format($tc) . "</td></tr>";

				echo "</table>";

			echo "</td>";
		}

	//pages
		if(isset($views['page'])){
			echo "<td class=body valign=top>";

				echo "<table>";

				echo "<tr>";
				echo "<td class=header align=center>Page</td>";
				echo "<td class=header align=center>Views / Clicks</td>";
				echo "</tr>";

				arsort($views['page']);

				$tv = 0;
				$tc = 0;

				foreach($views['page'] as $page => $val){
					echo "<tr>";
					echo "<td class=header align=right>$page</td>";
					echo "<td class=body align=right>" . number_format($views['page'][$page]) . " / " . (isset($clicks['page'][$page]) ? number_format($clicks['page'][$page]) : 0 ) . "</td>";
					echo "</tr>";

					$tv += $views['page'][$page];
					$tc += $clicks['page'][$page];
				}

				echo "<tr><td class=header align=right>Total:</td><td class=header align=right>" . number_format($tv) . " / " . number_format($tc) . "</td></tr>";

				echo "</table>";

			echo "</td>";
		}

	//interests
		if(isset($views['interests'])){
			echo "<td class=body valign=top>";

				echo "<table>";

				echo "<tr>";
				echo "<td class=header align=center>Page</td>";
				echo "<td class=header align=center>Views / Clicks</td>";
				echo "</tr>";

				arsort($views['interests']);

				global $usersdb;
				$interests = new category( $configdb, "locs");


				$tv = 0;
				$tc = 0;

				foreach($views['interests'] as $interest => $val){
					echo "<tr>";
					echo "<td class=header align=right>" . ($interest ? $interests->getCatName($interest) : "N/A") . "</td>";
					echo "<td class=body align=right>" . number_format($views['interests'][$interest]) . " / " . (isset($clicks['interests'][$interest]) ? number_format($clicks['interests'][$interest]) : 0 ) . "</td>";
					echo "</tr>";

					$tv += $views['interests'][$interest];
					$tc += $clicks['interests'][$interest];
				}

				echo "<tr><td class=header align=right>Total:</td><td class=header align=right>" . number_format($tv) . " / " . number_format($tc) . "</td></tr>";

				echo "</table>";

			echo "</td>";
		}
	}
/*
	echo "</tr>";

	echo "<tr><td class=header colspan=3>Views</td></tr>";
	echo "<td class=body colspan=3>";
	echo "<pre>";

	print_r($views);

	echo "</pre>";
	echo "</td></tr>";
	echo "<tr><td class=header colspan=3>Clicks</td></tr>";
	echo "<td class=body colspan=3>";
	echo "<pre>";

	print_r($clicks);

	echo "</pre>";
	echo "</td></tr>";
*/

	echo "</table>";

	incFooter();
	exit;
}






function listClients(){
	global $banner, $filterData, $config;

	//collect information about what clients we have
	$res = $banner->db->query("SELECT id, clientname FROM bannerclients");
	$res = $banner->db->query("SELECT id, userid, clientname, type, dateadded, credits FROM bannerclients ORDER BY clientname");

	$clients = array();
	$userids = array();
	while($line = $res->fetchrow()){
		$line['bannercount'] = 0;
		$line['campaigncount'] = 0;
		$line['views'] = 0;
		$line['clicks'] = "N/A";
		$clients[$line['id']] = $line;
		$userids[] = $line['userid'];
	}
	sortCols($clients, SORT_ASC, SORT_CASESTR, 'clientname');
	
	//Get our list of campaigns and add clientname to it before we sort it
	$res = $banner->db->prepare_query("SELECT * FROM bannercampaigns");
	$campaigns = array();
	while($line = $res->fetchrow()){
		$line['clientname'] = $clients[$line['clientid']];
		$campaigns[] = $line;
	}
	
	$res = $banner->db->prepare_query("SELECT * FROM banners");
	$banners = array();
	while($line = $res->fetchrow()){
		$banners[] = $line;
	}
	
	$endstatstime = usermktime(0, 0, 0, userdate('n'), userdate('d'), userdate('Y'));
	$res = $banner->db->prepare_query("SELECT bannerid, views, clicks, passbacks FROM bannerstats WHERE time >= # && time <= # ORDER BY time ASC", $endstatstime-86400, $endstatstime);
	$stats = array();
	while($line = $res->fetchrow()){
		if(!isset($stats[$line['bannerid']]))
			$stats[$line['bannerid']] = array('start' => $line);
		$stats[$line['bannerid']]['end'] = $line;
	}

	getUserName($userids); //get all at once, cache them

	
	$template = new template('admin/adminbanners/listClients');
	$template->set('jsloc', "$config[jsloc]sorttable.js");
	$menu_template = new template('admin/adminbanners/menu');
	$menu_template->setMultiple($filterData);
	$template->set('menu', $menu_template->toString());
	foreach($clients as &$line){
		$line['username'] = getUserName($line['userid']);
		$line['credits'] = number_format($line['credits']);
		foreach($campaigns as $campaign) {
			if ($campaign['clientid'] != $line['id']) {
				continue;
			}
			$line['campaigncount']++;
			foreach ($banners as $b) {
				if ($b['campaignid'] != $campaign['id']) {
					continue;
				}
				if ($line['clicks'] == "N/A" && $b['link']) {
					$line['clicks'] = 0;
				}
				if (isset($stats[$b['id']])) {
					$line['views'] += $stats[$b['id']]['end']['views'] - $stats[$b['id']]['start']['views'];
					if ($line['clicks'] != "N/A") $line['clicks'] += $stats[$b['id']]['end']['clicks'] - $stats[$b['id']]['start']['clicks'];
				}
				$line['bannercount']++;
			}
		}
	}

	$template->set("classes", array("body", "body2"));
	$template->set("clients", $clients);
	$template->setMultiple($filterData);
	$template->display();
	exit;
}

function addClient(){
	global $banner;

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	preserveFilterData();
	echo "<tr><td class=header colspan=2 align=center>Add Client</td></tr>";

	echo "<tr><td class=body>Client Name:</td><td class=body><input class=body type=text name=clientname></td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=clientusername></td></tr>";
	echo "<tr><td class=body>Type:</td><td class=body><select class=body name=clienttype>" . make_select_list($banner->clienttypes) . "</select></td></tr>";
	echo "<tr><td class=body>Notes:</td><td class=body><textarea class=body name=clientnotes cols=50 rows=6></textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Add Client'></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function editClient($id){
	global $banner;

	if($id){
		$res = $banner->db->prepare_query("SELECT * FROM bannerclients WHERE id = #", $id);
		$line = $res->fetchrow();
		$line['username'] = getUserName($line['userid']);
	}else{
		$line = array(
			'id' => 0,
			'userid' => 0,
			'username' => '',
			'clientname' => '',
			'contacttitle' => '',
			'contactname' => '',
			'phonenumber' => '',
			'altphonenumber' => '',
			'faxnumber' => '',
			'email' => '',
			'address' => '',
			'active' => 'y',
			'type' => '',
			'dateadded' => '',
			'loginurl' => '',
			'loginid' => '',
			'loginpassword' => '',
			'primarycontact' => '',
			'notes' => '',
		);
	}

	$template = new template('admin/adminbanners/addClient');
	$template->setMultiple($line);
	$template->display();

	exit;
}

function updateClient($id){
	global $banner;

	$params = array();
	
	$username = getPOSTval('username', 'int');
	if($username){
		$userid = getUserId($username);
		if($userid)
			$params[] = $banner->db->prepare("userid = #", $userid);
	}
	$params[] = $banner->db->prepare("dateadded = #",       time());

	$params[] = $banner->db->prepare("clientname = ?",      getPOSTval('clientname'));
	$params[] = $banner->db->prepare("contacttitle = ?",    getPOSTval('contacttitle'));
	$params[] = $banner->db->prepare("contactname = ?",     getPOSTval('contactname'));
	$params[] = $banner->db->prepare("phonenumber = ?",     getPOSTval('phonenumber'));
	$params[] = $banner->db->prepare("altphonenumber = ?",  getPOSTval('altphonenumber'));
	$params[] = $banner->db->prepare("faxnumber = ?",       getPOSTval('faxnumber'));
	$params[] = $banner->db->prepare("email = ?",           getPOSTval('email'));
	$params[] = $banner->db->prepare("address = ?",         getPOSTval('address'));
	$params[] = $banner->db->prepare("active = ?",          (getPOSTval('active', 'bool') ? 'y' : 'n'));
	$params[] = $banner->db->prepare("type = ?",            getPOSTval('type'));
	$params[] = $banner->db->prepare("loginurl = ?",        getPOSTval('loginurl'));
	$params[] = $banner->db->prepare("loginid = ?",         getPOSTval('loginid'));
	$params[] = $banner->db->prepare("loginpassword = ?",   getPOSTval('loginpassword'));
	$params[] = $banner->db->prepare("primarycontact = ?",  getPOSTval('primarycontact'));
	$params[] = $banner->db->prepare("notes = ?",           getPOSTval('notes'));

	if($id){
		$banner->db->query("UPDATE bannerclients SET " . implode(", ", $params) . $banner->db->prepare(" WHERE id = #", $id));
	}else{
		$banner->db->query("INSERT INTO bannerclients SET " . implode(", ", $params));
	}
}

function updateCredits($id, $credits) {
	global $banner, $msgs;
	$banner->db->prepare_query("UPDATE bannerclients SET credits = # WHERE id = #", $credits, $id);
	$msgs->addMsg("Credits updated.");
}

function deleteClient($id){
	global $msgs, $banner;
	$res = $banner->db->prepare_query("SELECT id FROM banners WHERE clientid = #", $id);

	if($res->fetchrow()){
		$msgs->addMsg("Client has banners remaining. Remove them first.");
		return false;
	}

	$banner->db->prepare_query("DELETE FROM bannerclients WHERE id = #", $id);
}

function creditClient($id) {
	global $banner;
	
	$res = $banner->db->prepare_query("SELECT id, clientname, type, credits FROM bannerclients WHERE id = #", $id);
	$client = $res->fetchrow();
	if (!$client) {
		$msgs->addMsg("No such client.");
		listClients(); //exit
	} elseif ($client['type'] != "payinadvance") {
		$msgs->addMsg("Credits are only available for payinadvance clients.");
		listClients(); //exit
	} else {
		incHeader();
		echo "<form action=$_SERVER[PHP_SELF] method=post><table><tr><td class=\"header\">Credits for ".$client['clientname']."</td></tr>";
		echo "<tr><td class=\"body\"><input class=body type=\"text\" size=\"10\" name=\"credits\" value=\"$client[credits]\"> 1 credit = 1/1000 of a cent</td></tr>";
		echo "<tr><td class=\"header\" align=\"right\"><input class=body type=\"submit\" name=\"action\" value=\"Update Credits\"></td></tr></table><input type=\"hidden\" name=\"id\" value=\"$client[id]\"></form>";
		incFooter();
		exit;
	}
}

function preserveFilterData() {
	global $filterData;
	echo "<input type=hidden name=size value=" . $filterData['size'] . ">";
	echo "<input type=hidden name=type value=" . $filterData['type'] . ">";
	echo "<input type=hidden name=clientid value=" . $filterData['clientid'] . ">";
	echo "<input type=hidden name=all value=" . $filterData['all'] . ">";
}

function filterDataURLString() {
	global $filterData;
	$dataString = '';
	$i = 0;
	foreach($filterData as $name => $value) {
		if ($i>0) {
			$dataString .= '&';
		}
		$i++;
		$dataString .= "$name=$value";
	}
	return $dataString;
}

