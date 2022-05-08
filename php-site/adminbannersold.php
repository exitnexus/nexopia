<?

	$login=1;

	require_once("include/general.lib.php");

	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");

	if(!$bannerAdmin)
		die("You don't have permission to see this");

	//assume $banner exists

	$scope = array('Active','Moded','All');

	switch($action){
		case "listbanners":
			if(empty($size))
				$size = false;
			if(empty($type))
				$type = false;
			if(empty($clientid))
				$clientid = false;
			if(empty($all))
				$all = false;

			listBanners($size, $type, $clientid, $all); //exit

		case "viewbanner":
			viewBanner($id); //exit

		case "editbanner":
			editBanner($id); //exit
		case "updatebanner":
			updateBanner($data);
			break;

		case "deletebanner":
			$banner->deleteBanner($id);
			break;

		case "bannerstats":
			if(empty($interval))
				$interval = 3600;
			if(empty($month) || empty($day) || empty($year))
				$start = time() - $interval*30;
			else
				$start = mktime(0,0,0,$month,$day,$year);

			bannerStats($id, $interval, $start);	//exit

		case "addbanner":
		case "Add Banner":
			if(isset($type) && in_array($type, $banner->bannertypes))
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
			$banner->addClient(getUserID($clientusername), $clientname, $clienttype, $clientnotes);
			listClients();	//exit

		case "editclient":
			editClient($id); //exit

		case "updateclient":
		case "Update Client":
			$banner->updateClient($id, $clientnotes);
			listClients();	//exit

		case "deleteclient":
			$banner->deleteClient($id);
			listClients();	//exit
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
		case 0:
			$where[] = "weight > 0";
		case 1:
			$where[] = "moded = 'y'";
//		case 2:
//		default:
	}

	$banner->db->query("SELECT id, clientname FROM bannerclients");

	$clients = array();
	while($line = $banner->db->fetchrow())
		$clients[$line['id']] = $line['clientname'];

	uasort($clients, 'strcasecmp');


	$banner->db->prepare_array_query("SELECT id, clientid, maxviewsperday, payrate, maxviews, startdate, enddate, bannersize, bannertype, views, clicks, title, weight, link, moded FROM banners WHERE " . implode(" && ", $where), $params);

	$banners = array();
	while($line = $banner->db->fetchrow()){
		$line['clientname'] = $clients[$line['clientid']];
		$banners[] = $line;
	}

	sortCols($banners, SORT_ASC, SORT_CASESTR, 'title', SORT_ASC, SORT_CASESTR, 'clientname', SORT_ASC, SORT_STRING, 'bannersize');

	incHeader();

	echo "<table align=center>";

	$cols = 17;

	echo "<tr>";
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<td class=header colspan=$cols align=center>";
	echo "<a class=header href=$_SERVER[PHP_SELF]?action=listclients>List Clients</a> ";
	echo "<select class=body name=size><option value=0>Size" . make_select_list($banner->bannersizes, $size) . "</select>";
	echo "<select class=body name=type><option value=0>Type" . make_select_list($banner->bannertypes, $type) . "</select>";
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
	echo "<td class=header>Pay Rate</td>";
	echo "<td class=header>Max Views/day</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Clickthru</td>";
	echo "<td class=header>Weight</td>";
	echo "<td class=header>Expected</td>";
	echo "<td class=header>Start</td>";
	echo "<td class=header>End</td>";
	echo "<td class=header>Max Views</td>";
	echo "<td class=header>Moded</td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	if($all)
		echo "<td class=header></td>";
	echo "</tr>";
	$maxviews = 0;
	$views = 0;
	$clicks = 0;
	$weight = 0;
	$expected = 0;

	$classes = array('body','body2');
	$i=1;

	foreach($banners as $line){
		$i = !$i;
		echo "<tr>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=listclients&id=$line[clientid]>" . $clients[$line['clientid']] . "</a></td>";
		echo "<td class=$classes[$i]>$line[title]</td>";
		echo "<td class=$classes[$i]>$line[bannersize]</td>";
		echo "<td class=$classes[$i]>$line[bannertype]</td>";
		echo "<td class=$classes[$i] align=right>$line[payrate] c</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($line['maxviewsperday']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($line['views']) . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] ? number_format($line['clicks']) : "N/A") . "</td>";
		echo "<td class=$classes[$i] align=right>" . ($line['link'] && $line['views'] ? number_format($line['clicks']/$line['views']*100, 3) . "%" : "N/A") . "</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($line['weight']*100, 3) . "%</td>";
		echo "<td class=$classes[$i] align=right>" . number_format($line['weight']*$banner->viewsperday($line['bannersize'])) . "</td>";
		echo "<td class=$classes[$i]>" . ($line['startdate'] > 0 ? userdate("M j, Y", $line['startdate']) : "Started") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['enddate'] > 0 ? userdate("M j, Y", $line['enddate']) : "Never")  . "</td>";
		echo "<td class=$classes[$i]>" . ($line['maxviews'] ? $line['maxviews'] : "Unlimited") . "</td>";
		echo "<td class=$classes[$i]>" . ($line['moded'] == 'n' ? "No" : "") . "</td>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=editbanner&id=$line[id]>Edit</a></td>";
		echo "<td class=$classes[$i]><a class=body href=$_SERVER[PHP_SELF]?action=bannerstats&id=$line[id]>Stats</a></td>";
		if($all)
			echo "<td class=$classes[$i]>" . ($line['weight'] ? "" : "<a class=body href=$_SERVER[PHP_SELF]?action=deletebanner&id=$line[id]>Delete</a>" ) . "</td>";

		echo "</tr>";
		$maxviews += $line['maxviewsperday'];
		$views += $line['views'];
		$clicks += $line['clicks'];
		$weight += $line['weight'];
		$expected += $line['weight']*$banner->viewsperday($line['bannersize']);
	}
	echo "<form action=$_SERVER[PHP_SELF]>";
	echo "<tr><td class=header colspan=5></td>";
	echo "<td class=header align=right>" . number_format($maxviews) . "</td>";
	echo "<td class=header align=right>" . number_format($views) . "</td>";
	echo "<td class=header align=right>" . number_format($clicks) . "</td>";
	echo "<td class=header align=right>" . ($views ? number_format($clicks/$views*100, 3) . "%" : "N/A") . "</td>";
	echo "<td class=header align=right>" . number_format($weight*100, 3) . "%</td>";
	echo "<td class=header align=right>" . number_format($expected) . "</td>";
	echo "<td class=header colspan=7 align=right><select class=body name=type>" . make_select_list($banner->bannertypes) . "</select><input class=body type=submit name=action value='Add Banner'></td></tr>";
	echo "</form>";
	echo "</table>";

	incFooter();
	exit;
}

