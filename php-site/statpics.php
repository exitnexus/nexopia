<?
	$login = 1;
	require_once('include/general.lib.php');
	require_once('include/chartClasses.php');
	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");
	
	if (!$bannerAdmin) {
		$res = $banner->db->prepare_query("SELECT id FROM bannerclients WHERE userid = #" , $userData['userid']);
		$client = $res->fetchrow();
		if(!$client)
			die("You are not a banner client");
		else
			$clientid = $client['id'];
	}
	
	$action = getREQval('action');
	$c = getREQval('c', 'array', null);
	$b = getREQval('b', 'array', null);
	$startdate = getREQval('startdate', 'string', userdate("Y/m/d H:i", time()-86400));
	$enddate = getREQval('enddate', 'string', userdate("Y/m/d H:i", time()));
	$campaignid = getREQval('campaignid', 'int', 0);
	$clientid = getREQval('clientid', 'int');
	
	$unixstartdate = parseDate($startdate);
	$unixenddate = parseDate($enddate);
	if ($unixstartdate > $unixenddate) {
		$unixstartdate = $unixenddate;
		$startdate = $enddate;
	}
	
	//collect information about what clients we have
	$res = $banner->db->query("SELECT id, clientname FROM bannerclients");
	$clients = array();
	while($line = $res->fetchrow())
	$clients[$line['id']] = $line['clientname'];
	
	$where = array();
	$params = array();
	$where[] = "enabled = ?";
	$params[] = "y";
	
	if ($clientid) {
			$where[] = "clientid = #";
			$params[] = $clientid;
	}
	
	$res = $banner->db->prepare_array_query("SELECT * FROM bannercampaigns WHERE " . implode(" && ", $where), $params);
	$campaigns = array();
	while($line = $res->fetchrow()){
		if ($c == null || $c[$line['id']]) {
			$line['clientname'] = $clients[$line['clientid']];
			$campaigns[$line['id']] = $line;
		}
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
	
	if (substr($action,0,7) == "Banners" && $campaignid) {
			$where[] = "campaignid = #";
			$params[] = $campaignid;
	}
	
	$banner->db->prepare_array_query("SELECT * FROM banners WHERE " . implode(" && ", $where), $params);
	while ($line = $banner->db->fetchrow()) {
		if ($b == null || (isset($b[$line['id']]) && $b[$line['id']])) {
			if (isset($campaigns[$line['campaignid']])) {
				$line['clientname'] = $campaigns[$line['campaignid']]['clientname'];
				$banners[$line['id']]=$line;
				$bannerslist[$line['campaignid']][$line['id']] = $line;
			}
		}
	}
	
	switch($action) {
		case "ImpressionsPerDay":
			if (!$bannerAdmin) {
				die("You do not have permission to view this.");
			}
			$chart = new Chart(300, 300, "impressionsperday");
			$chart->SetHeaderName("Impressions Per Day");
			$chart->SetY_Name("Impressions Per Day");
			$chart->SetImageType(JPEG);
			
			foreach($campaigns as $line){
				$impressionsperday = 0;
				if (isset($bannerslist[$line['id']])) {
					foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
						if (isset($stats[$bannerid])) {
							if (($stats[$bannerid]['end']['time'] - $stats[$bannerid]['start']['time']) > 0)
								$impressionsperday += ($stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'])/(($stats[$bannerid]['end']['time'] - $stats[$bannerid]['start']['time'])/86400);
						}
					}
				}
				$chart->AddValue(0, $impressionsperday, "$line[id]. $line[title]");
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->CreateChartImage(PIE_CHART);
			break;
		case "ClicksPerDay":
			if (!$bannerAdmin) {
				die("You do not have permission to view this.");
			}
			$chart = new Chart(300, 300, "clicksperday");
			$chart->SetHeaderName("Clicks Per Day");
			$chart->SetY_Name("Clicks Per Day");
			$chart->SetImageType(JPEG);
			
			foreach($campaigns as $line){
				$clicksperday = 0;
				$validClicks = false; //only add campaigns if some banner in the campaign generates click stats
				if (isset($bannerslist[$line['id']])) {
					foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
						if (isset($stats[$bannerid]) && $bannerline['link']) {
							$validClicks = true;
							$clicksperday += ($stats[$bannerid]['end']['clicks'] - $stats[$bannerid]['start']['clicks'])/(($stats[$bannerid]['end']['time'] - $stats[$bannerid]['start']['time'])/86400);
						}
					}
				}
				if ($validClicks) {
					$chart->AddValue(0, $clicksperday, "$line[id]. $line[title]");
				}
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->CreateChartImage(PIE_CHART);
			break;
		case "PassbacksVsImpressions":
			if (!$bannerAdmin) {
				die("You do not have permission to view this.");
			}
			$chart = new Chart(300, 300, "passbacksvsimpressions");
			$chart->SetHeaderName("Passbacks vs. Impressions");
			$chart->SetY_Name("Total");
			$chart->setImageType(JPEG);
			foreach($campaigns as $line){
				$impressions = 0;
				$passbacks = 0;
				if (isset($bannerslist[$line['id']])) {
					foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
						if (isset($stats[$bannerid])) {
							$impressions += $stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'];
							$passbacks += $stats[$bannerid]['end']['passbacks'] - $stats[$bannerid]['start']['passbacks'];
						}
					}
				}
				$chart->AddValue("$line[title]", $passbacks, "Passbacks" );
				$chart->AddValue("$line[title]", $impressions, "Impressions" );
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->SetY_Barchart_Show_Values(true);
			$chart->CreateChartImage(BAR_CHART);
			
			break;
		case "ImpressionsVsPotentialViews":
			if (!$bannerAdmin) {
				die("You do not have permission to view this.");
			}
			$chart = new Chart(300, 300, "impressionsvspotentialviews");
			$chart->SetHeaderName("Impressions vs. Potential Views");
			$chart->SetY_Name("Total");
			$chart->setImageType(JPEG);
			foreach($campaigns as $line){
				$impressions = 0;
				$potentialviews = 0;
				if (isset($bannerslist[$line['id']])) {
					foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
						if (isset($stats[$bannerid])) {
							$impressions += $stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'];
							$potentialviews += $stats[$bannerid]['end']['potentialviews'] - $stats[$bannerid]['start']['potentialviews'];
						}
					}
				}
				$chart->AddValue($line[title], $impressions, "Impressions");
				$chart->AddValue($line[title], $potentialviews, "Potential Views");
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->SetY_Barchart_Show_Values(true);
			$chart->CreateChartImage(BAR_CHART);
			
			break;
		case "Revenue":
			if (!$bannerAdmin) {
				die("You do not have permission to view this.");
			}
			$chart = new Chart(300, 300, "revenue");
			$chart->SetHeaderName("Revenue");
			$chart->SetY_Name("Revenue");
			$chart->SetImageType(JPEG);
		
			foreach($campaigns as $campaignid => $line){
				$revenue = 0;
				if (isset($bannerslist[$line['id']])) {
					foreach ($bannerslist[$line['id']] as $bannerid => $bannerline) {
						if (isset($stats[$bannerid])) {
							if ($bannerline['paytype'] == BANNER_CPM || ($bannerline['paytype'] == BANNER_INHERIT && $campaigns['paytype'] == BANNER_CPM)) {
								$revenue += (($stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'])/100000)*($bannerline['payrate']==-1?$line['payrate']:$bannerline['payrate']);
							} elseif ($bannerline['paytype'] == BANNER_CPC || ($bannerline['paytype'] == BANNER_INHERIT && $campaigns['paytype'] == BANNER_CPC)) {
								$revenue += (($stats[$bannerid]['end']['clicks'] - $stats[$bannerid]['start']['clicks'])/100000)*($bannerline['payrate']==-1?$line['payrate']:$bannerline['payrate']);
							} else {
								die("Paytype $bannerline[paytype] generates no paytype");
							}
						}
					}
				}
				$chart->AddValue(0, $revenue, "$line[id]. $line[title]");
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->CreateChartImage(PIE_CHART);
			break;
		case "BannersImpressionsPerDay":
			$chart = new Chart(300, 300, "bannersimpressionsperday");
			$chart->SetHeaderName("Impressions Per Day");
			$chart->SetY_Name("Impressions Per Day");
			$chart->SetImageType(JPEG);
			
			if (!empty($banners)) {
				foreach($banners as $bannerid => $b){
					$impressionsperday = 0;
					if (isset($stats[$bannerid])) {
						$impressionsperday = ($stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'])/(($stats[$bannerid]['end']['time'] - $stats[$bannerid]['start']['time'])/86400);
					}
					$chart->AddValue(0, $impressionsperday, "$b[id]. $b[title]");
				}
			} else {
				$chart->addValue(0,0,"No values available");
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->CreateChartImage(PIE_CHART);
			
			break;
		case "BannersClicksPerDay":
			$chart = new Chart(300, 300, "bannersclicksperday");
			$chart->SetHeaderName("Clicks Per Day");
			$chart->SetY_Name("Clicks Per Day");
			$chart->SetImageType(JPEG);
			
			if (!empty($banners)) {
				foreach($banners as $bannerid => $b){
					if (!$b['link']) {
						continue;
					}
					$clicksperday = 0;
					if (isset($stats[$bannerid])) {
						$clicksperday = ($stats[$bannerid]['end']['clicks'] - $stats[$bannerid]['start']['clicks'])/(($stats[$bannerid]['end']['time'] - $stats[$bannerid]['start']['time'])/86400);
					}
					$chart->AddValue(0, $clicksperday, "$b[id]. $b[title]");
				}
			} else {
				$chart->addValue(0,0,"No values available");
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->CreateChartImage(PIE_CHART);
			
			break;
		case "BannersPassbacksVsImpressions":
			if (!$bannerAdmin) {
				die("You do not have permission to view this.");
			}
			$chart = new Chart(300, 300, "bannerspassbacksvsimpressions");
			$chart->SetHeaderName("Passbacks vs. Impressions");
			$chart->SetY_Name("Passbacks");
			$chart->SetX_Name("Impressions");
			$chart->SetImageType(JPEG);
			
			foreach($banners as $bannerid => $b){
				$impressions = 0;
				$passbacks = 0;
				if (isset($stats[$bannerid])) {
					$impressions = $stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'];
					$passbacks = $stats[$bannerid]['end']['passbacks'] - $stats[$bannerid]['start']['passbacks'];
				}
				$chart->AddValue($impressions, $passbacks, "$b[id]. $b[title]");
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->SetY_Barchart_Show_Values(true);
			$chart->CreateChartImage(BAR_CHART);
			
			break;
			
		case "BannersImpressionsVsPotential":
			if (!$bannerAdmin) {
				die("You do not have permission to view this.");
			}
			$chart = new Chart(300, 300, "bannersimpressionsvspotential");
			$chart->SetHeaderName("Impressions vs. Potential Views");
			$chart->SetY_Name("Impressions");
			$chart->SetX_Name("Potential Views");
			$chart->SetImageType(JPEG);
			
			
			foreach($banners as $bannerid => $b){
				$impressions = 0;
				$passbacks = 0;
				if (isset($stats[$bannerid])) {
					$impressions = $stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'];
					$potentialviews = $stats[$bannerid]['end']['potentialviews'] - $stats[$bannerid]['start']['potentialviews'];
				}
				$chart->AddValue($potentialviews, $impressions, "$b[id]. $b[title]");
			}
			
			$chart->SetBackgroundColor("white");
			$chart->SetFontColor("#000000");
			$chart->SetHeaderBackgroundColor("black");
			$chart->SetHeaderFontColor("#b8b2cd");
			$chart->SetY_Barchart_Show_Values(true);
			$chart->CreateChartImage(BAR_CHART);
			
			break;
	}
	
	function parseDate($date) {
		return usermktime((int)substr($date, 11, 2), 
							(int)substr($date, 14, 2),
							0,
							(int)substr($date, 5, 2),
							(int)substr($date, 8, 2),
							(int)substr($date, 0, 4));
	}
	
