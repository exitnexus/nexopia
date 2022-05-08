<?
//check categories,locations. Verify data

	$login=0;

	require_once("include/general.lib.php");


	for($i=1;$i<=12;$i++)
		$months[$i] = gmdate("F", gmmktime(0,0,0,$i,1,2003));
	$days = array("Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday");



//global/public/private
	if(!isset($calsort['scope']) || !($calsort['scope']=='global' || $calsort['scope']=='public' || $calsort['scope']=='private'))
		$calsort['scope']='global';

	if(!$userData['loggedIn']){
		if($calsort['scope']=='private'){
			$calsort['scope']='global';
			$calsort['uid']=0;
		}
	}



	if(!isset($calsort['uid']) || !is_numeric($calsort['uid'])){
		if($userData['loggedIn'])
			$calsort['uid']=$userData['userid'];
		else{
			$calsort['scope']='global';
			$calsort['uid']=0;
		}
	}



//category
	$catdata = getChildData("schedulecats");
	$catbranch = makeBranch($catdata);

	if(!isset($calsort['category']) || isValidCat($catbranch,$calsort['category'])===false)
		$calsort['category'] =0;

	if($calsort['category']!=0)
		$catsubbranch=makeBranch($catdata,$calsort['category']);

//location
	$locdata = getChildData("locs");
	$locbranch = makeBranch($locdata);

	if(!isset($calsort['loc']) || isValidCat($locbranch,$calsort['loc'])===false)
		$calsort['loc'] =0;

	if($calsort['loc']!=0)
		$locsubbranch=makeBranch($locdata,$calsort['loc']);


//date
	if(!isset($month))	$month = userdate('n');
	if(!isset($year))	$year = userdate('Y');
	if(!isset($day))	$day = userdate('j');

//admin stuff
	if($userData['loggedIn'])
		$isAdmin = $mods->isAdmin($userData['userid'],'schedule');
	else
		$isAdmin = false;

	if($isAdmin){
		if(!isset($adminaction))
			$adminaction="";

		switch($adminaction){
			case "Delete":
				foreach($check as $id){
					$query = "DELETE FROM schedule WHERE id='$id'";
					mysql_query($query);
				}
				break;
		}
	}

	if(!isset($action)) $action="";

	sqlSafe(&$data,&$month,&$day,&$year,&$calsort);

	switch($action){
		case "showday":
			showCalDay($month,$day,$year,$calsort);
			break;
		case "add":
			addCalItem();
			break;
		case "insert":
			insertCalItem($data);
			break;

	}
	showCalMonth($month,$year,$calsort);



function addCalItem(){
	global $months,$userData;

	if(!$userData['loggedIn'])
		return;

	incHeader();

	echo "<table>";

	echo "<form method=post action=$_SERVER[PHP_SELF]>";

	echo "<tr><td class=body>Date:</td><td class=body>";
	echo "<select class=body name=\"data[month]\"><option value=0>Month" . make_select_list_key($months,userdate("m")) . "</select>";
	echo "<select class=body name=\"data[day]\"><option value=0>Day" . make_select_list(range(1,31),userdate("j")) . "</select>";
	echo "<select class=body name=\"data[year]\"><option value=0>Year" . make_select_list(range(userdate("Y"),date("Y")+5),userdate("Y")) . "</select>";
	echo "</td></tr>";

	echo "<tr><td class=body>Visibility:</td><td class=body>";
	echo "<select class=body name=data[scope]><option value=private>Private<option value=public>Public<option value=global>Global</select>";
	echo "</td></tr>";

	$catdata = getChildData("schedulecats");
	$catbranch = makeBranch($catdata);

	echo "<tr><td class=body>Category:</td><td class=body><select class=body name=data[cat]><option value=0>Choose a Category";
	echo makeCatSelect($catbranch);
	echo "</td></tr>";

	$locdata = getChildData("locs");
	$locbranch = makeBranch($locdata);

	echo "<tr><td class=body>Location:</td><td class=body><select class=body name=data[loc]><option value=0>Choose a Location (if applicable)";
	echo makeCatSelect($locbranch);
	echo "</td></tr>";

	echo "<tr><td class=body>Title:</td><td class=body><input class=body type=text name=data[title] maxlength=31 size=30></td></tr>";

	echo "<tr><td class=body colspan=2>Description:<br><textarea name=data[desc] cols=40 rows=4></textarea></td></tr>";

	echo "<tr><td class=body></td><td class=body><input type=hidden name=action value=insert><input class=body type=submit value=Add><input class=body name=action type=submit value=Cancel></td></tr>";

	echo "</table>";

	incFooter();
	exit;
}

