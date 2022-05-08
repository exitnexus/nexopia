<?

	$login=1;

	require_once("include/general.lib.php");

	$bannerAdmin = $mods->isAdmin($userData['userid'],"listbanners");


	$fastdb->prepare_query("SELECT id FROM bannerclients WHERE userid = ?" , $userData['userid']);

	if($fastdb->numrows()==0){
		if(!$bannerAdmin)
			die("You are not a banner client");
		$clientid=0;
	}else
		$clientid = $fastdb->fetchfield();


	switch($action){
		case "addbanner":			addBanner();				break;		//exits
		case "insertbanner":		insertBanner($data);		break;
		case "editbanner":			editBanner($id);			break;		//exits
		case "updatebanner":		updateBanner($id,$data);	break;
		case "deletebanner":		deleteBanner($id);			break;
		case "addclient":			addClient();				break;		//exits
		case "insertclient":		insertClient($data);		break;
		case "editclient":			editClient($id);			break;		//exits
		case "updateclient":		updateClient($id,$data);	break;
		case "deleteclient":		deleteClient($id);			break;
	}
	display();	//exit



function addBanner(){
	global $userData,$bannerAdmin,$clientid,$msgs,$PHP_SELF, $db, $fastdb;

	incHeader();

	echo "<table><form action=\"$PHP_SELF\" method=post enctype='multipart/form-data'>\n";

	if($bannerAdmin){

		$query = "SELECT id,clientname FROM bannerclients";
		$fastdb->query($query);

		while($line = $fastdb->fetchrow())
			$clients[$line['id']]=$line['clientname'];

		echo "<tr><td class=body>Client:</td><td class=body><select class=body name=data[clientid]>";
		echo "<option value=0>Client";
		echo make_select_list_key($clients);
		echo "</select></td></tr>";
	}

	echo "<tr><td class=body valign=top>Banner Type:</td><td class=body>";
	echo "<select class=body name=data[bannertype]><option value=image>Image<option value=text>Text<option value=flash>Flash<option value=iframe>Webpage</select> ";
	echo "If you select webpage, make sure to provide a link to the page, not an upload";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Banner Size:</td><td class=body>";
	echo "<select class=body name=data[bannersize]><option value=468x60>468x60<option value=text>Text<option value=120x60>120x60<option value=120x240>120x240</select> ";
//	echo "If you select webpage, make sure to provide a link to the page, not an upload";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Banner:</td><td class=body>";
	echo "<input type=radio name=data[imagetype] value=link><input class=body type=text name=data[image] value=\"http://\" size=40 maxlength=128> ";
	echo "For text ad, put the title here<br>";
	echo "<input type=radio name=data[imagetype] value=file checked><input class=body type=file name=imagefile size=30>";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Link:</td><td class=body>";
	echo "<input class=body type=text name=data[link] size=40 maxlength=128> Only applies to banners of type Image and Text";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Alt:</td><td class=body>";
	echo "<input class=body type=text name=data[alt] size=40 maxlength=255> Only applies to banners of type Image and Text. For Text, put the description here";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Status:</td><td class=body>";
	echo "<input class=body type=text name=data[status] size=40 maxlength=64> Only applies to banners of type Image and Text";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Max Views</td><td class=body>";
	echo "<input type=radio name=data[views] value=unlimited checked> Unlimited<br>";
	echo "<input type=radio name=data[views] value=limited><input class=body type=text name=data[maxviews] size=5>";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Weight:</td><td class=body>";
	echo "<input class=body type=text name=data[weight] value=\"1\" size=3 maxlength=1> ";
	echo "Setting a weight >1 is equivalent to having the same banner up that many times.";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>Start Date</td><td class=body>";
	echo "<input type=radio name=data[start] value=now checked>Now<br>";
	echo "<input type=radio name=data[start] value=later>";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list(range(1,12)) . "</select>";
		echo "<select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31)) . "</select>";
		echo "<select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1)) . "</select>";
	echo "</td></tr>\n";

	echo "<tr><td class=body valign=top>End Date</td><td class=body>";
	echo "<input type=radio name=data[end] value=never checked>Never<br>";
	echo "<input type=radio name=data[end] value=later>";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list(range(1,12)) . "</select>";
		echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31)) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1)) . "</select>";
	echo "</td></tr>\n";

	$sex = array("unknown"=>"Either", "male" => "Male", "female" => "Female");
	echo "<tr><td class=body>Sex:</td><td class=body><select class=body name=data[sex]>" . make_select_list_key($sex) . "</select></td></tr>";

	echo "<tr><td class=body>Age:</td><td class=body>Between <input class=body type=text name=data[minage] value=0 size=3> and <input class=body type=text name=data[maxage] value=0 size=3>. Leave as 0,0 for all users</td></tr>";

	echo "<tr><td class=body></td><td class=body><input type=hidden name=action value=insertbanner><input class=body type=submit value=Add><input class=body type=submit name=action value=Cancel></td></tr>\n";

	echo "</form></table>\n";


	incFooter(array('incAdminBlock'));
	exit;


}