function addBanner($type, $data = array()){
	global $banner;

	$bannersize		= "";
	$clientid		= 0;
	$maxviewsperday	= 0;
	$payrate		= 0;
	$maxviews		= 0;
	$start			= "now";
	$startmonth		= 0;
	$startday		= 0;
	$startyear		= 0;
	$end			= "never";
	$endmonth		= 0;
	$endday			= 0;
	$endyear		= 0;
	$title			= "";
	$image			= "";
	$link			= "";
	$alt			= "";

	extract($data);


	$banner->db->query("SELECT id, clientname FROM bannerclients");

	while($line = $banner->db->fetchrow())
		$clients[$line['id']]=$line['clientname'];

	uasort($clients, 'strcasecmp');

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>\n";

	echo "<tr><td class=header colspan=2 align=center>Add Image Banner</td></tr>";

	echo "<input type=hidden name=data[bannertype] value=$type>";

	echo "<tr><td class=body>Client:</td><td class=body><select class=body name=data[clientid]>";
	echo "<option value=0>Client";
	echo make_select_list_key($clients, $clientid);
	echo "</select></td></tr>";

	echo "<tr><td class=body valign=top>Banner Size:</td><td class=body>";
	echo "<select class=body name=data[bannersize]>" . make_select_list($banner->bannersizes, $bannersize) . "</select> ";
	echo "</td></tr>\n";

	echo "<tr><td class=body>Title:</td><td class=body>";
	echo "<input class=body type=text name=data[title] value=\"$title\" size=20 maxlength=32> For reference only";
	echo "</td></tr>\n";

	switch($type){
		case "image":
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

		case "flash":
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

		case "iframe":
			echo "<tr><td class=body>Banner:</td><td class=body>";
			echo "<input class=body type=text name=data[image] value=\"$image\" size=40 maxlength=255>";
			echo "</td></tr>\n";

			break;

		case "html":
			echo "<tr><td class=body valign=top>HTML:</td><td class=body>";
			echo "<textarea class=body cols=60 rows=10 name=data[alt]>$alt</textarea>";
			echo "</td></tr>\n";

			break;

		case "text":
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

	echo "<tr><td class=header colspan=2 align=center>Limiting Conditions</td></tr>";

	echo "<tr><td class=body valign=top>Max Views Per day:</td><td class=body>";
	echo "<input class=body type=text name=data[maxviewsperday] size=10 value=$maxviewsperday> 0 to disable the banner";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Pay Rate:</td><td class=body>";
	echo "<input class=body type=text name=data[payrate] size=10 value=$payrate> in cents per 1000 impressions (ie CPM / 100)";
	echo "</td></tr>\n";
	echo "<tr><td class=body colspan=2>Priority will be givin to those with the higher pay rates.<br>Don't put in a max view per day higher than you are willing to pay for, as you might get them.</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Stop Conditions</td></tr>";

	echo "<tr><td class=body valign=top>Max Views:</td><td class=body>";
	echo "<input class=body type=text name=data[maxviews] size=10 value=$maxviews> 0 for unlimited";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Start Date:</td><td class=body>";
	echo "<input type=radio name=data[start] value=now" . ($start == 'now' ? " checked" : "") . ">Now<br>";
	echo "<input type=radio name=data[start] value=later" . ($start == 'later' ? " checked" : "") . ">";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list(range(1,12), $startmonth) . "</select>";
		echo "<select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31), $startday) . "</select>";
		echo "<select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1), $startyear) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>End Date:</td><td class=body>";
	echo "<input type=radio name=data[end] value=never" . ($start == 'never' ? " checked" : "") . ">Never<br>";
	echo "<input type=radio name=data[end] value=later" . ($start == 'later' ? " checked" : "") . ">";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list(range(1,12), $endmonth) . "</select>";
		echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31), $endday) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1), $endyear) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body></td><td class=body><input type=hidden name=action value=insertbanner><input class=body type=submit value='Add Banner'><input class=body type=submit name=action value=Cancel></td></tr>\n";

	echo "</form></table>\n";


	incFooter();
	exit;
}

