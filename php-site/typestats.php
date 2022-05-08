<?
$login = 1;
$accepttype = false;
require_once('include/general.lib.php');
require_once('include/chartClasses.php');
	
$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");

if(!$bannerAdmin)
	die("You don't have permission to see this");

$defaults = array("ALL_EXCEPT" => 'All except', "ONLY" => 'Only');
$sexes = array(SEX_UNKNOWN => "Unknown", SEX_MALE => "Male", SEX_FEMALE => "Female");

$data = getREQval('data', 'array', array());
$startdate = getREQval('startdate', 'string', userdate("Y/m/d", time()-(86400)));
$enddate = getREQval('enddate', 'string', userdate("Y/m/d", time()));
$sizeSelect = getREQval('size', 'int');
$size = getREQval('size', 'int');
$type = getREQval('type', 'int');
$clientid = getREQval('clientid', 'int');
$all = getREQval('all', 'int');
$campaignid = getREQval('campaignid', 'int');

if (!isset($data['agedefault'])) $data['agedefault'] = "ONLY";
if (!isset($data['locdefault'])) $data['locdefault'] = "ONLY";
if (!isset($data['pagedefault'])) $data['pagedefault'] = "ONLY";

//obtain filter data
$validinterests = array();
if (isset($data['si']) && is_array($data['si'])) {
	foreach ($data['si'] as $interest) {
		$validinterests[$interest] = true;
	}
}

$validages = array();
for ($i=0; $i<100; $i++) {
	$validages[$i] = ($data['agedefault']=="ALL_EXCEPT");
}
if (isset($data['sa']) && is_array($data['sa'])) {
	foreach ($data['sa'] as $age) {
		$validages[$age] = !($data['agedefault']=="ALL_EXCEPT");
	}
}

$validsexes = array();
if (isset($data['ss']) && is_array($data['ss'])) {
	foreach ($data['ss'] as $sex) {
		$validsexes[$sex] = true;
	}
} else {
	foreach ($sexes as $sex=>$name) {
		$validsexes[$sex] = true;
	}
}

$validlocations = array();
if (isset($data['sl']) && is_array($data['sl'])) {
	foreach ($data['sl'] as $loc) {
		$validlocations[$loc] = !($data['locdefault']=="ALL_EXCEPT");
	}
}

if (isset($data['sp']))
	$sp = explode("\n", $data['sp']);
else
	$sp = array();
$validpages = array();
foreach ($sp as $page) {
	$validpages[trim($page)] = !($data['pagedefault']=="ALL_EXCEPT");
}

$action = getREQval('action', 'string', "");
$chart = getREQval('chart', 'string', "ViewsByAgeSex");

$pictureVariables = "chart=" . urlencode($chart);
$pictureVariables .= "&size=" . $sizeSelect;
$pictureVariables .= "&startdate=" . urlencode($startdate);
$pictureVariables .= "&enddate=" . urlencode($enddate);
$pictureVariables .= recursive_urlencode_array('data',$data);

function recursive_urlencode_array($arrayname, $array) {
	$str = "";
	foreach ($array as $key => $val) {
		if (is_array($val)) {
			$str .= recursive_urlencode_array($arrayname.'['.$key.']', $val);
		} else {
			$str .= "&".urlencode($arrayname)."[".urlencode($key)."]=".urlencode($val);
		}
	}
	return $str;
}

if (!$sizeSelect) {
	$sizes=$banner->sizes;
} else {
	$sizes=array($sizeSelect => $banner->sizes[$sizeSelect]);
}

$unixstartdate = parseDate($startdate);
$unixenddate = parseDate($enddate);
if ($unixstartdate > $unixenddate) {
	$unixstartdate = $unixenddate;
	$startdate = $enddate;
}

