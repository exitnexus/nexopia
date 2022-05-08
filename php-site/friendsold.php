<?

	$login=1;

	require_once("include/enternexus.lib.php");

	$sortlist = array(  'username' => "username",
						'dob' => "dob",
						'sex' => "sex",
						'loc' => "loc",
						'online' => "((activetime>" . ((time()-($config['friendAwayTime']*60))) ."))",
						'twoway' => "twoway"
						);

	isValidSortt($sortlist,$sortt);
	isValidSortd($sortd,'ASC');


	sqlSafe(&$id,&$deleteID);

	if(!isset($action))
		$action="";

	switch($action){
		case 'delete':
			if(sizeof($deleteID)==0)
				break;
			foreach($deleteID as $id){
				$query = "SELECT * FROM friends WHERE id='$id'";
				$result = mysql_query($query);
				$line = mysql_fetch_assoc($result);

				if($line['user1']==$userData['userid'] && $line['twoway']=='n')
					$query = "DELETE FROM friends WHERE id='$id'";
				elseif($line['user1']==$userData['userid'] && $line['twoway']=='y')
					$query = "UPDATE friends SET user1='$line[user2]', user2='$line[user1]', twoway='n' WHERE id='$id'";
				elseif($line['user2']==$userData['userid'] && $line['twoway']=='y')
					$query = "UPDATE friends SET twoway='n' WHERE id='$id'";
				mysql_query($query);
			}
			break;
		case 'add':
			$time=time();
			if(!is_numeric($id))
				$id=getUserId($id);
			if($id==$userData['userid']){
				$msgs->addMsg("You cannot add yourself");
				break;
			}
			$query = "SELECT id,user1,user2,twoway FROM friends WHERE (user1='$id' && user2='$userData[userid]') || (user1='$userData[userid]' && user2='$id')";
			$result = mysql_query($query);
			if(mysql_num_rows($result)==0){
				$query = "INSERT INTO friends SET user1='$userData[userid]', user2='$id',twoway='n'";
				$msgs->addMsg("Friend Added");
			}else{
				$data = mysql_fetch_assoc($result);
				if($data['user1']==$userData['userid'] || ($data['user2']==$userData['userid'] && $data['twoway']=='y')){
					$msgs->addMsg("That user is already on your friends list");
					break;
				}else{
					$query = "UPDATE friends SET twoway='y' WHERE id='$data[id]'";
					$msgs->addMsg("Friend Added");
				}
			}
			mysql_query($query);
			break;
	}

	$query = "SELECT id,twoway,userid,username,dob,sex,loc,((activetime>" . ((time()-($config['friendAwayTime']*60))) .")) as online FROM friends,users WHERE ((user1='$userData[userid]' && user2=users.userid) || (user2='$userData[userid]' && user1=users.userid && twoway='y')) ORDER BY $sortt $sortd,username ASC";
	$result = mysql_query($query);

	incHeader(0,false,getPersonalMenu());

	echo "<table width=100% cellspacing=1 cellpadding=3><form action='$PHP_SELF' method=post>\n";
	echo "<tr>";
		echo "<td class=header width=22>&nbsp;</td>";
		makeSortTableHeader($sortlist,"Username","username");
		makeSortTableHeader($sortlist,"Age","dob",array(),"",'center');
		makeSortTableHeader($sortlist,"Sex","sex",array(),"",'center');
		makeSortTableHeader($sortlist,"Location","loc");
		makeSortTableHeader($sortlist,"Online","online",array(),"",'center');
		makeSortTableHeader($sortlist,"Match","twoway",array(),"",'center');
		echo "<td class=header align=center>Message</td>";
	echo "</tr>\n";

	while($line = mysql_fetch_assoc($result)){
		echo "<tr><td class=body><input type=checkbox name=deleteID[] value='$line[id]'></td><td class=body><a class=body href='profile.php?uid=$line[userid]'>$line[username]</a></td><td class=body align=center>" . getAge($line['dob']) . "</td><td class=body align=center>$line[sex]</td><td class=body>" . getCatName($line['loc'],'locs') . "</td><td class=body align=center>";
		if($line['online'])
			echo "Online";
		echo "</td><td class=body align=center>";
		if($line['twoway']=='y')
			echo "Two way";
		echo "</td><td class=body align=center><a class=body href='messages.php?action=write&to=$line[userid]'>[Msg]</a></td>";
		echo "</tr>\n";
	}

	echo "<tr><td class=header colspan=8>";
	echo "<input class=body name=selectall type=checkbox value='Check All' onClick=\"this.value=check(this.form,'delete')\">";
	echo "<input class=body type=submit name=action value=delete></td></tr>\n";
	echo "</form></table>\n";

	incFooter();