function insertBanner($data){
	global $banner, $msgs;

	if(empty($data['clientid'])){
		$msgs->addMsg("Bad Client");
		addBanner($data['bannertype'], $data); //exit
	}

	$start = ($data['start'] == 'now' ? 0 : mktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['end'] == 'never' ? 0 : mktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$banner->addBanner($data['bannertype'], $data['bannersize'], $data['clientid'], $data['maxviewsperday'], $data['payrate'], $data['maxviews'],
			$start, $end, $data['title'], $data['image'], $data['link'], $data['alt']);

	$msgs->addMsg("Banner Added");
}

function updateBanner($data){
	global $banner, $msgs;

	$start = ($data['start'] != 'later' ? 0 : mktime(0,0,0,$data['startmonth'],$data['startday'],$data['startyear']));
	$end   = ($data['end']   != 'later' ? 0 : mktime(23,59,59,$data['endmonth'],$data['endday'],$data['endyear']));

	$banner->updateBanner($data['id'], $data['maxviewsperday'], $data['payrate'], $data['maxviews'], $start, $end);
}

function editBanner($id){
	global $banner;

	$banner->db->prepare_query("SELECT views, clicks, title, maxviewsperday, payrate, maxviews, startdate, enddate FROM banners WHERE id = ?", $id);

	$line = $banner->db->fetchrow();

	incHeader();

	echo "<table align=center><form action=$_SERVER[PHP_SELF] method=post>\n";

	echo "<input type=hidden name=data[id] value=$id>";

	echo "<tr><td class=header colspan=2 align=center>Edit Banner</td></tr>";

	echo "<tr><td class=body colspan=2 align=center>" . $banner->getBannerID($id) . "</td></tr>";

	echo "<tr><td class=body>Title:</td><td class=body>$line[title]</td></tr>";
	echo "<tr><td class=body>Views:</td><td class=body>$line[views]</td></tr>";
	echo "<tr><td class=body>Clicks:</td><td class=body>$line[clicks]</td></tr>";
	echo "<tr><td class=body>Clickthrough:</td><td class=body>" . ($line['views'] ? number_format($line['clicks'] / $line['views']*100,3) . "%" : "N/A" ) . "</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Limiting Conditions</td></tr>";

	echo "<tr><td class=body valign=top>Max Views Per day:</td><td class=body>";
	echo "<input class=body type=text name=data[maxviewsperday] size=10 value=$line[maxviewsperday]> 0 to disable the banner";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Pay Rate:</td><td class=body>";
	echo "<input class=body type=text name=data[payrate] size=10 value=$line[payrate]> in cents per 1000 impressions (ie CPM / 100)";
	echo "</td></tr>\n";
	echo "<tr><td class=body colspan=2>Priority will be givin to those with the higher pay rates.<br>Don't put in a max view per day higher than you are willing to pay for, as you might get them.</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Stop Conditions</td></tr>";

	echo "<tr><td class=body valign=top>Max Views:</td><td class=body>";
	echo "<input class=body type=text name=data[maxviews] size=10 value=$line[maxviews]> 0 for unlimited";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Start Date:</td><td class=body>";
	echo "<input type=radio name=data[start] value=now" . ($line['startdate'] == 0 ? " checked" : "") . ">Now<br>";
	echo "<input type=radio name=data[start] value=later" . ($line['startdate'] != 0 ? " checked" : "") . ">";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list(range(1,12), userdate("m",$line['startdate'])) . "</select>";
		echo "<select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31),userdate("j",$line['startdate'])) . "</select>";
		echo "<select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1),userdate("Y",$line['startdate'])) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>End Date:</td><td class=body>";
	echo "<input type=radio name=data[end] value=never" . ($line['enddate'] == 0 ? " checked" : "") . ">Never<br>";
	echo "<input type=radio name=data[end] value=later" . ($line['enddate'] != 0 ? " checked" : "") . ">";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list(range(1,12),userdate("m",$line['enddate'])) . "</select>";
		echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31),userdate("j",$line['enddate'])) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1),userdate("Y",$line['enddate'])) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body></td><td class=body><input type=hidden name=action value=updatebanner><input class=body type=submit value='Update Banner'><input class=body type=submit name=action value=Cancel></td></tr>\n";

	echo "</form></table>\n";


	incFooter();
	exit;
}

