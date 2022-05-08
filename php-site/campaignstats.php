<?
	$login = 1;
	$accepttype = false;
	require_once('include/general.lib.php');
	
	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");
	
	if(!$bannerAdmin)
		die("You don't have permission to see this");
		
	$action = getREQval('action', 'string', "ImpressionsPerDay");
	$c = getREQval('c', 'array', null);
	$startdate = getREQval('startdate', 'string', userdate("Y/m/d H:i", time()-86400));
	$enddate = getREQval('enddate', 'string', userdate("Y/m/d H:i", time()));
	$size = getREQval('size', 'int');
	$type = getREQval('type', 'int');
	$clientid = getREQval('clientid', 'int');
	$all = getREQval('all', 'int');
	$campaignid = getREQval('campaignid', 'int');

	$unixstartdate = parseDate($startdate);
	$unixenddate = parseDate($enddate);
	if ($unixstartdate > $unixenddate) {
		$unixstartdate = $unixenddate;
		$startdate = $enddate;
	}
	
	$pictureVariables = "action=".urlencode($action);
	$pictureVariables .= "&startdate=".urlencode($startdate);
	$pictureVariables .= "&enddate=".urlencode($enddate);
	if ($c != null) {
		foreach ($c as $id => $val) {
			$pictureVariables .= "&c[".urlencode($id)."]=".urlencode($val);
		}
	}
	
	//collect information about what clients we have
	$res = $banner->db->query("SELECT id, clientname FROM bannerclients");
	$clients = array();
	while($line = $res->fetchrow())
	$clients[$line['id']] = $line['clientname'];
	
	$res = $banner->db->prepare_query("SELECT * FROM bannercampaigns");// WHERE " . implode(" && ", $where), $params);
	$campaigns = array();
	while($line = $res->fetchrow()){
		$line['clientname'] = $clients[$line['clientid']];
		if ($c == null) {
			$line['checked'] = "CHECKED";
		} else {
			$line['checked'] = ($c[$line['id']] ? "CHECKED" : "");
		}
		$campaigns[] = $line;
	}
	
	sortCols($campaigns, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_CASESTR, 'clientname');
	
	
	$res = $banner->db->prepare_query("SELECT bannerid, views, potentialviews, clicks, passbacks, time FROM bannerstats WHERE time >= # && time <= # ORDER BY time ASC", $unixstartdate, $unixenddate);
	$stats = array();
	while($line = $res->fetchrow()){
		if(!isset($stats[$line['bannerid']]))
		$stats[$line['bannerid']] = array('start' => $line);
		
		$stats[$line['bannerid']]['end'] = $line;
	}
	$banner->db->prepare_query("SELECT * FROM banners WHERE enabled = 'y'");
	while ($line = $banner->db->fetchrow()) {
		$bannerslist[$line['campaignid']][$line['id']] = $line;
	}
	foreach($campaigns as $key=>$line){
		$campaigns[$key]['views'] = 0;
		$campaigns[$key]['passbacks'] = 0;
		$campaigns[$key]['potentialviews'] = 0;
		$campaigns[$key]['clicks'] = "N/A";
		$campaigns[$key]['clickthru'] = "N/A";
		if (isset($bannerslist[$campaigns[$key]['id']])) {
			$clickviews = 0; //number of views for banners that we keep click stats on
			foreach ($bannerslist[$campaigns[$key]['id']] as $bannerid => $bannerline) {
				if ($bannerline['link']) {
					if ($campaigns[$key]['clicks'] === "N/A") {
						$campaigns[$key]['clicks'] = 0;
					}
				}
				if (isset($stats[$bannerid])) {
					$campaigns[$key]['views'] += $stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'];
					$campaigns[$key]['passbacks'] += $stats[$bannerid]['end']['passbacks'] - $stats[$bannerid]['start']['passbacks'];
					$campaigns[$key]['potentialviews'] += $stats[$bannerid]['end']['potentialviews'] - $stats[$bannerid]['start']['potentialviews'];
					if ($bannerline['link']) {
						$campaigns[$key]['clicks'] += $stats[$bannerid]['end']['clicks'] - $stats[$bannerid]['start']['clicks'];
						$clickviews += $stats[$bannerid]['end']['views'] - $stats[$bannerid]['start']['views'];
					}
				}
			}
			if ($campaigns[$key]['clicks'] !== "N/A") {
				if ($clickviews) {
					$campaigns[$key]['clickthru'] = number_format($campaigns[$key]['clicks']/$clickviews);
				} else {
					$campaigns[$key]['clickthru'] = $campaigns[$key]['clicks'];
				}
			}
		}
	}
	
	$template = new template('admin/adminbanners/campaignstats');
	$template->set('jsloc', "$config[jsloc]calendar.js");
	$template->set('cssloc', "$config[jsloc]calendar.css");
	$template->set('calimgloc', "$config[imageloc]calendar/");
	
	$template->set('pictureVariables', $pictureVariables);
	$template->set('selectImage', make_select_list_key(array("ClicksPerDay"=>"Clicks Per Day",
													   "ImpressionsPerDay"=>"Impressions Per Day",
													   "ImpressionsVsPotentialViews"=>"Impressions vs. Potential Views",
													   "PassbacksVsImpressions"=>"Passbacks vs. Impressions",
													   "Revenue"=>"Revenue"), htmlentities($action)));
	$template->set('campaigns', $campaigns);
	$template->set('classes', array('body', 'body2'));
	$template->set('selectAll', ($c==null?"CHECKED":""));
	$template->set('startdate', $startdate);
	$template->set('enddate', $enddate);
	$template->set('size', $size);
	$template->set('type', $type);
	$template->set('clientid', $clientid);
	$template->set('all', $all);
	$template->set('campaignid', $campaignid);
	$menu_template = new template('admin/adminbanners/menu');
	$menu_template->set('size', $size);
	$menu_template->set('type', $type);
	$menu_template->set('clientid', $clientid);
	$menu_template->set('all', $all);
	$menu_template->set('campaignid', $campaignid);
	$template->set('menu', $menu_template->toString());
	$template->display();
	
	
	function parseDate($date) {
		return usermktime((int)substr($date, 11, 2), 
							(int)substr($date, 14, 2),
							0,
							(int)substr($date, 5, 2),
							(int)substr($date, 8, 2),
							(int)substr($date, 0, 4));
	}
	
