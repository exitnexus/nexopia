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

	switch($action){
		case "listbanners":
			$size = getREQval('size', 'int');
			$type = getREQval('type', 'int');
			$clientid = getREQval('clientid', 'int');
			$all = getREQval('all', 'int');

			listBanners($size, $type, $clientid, $all); //exit

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
			if(($id = getREQval('id', 'int')) && ($data = getPOSTval('data', 'array')))
				updateBanner($id, $data);
			break;

		case "deletebanner":
			if($id = getREQval('id', 'int'))
				deleteBanner($id);
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
			if(($type = getREQval('type', 'int')) && isset($banner->types[$type]))
				addBanner($type);	//exit
			break;
		case "insertbanner":
			insertBanner($data);
			break;

		case "listclients":
			listClients();	//exit

		case "addclient":
			addClient();	//exit

		case "Add Client":
		case "insertclient":
			insertClient(getUserID($clientusername), $clientname, $clienttype, $clientnotes);
			listClients();	//exit

		case "editclient":
			if($id = getREQval('id', 'int'))
				editClient($id); //exit

		case "updateclient":
		case "Update Client":
			updateClient($id, getUserID($clientusername), $clientname, $clienttype, $clientnotes);
			listClients();	//exit

		case "deleteclient":
			if($id = getREQval('id', 'int'))
				deleteClient($id);
			listClients();	//exit

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

	listBanners();


function listBanners($size = false, $type = false, $clientid = false, $all = false){
	global $banner, $scope;

	$params = array();
	$where = array("1");

	if($size){
		$where[] = "bannersize = ?";
		$params[] = $size;
	}
	if($type){
		$where[] = "bannertype = ?";
		$params[] = $type;
	}
	if($clientid){
		$where[] = "clientid = ?";
		$params[] = $clientid;
	}
	switch($all){
		case 1:
			break;
		case 0:
		default:
			$where[] = "enabled = 'y'";
	}

	$banner->db->query("SELECT id, clientname FROM bannerclients");

	$clients = array();
	while($line = $banner->db->fetchrow())
		$clients[$line['id']] = $line['clientname'];

	uasort($clients, 'strcasecmp');


	$banner->db->prepare_array_query("SELECT * FROM banners WHERE " . implode(" && ", $where), $params);

	$banners = array();
	while($line = $banner->db->fetchrow()){
		$line['clientname'] = $clients[$line['clientid']];
		$banners[] = $line;
	}

	sortCols($banners, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_CASESTR, 'clientname', SORT_ASC, SORT_STRING, 'bannersize');

	incHeader();

	echo "<table align=center>";

	$cols = 22;

	echo "<tr>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<td class=header colspan=$cols align=center>";
	echo "<a class=header href=$_SERVER[PHP_SELF]?action=listclients>List Clients</a> | ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?action=typestats>Type Stats</a> ";
	echo "<select class=body name=size><option value=0>Size" . make_select_list_key($banner->sizes, $size) . "</select>";
	echo "<select class=body name=type><option value=0>Type" . make_select_list_key($banner->types, $type) . "</select>";
	echo "<select class=body name=clientid><option value=0>Client" . make_select_list_key($clients, $clientid) . "</select>";
	echo "<select class=body name=all>" . make_select_list_key($scope, $all) . "</select>";
	echo "<input type=hidden name=action value=listbanners>";
	echo "<input class=body type=submit value=Go>";
	echo "</td>";
	echo "</form>";
	echo "</tr>";

	echo "<tr>";
	echo "<td class=header>Client</td>";
	echo "<td class=header>Title</td>";
	echo "<td class=header>Size</td>";
	echo "<td class=header>Type</td>";
	echo "<td class=header>Pay Rate</td>"; // $ CPC | $ CPM
	echo "<td class=header>Views/day</td>";
	echo "<td class=header>Freq Cap</td>"; // x/day | x/h
	echo "<td class=header>Max Views</td>";
	echo "<td class=header>Start</td>";
	echo "<td class=header>End</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Clickthru</td>";
	echo "<td class=header>Pass</td>";
	echo "<td class=header>Age</td>";
	echo "<td class=header>Sex</td>";
	echo "<td class=header>Loc</td>";
	echo "<td class=header>Page</td>";
	echo "<td class=header>Inter</td>";
//	echo "<td class=header>Moded</td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "</tr>";
	$maxviewsperday = 0;
	$views = 0;
	$clicks = 0;

	$classes = array('body','body2');
	$i=1;

	foreach($banners as $line){
		$i = !$i;
		echo "<tr>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=listclients&id=$line[clientid]>" . $clients[$line['clientid']] . "</a></td>";
		echo "<td class=$classes[$i]>$line[title]</td>";
		echo "<td class=$classes[$i]>" . $banner->sizes[$line['bannersize']] . "</td>";
		echo "<td class=$classes[$i]>" . $banner->types[$line['bannertype']] . "</td>";
		echo "<td class=$classes[$i] align=right nowrap>$line[payrate] c " . ($line['paytype'] == BANNER_CPC ? "CPC" : "CPM") . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['enabled'] == 'y' ? ($line['viewsperday'] ? number_format($line['viewsperday']) : "unlim" ) : 'disabled') . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['viewsperuser'] ? number_format($line['viewsperuser']) . ($line['limitbyhour'] == 'y' ? "/h" : "/d") : "" ) . "</td>";

		echo "<td class=$classes[$i] align=right>" . ($line['maxviews'] ? number_format($line['maxviews']) : "unlim") . "</td>";
		echo "<td class=$classes[$i] nowrap>" . ($line['startdate'] > 0 ? userdate("M j", $line['startdate']) : "") . "</td>";
		echo "<td class=$classes[$i] nowrap>" . ($line['enddate'] > 0 ? userdate("M j", $line['enddate']) : "")  . "</td>";

		echo "<td class=$classes[$i] align=right>" . number_format($line['views']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] ? number_format($line['clicks']) : "N/A") . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] && $line['views'] ? number_format($line['clicks']/$line['views']*100, 3) . "%" : "N/A") . "</td>";

		echo "<td class=$classes[$i] align=right>" . number_format($line['passbacks']) . "</td>";

		echo "<td class=$classes[$i]>" . ($line['age'] == '' ? "" : "Yes") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['sex'] == '' ? "" : "Yes") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['loc'] == '' ? "" : "Yes") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['page']== '' ? "" : "Yes") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['interests']== '' ? "" : "Yes") . "</td>";