foreach ($sizes as $size=>$sizename) {
	foreach ($sexes as $sex=>$name) {
		$agesexviewtotal[$size][$sex] = 0;
		$agesexclicktotal[$size][$sex] = 0;
	}
	$locationviewtotal[$size] = 0;
	$locationclicktotal[$size] = 0;
	$pageviewtotal[$size] = 0;
	$pageclicktotal[$size] = 0;
	$locationviews[$size] = array();
	$locationclicks[$size] = array();
	$agesexviews[$size] = array();
	$agesexclicks[$size] = array();
	$interestviews[$size] = array();
	$interestclicks[$size] = array();
	$dayhourviews[$size] = array();
	$dayhourclicks[$size] = array();
	$pageviews[$size] = array();
	$pageclicks[$size] = array();
}

	$locationNames = new category( $configdb, "locs");
	$interestNames = new category( $configdb, "interests");


$res = $banner->db->prepare_query("SELECT * FROM bannertypestats WHERE  time >= # && time <= #", $unixstartdate, $unixenddate);
while($line = $res->fetchrow()){
	$viewstat = unserialize(gzuncompress($line['viewsdump']));
	$clickstat = unserialize(gzuncompress($line['clicksdump']));
	
	//generate totals data
	if (!isset($totalviews[$line['size']])) {
			$totalviews[$line['size']] = 0;
	}
	$totalviews[$line['size']] += $viewstat->total;
	if (!isset($totalclicks[$line['size']])) {
			$totalclicks[$line['size']] = 0;
	}
	$totalclicks[$line['size']] += $clickstat->total;
	
	
	//we only want specific data for sizes in our list
	if (!isset($sizes[$line['size']])) {
		continue;
	}
	//generate location summary data
	foreach ($viewstat->loc as $location => $count) {
		if (!isset($validlocations[$location])) {
			if (!($data['locdefault']=="ALL_EXCEPT")) {
				continue;
			}
		} else {
			if (!$validlocations[$location]) {
				continue;
			}
		}
		
		$locationName = ($location ? $locationNames->getCatName($location) : "Unknown");
		if (!isset($locationviews[$line['size']][$locationName])) {
			$locationviews[$line['size']][$locationName] = 0;
		}
		if (!isset($locationclicks[$line['size']][$locationName])) {
			$locationclicks[$line['size']][$locationName] = 0;
		}
		$locationviews[$line['size']][$locationName] += $count;
		$locationviewtotal[$line['size']] += $count;
	}
	foreach ($clickstat->loc as $location => $count) {
		if (!isset($validlocations[$location])) {
			if (!($data['locdefault']=="ALL_EXCEPT")) {
				continue;
			}
		} else {
			if (!$validlocations[$location]) {
				continue;
			}
		}
		
		$locationName = ($location ? $locationNames->getCatName($location) : "Unknown");
		if (!isset($locationviews[$line['size']][$locationName])) {
			$locationviews[$line['size']][$locationName] = 0;
		}
		if (!isset($locationclicks[$line['size']][$locationName])) {
			$locationclicks[$line['size']][$locationName] = 0;
		}
		$locationclicks[$line['size']][$locationName] += $count;
		$locationclicktotal[$line['size']] += $count;
	}
	
	//generate age/sex summary data
	foreach ($viewstat->agesex as $age => $sex) {
		if (!$validages[$age]) {
			continue;
		}
		foreach ($sex as $sexNumber => $count) {
			if (!empty($validsexes) && !isset($validsexes[$sexNumber])) {
				continue;
			}
			if ($count) {
				if (!isset($agesexviews[$line['size']][$age][$sexNumber])) {
					$agesexviews[$line['size']][$age][$sexNumber] = 0;
				}
				$agesexviews[$line['size']][$age][$sexNumber] += $count;
				$agesexviewtotal[$line['size']][$sexNumber] += $count;
			}
		}
	}
	foreach ($clickstat->agesex as $age => $sex) {
		if (!isset($validages[$age]) || !$validages[$age]) {
			continue;
		}
		$agesexclicks[$line['size']][$age] = array();
		foreach ($sex as $sexNumber => $count) {
			if (!empty($validsexes) && !isset($validsexes[$sexNumber])) {
				continue;
			}
			if ($count) {
				if (!isset($agesexclicks[$line['size']][$age][$sexNumber])) {
					$agesexclicks[$line['size']][$age][$sexNumber] = 0;
				}
				$agesexclicks[$line['size']][$age][$sexName] += $count;
				$agesexclicktotal[$line['size']][$sexName] += $count;
			}
		}
	}
	
	//generate interests summary data
	foreach ($viewstat->interests as $interest => $count) {
		if ($interest && !isset($validinterests[$interest])) {
			continue;
		}
		$interestName = ($interest ? $interestNames->getCatName($interest) : "Any");
		if (!isset($interestviews[$line['size']][$interestName])) {
			$interestviews[$line['size']][$interestName] = 0;
		}
		if (!isset($interestclicks[$line['size']][$interestName])) {
			$interestclicks[$line['size']][$interestName] = 0;
		}
		$interestviews[$line['size']][$interestName] += $count;
	}
	foreach ($clickstat->interests as $interest => $count) {
		if ($interest && !empty($validinterests) && !isset($validinterests[$interest])) {
			continue;
		}
		$interestName = ($interest ? $interestNames->getCatName($interest) : "Any");
		if (!isset($interestviews[$line['size']][$interestName])) {
			$interestviews[$line['size']][$interestName] = 0;
		}
		if (!isset($interestclicks[$line['size']][$interestName])) {
			$interestclicks[$line['size']][$interestName] = 0;
		}
		$interestclicks[$line['size']][$interestName] += $count;
	}
	
	//generate day/hour summary data
	$days = array(0 => "Sun", 1 => "Mon", 2 => "Tue", 3 => "Wed", 4 => "Thu", 5 => "Fri", 6 => "Sat");
	if (isset($viewstat->hittimes) && is_array($viewstat->hittimes)) {
		foreach ($viewstat->hittimes as $day => $hour) {
			$dayhourviews[$line['size']][$days[$day]] = array();
			foreach ($hour as $hourNumber => $count) {
				if ($count) {
					if (!isset($dayhourviews[$line['size']][$days[$day]][$hourNumber])) {
						$dayhourviews[$line['size']][$days[$day]][$hourNumber] = 0;
					}
					$dayhourviews[$line['size']][$days[$day]][$hourNumber] += $count;
				}
			}
		}
	}
	if (isset($clickstat->hittimes) && is_array($clickstat->hittimes)) {
		foreach ($clickstat->hittimes as $day => $hour) {
			$dayhourclicks[$line['size']][$days[$day]] = array();
			foreach ($hour as $hourNumber => $count) {
				if ($count) {
					if (!isset($dayhourclicks[$line['size']][$days[$day]][$hourNumber])) {
						$dayhourclicks[$line['size']][$days[$day]][$hourNumber] = 0;
					}
					$dayhourclicks[$line['size']][$days[$day]][$hourNumber] += $count;
				}
			}
		}
	}
	
	//generate pages summary data
	foreach ($viewstat->page as $page => $count) {
		if (!isset($validpages[$page])) {
			if (!($data['pagedefault']=="ALL_EXCEPT")) {
				continue;
			}
		} else {
			if (!$validpages[$page]) {
				continue;
			}
		}
		if (!isset($pageviews[$line['size']][$page])) {
			$pageviews[$line['size']][$page] = 0;
		}
		if (!isset($pageclicks[$line['size']][$page])) {
			$pageclicks[$line['size']][$page] = 0;
		}
		$pageviews[$line['size']][$page] += $count;
		$pageviewtotal[$line['size']] += $count;
	}
	foreach ($clickstat->page as $page => $count) {
		if (!isset($validpages[$page])) {
			if (!($data['pagedefault']=="ALL_EXCEPT")) {
				continue;
			}
		} else {
			if (!$validpages[$page]) {
				continue;
			}
		}
		if (!isset($pageviews[$line['size']][$page])) {
			$pageviews[$line['size']][$page] = 0;
		}
		if (!isset($pageclicks[$line['size']][$page])) {
			$pageclicks[$line['size']][$page] = 0;
		}
		$pageclicks[$line['size']][$page] += $count;
		$pageclicktotal[$line['size']] += $count;
	}
	
	unset($viewstat);
	unset($clickstat);
}	