function insertBanner($data){
	global $userData,$bannerAdmin,$clientid,$msgs,$config,$masterserver,$imagefile,$imagefile_tmp_name,$imagefile_name, $db, $fastdb, $mods;

	if($bannerAdmin)		$bannerclientid=$data['clientid'];
	else					$bannerclientid=$clientid;

	if($bannerclientid == 0)
		die("bad client");


	if($data['views']=='unlimited' || !isset($data['maxviews']) || $data['maxviews']<=0)
		$maxviews = 0;
	else
		$maxviews = $data['maxviews'];


	switch($data['bannertype']){
		case "image":
			if($data['imagesize']=='text')
				die("webpages can't be of size text");
			break;
		case "text":
			$data['imagesize'] = 'text';
			if($data['imagetype']!='link')
				die("can't upload a file and choose banner type text");
			break;
		case "iframe":
			if($data['imagetype']!='link')
				die("can't upload a file and choose banner type webpage");
			if($data['imagesize']=='text')
				die("webpages can't be of size text");
			break;
		case "flash":
			if($data['imagesize']=='text')
				die("flash ads can't be of size text");
			break;
	}


	if($data['imagetype']=='link'){
		$image= $data['image'];
	}else{
		if(!is_uploaded_file($imagefile)) {
			$msgs->addMsg("You must upload a file");
			return false;
		}
		$image = $imagefile_name;
	}



	$maxweight = remainingWeight($bannerclientid); //returns max of 9

	if($maxweight <= 0){
		$msgs->addMsg("You have no more banner weight to put towards this banner. Either delete another banner, or reduce another banner's weight");
		return;
	}

	if($data['weight']<1)
		$weight= 1;
	elseif($data['weight']>$maxweight)
		$weight = $maxweight;
	else
		$weight= $data['weight'];

	if($data['start']=='now')
		$startdate = 0;
	else
		$startdate = gmmktime(0,0,1,$data['startmonth'],$data['startday'],$data['startyear']);

	if($data['end']=='never')
		$enddate = 0;
	else
		$enddate = gmmktime(0,0,1,$data['endmonth'],$data['endday'],$data['endyear']);

	$sex = array("unknown"=>"Either", "male" => "Male", "female" => "Female");
	if(!in_array($data['sex'],array_keys($sex)))
		$data['sex']='unknown';

	if(!is_numeric($data['minage']))
		$data['minage']=0;
	if(!is_numeric($data['maxage']))
		$data['maxage']=0;

	if($data['minage'] > $data['maxage']){
		$minage = $data['maxage'];
		$data['maxage'] = $data['minage'];
		$data['minage'] = $minage;
	}


	$fastdb->prepare_query("INSERT INTO banners SET clientid = ?, minage = ?, maxage = ?, sex = ?, moded = ?, link = ?, alt = ?, status = ?, weight = ?, startdate = ?, enddate = ?, maxviews = ?, bannertype = ?, bannersize = ?, image = ?",
			$bannerclientid, $data['minage'], $data['maxage'], $data['sex'], 'n', $data['link'], $data['alt'], $data['status'], $weight, $startdate, $enddate, $maxviews, $data['bannertype'], $data['bannersize'], $image);

	$itemid = $fastdb->insertid();

	if($data['imagetype']=='file'){
		$filename = $itemid . substr($imagefile_name,-4);
		move_uploaded_file($imagefile, $masterserver . $config['bannerdir'] . $filename);
		chmod($masterserver . $config['bannerdir'] . $filename,0644);
		$fastdb->prepare_query("UPDATE banners SET image = ? WHERE id = ?", $filename, $itemid);
	}

	$mods->newItem(MOD_BANNER,$itemid);

	$msgs->addMsg("Banner Added");
}