//		echo "<td class=$classes[$i]>" . ($line['moded'] == 'n' ? "No" : "") . "</td>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=editbanner&id=$line[id]>Edit</a></td>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=bannerstats&id=$line[id]>Stats</a></td>";
		echo "<td class=$classes[$i]><a class=body href=\"javascript:confirmLink('$_SERVER[PHP_SELF]?action=deletebanner&id=$line[id]','delete this ad?')\">Delete</a></td>";

		echo "</tr>";
		$maxviewsperday += $line['viewsperday'];
		$views += $line['views'];
		$clicks += $line['clicks'];
	}
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header colspan=5></td>";
	echo "<td class=header align=right>" . number_format($maxviewsperday) . "</td>";
	echo "<td class=header colspan=4></td>";
	echo "<td class=header align=right>" . number_format($views) . "</td>";
	echo "<td class=header align=right>" . number_format($clicks) . "</td>";
	echo "<td class=header align=right>" . ($views ? number_format($clicks/$views*100, 3) . "%" : "N/A") . "</td>";
	echo "<td class=header colspan=10 align=right><select class=body name=type>" . make_select_list_key($banner->types) . "</select><input class=body type=submit name=action value='Add Banner'></td></tr>";
	echo "</form>";
	echo "</table>";

	incFooter();
	exit;
}