function insertCalItem($data){ //$month,$day,$year,$cat,$scope,$desc,$loc
	global $userData,$msgs;

	if(!$userData['loggedIn'])
		return;


	$timeadded=time();
	$timeoccur=gmmktime(0,0,0,$data['month'],$data['day'],$data['year']); //noon, gmtime

	$moded='y';
	if($data['scope']=='global')
		$moded='n';

	$query = "INSERT INTO schedule SET author='$userData[username]',authorid='$userData[userid]',timeadded='$timeadded',timeoccur='$timeoccur',location='$data[loc]',title='$data[title]',category='$data[cat]',description='$data[desc]',scope='$data[scope]',moded='$moded'";
	mysql_query($query);

	$ID = mysql_insert_id();
	if($data['scope']=='global')
		newModItem("schedule",$ID);
}

function showCalDay($month,$day,$year,$calsort){
	global $days,$userData,$locbranch,$catbranch,$locsubbranch,$catsubbranch,$isAdmin;

	if(!$userData['loggedIn'])
		$calsort['scope'] = "global";


	$timebegin = gmmktime(0,0,0,$month,$day,$year);
	$timeend = gmmktime(23,59,59,$month,$day,$year);

	$query = "SELECT * FROM schedule WHERE ";

	$where[] = "timeoccur >= '$timebegin'";
	$where[] = "timeoccur <= '$timeend'";
	$where[] = "moded = 'y'";

	if($calsort['scope']=='private')
		$where[] = "(scope = 'private' || scope = 'public')";
	else
		$where[] = "scope = '$calsort[scope]'";

	if($calsort['loc'] != 0){
		global $locbranch;
		$locs=array();
		$locs[] = "location = '$calsort[loc]'";
		foreach($locbranch as $loc)
			$locs[] = "location = '$loc[id]'";
		$where[] = "(" . implode(" || ", $locs) . ")";
	}

	if($calsort['category'] != 0){
		global $catbranch;
		$cats=array();
		$cats[] = "category = '$calsort[category]'";
		foreach($catbranch as $cat)
			$cats[] = "category = '$cat[id]'";
		$where[] = "(" . implode(" || ", $cats) . ")";
	}



	$query .= implode(" && ",$where);
	$query .= " ORDER BY timeoccur ASC";
	$result = mysql_query($query);

	$numDays = date("t",gmmktime(1,1,1,$month,1,$year));

	$prevtime = gmmktime(0,0,0,$month,$day-1,$year);
	$prevmonth=gmdate('n',$prevtime);
	$prevyear=gmdate('Y',$prevtime);
	$prevday=gmdate('j',$prevtime);


	$nexttime = gmmktime(0,0,0,$month,$day+1,$year);
	$nextmonth=gmdate('n',$nexttime);
	$nextyear=gmdate('Y',$nexttime);
	$nextday=gmdate('j',$nexttime);

	incHeader();


	echo "<table width=100%>";
	if($isAdmin){
		echo "<form action=$_SERVER[PHP_SELF] method=get>";
		echo "<input type=hidden name=action value=showday>";
		echo "<input type=hidden name=month value=$month>";
		echo "<input type=hidden name=day value=$day>";
		echo "<input type=hidden name=year value=$year>";
		echo "<input type=hidden name=action value=showday>";
		foreach($calsort as $n => $v)
			echo "<input type=hidden name=calsort[$n] value=$v>";
	}
	echo "<tr><td class=header colspan=3>";
	echo "<table width=100%><tr>";
	echo "<td class=header align=left><a class=header href=$_SERVER[PHP_SELF]?action=showday&month=$prevmonth&year=$prevyear&day=$prevday&calsort[scope]=$calsort[scope]&calsort[uid]=$calsort[uid]&calsort[loc]=$calsort[loc]&calsort[category]=$calsort[category]>Previous</a></td>";
	echo "<td class=header align=center><b>" . gmdate("F j, Y",$timebegin) . "</b></td>";
	echo "<td class=header align=right><a class=header href=$_SERVER[PHP_SELF]?action=showday&month=$nextmonth&year=$nextyear&day=$nextday&calsort[scope]=$calsort[scope]&calsort[uid]=$calsort[uid]&calsort[loc]=$calsort[loc]&calsort[category]=$calsort[category]>Next</a></td>";
	echo "</tr></table>";
	echo "</td></tr>";

	while($line = mysql_fetch_assoc($result)){
		$loc = getCatName($line['location'],"locs");
		$cat = getCatName($line['category'],"schedulecats");
		echo "<tr><td class=header width=34%>";
		if($isAdmin)
			echo "<input type=checkbox name=check[] value=$line[id]>";
		echo "$line[title]</td><td class=header width=33%>Location: $loc</td><td class=header width=33%>Category: $cat</td></tr>";
		echo "<tr><td class=body colspan=3>$line[description]</td></tr>";
	}
	echo "<tr><td class=header colspan=3>";
	if($isAdmin)
		echo "<input class=body type=submit name=adminaction value=Delete> ";
	echo "<a class=header href=$_SERVER[PHP_SELF]?month=$month&year=$year&calsort[scope]=$calsort[scope]&calsort[uid]=$calsort[uid]&calsort[loc]=$calsort[loc]&calsort[category]=$calsort[category]>Back</a></td></tr>";
	if($isAdmin)
		echo "</form>";
	echo "</table>";

	incFooter();

	exit;
}