function editBanner($id){
	global $userData,$bannerAdmin,$clientid,$msgs,$PHP_SELF, $db, $fastdb;

	incHeader();

	$query = $fastdb->prepare("SELECT * FROM banners WHERE id = ?", $id);
	if(!$bannerAdmin)
		$query .= $fastdb->prepare(" && clientid = ?", $clientid);
	$fastdb->query($query);
	if($fastdb->numrows()==0){
		$msgs->addMsg("Banner does not exist, or you don't have permission to edit it");
		return;
	}

	$line  = $fastdb->fetchrow();

	echo "<table><form action=\"$PHP_SELF\" method=post>\n";
	echo "<tr><td colspan=2>" . banner($id) . "</td></tr>\n";
	echo "<tr><td class=body>Views:</td><td class=body>" . number_format($line['views']) . "</td></tr>\n";

	echo "<tr><td class=body>Clicks:</td><td class=body>$line[clicks]</td></tr>\n";
	echo "<tr><td class=body>Banner Type:</td><td class=body>";
	switch($line['bannertype']){
		case "image": 	echo "Image";		break;
		case "flash": 	echo "Flash";		break;
		case "iframe":	echo "Webpage";		break;
		case "text":	echo "Text";		break;
	}
	echo "</td></tr>\n";
	echo "<tr><td class=body>Banner Size:</td><td class=body>$line[bannersize]</td></tr>\n";

	echo "<tr><td class=body>Banner:</td><td class=body>";
	if($line['bannertype']=='image' && substr($line['image'],0,7)!='http://'){
		echo "$line[image]";
	}else{
		echo "<input type=text name=data[image] value=\"$line[image]\" size=40 maxlength=255> title in text ads";
	}
	echo "</td></tr>\n";
	echo "<tr><td class=body>Link:</td><td class=body><input type=text name=data[link] value=\"$line[link]\" size=40 maxlength=128></td></tr>\n";
	echo "<tr><td class=body>Alt:</td><td class=body><input type=text name=data[alt] value=\"$line[alt]\" size=40 maxlength=255> description in text ads</td></tr>\n";
	echo "<tr><td class=body>Status:</td><td class=body><input type=text name=data[status] value=\"$line[status]\" size=40 maxlength=64></td></tr>\n";

	echo "<tr><td class=body valign=top>Max Views</td><td class=body>";
		echo "<input type=radio name=data[views] value=unlimited" . ($line['maxviews']==0 ? " checked" : "") . "> Unlimited<br>";
		echo "<input type=radio name=data[views] value=limited" . ($line['maxviews']>0 ? " checked" : "") . "><input type=text value=\"$line[maxviews]\" name=data[maxviews] size=5>";
	echo "</td></tr>\n";

	echo "<tr><td class=body>Weight:</td><td class=body><input type=text name=data[weight] value=\"$line[weight]\" size=3 maxlength=1></td></tr>\n";
	echo "<tr><td class=body>Verified</td><td class=body>";
	if($line['moded']=='y')
		echo "Allowed";
	else
		echo "Soon";
	echo "</td></tr>\n";


	echo "<tr><td class=body valign=top>Start Date</td><td class=body>";
	if($line['startdate']<time()){
		echo "<input type=radio name=data[start] value=now checked>Started<br><input type=radio name=data[start] value=later>";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list(range(1,12)) . "</select><select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31)) . "</select><select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1)) . "</select>";
	}else{
		echo "<input type=radio name=data[start] value=now>Start<br><input type=radio name=data[start] value=later checked>";
		echo "<select class=body name=data[startmonth]><option value=0>Month" . make_select_list(range(1,12),userdate("m",$line['startdate'])) . "</select><select class=body name=data[startday]><option value=0>Day" . make_select_list(range(1,31),userdate("j",$line['startdate'])) . "</select><select class=body name=data[startyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1),userdate("Y",$line['startdate'])) . "</select>";
	}
	echo "</td></tr>\n";


	echo "<tr><td class=body valign=top>End Date</td><td class=body>";
	if($line['enddate']<time()){
		echo "<input type=radio name=data[end] value=never checked>Never<br><input type=radio name=data[end] value=later>";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list(range(1,12)) . "</select>";
		echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31)) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1)) . "</select>";
	}else{
		echo "<input type=radio name=data[end] value=never>Never<br><input type=radio name=data[end] value=later checked>";
		echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list(range(1,12),userdate("m",$line['enddate'])) . "</select>";
		echo "<select class=body name=data[endday]\"><option value=0>Day" . make_select_list(range(1,31),userdate("j",$line['enddate'])) . "</select>";
		echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1),userdate("Y",$line['enddate'])) . "</select>";
	}
	echo "</td></tr>\n";


	$sex = array("unknown"=>"Either", "male" => "Male", "female" => "Female");
	echo "<tr><td class=body>Sex:</td><td class=body><select class=body name=data[sex]>" . make_select_list_key($sex,$line['sex']) . "</select></td></tr>";

	echo "<tr><td class=body>Age:</td><td class=body>Between <input class=body type=text name=data[minage] value='$line[minage]' size=3> and <input class=body type=text name=data[maxage] value='$line[maxage]' size=3>. Leave as 0,0 for all users</td></tr>";


	echo "<tr><td class=body></td><td class=body><input type=hidden name=action value=updatebanner><input type=hidden name=id value=$line[id]>";
	echo "<input class=body type=submit value=Update><input class=body type=submit name=action value=Cancel></td></tr>\n";


	echo "</form></table>\n";

	incFooter(array('incAdminBlock'));
	exit;
}