function bannerStats($id, $interval = 3600, $start = false, $end = false){
	global $banner;

	$intervals = array(3600 => "Hourly", 86400 => "Daily");

	if(!$start)
		$start = time() - $interval*30;

	if(!$end)
		$end = $start + $interval*30;


	$banner->db->prepare_query("SELECT time, payrate, views, clicks, weight FROM bannerstats WHERE bannerid = ? && time >= ? && time <= ? ORDER BY time", $id, $start, $end);

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

	echo "<form action=$_SERVER[PHP_SELF]><tr><td class=header colspan=7 align=center>";
	echo "<select class=body name=interval>" . make_select_list_key($intervals, $interval) . "</select>";
	echo "<select class=body name=\"month\"><option value=0>Month" . make_select_list_key($months,$month) . "</select>";
	echo "<select class=body name=\"day\"><option value=0>Day" . make_select_list(range(1,31),$day) . "</select>";
	echo "<select class=body name=\"year\"><option value=0>Year" . make_select_list(array_reverse(range(2002,date("Y"))),$year) . "</select>";
	echo "<input type=hidden name=action value=bannerstats>";
	echo "<input type=hidden name=id value=$id>";
	echo "<input class=body type=submit value=Go>";

	echo "</td></tr></form>";

	echo "<tr>";
	echo "<td class=header>Time</td>";
	echo "<td class=header>Pay Rate</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Click Thru</td>";
	echo "<td class=header>Weight</td>";
	echo "<td class=header>Owed</td>";
	echo "</tr>";

	$totalviews = 0;
	$totalclicks = 0;
	$totalowed = 0;
	$periods = 0;

	$periodstart = 0;

	foreach($stats as $line){

		$periodviews += $line['views'];
		$periodclicks += $line['clicks'];
		$periodpay += $line['payrate'];
		$periodweight += $line['weight'];
		$periodowed += ($line['payrate']/100) * ($line['views'] / 1000);
		$periods++;

		$totalviews += $line['views'];
		$totalclicks += $line['clicks'];
		$totalowed += ($line['payrate']/100) * ($line['views'] / 1000);

		if($periodstart <= $line['time'] - $interval){
			echo "<tr>";
			echo "<td class=body>" . userDate("M j, Y, g:i a", $line['time']) . "</td>";
			echo "<td class=body align=right>" . number_format($periodpay/$periods) . " c</td>";
			echo "<td class=body align=right>" . number_format($periodviews) . "</td>";
			echo "<td class=body align=right>" . number_format($periodclicks) . "</td>";
			echo "<td class=body align=right>" . ($periodviews ? number_format(100*$periodclicks / $periodviews,3) . "%" : "N/A" ) ."</td>";
			echo "<td class=body align=right>" . number_format(100*$periodweight/$periods, 3) . "%</td>";
			echo "<td class=body align=right>\$" . number_format($periodowed, 3) . "</td>";
			echo "</tr>";

			$periodviews = 0;
			$periodclicks = 0;
			$periodpay = 0;
			$periods = 0;
			$periodstart = $line['time'];
			$periodweight = 0;
			$periodowed = 0;
		}
	}

	echo "<tr>";
	echo "<td class=header colspan=2></td>";
	echo "<td class=header>$totalviews</td>";
	echo "<td class=header>$totalclicks</td>";
	echo "<td class=header>" . ($totalviews ? number_format(100*$totalclicks / $totalviews,3) . "%" : "N/A" ) . "</td>";
	echo "<td class=header></td>";
	echo "<td class=header>\$" . number_format($totalowed, 3) . "</td>";
	echo "</tr>";

	echo "</table>";

	incFooter();
	exit;
}









