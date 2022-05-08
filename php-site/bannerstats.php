<?
	$login=1;
	require_once('include/general.lib.php');
	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");
	
	if (!$bannerAdmin) {
		$res = $banner->db->prepare_query("SELECT id FROM bannerclients WHERE userid = #" , $userData['userid']);
		$client = $res->fetchrow();
		if(!$client)
			die("You are not a banner client");
		else
			$clientid = $client['id'];
	}
	
	$action = getREQval('action', 'string', "BannersImpressionsPerDay");
	$b = getREQval('b', 'array', null);
	$startdate = getREQval('startdate', 'string', userdate("Y/m/d H:i", time()-86400));
	$enddate = getREQval('enddate', 'string', userdate("Y/m/d H:i", time()));
	$size = getREQval('size', 'int');
	$type = getREQval('type', 'int');
	if ($bannerAdmin) $clientid = getREQval('clientid', 'int');
	$all = getREQval('all', 'int');
	$campaignid = getREQval('campaignid', 'int');
	
	$unixstartdate = parseDate($startdate);
	$unixenddate = parseDate($enddate);
	if ($unixstartdate > $unixenddate) {
		$unixstartdate = $unixenddate;
		$startdate = $enddate;
	}
	
	
	//collect information about what clients we have
	$res = $banner->db->query("SELECT id, clientname FROM bannerclients");
	$clients = array();
	while($line = $res->fetchrow()) {
		$clients[$line['id']] = $line;
	}
	sortCols($clients, SORT_ASC, SORT_CASESTR, 'clientname');
	foreach($clients as &$client) {
		$clients[$client['id']] = $client['clientname'];
	}
	
	$where = array();
	$params = array();
	$where[] = "enabled = ?";
	$params[] = "y";
	
	if ($clientid) {
			$where[] = "clientid = #";
			$params[] = $clientid;
	}
	
	$res = $banner->db->prepare_array_query("SELECT title, clientid, id FROM bannercampaigns WHERE " . implode(" && ", $where), $params);
	$campaigns = array();
	while ($line = $res->fetchrow()) {
		$line['clientname'] = $clients[$line['clientid']];
		$campaigns[$line['id']] = $line;
		$campaignnames[$line['id']] = array('id'=>$line['id'], 'title'=>$line['title']);
	}
	sortCols($campaignnames, SORT_ASC, SORT_CASESTR, 'title');
	foreach ($campaignnames as &$cn) {
		$campaignnames[$cn['id']] = $cn['title'];
	}
	
	$res = $banner->db->prepare_query("SELECT id, bannerid, views, potentialviews, clicks, passbacks, time FROM bannerstats WHERE time >= # && time <= # ORDER BY time ASC", $unixstartdate, $unixenddate);
	$stats = array();
	while($line = $res->fetchrow()){
		if(!isset($stats[$line['bannerid']]))
			$stats[$line['bannerid']] = array('start' => $line);
		$stats[$line['bannerid']]['end'] = $line;
	}
	
	$where = array();
	$params = array();
	$where[] = "enabled = ?";
	$params[] = "y";
	if ($campaignid) {
			$where[] = "campaignid = #";
			$params[] = $campaignid;
	}
		
	$banner->db->prepare_array_query("SELECT * FROM banners WHERE " . implode(" && ", $where), $params);
	$banners = array();
	while ($line = $banner->db->fetchrow()) {
		if ($b == null) {
			$line['checked'] = "CHECKED";
		} else {
			$line['checked'] = ($b[$line['id']] ? "CHECKED" : "");
		}
		if (isset($campaigns[$line['campaignid']])) {
			$line['clientname'] = $campaigns[$line['campaignid']]['clientname'];
			$line['campaignname'] = $campaigns[$line['campaignid']]['title'];
			$line['startdate'] = ($line['startdate'] > 0 ? userdate("M j, Y", $line['startdate']) : "Started");
			$line['bannersize'] = $banner->sizes[$line['bannersize']];
			$line['enddate'] = ($line['enddate'] > 0 ? userdate("M j, Y", $line['enddate']) : "Never");
			$banners[$line['id']] = $line;
		}
	}
	sortCols($banners, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_CASESTR, 'campaignname', SORT_ASC, SORT_CASESTR, 'clientname');
	
	
	foreach($banners as $id=>$b){
		$banners[$id]['views'] = 0;
		$banners[$id]['passbacks'] = 0;
		$banners[$id]['potentialviews'] = 0;
		$banners[$id]['clicks'] = ($b['link'] ? 0 : "N/A");
		$banners[$id]['clickthru'] = ($b['link'] ? 0 : "N/A");
			
		if (isset($stats[$id])) {
			$banners[$id]['views'] = $stats[$id]['end']['views'] - $stats[$id]['start']['views'];
			$banners[$id]['passbacks'] = $stats[$id]['end']['passbacks'] - $stats[$id]['start']['passbacks'];
			$banners[$id]['potentialviews'] = $stats[$id]['end']['potentialviews'] - $stats[$id]['start']['potentialviews'];
			if ($b['link']) {
				$banners[$id]['clicks'] = $stats[$id]['end']['clicks'] - $stats[$id]['start']['clicks'];
				if ($banners[$id]['views']) {
					$banners[$id]['clickthru'] = $banners[$id]['clicks']/$banners[$id]['views'];
				} else {
					$banners[$id]['clickthru'] = $banners[$id]['clicks'];
				}
			}
		}
	}
	if ($bannerAdmin) {
		$template = new template('admin/adminbanners/bannerstats');
		$template->set('selectImage', make_select_list_key(array(
													   "BannersClicksPerDay"=>"Clicks Per Day",
													   "BannersImpressionsPerDay"=>"Impressions Per Day",
													   "BannersImpressionsVsPotential"=>"Impressions vs. Potential Views",
													   "BannersPassbacksVsImpressions"=>"Passbacks vs. Impressions",
													   ), htmlentities($action)));
	} else {
		$template = new template('admin/adminbanners/clientstats');
		$template->set('selectImage', make_select_list_key(array("BannersImpressionsPerDay"=>"Impressions Per Day",
													   "BannersClicksPerDay"=>"Clicks Per Day",
													   ), htmlentities($action)));
	}
	
	$template->set('selectCampaign', make_select_list_key($campaignnames, $campaignid));
	$template->set('selectClient', make_select_list_key($clients, $clientid));
	
	$template->set('startdate', $startdate);
	$template->set('enddate', $enddate);
	$template->set('classes', array('body', 'body2'));
	$selectAll = "CHECKED";
	foreach ($b as $id => $val) {
		if (isset($banners[$id])) {
			$selectAll = "";
		}
	}
	if ($selectAll == "CHECKED") {
		foreach ($banners as $id => $val) {
			$banners[$id]['checked'] = "CHECKED";
			$b[$id] = "on";
		}
	}
	
	$pictureVariables = "action=".urlencode($action);
	$pictureVariables .= "&startdate=".urlencode($startdate);
	$pictureVariables .= "&enddate=".urlencode($enddate);
	$pictureVariables .= "&campaignid=$campaignid";
	$pictureVariables .= "&clientid=$clientid";
	
	if ($b != null) {
		foreach ($b as $id => $val) {
			$pictureVariables .= "&b[".urlencode($id)."]=".urlencode($val);
		}
	}
	
	$template->set('pictureVariables', $pictureVariables);
	$template->set('banners', $banners);
	$template->set('selectAll', $selectAll);
	$template->set('size', $size);
	$template->set('type', $type);
	$template->set('clientid', $clientid);
	$template->set('all', $all);
	$template->set('campaignid', $campaignid);
	$template->display();
	
	function parseDate($date) {
		return usermktime((int)substr($date, 11, 2), 
							(int)substr($date, 14, 2),
							0,
							(int)substr($date, 5, 2),
							(int)substr($date, 8, 2),
							(int)substr($date, 0, 4));
	}
	