function updateBanner($id,$data){
	global $userData,$bannerAdmin,$clientid,$msgs, $db, $fastdb;

	$fastdb->prepare_query("SELECT clientid,weight,image, bannertype FROM banners WHERE id = ?", $id);
	$line = $db->fetchrow();

	if(!$bannerAdmin && $line['clientid'] != $clientid)
		return;

	$maxweight = remainingWeight($line['clientid'],$line['weight']);

	if($data['views']=='limited' && $data['maxviews']>=1)
		$maxviews = $data['maxviews'];
	else
		$maxviews = 0;

	if($line['bannertype']=='image' && substr($line['image'],0,7)!='http://')

	$link = $data['link'];
	$alt = $data['alt'];
	$status = $data['status'];

	if($data['weight']<1)
		$weight = 1;
	elseif($data['weight']>$maxweight)
		$weight = $maxweight;
	else
		$weight = $data['weight'];

	if($data['start']=='now')
		$startdate = 0;
	else
		$startdate = gmmktime(0,0,1,$data['startmonth'],$data['startday'],$data['startyear']);

	if($data['end']=='never')
		$enddate = 0;
	else
		$enddate = gmmktime(0,0,1,$data['endmonth'],$data['endday'],$data['endyear']);

	$sex = array("unknown"=>"Either", "male" => "Male", "female" => "Female");
	if(!in_array($data['sex'],array_keys($sex)))
		$data['sex']='unknown';

	$commands[] = "sex='$data[sex]'";

	if(!is_numeric($data['minage']))
		$data['minage']=0;
	if(!is_numeric($data['maxage']))
		$data['maxage']=0;

	if($data['minage'] > $data['maxage']){
		$minage = $data['maxage'];
		$data['maxage'] = $data['minage'];
		$data['minage'] = $minage;
	}

	$minage = $data['minage'];
	$maxage = $data['maxage'];

	$query = "UPDATE banners SET " . implode(', ',$commands) . " WHERE id='$id'";
	$fastdb->query($query);

	$fastdb->prepare_query("UPDATE banners SET minage = ?, maxage = ?, sex = ?, moded = ?, link = ?, alt = ?, status = ?, weight = ?, startdate = ?, enddate = ?, maxviews = ?, bannertype = ?, bannersize = ?, image = ?",
			$data['minage'], $data['maxage'], $data['sex'], 'n', $data['link'], $data['alt'], $data['status'], $weight, $startdate, $enddate, $maxviews, $data['bannertype'], $data['bannersize'], $image);

	$msgs->addMsg("Updated");
}