//cleanup the agesex and locations array
foreach ($sizes as $size=>$name) {
	$allEmpty = true;
	if (isset($agesexviews[$size]) && is_array($agesexviews[$size])) {
		foreach ($agesexviews[$size] as $age => $sexcounts) {
			if (!empty($agesexviews[$size][$age])) { //we assume that clicks will never have data unless views does
				$allEmpty = false;
				foreach ($sexes as $sexNumber => $name) {
					if (!isset($agesexviews[$size][$age][$sexNumber])) {
						$agesexviews[$size][$age][$sexNumber] = 0;
					}
					if (!isset($agesexclicks[$size][$age][$sexNumber])) {
						$agesexclicks[$size][$age][$sexNumber] = 0;
					}
				}
			}
		}
		if ($allEmpty) {
			$agesexviews[$size] = array();
			$agesexclicks[$size] = array();
		}
	}
	
	if (isset($locationviews[$size]) && is_array($locationviews[$size]) && isset($locationclicks[$size]) && is_array($locationclicks[$size])) {
		if (isset($locationviews[$size]['Unknown']) && !$locationviews[$size]['Unknown'] && isset($locationclicks[$size]['Unknown']) && !$locationclicks[$size]['Unknown']) {
			unset($locationviews[$size]['Unknown']);
			unset($locationclicks[$size]['Unknown']);
		}
	}
}