function addBanner($bannertype, $id = 0, $data = array()){
	global $banner, $defaults, $sexes;

	$bannersize		= "";
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
	$image			= "";
	$link			= "";
	$alt			= "";

	$sex			= array();
	$agedefault		= ALL_EXCEPT;
	$age 			= array();
	$locdefault		= ALL_EXCEPT;
	$loc			= array();
	$interests		= array();
	$pagedefault	= ALL_EXCEPT;
	$pages			= '';
	$paytype		= 0;
	$viewsperuser	= 0;
	$freqperiod		= 'y';

	$enabled		= 'y';

	$refresh = 30;

	if($id){
		$banner->db->prepare_query("SELECT * FROM banners WHERE id = ?", $id);

		if($banner->db->numrows()){
			$line = $banner->db->fetchrow();
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

			$freqperiod = $limitbyhour;

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
		}
	}else{
		if(count($data))
			extract($data);
	}


	$banner->db->query("SELECT id, clientname FROM bannerclients");

	while($line = $banner->db->fetchrow())
		$clients[$line['id']]=$line['clientname'];

	uasort($clients, 'strcasecmp');

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center><form action=\"$_SERVER[PHP_SELF]\" method=post>\n";

	if($id){
		echo "<tr><td class=body colspan=2 align=center>" . $banner->getBannerID($id) . "</td></tr>";
		echo "<tr><td class=header colspan=2 align=center>Edit Banner</td></tr>";
	}else{
		echo "<tr><td class=header colspan=2 align=center>Add Banner</td></tr>";
	}
	echo "<input type=hidden name=data[bannertype] value=$bannertype>";

	echo "<tr><td class=body>Client:</td><td class=body><select class=body name=data[clientid]>";
	echo "<option value=0>Client";
	echo make_select_list_key($clients, $clientid);
	echo "</select></td></tr>";

	echo "<tr><td class=body valign=top>Banner Size:</td><td class=body>";
	echo "<select class=body name=data[bannersize]>" . make_select_list_key($banner->sizes, $bannersize) . "</select> ";
	echo makeCheckBox('data[enabled]','Enabled', $enabled == 'y');
	echo "</td></tr>\n";

	echo "<tr><td class=body>Title:</td><td class=body>";
	echo "<input class=body type=text name=data[title] value=\"$title\" size=20 maxlength=32> For reference only";
	echo "</td></tr>\n";

	switch($bannertype){
		case BANNER_IMAGE:
			echo "<tr><td class=body>Banner:</td><td class=body>";
			echo "<input class=body type=text name=data[image] value=\"$image\" size=40 maxlength=128>";
			echo "</td></tr>\n";

			echo "<tr><td class=body valign=top>Link:</td><td class=body>";
			echo "<input class=body type=text name=data[link] value=\"$link\" size=40 maxlength=128>";
			echo "</td></tr>\n";

			echo "<tr><td class=body valign=top>Alt Text:</td><td class=body>";
			echo "<input class=body type=text name=data[alt] value=\"$alt\" size=40 maxlength=255>";
			echo "</td></tr>\n";
			break;

		case BANNER_FLASH:
			echo "<tr><td class=body>Flash Banner:</td><td class=body>";
			echo "<input class=body type=text name=data[alt] value=\"$alt\" size=40 maxlength=255>";
			echo "</td></tr>\n";

			echo "<tr><td class=body valign=top>Backup Image Banner:</td><td class=body>";
			echo "<input class=body type=text name=data[image] value=\"$image\" size=40 maxlength=255>";
			echo "</td></tr>\n";

			echo "<tr><td class=body valign=top>Backup Image Link:</td><td class=body>";
			echo "<input class=body type=text name=data[link] value=\"$link\" size=40 maxlength=255>";
			echo "</td></tr>\n";

			echo "<tr><td class=body colspan=2>The backup image and link won't be used unless the user doesn't have flash installed</td></tr>";

			break;

		case BANNER_IFRAME:
			echo "<tr><td class=body>Banner:</td><td class=body>";
			echo "<input class=body type=text name=data[image] value=\"$image\" size=40 maxlength=255>";
			echo "</td></tr>\n";
			echo "<input class=body type=hidden name=data[alt] value=''>";
			echo "<input class=body type=hidden name=data[link] value=''>";

			break;

		case BANNER_HTML:
			echo "<tr><td class=body valign=top>HTML:</td><td class=body>";
			echo "<textarea class=body cols=60 rows=10 name=data[alt]>$alt</textarea>";
			echo "</td></tr>\n";
			echo "<input class=body type=hidden name=data[image] value=''>";
			echo "<input class=body type=hidden name=data[link] value=''>";

			break;

		case BANNER_TEXT:
			echo "<tr><td class=body>Link Title</td><td class=body>";
			echo "<input class=body type=text name=data[image] size=30 value=\"$image\" maxlength=32>";
			echo "</td></tr>\n";

			echo "<tr><td class=body valign=top>Link:</td><td class=body>";
			echo "<input class=body type=text name=data[link] value=\"$link\" size=40 maxlength=255>";
			echo "</td></tr>\n";

			echo "<tr><td class=body valign=top>Text:</td><td class=body>";
			echo "<input class=body type=text name=data[alt] value=\"$alt\" size=40 maxlength=255>";
			echo "</td></tr>\n";

			break;
	}

//targetting
	echo "<tr><td class=header colspan=2 align=center>Targetting</td></tr>";

	echo "<tr><td class=body colspan=2>";

	echo "<table width=100%><tr>\n";
//sex
	echo "<td class=body valign=top>Sexes:<br>";
	echo "<select class=body name=data[sexes][] size=3 multiple=multiple>" . make_select_list_multiple_key($sexes, $sex) . "</select>";
	echo "</td>\n";
//age
	echo "<td class=body valign=top>Ages:<br>";
	echo "<select class=body name=data[agedefault] style=\"width:75px\">" . make_select_list_key($defaults, $agedefault) . "</select><br>";
	echo "<select class=body name=data[ages][] size=15 multiple=multiple style=\"width:75\">" . make_select_list_multiple(range(14,65), $age) . "</select>";
	echo "</td>\n";

//loc
	$locations = & new category( $db, "locs");
	echo "<td class=body valign=top>Locations:<br>";
	echo "<select class=body name=data[locdefault] style=\"width:75px\">" . make_select_list_key($defaults, $locdefault) . "</select><br>";
	echo "<select class=body name=data[locs][] size=15 multiple=multiple>" . makeCatSelect_multiple($locations->makeBranch(), $loc) . "</select>";
	echo "</td>\n";

//page
	echo "<td class=body valign=top>Pages:<br>";
	echo "<select class=body name=data[pagedefault] style=\"width:75px\">" . make_select_list_key($defaults, $pagedefault) . "</select><br>";
	echo "<textarea class=body name=data[pages] rows=13 cols=10>$page</textarea><br>One page per line";
	echo "</td>\n";

//interests
	$interestcats = & new category( $db, "interests");
	echo "<td class=body valign=top>Interests:<br>";
	echo "<select class=body name=data[interests][] size=16 multiple=multiple>" . makeCatSelect_multiple($interestcats->makeBranch(), $interests) . "</select>";
	echo "</td>\n";

	echo "</tr>";
	echo "<tr><td class=body colspan=5>Choosing 'Only' for ages or locations makes it only viewable to logged in users</td></tr>";

	echo "</table>";
	echo "</td></tr>\n";


//limits
	echo "<tr><td class=header colspan=2 align=center>Limiting Conditions</td></tr>";

	echo "<tr><td class=body>Refresh Rate:</td><td class=body><input class=body type=text name=data[refresh] value=$refresh size=5> seconds. 0 to disable</td></tr>";

	echo "<tr><td class=body>Pay Rate:</td><td class=body><input class=body type=text name=data[payrate] value=$payrate size=5>";
	echo "<select class=body name=data[paytype]>" . make_select_list_key(array(BANNER_CPM => "CPM", BANNER_CPC => "CPC"), $paytype) . "</select></td></tr>";


	echo "<tr><td class=body valign=top>Max Views:</td><td class=body><input class=body type=text name=data[maxviews] size=10 value=$maxviews> 0 for unlimited</td></tr>\n";
	echo "<tr><td class=body valign=top>Max Clicks:</td><td class=body><input class=body type=text name=data[maxclicks] size=10 value=$maxclicks> 0 for unlimited</td></tr>\n";
	echo "<tr><td class=body valign=top>Max Views per Day:</td><td class=body><input class=body type=text name=data[viewsperday] size=10 value=$viewsperday> 0 for unlimited</td></tr>\n";
	echo "<tr><td class=body valign=top>Max Clicks per Day:</td><td class=body><input class=body type=text name=data[clicksperday] size=10 value=$clicksperday> 0 for unlimited</td></tr>\n";

	echo "<tr><td class=body valign=top>Max Views Per User:</td><td class=body>";
	echo "<input class=body type=text name=data[viewsperuser] size=10 value=$viewsperuser>";
	echo "<select class=body name=data[freqperiod]>" . make_select_list_key(array( 'y' => "Hour", 'n' => "Day"), $freqperiod) . "</select> 0 for unlimited";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Start Date:</td><td class=body>";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list_key($months, $startmonth) . "</select>";
		echo "<select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31), $startday) . "</select>";
		echo "<select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1), $startyear) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>End Date:</td><td class=body>";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list_key($months, $endmonth) . "</select>";
		echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31), $endday) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1), $endyear) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body></td><td class=body>";

	if($id){ //edit
		echo "<input type=hidden name=id value=$id>";
		echo "<input type=hidden name=action value=updatebanner><input class=body type=submit value='Update Banner'><input class=body type=submit name=action value=Cancel>";
	}else{ //add
		echo "<input type=hidden name=action value=insertbanner><input class=body type=submit value='Add Banner'><input class=body type=submit name=action value=Cancel>";
	}
	echo "</td></tr>\n";

	echo "</form></table>\n";


	incFooter();
	exit;
}