function deleteBanner($id){
	global $userData,$bannerAdmin,$clientid,$msgs,$masterserver,$config, $db, $fastdb, $mods;

	$query = $fastdb->prepare("SELECT image FROM banners WHERE id = ?", $id);
	if(!$bannerAdmin)
		$query .= $fastdb->prepare(" && clientid = ?", $clientid);
	$fastdb->query($query);
	if($fastdb->numrows()==0){
		$msgs->addMsg("Banner does not exist, or you don't have permission to delete it");
		return;
	}
	$data  = $fastdb->fetchfield();

	if(substr($data,0,7)!="http://")
		unlink ($masterserver . $config['bannerdir'] . $data);

	$query = $fastdb->prepare("DELETE FROM banners WHERE id = ?", $id);
	$fastdb->query($query);

	$mods->deleteItem("banners",$id);

	$msgs->addMsg("Banner deleted");
}

function addClient($userid=0){
	global $bannerAdmin,$PHP_SELF;

	if(!$bannerAdmin)
		return;

	if($userid!=0)
		$username=getUserName($userid);
	else
		$username="";

	incHeader();

	echo "<table><form action=$PHP_SELF method=get>";
	echo "<tr><td class=header colspan=2 align=center>Add New Banner Client</td></tr>";

	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=data[username] value=\"$username\" size=12></td></tr>";
	echo "<tr><td class=body>Client Name:</td><td class=body><input class=body type=text name=data[clientname] value=$username></td></tr>";
	echo "<tr><td class=body valign=top>Limit:</td><td class=body>";
		echo "<input type=radio name=data[limit] value=views>By Views: <input class=body type=text name=data[views] value=1000000 size=5><br>";
		echo "<input type=radio name=data[limit] value=time>By Date: ";
			echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list(range(1,12)) . "</select>";
			echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31)) . "</select>";
			echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1)) . "</select><br>";
		echo "<input type=radio name=data[limit] value=none checked>Unlimited";
	echo "</td></tr>";

	echo "<tr><td class=body valign=top>Maximum Banner Weight:</td><td class=body>";
		echo "<input type=radio name=data[maxweight] value=unlimited checked> Unlimited<br>";
		echo "<input type=radio name=data[maxweight] value=limited> <input class=body type=text name=data[maxweightamount] value=1 size=5>";
	echo "</td></tr>";
	echo "<tr><td class=body>Admin Comments:</td><td class=body><input type=text name=data[notes] size=30></td></tr>";

	echo "<input type=hidden name=action value=insertclient>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Add><input class=body type=submit name=action value=Cancel></td></tr>";
	echo "</table></form>";

	incFooter(array('incAdminBlock'));
	exit;
}