$statstype = "Clicks";
$agesexes=&$agesexclicks;
$interests=&$interestclicks;
$locations=&$locationclicks;
$dayhours=&$dayhourclicks;
$total=&$totalclicks;

switch ($action) {
	case "GetChart":
	switch ($chart) {
		case "ViewsByAgeSex":
			$statstype="Views";
			$agesexes=&$agesexviews;
		case "ClicksByAgeSex":
		$image = new Chart(750, 300, $statstype ."ByAgeSex");
		$image->SetHeaderName($statstype ." By Age/Sex");
		$image->SetY_Name($statstype);
		$image->SetX_Name("Age");
		$image->SetImageType(JPEG);
		$summedagesex = array();
		foreach ($agesexes as $size => $agesex) {
			foreach ($agesex as $age => $sexlist) {
				foreach ($sexlist as $sex => $views) {
					if (!isset($summedagesex[$age]))
						$summedagesex[$age] = array();
					if (!isset($summedagesex[$age][$sex]))
						$summedagesex[$age][$sex]= 0;
					$summedagesex[$age][$sex] += $views;
				}
			}
		}
		foreach ($summedagesex as $age => $sexlist) {
			foreach ($sexlist as $sex => $views) {
				if (isset($validsexes[$sex])) {
					$image->AddValue("$age", $views, $sexes[$sex]);
				}
			}
		}
		
		$image->SetBackgroundColor("white");
		$image->SetFontColor("black");
		$image->SetHeaderBackgroundColor("black");
		$image->SetHeaderFontColor("#b8b2cd");
		$image->SetY_Barchart_Show_Values(true);
		$image->CreateChartImage(BAR_CHART);
			
		break;
		case "ViewsByInterest":
			$interests=&$interestviews;
			$statstype="Views";
		case "ClicksByInterest":
		$image = new Chart(750, 300, $statstype."ByInterest");
		$image->SetHeaderName($statstype." By Interest");
		$image->SetY_Name($statstype);
		$image->SetImageType(JPEG);
		foreach ($interests as $size => $interestlist) {
			foreach ($interestlist as $interest => $views) {
				$image->AddValue("$interest", $views, $banner->sizes[$size]);
			}
		}
		
		$image->SetBackgroundColor("white");
		$image->SetFontColor("black");
		$image->SetHeaderBackgroundColor("black");
		$image->SetHeaderFontColor("#b8b2cd");
		$image->SetY_Barchart_Show_Values(true);
		$image->CreateChartImage(BAR_CHART);
		break;
		case "ViewsByLocation":
			$statstype="Views";
			$locations=&$locationviews;
		case "ClicksByLocation":
		$image = new Chart(750, 300, $statstype."ByLocation");
		$image->SetHeaderName($statstype." By Location");
		$image->SetY_Name($statstype);
		$image->SetImageType(JPEG);
		foreach ($locations as $size => $locationlist) {
			foreach ($locationlist as $location => $views) {
				$image->AddValue("$location", $views, $banner->sizes[$size]);
			}
		}
		
		$image->SetBackgroundColor("white");
		$image->SetFontColor("black");
		$image->SetHeaderBackgroundColor("black");
		$image->SetHeaderFontColor("#b8b2cd");
		$image->SetY_Barchart_Show_Values(true);
		$image->CreateChartImage(BAR_CHART);
		break;
		case "ViewsBySize":
			$statstype="Views";
			$total=&$totalviews;
		case "ClicksBySize":
		$image = new Chart(750, 300, $statstype."BySize");
		$image->SetHeaderName($statstype." By Size");
		$image->SetY_Name($statstype);
		$image->SetImageType(JPEG);
		
		foreach($total as $size=>$count) {
				$image->AddValue($banner->sizes[$size], $count);
		}
		
		$image->SetBackgroundColor("white");
		$image->SetFontColor("black");
		$image->SetHeaderBackgroundColor("black");
		$image->SetHeaderFontColor("#b8b2cd");
		$image->SetY_Barchart_Show_Values(true);
		$image->CreateChartImage(BAR_CHART);
		break;
		
		case "ViewsByDay":
			$statstype="Views";
			$dayhours=&$dayhourviews;
		case "ClicksByDay":
		$image = new Chart(750, 300, $statstype."ByDay");
		$image->SetHeaderName($statstype." By Day");
		$image->SetY_Name($statstype);
		$image->SetX_Name("Day of the Week");
		$image->SetImageType(JPEG);
		
		foreach ($dayhours as $size=>$dayhour) {
			foreach ($dayhour as $day => $hours) {
				$totalviews = 0;
				foreach ($hours as $hour => $views) {
					$totalviews += $views;
				}
				$image->AddValue($day, $views, $banner->sizes[$size]);
			}
		}
		
		$image->SetBackgroundColor("white");
		$image->SetFontColor("black");
		$image->SetHeaderBackgroundColor("black");
		$image->SetHeaderFontColor("#b8b2cd");
		$image->Set_Linechart_Mark_Values(true);
		$image->CreateChartImage(LINE_CHART);
		break;
		case "ViewsByHour":
			$statstype="Views";
			$dayhours=&$dayhourviews;
		case "ClicksByHour":
		$image = new Chart(750, 300, $statstype."ByHour");
		$image->SetHeaderName($statstype." By Hour");
		$image->SetY_Name($statstype);
		$image->SetX_Name("Hour of the Day");
		$image->SetX_Max_Gridlines(6);
		$image->SetX_Max(24);
		$image->SetImageType(JPEG);
		
		$totalviews = array();
		foreach ($dayhours as $size=>$dayhour) {
			foreach ($dayhour as $day => $hours) {
				foreach ($hours as $hour => $views) {
					if (!isset($totalviews[$hour]))
						$totalviews[$hour] = 0;
					$totalviews[$hour] += $views;
				}
			}
			foreach ($totalviews as $hour => $views) {
				$image->AddValue($hour, $views, $banner->sizes[$size]);
			}
		}
		
		$image->SetBackgroundColor("white");
		$image->SetFontColor("black");
		$image->SetHeaderBackgroundColor("black");
		$image->SetHeaderFontColor("#b8b2cd");
		$image->Set_Linechart_Mark_Values(true);
		$image->CreateChartImage(LINE_CHART);
		break;
				
			
	}
	break;
	default:
	extract($data);
	$template = new template('admin/adminbanners/typestats');
	
	$template->set('jsloc', "$config[jsloc]calendar.js");
	$template->set('cssloc', "$config[jsloc]calendar.css");
	$template->set('calimgloc', "$config[imageloc]calendar/");
	
	$template->set('sexes', $sexes);
	$template->set('startdate', $startdate);
	$template->set('enddate', $enddate);
	$template->set('sizes', $sizes);
	$template->set('classes', array('body', 'body2'));
	$template->set('locationclicks', $locationclicks);
	$template->set('interestclicks', $interestclicks);
	$template->set('agesexclicks', $agesexclicks);
	$template->set('locations', $locationviews);
	$template->set('locationviewtotal', $locationviewtotal);
	$template->set('locationclicktotal', $locationclicktotal);
	$template->set('interests', $interestviews);
	$template->set('agesex', $agesexviews);
	$template->set('agesexviewtotal', $agesexviewtotal);
	$template->set('agesexclicktotal', $agesexclicktotal);
	$template->set('pageviews', $pageviews);
	$template->set('pageclicks', $pageclicks);
	$template->set('pageviewtotal', $pageviewtotal);
	$template->set('pageclicktotal', $pageclicktotal);
	
	
	if (!isset($ss)) $ss=array();
	$template->set('selectSex', make_select_list_multiple_key($sexes, $ss));
	
	$template->set('selectAgeType', make_select_list_key($defaults, $agedefault));
	if (!isset($sa)) $sa=array();
	$template->set('selectAgeRange', make_select_list_multiple(range(14,65), $sa));
	
	$template->set('validsexes', $validsexes);
	
	$locations = new category( $configdb, "locs");
	$template->set('selectLocationType', make_select_list_key($defaults, $locdefault));
	if (!isset($sl)) $sl=array();
	$template->set('selectLocations', makeCatSelect_multiple($locations->makeBranch(), $sl));
	
	$template->set('selectPagesType', make_select_list_key($defaults, $pagedefault));
	if (is_array($sp)) $sp = implode("\n", $sp);
	$template->set('page', htmlentities($sp));

	$interestcats = new category( $usersdb, "interests");
	if (!isset($si)) $si=array();
	$template->set('selectInterests', makeCatSelect_multiple($interestcats->makeBranch(), $si));

	$template->set('selectSize', make_select_list_key($banner->sizes, $sizeSelect));
	$template->set('selectImage', make_select_list_key(array("ViewsByAgeSex"=>"Views By Age/Sex",
													   "ViewsByInterest"=>"Views By Interest",
													   "ViewsByLocation"=>"Views By Location",
													   "ViewsBySize"=>"Views By Size",
													   "ViewsByDay"=>"Views By Day",
													   "ViewsByHour"=>"Views By Hour",
													   "ClicksByAgeSex"=>"Clicks By Age/Sex",
													   "ClicksByInterest"=>"Clicks By Interest",
													   "ClicksByLocation"=>"Clicks By Location",
													   "ClicksBySize"=>"Clicks By Size",
													   "ClicksByDay"=>"Clicks By Day",
													   "ClicksByHour"=>"Clicks By Hour",
													   ), htmlentities($chart)));
	$template->set('pictureVariables', $pictureVariables);
	$template->set('size', $sizeSelect);
	$template->set('type', $type);
	$template->set('clientid', $clientid);
	$template->set('all', $all);
	$template->set('campaignid', $campaignid);
	$template->setMultiple($data);
	$menu_template = new template('admin/adminbanners/menu');
	$menu_template->set('size', $sizeSelect);
	$menu_template->set('type', $type);
	$menu_template->set('clientid', $clientid);
	$menu_template->set('all', $all);
	$menu_template->set('campaignid', $campaignid);
	$template->set('menu', $menu_template->toString());
	$template->display();
}


function parseDate($date) {
	return usermktime(0, 
					0,
					0,
					(int)substr($date, 5, 2),
					(int)substr($date, 8, 2),
					(int)substr($date, 0, 4));
}