function insertBanner($data){
	global $banner, $msgs;

	if(empty($data['clientid'])){
		$msgs->addMsg("Bad Client");
		addBanner($data['bannertype'], 0, $data); //exit
	}

	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? 0 : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? 0 : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$sex		= (!isset($data['sexes'])		|| !count($data['sexes'])		? "" : implode(",", $data['sexes']));
	$age		= (!isset($data['ages'])		|| !count($data['ages'])		? "" : ($data['agedefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['ages'])); //if it includes anon, start with 0. This is all except
	$loc		= (!isset($data['locs'])		|| !count($data['locs'])		? "" : ($data['locdefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['locs']));
	$page		= (!isset($data['pages'])		|| !strlen($data['pages'])		? "" : ($data['pagedefault']		== ALL_EXCEPT ? "0," : "") . implode(",", array_map('trim', explode("\n", $data['pages']))));
	$interests	= (!isset($data['interests'])	|| !strlen($data['interests'])	? "" : implode(",", $data['interests']));

	$set[] = "clientid = ?"; 		$params[] = $data['clientid'];
	$set[] = "bannersize = ?"; 		$params[] = $data['bannersize'];
	$set[] = "bannertype = ?"; 		$params[] = $data['bannertype'];
	$set[] = "maxviews = ?"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = ?"; 		$params[] = $data['maxclicks'];
	$set[] = "viewsperday = ?"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = ?";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = ?";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = ?"; 		$params[] = $start;
	$set[] = "enddate = ?"; 		$params[] = $end;
	$set[] = "payrate = ?"; 		$params[] = $data['payrate'];
	$set[] = "paytype = ?"; 		$params[] = $data['paytype'];
	$set[] = "moded = ?"; 			$params[] = 'y';
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "image = ?"; 			$params[] = (isset($data['image']) ? $data['image'] : "");
	$set[] = "link = ?"; 			$params[] = (isset($data['link']) ? $data['link'] : "");
	$set[] = "alt = ?"; 			$params[] = (isset($data['alt']) ? $data['alt'] : "");
	$set[] = "dateadded = ?"; 		$params[] = time();
	$set[] = "age = ?"; 			$params[] = $age;
	$set[] = "sex = ?"; 			$params[] = $sex;
	$set[] = "loc = ?"; 			$params[] = $loc;
	$set[] = "page = ?"; 			$params[] = $page;
	$set[] = "interests = ?"; 		$params[] = $interests;
	$set[] = "enabled = ?"; 		$params[] = (isset($data['enabled']) ? 'y' : 'n');
	$set[] = "refresh = ?"; 		$params[] = $data['refresh'];

	$banner->db->prepare_array_query("INSERT INTO banners SET " . implode(", ", $set), $params);

	$id = $banner->db->insertid();

	$banner->addBanner($id);

	$msgs->addMsg("Banner Added");
}



function updateBanner($id, $data){
	global $banner, $msgs;

	$start = ($data['startmonth'] == 0 && $data['startday'] == 0 && $data['startyear'] == 0 ? 0 : usermktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['endmonth'] == 0 && $data['endday'] == 0 && $data['endyear'] == 0 ? 0 : usermktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$sex		= (!isset($data['sexes'])		|| !count($data['sexes'])		? "" : implode(",", $data['sexes']));
	$age		= (!isset($data['ages'])		|| !count($data['ages'])		? "" : ($data['agedefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['ages'])); //if it includes anon, start with 0. This is all except
	$loc		= (!isset($data['locs'])		|| !count($data['locs'])		? "" : ($data['locdefault']			== ALL_EXCEPT ? "0," : "") . implode(",", $data['locs']));
	$page		= (!isset($data['pages']) 		|| !strlen($data['pages'])		? "" : ($data['pagedefault']		== ALL_EXCEPT ? "0," : "") . implode(",", array_map('trim', explode("\n", $data['pages']))));
	$interests	= (!isset($data['interests'])	|| !strlen($data['interests'])	? "" : implode(",", $data['interests']));

	$set[] = "clientid = ?"; 		$params[] = $data['clientid'];
	$set[] = "bannersize = ?"; 		$params[] = $data['bannersize'];
	$set[] = "bannertype = ?"; 		$params[] = $data['bannertype'];
	$set[] = "maxviews = ?"; 		$params[] = $data['maxviews'];
	$set[] = "maxclicks = ?"; 		$params[] = $data['maxclicks'];
	$set[] = "viewsperday = ?"; 	$params[] = $data['viewsperday'];
	$set[] = "clicksperday = ?";	$params[] = $data['clicksperday'];
	$set[] = "viewsperuser = ?";	$params[] = $data['viewsperuser'];
	$set[] = "limitbyhour = ?"; 	$params[] = $data['freqperiod'];
	$set[] = "startdate = ?"; 		$params[] = $start;
	$set[] = "enddate = ?"; 		$params[] = $end;
	$set[] = "payrate = ?"; 		$params[] = $data['payrate'];
	$set[] = "paytype = ?"; 		$params[] = $data['paytype'];
	$set[] = "moded = ?"; 			$params[] = 'y';
	$set[] = "title = ?"; 			$params[] = $data['title'];
	$set[] = "image = ?"; 			$params[] = (isset($data['image']) ? $data['image'] : "");
	$set[] = "link = ?"; 			$params[] = (isset($data['link']) ? $data['link'] : "");
	$set[] = "alt = ?"; 			$params[] = (isset($data['alt']) ? $data['alt'] : "");
	$set[] = "dateadded = ?"; 		$params[] = time();
	$set[] = "age = ?"; 			$params[] = $age;
	$set[] = "sex = ?"; 			$params[] = $sex;
	$set[] = "loc = ?"; 			$params[] = $loc;
	$set[] = "page = ?"; 			$params[] = $page;
	$set[] = "interests = ?"; 		$params[] = $interests;
	$set[] = "enabled = ?"; 		$params[] = (isset($data['enabled']) ? 'y' : 'n');
	$set[] = "refresh = ?"; 		$params[] = $data['refresh'];

	$params[] = $id;

	$banner->db->prepare_array_query("UPDATE banners SET " . implode(", ", $set) . " WHERE id = ?", $params);

	$banner->updateBanner($id);

	$msgs->addMsg("Banner Updated");
}

function deleteBanner($id){
	global $banner;

	$banner->db->prepare_query("DELETE FROM banners WHERE id = ?", $id);
	$banner->db->prepare_query("DELETE FROM bannerstats WHERE bannerid = ?", $id);

	$banner->deleteBanner($id);
}

function bannerStats($id, $start){
	global $banner;

	$end = $start + 86400;

	$banner->db->prepare_query("SELECT time, views, clicks FROM bannerstats WHERE bannerid = ? && time >= ? && time <= ? ORDER BY time", $id, $start, $end);

	$stats = array();
	while($line = $banner->db->fetchrow())
		$stats[] = $line;

	$month= userdate("n",$start);
	$day  = userdate("j",$start);
	$year = userdate("Y",$start);


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";

	echo "<form action=$_SERVER[PHP_SELF]><tr><td class=header colspan=8 align=center>";
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
		echo "<td class=header colspan=3></td>";
		echo "</tr>";
	}

	echo "</table>";

	incFooter();
	exit;
}

function array_merge_recursive_sum($array, $second){
	foreach($second as $key => $value){
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
	global $banner;


	$banner->db->prepare_query("SELECT * FROM bannertypestats WHERE size = ? && time >= ? && time <= ?", $size, $date, $date + 86400);

	$views = array();
	$clicks = array();

	while($line = $banner->db->fetchrow()){
		$views = array_merge_recursive_sum($views, unserialize(gzuncompress($line['viewsdump'])));
		$clicks = array_merge_recursive_sum($clicks, unserialize(gzuncompress($line['clicksdump'])));
	}

	$month= userdate("n",$date);
	$day  = userdate("j",$date);
	$year = userdate("Y",$date);


	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	incHeader();

	echo "<table align=center>";
	echo "<form action=$_SERVER[PHP_SELF]>";
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

				global $db;
				$locations = & new category( $db, "locs");

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

				global $db;
				$interests = & new category( $db, "locs");


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
	global $banner;

	$banner->db->query("SELECT id, userid, clientname, type, dateadded FROM bannerclients ORDER BY clientname");

	$clients = array();
	$userids = array();
	while($line = $banner->db->fetchrow()){
		$clients[] = $line;
		$userids[] = $line['userid'];
	}

	getUserName($userids); //get all at once, cache them

	incHeader();

	echo "<table align=center>";

	echo "<tr>";
	echo "<td class=header>Client Name</td>";
	echo "<td class=header>User</td>";
	echo "<td class=header>Type</td>";
	echo "<td class=header>Date Added</td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "</tr>";

	foreach($clients as $line){
		echo "<tr>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=listbanners&clientid=$line[id]&all=2>$line[clientname]</a></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[userid]>" . getUserName($line['userid']) . "</td>";
		echo "<td class=body>$line[type]</td>";
		echo "<td class=body>" . userdate("M j, Y", $line['dateadded']) . "</td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=editclient&id=$line[id]>Edit</a></td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=deleteclient&id=$line[id]>Delete</a></td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=6 align=right><a class=header href=$_SERVER[PHP_SELF]?action=addclient>Add Client</a></td></tr>";
	echo "</table>";

	incFooter();
	exit;
}

function addClient(){
	global $banner;

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
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

	$banner->db->prepare_query("SELECT userid, clientname, type, dateadded, notes FROM bannerclients WHERE id = ?", $id);

	$line = $banner->db->fetchrow();

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>";
	echo "<tr><td class=header colspan=2 align=center>Edit Client</td></tr>";
	echo "<input type=hidden name=id value=$id>";

	echo "<tr><td class=body>Client Name:</td><td class=body><input class=body type=text name=clientname value='$line[clientname]'></td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=clientusername value='" . getUserName($line['userid']) . "'></td></tr>";
	echo "<tr><td class=body>Type:</td><td class=body><select class=body name=clienttype>" . make_select_list($banner->clienttypes, $line['type']) . "</select></td></tr>";
	echo "<tr><td class=body>Notes:</td><td class=body><textarea class=body name=clientnotes cols=50 rows=6>$line[notes]</textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Update Client'></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

function insertClient($userid, $name, $type, $notes){
	global $banner;

	$banner->db->prepare_query("INSERT INTO bannerclients SET userid = ?, clientname = ?, type = ?, dateadded = ?, notes = ?", $userid, $name, $type, time(), $notes);
}

function updateClient($id, $userid, $name, $type, $notes){
	global $banner;

	$banner->db->prepare_query("UPDATE bannerclients SET userid = ?, clientname = ?, type = ?, notes = ? WHERE id = ?", $userid, $name, $type, $notes, $id);
}

function deleteClient($id){
	global $msgs;
	$this->db->prepare_query("SELECT id FROM banners WHERE clientid = ?", $id);

	if($this->db->numrows()){
		$msgs->addMsg("Client has banners remaining. Remove them first.");
		return false;
	}

	$this->db->prepare_query("DELETE FROM bannerclients WHERE id = ?", $id);
}