function insertClient($data){
	global $bannerAdmin,$msgs, $db, $fastdb;

	if(!$bannerAdmin)
		return;

	if($data['maxweight']=='unlimited')
		$data['maxweightamount']=0;


	if($data['limit']=='views')
		$credits = $data['views'];
	elseif($data['limit']=='time')
		$credits = gmmktime(0,0,1,$data['endmonth'],$data['endday'],$data['endyear']);
	else
		$credits = 0;


	$fastdb->prepare_query("INSERT INTO bannerclients SET userid = ?, clientname = ?, `limit` = ?, credits = ?, notes = ?, maxweight = ?",
						getUserID($data['username']), $data['clientname'], $data['limit'], $credits, $data['notes'],$data['maxweightamount']);

	$msgs->addMsg("Client Added");
}

function editClient($id){
	global $bannerAdmin,$PHP_SELF,$db, $fastdb;

	if(!$bannerAdmin)
		return;

	$fastdb->prepare_query("SELECT * FROM bannerclients WHERE id = ?", $id);
	$data = $fastdb->fetchrow();

	incHeader();

	echo "<table><form action=$PHP_SELF method=post>";


	echo "<tr><td class=header colspan=2 align=center>Edit Banner Client</td></tr>";
	echo "<tr><td class=body>Username:</td><td class=body><input class=body type=text name=data[username] value=\"" . getUserName($data['userid']) . "\" size=12></td></tr>";
	echo "<tr><td class=body>Client Name:</td><td class=body><input class=body type=text name=data[clientname] value=$data[clientname]></td></tr>";
	echo "<tr><td class=body valign=top>Limit:</td><td class=body>";
		echo "<input type=radio name=data[limit] value=views" . ($data['limit']=='views' ? ' checked' : '') . ">By Views: <input class=body type=text name=data[views] value='" . ($data['limit']=='views' ? $data['credits'] : '0') . "' size=5><br>";
		echo "<input type=radio name=data[limit] value=time" . ($data['limit']=='time' ? ' checked' : '') . ">By Date: ";
			echo "<select class=body name=data[endmonth]><option value=0>Month" . make_select_list(range(1,12),($data['limit']=='time' ? gmdate("n",$data['credits']) : 0)) . "</select>";
			echo "<select class=body name=data[endday]><option value=0>Day" . make_select_list(range(1,31),($data['limit']=='time' ? gmdate("j",$data['credits']) : 0)) . "</select>";
			echo "<select class=body name=data[endyear]><option value=0>Year" . make_select_list(range(userdate("Y"),userdate("Y")+1),($data['limit']=='time' ? gmdate("Y",$data['credits']) : 0)) . "</select><br>";
		echo "<input type=radio name=data[limit] value=none" . ($data['limit']=='none' ? ' checked' : '') . ">Unlimited";
	echo "</td></tr>";
	echo "<tr><td class=body valign=top>Maximum Banner Weight:</td><td class=body>";
		echo "<input type=radio name=data[maxweight] value=unlimited" . ($data['maxweight']==0 ? " checked" : "") . "> Unlimited<br>";
		echo "<input type=radio name=data[maxweight] value=limited" . ($data['maxweight']==0 ? "" : " checked") . "> <input class=body type=text name=data[maxweightamount] value=" . ($data['maxweight']==0 ? "1" : "$data[maxweight]") . " size=5>";
	echo "</td></tr>";
	echo "<tr><td class=body>Comments:</td><td class=body><input type=text name=data[notes] size=30 value=\"$data[notes]\"></td></tr>";


	echo "<input type=hidden name=action value=updateclient>";
	echo "<input type=hidden name=id value=$data[id]>";
	echo "<tr><td class=body></td><td class=body><input class=body type=submit value=Update><input class=body type=submit name=action value=Cancel></td></tr>";
	echo "</table></form>";

	incFooter(array('incAdminBlock'));
	exit;
}