function showCalMonth($month,$year,$calsort){
	global $days,$userData,$locbranch,$catdata,$catbranch,$locsubbranch,$catsubbranch;


	$dayOfFirst = gmdate("w",gmmktime(1,1,1,$month,1,$year));
	$numDays = gmdate("t",gmmktime(1,1,1,$month,1,$year));

	$nextmonth = ($month)%12+1;
	$prevmonth = ($month+10)%12+1;
	$nextyear = $year;
	$prevyear = $year;
	if($nextmonth<$month)	$nextyear++;
	if($prevmonth>$month)	$prevyear--;

	$monthbegin = gmmktime(0,0,0,$month,1,$year);
	$monthend = gmmktime(23,23,59,$month,$numDays,$year);

	if(!$userData['loggedIn'])
		$calsort['scope'] = "global";


	$query = "SELECT * FROM schedule WHERE ";

	$where = array();

	$where[] = "timeoccur >= '$monthbegin'";
	$where[] = "timeoccur <= '$monthend'";
	$where[] = "moded = 'y'";

	if($calsort['scope']=='private')
		$where[] = "(scope = 'private' || scope = 'public')";
	else
		$where[] = "scope = '$calsort[scope]'";

	if($calsort['scope']=='public')
		$where[] = "authorid = '$calsort[uid]'";
	if($calsort['scope']=='private')
		$where[] = "authorid = '$userData[userid]'";


	if($calsort['loc'] != 0){
		global $locbranch;
		$locs=array();
		$locs[] = "location = '$calsort[loc]'";
		foreach($locsubbranch as $loc)
			$locs[] = "location = '$loc[id]'";
		$where[] = "(" . implode(" || ", $locs) . ")";
	}

	if($calsort['category'] != 0){
		global $catbranch;
		$cats=array();
		$cats[] = "category = '$calsort[category]'";
		foreach($catsubbranch as $cat)
			$cats[] = "category = '$cat[id]'";
		$where[] = "(" . implode(" || ", $cats) . ")";
	}

	$query .= implode(" && ",$where);
	$query .= " ORDER BY timeoccur ASC";
	$result = mysql_query($query);

/*
	echo "<table><tr><td bgcolor=white>";
	echo "<pre>";
	print_r($calsort);
	echo "</pre>";
	echo "$query<br>\n";
	echo mysql_error();
	echo "<br>\nrows:";
	echo mysql_num_rows($result);
	echo "</td></tr></table>";
//*/

	$data = array();
	while($line = mysql_fetch_assoc($result))
		$data[] = $line;


//must use this mysql method, cause bdays don't depend on year
	$showbdays=false;
	if($calsort['category']==0 || (isset($catdata[0][$calsort['category']]) && $catdata[0][$calsort['category']]=="Birthdays")){
		if($calsort['scope']=='global')
			$query = "SELECT userid,username,dob FROM users WHERE MONTH(FROM_UNIXTIME(dob + UNIX_TIMESTAMP('1970-01-01 00:00:00'))) = '$month' && showbday='y'";
		else{
			if($calsort['scope']=='public')		$uid = $calsort['uid'];
			if($calsort['scope']=='private')	$uid = $userData['userid'];

			$query = "SELECT users.userid,username,dob FROM users,friends WHERE MONTH(FROM_UNIXTIME(dob + UNIX_TIMESTAMP('1970-01-01 00:00:00'))) = '$month' && showbday='y' && friends.userid='$uid' && friends.friendid=users.userid";
		}

		$result = mysql_query($query);

		if(!$result)
			trigger_error ("mysql error: " . mysql_error(), E_USER_NOTICE);


		$bdays=array();
		while($line = mysql_fetch_assoc($result))
			$bdays[gmdate('j',$line['dob'])][] = $line;

		$showbdays=true;
	}


	incHeader(false);
	echo "\n\n\n\n\n\n\n<!-start>\n\n";


	echo "<table cellspacing=1 width=100% cellpadding=2>";

	echo "<form action=$_SERVER[PHP_SELF] method=get>";
	echo "<tr><td class=header colspan=7 align=center>";

	$scope = array("global" => "Global");
	if($userData['loggedIn']){
		$scope["public"] = "Public";
		$scope["private"] = "Private";
	}

	if($calsort['scope']=='public')
		echo getUsername($calsort['uid']) . "'s Schedule ";

	echo "<select name=calsort[scope] class=body>" . make_select_list_key($scope,$calsort['scope']) . "</select>";
	echo "<select name=calsort[category] class=body><option value=0>Category" . makeCatSelect($catbranch,$calsort['category']) . "</select>";
	echo "<select name=calsort[loc] class=body><option value=0>Location" . makeCatSelect($locbranch,$calsort['loc']) . "</select>";

	echo "<input type=submit class=body value=Go>";

	echo "</td></tr>";
	echo "</form>";

	echo "<tr><td class=header colspan=7 align=center>";
		echo "<table width=100%><tr>";

		echo "<td class=header align=left width=33%><a class=header href=$_SERVER[PHP_SELF]?year=$prevyear&month=$prevmonth&calsort[scope]=$calsort[scope]&calsort[uid]=$calsort[uid]&calsort[loc]=$calsort[loc]&calsort[category]=$calsort[category]>Previous</a></td>";
		echo "<td class=header align=center width=33%><b>" . gmdate("F",gmmktime(1,1,1,$month,1,$year)). " $year</b></td>";
		echo "<td class=header align=right width=33%><a class=header href=$_SERVER[PHP_SELF]?year=$nextyear&month=$nextmonth&calsort[scope]=$calsort[scope]&calsort[uid]=$calsort[uid]&calsort[loc]=$calsort[loc]&calsort[category]=$calsort[category]>Next</a></td>";

		echo "</tr></table>";
	echo "</td></tr>\n";

	echo "<tr>";
	foreach($days as $day)
		echo "<td class=header width=14.3%>$day</td>";
	echo "</tr>\n";

	$curDay=-$dayOfFirst;
	$curItem=0;
	while(1){
		$curDay++;

		if(($dayOfFirst+$curDay)%7==1)
			echo "<tr height=70>";

		echo "<td class=body valign=top align=left>";

		if($curDay<=0 || $curDay>$numDays){
			echo "&nbsp;";
		}else{
			echo "<a class=body href=$_SERVER[PHP_SELF]?action=showday&month=$month&year=$year&day=$curDay&calsort[scope]=$calsort[scope]&calsort[uid]=$calsort[uid]&calsort[loc]=$calsort[loc]&calsort[category]=$calsort[category]>$curDay</a>";
			if($month==userdate('n') && $year==userdate('Y') && $curDay==userdate('j'))
				echo " - Today";
			echo "<br>";

			while(1){
				if(isset($data[$curItem]) && gmdate('j',$data[$curItem]['timeoccur'])==$curDay){
					echo $data[$curItem]['title'] . "<br>";
					$curItem++;
				}else{
					break;
				}
			}


			if($showbdays){ // either always true, or always false
				if(isset($bdays[$curDay]))
					foreach($bdays[$curDay] as $bday)
						echo "<img src=/images/birthday.png valign=baseline> <a class=body href=profile.php?uid=" . $bday['userid'] . ">" . $bday['username'] . "</a><br>";
			}
		}
		echo "</td>";

		if(($dayOfFirst+$curDay)%7==0){
			echo "</tr>";
			if($curDay>=$numDays)
				break;
		}
	}

	if($userData['loggedIn'])
		echo "<tr><td class=header colspan=7><a class=header href=$_SERVER[PHP_SELF]?action=add>Add New Item</a></td></tr>";

	echo "</table>";

	echo "<!-end>\n\n\n\n\n\n\n";
	incFooter();
}