function listClients(){
	global $banner;

	$banner->db->query("SELECT id, userid, clientname, type, dateadded, owed FROM bannerclients ORDER BY clientname");

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
	echo "<td class=header>Amount Owed</td>";
	echo "<td class=header></td>";
	echo "<td class=header></td>";
	echo "</tr>";

	foreach($clients as $line){
		echo "<tr>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=listbanners&clientid=$line[id]&all=2>$line[clientname]</a></td>";
		echo "<td class=body><a class=body href=profile.php?uid=$line[userid]>" . getUserName($line['userid']) . "</td>";
		echo "<td class=body>$line[type]</td>";
		echo "<td class=body>" . userdate("M j, Y", $line['dateadded']) . "</td>";
		echo "<td class=body align=right>\$$line[owed]</td>";
		echo "<td class=body><a class=body href=$_SERVER[PHP_SELF]?action=editclient&id=$line[id]>Edit</a></td>";
		echo "<td class=body>" . ($line['owed'] != 0 ? "" : "<a class=body href=$_SERVER[PHP_SELF]?action=deleteclient&id=$line[id]>Delete</a>") . "</td>";
		echo "</tr>";
	}
	echo "<tr><td class=header colspan=7 align=right><a class=header href=$_SERVER[PHP_SELF]?action=addclient>Add Client</a></td></tr>";
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

	echo "<tr><td class=body>Client Name:</td><td class=body>$line[clientname]</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body>" . getUserName($line['userid']) . "</td></tr>";
	echo "<tr><td class=body>Type:</td><td class=body>$line[type]</td></tr>";
	echo "<tr><td class=body>Notes:</td><td class=body><textarea class=body name=clientnotes cols=50 rows=6>$line[notes]</textarea></td></tr>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit name=action value='Update Client'></td></tr>";
	echo "</form></table>";

	incFooter();
	exit;
}