function updateClient($id,$data){
	global $bannerAdmin,$msgs,$db,$fastdb;

	if(!$bannerAdmin)
		return;

	if($data['maxweight']=='unlimited')
		$data['maxweightamount']=0;

	if($data['limit']=='views')
		$credits = $data['views'];
	elseif($data['limit']=='time')
		$credits = gmmktime(0,0,1,$data['endmonth'],$data['endday'],$data['endyear']);
	else
		$credits = 0;

	$fastdb->prepare_query("UPDATE bannerclients SET userid = ?, clientname = ?, `limit` = ?, credits = ?, notes = ?, maxweight = ? WHERE id = ?",
					getUserID($data['username']), $data['clientname'], $data['limit'], $credits, $data['notes'], $data['maxweightamount'], $id);

	$msgs->addMsg("Update Complete");
}

function deleteClient($id){
	global $bannerAdmin,$msgs,$db, $fastdb;

	if(!$bannerAdmin)
		return;


	$query = $fastdb->prepare("SELECT id FROM banners WHERE clientid = ?", $id);
	$result = $fastdb->query($query);

	while($line = $fastdb->fetchrow($result))
		deleteBanner($line['id']);

	$query = $fastdb->prepare("DELETE FROM bannerclients WHERE id = ?", $id);
	$fastdb->query($query);

	$msgs->addMsg("Client Deleted");
}




function display(){
	global $userData,$bannerAdmin,$clientid,$PHP_SELF,$config,$db, $fastdb;

	incHeader();

	$sex = array("unknown"=>"Either", "male" => "Male", "female" => "Female");

	$query = "SELECT * FROM bannerclients";

	if(!$bannerAdmin)
		$query .= $fastdb->prepare(" WHERE id = ?", $clientid);

	$query .= " ORDER BY clientname";
	$result = $fastdb->query($query);


	echo "<center><b>Client Information</b></center>";
	echo "<table width=100%>";
	echo "<tr><td class=header>Name:</td>";
	echo "<td class=header>Limit by:</td>";
	echo "<td class=header>Limit:</td>";
	echo "<td class=header>Maximum Banner Weight:</td>";
	if($bannerAdmin){
		echo "<td class=header>Notes</td>";
		echo "<td class=header>Funcs</td></tr>";
	}

	while($line = $fastdb->fetchrow($result)){
		echo "<tr><td class=body>$line[clientname]</td>";

		if($line['limit']=='views')
			echo "<td class=body>Views</td><td class=body>" . number_format($line['credits']) . "</td>";
		elseif($line['limit']=='time')
			echo "<td class=body>Date</td><td class=body>" . gmdate("M j, Y",$line['credits']) . "</td>";
		else
			echo "<td class=body>Unlimited</td><td class=body>" . number_format($line['credits']) . "</td>";

		echo "<td class=body>" . ($line['maxweight']==0 ? "Unlimited" : "$line[maxweight]" ) . "</td>";

		if($bannerAdmin){
			echo "<td class=body>$line[notes]</td>";
			echo "<td class=body>";
				echo "<a href=$PHP_SELF?action=editclient&id=$line[id]><img src=/images/edit.gif border=0 alt=Edit></a>";
				echo "<a href=\"javascript:confirmLink('$PHP_SELF?action=deleteclient&id=$line[id]','delete this client');\"><img src=/images/delete.gif border=0 alt=Delete></a>";
			echo "</td>";
		}
		echo "</tr>";
	}


	if($bannerAdmin)
		echo "<tr><td class=header colspan=6 align=right><a class=header href=\"$PHP_SELF?action=addclient\">Add New Client</a></td></tr>";

	echo "</table>";



	$query = "SELECT banners.*,clientname FROM banners,bannerclients WHERE clientid=bannerclients.id";
	if(!$bannerAdmin)
		$query .= $fastdb->prepare(" && clientid = ?", $clientid);
	$query .= " ORDER BY clientname";
	$result= $fastdb->query($query);

	$numbanners = $fastdb->numrows();
	$views = 0;
	$weight = 0;
	$clicks = 0;
	echo "<br><br><center><b>Banner Information</b></center>";
	echo "<table width=100%>";
	echo "<tr>";
	if($bannerAdmin)
		echo "<td class=header>Client Name</td>";
	echo "<td class=header>Type</td>";
	echo "<td class=header>Size</td>";
	echo "<td class=header>Max Views</td>";
	echo "<td class=header>Views</td>";
	echo "<td class=header>Clicks</td>";
	echo "<td class=header>Weight</td>";
	echo "<td class=header>Start Date</td>";
	echo "<td class=header>End Date</td>";
	echo "<td class=header>Target</td>";
	echo "<td class=header>Funcs</td>";

	echo"</tr>";
	while($line = $fastdb->fetchrow($result)){
		echo "<tr>";
		if($bannerAdmin)
			echo "<td class=body>$line[clientname]</td>";
		echo "<td class=body>$line[bannertype]</td>";
		echo "<td class=body>$line[bannersize]</td>";

		if($line['maxviews']==0)
			echo "<td class=body>Unlimited</td>";
		else
			echo "<td class=body>" . number_format($line['maxviews']) . "</td>";
		echo "<td class=body>" . number_format($line['views']) . "</td>";

		if($line['link']=='')
			echo "<td class=body>N/A</td>";
		else
			echo "<td class=body>" . number_format($line['clicks']) . "</td>";
		echo "<td class=body>$line[weight]</td>";


		if($line['moded']=='n')
			echo "<td class=body>Soon</td>";
		elseif($line['startdate']<time())
			echo "<td class=body>Started</td>";
		else
			echo "<td class=body>" . userdate("M j, Y",$line['startdate']) . "</td>";

		if($line['enddate']>time())
			echo "<td class=body>" . userdate("M j, Y",$line['enddate']) . "</td>";
		elseif($line['enddate']==0)
			echo "<td class=body>Never</td>";
		else
			echo "<td class=body>Ended</td>";

		echo "<td class=body>";
		if($line['sex']!='unknown')
			echo $sex[$line['sex']] . " ";
		if($line['minage'] > 0 && $line['maxage'] > 0)
			echo "aged: $line[minage] - $line[maxage]";
		elseif($line['minage'] > 0)
			echo "aged: over $line[minage]";
		elseif($line['maxage'] > 0)
			echo "aged: under $line[maxage]";

		echo "</td>";

		echo "<td class=body><a href=$PHP_SELF?action=editbanner&id=$line[id]><img src=/images/edit.gif border=0 alt=Edit></a>";
		echo "<a href=\"javascript:confirmLink('$PHP_SELF?action=deletebanner&id=$line[id]','delete this banner')\"><img src=/images/delete.gif border=0 alt=Delete></a></td>";

		echo "</tr>";
		$views += $line['views'];
		$weight += $line['weight'];
		$clicks += $line['clicks'];
	}

	echo "<tr><td class=header colspan=4>";
	echo "$numbanners ads";
	echo "</td>";
	echo "<td class=header>" . number_format($views) . "</td>";
	echo "<td class=header>" . number_format($clicks) . "</td>";
	echo "<td class=header>$weight</td>";
	echo "<td class=header colspan=4 align=right>";
	if($bannerAdmin || remainingWeight($clientid) > 0)
		echo "<a class=header href=\"$PHP_SELF?action=addbanner\">Add New Banner</a>";
	echo "</td>";
	echo "</tr>";

	echo "</table>";

	if(!$config['enablebanners'])
		echo "Banners are currently disabled";

	incFooter(array('incAdminBlock'));
	exit;
}

function remainingWeight($clientid,$weight=0){
	global $db,$fastdb;
	$query = "SELECT maxweight FROM bannerclients WHERE id='$clientid'";
	$result = $fastdb->query($query);
	$maxweight = $fastdb->fetchfield();

	if($maxweight == 0)
		return 9;

	$query = "SELECT weight FROM banners WHERE clientid='$clientid'";
	$result = $fastdb->query($query);

	$totalweight = 0;

	while($line = $fastdb->fetchrow($result))
		$totalweight+= $line['weight'];

	$remaining = $maxweight - $totalweight + $weight;

	if($remaining >= 9)
		return 9;
	else
		return $remaining;
}





