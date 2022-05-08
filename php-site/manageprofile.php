<?

	$login=1;

	require_once("include/general.lib.php");

	$isAdmin = $mods->isAdmin($userData['userid'],'editprofile');
	if(!isset($uid) || !$isAdmin)
		$uid = $userData['userid'];

	$maxlengths['tagline'] = 300;
	$maxlengths['about'] = 10000;
	$maxlengths['likes'] = 10000;
	$maxlengths['dislikes'] = 10000;
	$maxlengths['signiture'] = 1000;


	$locations = & new category("locs");

	if($action){

		$db->prepare_query("SELECT userid,username, age, sex, premiumexpiry, dob FROM users WHERE userid = ?", $uid);
		$user = $db->fetchrow();
		$plus = $user['premiumexpiry'] > time();

		if($data['month']<=0 || $data['month']>12)
			$msgs->addMsg("Invalid Month");
		elseif($data['day']<=0 || $data['month']>31)
			$msgs->addMsg("Invalid Day");
		else{
			$dob = my_gmmktime(0,0,0, $data['month'],$data['day'],$data['year']);
			$age = getAge($dob);

			if($age < $config['minAge'] || $age > $config['maxAge']){
				$msgs->addMsg("Invalid Year");
			}else{
				if($dob != $user['dob']){
					$commands[] = $db->prepare("dob = ?", $dob);
					$commands[] = $db->prepare("age = ?", $age);
					$db->prepare_query("UPDATE pics SET v1=0, v2=0, v3=0, v4=0, v5=0, v6=0, v7=0, v8=0, v9=0, v10=0, votes=0, score=0, age = ? WHERE itemid = ?", $age, $uid);
				}
			}
		}

		if($locations->isValidCat($data['loc']))
			$commands[] = $db->prepare("loc = ?", $data['loc'] );

		if(isset($prof) && is_array($prof)){
			$commands[] = $db->prepare("profile = ?", encodeProfile($prof) );
			$commands[] = $db->prepare("single = ?", ($prof[3] == 1 ? 'y' : 'n') );
			$commands[] = $db->prepare("sexuality = ?", $prof[2] );
		}

		$commands[] = $db->prepare("profileupdatetime = ?", time());

		$db->query("UPDATE users SET " . implode(", ", $commands) . " WHERE userid = '" . $db->escape($uid) . "'");

		$db->prepare_query("INSERT IGNORE INTO newestprofile SET userid = ?, username = ?, age = ?, sex = ?", $user['userid'], $user['username'], $user['age'], $user['sex']);

		$set = array();


		if(empty($profileskin) || !$plus)
			$profileskin = 0;

		if($profileskin != 0){
			$db->prepare_query("SELECT id FROM profileskins WHERE id = ? && userid IN (0,?)", $profileskin, $uid);

			if($db->numrows() == 0)
				$profileskin = 0;
		}

		$set[] = $db->prepare("skin = ?", $profileskin);

		if(isset($tagline)){

			$tagline = removeHTML(trim(substr($tagline,0,$maxlengths['tagline'])));

			$pos = 0;
			for($i=0;$i<5;$i++)
				if($pos+1 < strlen($tagline))
					$pos = strpos($tagline, "\n", $pos+1);
			if($pos)
				$tagline = substr($tagline, 0, $pos-1);

			$ntagline = nl2br(wrap(smilies($tagline)));

			$set[] = $db->prepare("tagline = ?", $tagline);
			$set[] = $db->prepare("ntagline = ?", $ntagline);
		}

		if(isset($signiture)){
			if($isAdmin)
				$set[] = $db->prepare("enablesignature = ?", (isset($enablesignature) ? "y" : "n") );

			$signiture = removeHTML(trim(substr($signiture,0,$maxlengths['signiture'])));
			$nsigniture = nl2br(wrap(parseHTML(smilies($signiture))));
			$set[] = $db->prepare("signiture = ?", $signiture);
			$set[] = $db->prepare("nsigniture = ?", $nsigniture);
		}

		if(isset($about)){
			$about = removeHTML(censor(trim(substr($about,0,$maxlengths['about']))));
			$nabout = nl2br(wrap(parseHTML(smilies($about))));
			$set[] = $db->prepare("about = ?", $about);
			$set[] = $db->prepare("nabout = ?", $nabout);
		}

		if(isset($likes)){
			$likes = removeHTML(censor(trim(substr($likes,0,$maxlengths['likes']))));
			$nlikes = nl2br(wrap(parseHTML(smilies($likes))));
			$set[] = $db->prepare("likes = ?", $likes);
			$set[] = $db->prepare("nlikes = ?", $nlikes);
		}

		if(isset($dislikes)){
			$dislikes = removeHTML(censor(trim(substr($dislikes,0,$maxlengths['dislikes']))));
			$ndislikes = nl2br(wrap(parseHTML(smilies($dislikes))));
			$set[] = $db->prepare("dislikes = ?", $dislikes);
			$set[] = $db->prepare("ndislikes = ?", $ndislikes);
		}

		if(isset($data['icq'])){
			$data['icq'] = removeHTML($data['icq']);
			$set[] = $db->prepare("icq = ?", $data['icq']);
		}
		if(isset($data['yahoo'])){
			$data['yahoo'] = removeHTML($data['yahoo']);
			$set[] = $db->prepare("yahoo = ?", $data['yahoo']);
		}
		if(isset($data['msn'])){
			$data['msn'] = removeHTML($data['msn']);
			$set[] = $db->prepare("msn = ?", $data['msn']);
		}
		if(isset($data['aim'])){
			$data['aim'] = removeHTML($data['aim']);
			$set[] = $db->prepare("aim = ?", $data['aim']);
		}

		$db->query("UPDATE profile SET " . implode(", ", $set) . $db->prepare(" WHERE userid = ?", $uid));

		if($plus){
			$db->prepare_query("SELECT forumrank FROM users WHERE userid = ?", $uid);
			$oldforumrank = $db->fetchfield();

			$db->prepare_query("SELECT id,forumrank FROM forumrankspending WHERE userid = ?", $uid);
			$newforumrank = array('id' => 0, 'forumrank' => "");
			if($db->numrows())
				$newforumrank = $db->fetchrow();


			if(!isset($forumrank))
				$forumrank = "";
			$forumrank = trim($forumrank);

			if(!isset($forumrankchoice))
				$forumrankchoice = "";

			switch($forumrankchoice){
				case "default":
					$db->prepare_query("UPDATE users SET forumrank = '' WHERE userid = ?", $uid);
        		case "current":
					if($newforumrank['forumrank'] != ""){
						$db->prepare_query("DELETE FROM forumrankspending WHERE userid = ?", $uid);
						$mods->deleteItem("forumrank",$newforumrank['id']);
					}
					break;
              	case "new":
              		if($forumrank != $newforumrank['forumrank']){
              			if($newforumrank['forumrank'] != ""){
	              			$db->prepare_query("DELETE FROM forumrankspending WHERE userid = ?", $uid);
							$mods->deleteItem("forumrank",$newforumrank['id']);
						}
	              		if($forumrank == ""){
	              			$db->prepare_query("UPDATE users SET forumrank = '' WHERE userid = ?", $uid);
	              		}else{
		              		$db->prepare_query("INSERT INTO forumrankspending SET userid = ?, forumrank = ?", $uid,$forumrank);
		              		$id = $db->insertid();
		              		$mods->newItem(MOD_FORUMRANK, $id);
		              	}
		            }
					break;
			}
		}

		if($uid != $userData['userid']){
			$mods->adminlog("update profile", "Update user profile: userid $uid");
			header("location: profile.php?uid=$uid");
			exit;
		}

		$msgs->addMsg("Updated. Check <a href='profile.php?uid=$uid'>your profile</a> to see the changes");
	}

	if($action != "") $db->begin();

	$db->prepare_query("SELECT dob, loc, icq, msn, yahoo, aim, forumrank, posts, profile, tagline, likes, dislikes, about, enablesignature, signiture, premiumexpiry, profile.skin FROM users,profile WHERE users.userid=profile.userid && users.userid = ?", $uid);
	$user = $db->fetchrow();

	if($user['premiumexpiry'] > time()){
		$db->prepare_query("SELECT forumrank FROM forumrankspending WHERE userid = ?", $uid);

		$forumrank = "";
		if($db->numrows())
			$forumrank = $db->fetchfield();
	}

	if($action != "") $db->commit();

	$prof = decodeProfile($user['profile']);

	for($i=1;$i<=12;$i++)
		$months[$i] = date("F", mktime(0,0,0,$i,1,0));

	if($uid != $userData['userid'])
		$mods->adminlog("edit profile", "Edit user profile: userid $uid");

	incHeader();

	$time = time();

	echo "<script>";

	echo "var taglinemaxlength = $maxlengths[tagline];\n";
	echo "var aboutmaxlength = $maxlengths[about];\n";
	echo "var likesmaxlength = $maxlengths[likes];\n";
	echo "var dislikesmaxlength = $maxlengths[dislikes];\n";
	echo "var sigmaxlength = $maxlengths[signiture];\n";


?>
function checkLength(){
	var formErrors = "";

	if(document.editbox.about.value.length > aboutmaxlength)
		formErrors += "Your About entry is " + (document.editbox.about.value.length - aboutmaxlength) + " characters too long\n";

	if(document.editbox.likes.value.length > likesmaxlength)
		formErrors += "Your Likes entry is " + (document.editbox.likes.value.length - likesmaxlength) + " characters too long\n";

	if(document.editbox.dislikes.value.length > dislikesmaxlength)
		formErrors += "Your Dislikes entry is " + (document.editbox.dislikes.value.length - dislikesmaxlength) + " characters too long\n";

	if(document.editbox.signiture.value.length > sigmaxlength)
		formErrors += "Your Signiture is " + (document.editbox.signiture.value.length - sigmaxlength) + " characters too long\n";


	if(formErrors != ""){
		alert(formErrors);
		return false;
	}else{
		document.editbox.submit();
	}
}

function setLengths(){

	putinnerHTML('taglinelength', "Length: " + document.editbox.tagline.value.length + " / " + taglinemaxlength );
	putinnerHTML('aboutlength', "Length: " + document.editbox.about.value.length + " / " + aboutmaxlength );
	putinnerHTML('likeslength', "Length: " + document.editbox.likes.value.length + " / " + likesmaxlength );
	putinnerHTML('dislikeslength', "Length: " + document.editbox.dislikes.value.length + " / " + dislikesmaxlength );
	putinnerHTML('siglength', "Length: " + document.editbox.signiture.value.length + " / " + sigmaxlength );
/*
	if(document.all){
		document.all.aboutlength.innerHTML = "Length: " + document.editbox.about.value.length + " / " + aboutmaxlength;
		document.all.likeslength.innerHTML = "Length: " + document.editbox.likes.value.length + " / " + likesmaxlength;
		document.all.dislikeslength.innerHTML = "Length: " + document.editbox.dislikes.value.length + " / " + dislikesmaxlength;
		document.all.siglength.innerHTML = "Length: " + document.editbox.signiture.value.length + " / " + sigmaxlength;
	}else{
		document.getElementById('aboutlength').innerHTML = "Length: " + document.editbox.about.value.length + " / " + aboutmaxlength;
		document.getElementById('likeslength').innerHTML = "Length: " + document.editbox.likes.value.length + " / " + likesmaxlength;
		document.getElementById('dislikeslength').innerHTML = "Length: " + document.editbox.dislikes.value.length + " / " + dislikesmaxlength;
		document.getElementById('siglength').innerHTML = "Length: " + document.editbox.signiture.value.length + " / " + sigmaxlength;
	}
*/
}

<?
	echo "</script>";


	echo "<table align=center><form method=post action=\"$PHP_SELF\" name=editbox>\n";

	echo "<input type=hidden name=uid value=$uid>";

	echo "<tr><td class=header colspan=2 align=center>Basics</td></tr>";

	echo "<tr><td class=body>Date of Birth:</td><td class=body><select class=body name=\"data[month]\"><option value=0>Month" . make_select_list_key($months,gmdate("m",$user['dob'])) . "</select><select class=body name=\"data[day]\"><option value=0>Day" . make_select_list(range(1,31),gmdate("j",$user['dob'])) . "</select><select class=body name=\"data[year]\"><option value=0>Year" . make_select_list(array_reverse(range(date("Y")-$config['maxAge'],date("Y")-$config['minAge'])),gmdate("Y",$user['dob'])) . "</select><br>Changing your age will reset your votes</td></tr>";

	echo "<tr><td class=body>Location:</td><td class=body><select class=body name=\"data[loc]\">" . makeCatSelect($locations->makeBranch(),$user['loc']) . "</select></td></tr>";

	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Contact</td></tr>";

	echo "<tr><td class=body>ICQ:</font></td><td class=body><input class=body type=text name=\"data[icq]\" size=40 value=\"" . ($user['icq']==0 ? "" : $user['icq']) . "\"></td></tr>";
	echo "<tr><td class=body>MSN:</font></td><td class=body><input class=body type=text name=\"data[msn]\" size=40 value=\"$user[msn]\"></td></tr>";
	echo "<tr><td class=body>Yahoo:</font></td><td class=body><input class=body type=text name=\"data[yahoo]\" size=40 value=\"$user[yahoo]\"></td></tr>";
	echo "<tr><td class=body>AIM:</font></td><td class=body><input class=body type=text name=\"data[aim]\" size=40 value=\"$user[aim]\"></td></tr>";

	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Profile</td></tr>";

	foreach($profile as $qnum => $val){
		echo "<tr><td class=body>What is your $val[question]?</td>";
		echo "<td><select class=body name=\"prof[$qnum]\" style=\"width:250px\">\n";


		echo "	<option value=0" . ($prof[$qnum]==0 ? " selected" : "" ) . ">No Comment";
		foreach($val['answers'] as $anum => $ans)
			echo "	<option value=" . ($anum+1) . ($prof[$qnum]==$anum+1 ? " selected" : "" ) . ">$ans";
		echo "</td></tr>\n";

	}

	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=header colspan=2 align=center>Details</td></tr>";

	echo "<tr><td class=body>Tag line:</td><td class=body><div id=taglinelength>Length: " . strlen($user['tagline']) . " / $maxlengths[tagline]</div></td></tr>";
	echo "<tr><td class=body colspan=2>This is shown in the user search page. Only text and smilies are allowed.<br>No images, fonts, etc are allowed. Anything past 5 lines will be removed</td></tr>";
	echo "<tr><td class=body colspan=2><textarea class=body cols=100 rows=5 name=tagline onChange=\"setLengths();\">$user[tagline]</textarea></td></tr>\n";
	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=body>About you:</td><td class=body><div id=aboutlength>Length: " . strlen($user['about']) . " / $maxlengths[about]</div></td></tr>";
	echo "<tr><td class=body colspan=2><textarea class=body cols=100 rows=20 name=about onChange=\"setLengths();\">$user[about]</textarea></td></tr>\n";
	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=body>Likes:</td><td class=body><div id=likeslength>Length: " . strlen($user['likes']) . " / $maxlengths[likes]</div></tr>";
	echo "<tr><td class=body colspan=2><textarea class=body cols=100 rows=20 name=likes onChange=\"setLengths();\">$user[likes]</textarea></td></tr>\n";
	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	echo "<tr><td class=body>Dislikes:</td><td class=body><div id=dislikeslength>Length: " . strlen($user['dislikes']) . " / $maxlengths[dislikes]</div></tr>";
	echo "<tr><td class=body colspan=2><textarea class=body cols=100 rows=20 name=dislikes onChange=\"setLengths();\">$user[dislikes]</textarea></td></tr>\n";
	echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";

	if($user['premiumexpiry'] > $time){

		$db->prepare_query("SELECT id, name FROM profileskins WHERE userid IN (0,?) ORDER BY userid, name", $uid);

		$profileskins = array();
		while($line = $db->fetchrow())
			$profileskins[$line['id']] = $line['name'];

		echo "<tr><td class=header colspan=2 align=center>Profile Skin</td></tr>";

		echo "<tr><td class=body>Choose a profile skin:</td><td class=body><select class=body name=profileskin><option value=0>User Default";
		echo make_select_list_key($profileskins, $user['skin']);

		echo "</select></td></tr>";
		echo "<tr><td class=body colspan=2 align=center>Don't like the presets? <a class=body href=manageprofileskins.php>Create your own</a></td></tr>";

		echo "<tr><td class=body colspan=2>&nbsp;</td></tr>";
	}

	echo "<tr><td class=header colspan=2 align=center>Forums</td></tr>";

	if($isAdmin){
		echo "<tr><td class=body colspan=2>" . makeCheckBox("enablesignature", "Enable Signature", 'body', $user['enablesignature'] == 'y') . "</td></tr>";
	}
	if($user['enablesignature'] == 'y' || $isAdmin){
		echo "<tr><td class=body>Forum Signature:</td><td class=body><div id=siglength>Length: " . strlen($user['signiture']) . " / $maxlengths[signiture]</div></tr>";
		echo "<tr><td class=body colspan=2><textarea class=body cols=100 rows=8 name=signiture onChange=\"setLengths();\">$user[signiture]</textarea></td></tr>\n";
	}else
		echo "<tr><td class=body colspan=2>Your Forum Signature has been disabled</td></tr>";

	if($user['premiumexpiry'] > $time){
//<label for=\"$name/$k\" class=side>$v</label>
		echo "<tr><td class=body valign=top>Forum Rank:</td><td class=body>";

		if($user['forumrank'] != "")
			echo "<input type=radio name=forumrankchoice id=forumrankchoicecurrent value=current" . ($forumrank == "" ? " checked" : "" ) . "> <label for=forumrankchoicecurrent class=body>Keep your current:</label> $user[forumrank]<br>";
		echo "<input type=radio name=forumrankchoice id=forumrankchoicedefault value=default" . ($user['forumrank'] == "" && $forumrank == "" ? " checked" : "" ) . "> <label for=forumrankchoicedefault class=body>Use the default:</label> " . forumrank($user['posts']) . "<br>";
		echo "<input type=radio name=forumrankchoice id=forumrankchoicenew value=new" . ($forumrank != "" ? " checked" : "" ) . "> <label for=forumrankchoicenew class=body>New Forum Rank:</label> <input class=body type=text name=forumrank value=\"" . htmlentities($forumrank) . "\" maxlength=18></td></tr>";

		echo "<tr><td class=body colspan=2>New Forum Ranks will be moderated, so may take a while to appear.<br>Excessive swearing, and impersonating a moderator or administrator will not be allowed";

		echo "</td></tr>";
	}

//	echo "<tr><td class=body colspan=2 align=center><br><b>Please do not share personal or financial information while using Nexopia.com.<br>All information passed through the use of this site, is at the risk of the user.<br>Nexopia.com will assume no liability for any users' actions.</b></td></tr>";


	echo "<tr><td colspan=3 class=body align=center><input type=hidden name=action value=Update><input class=body type='button' value='Update' onClick=\"checkLength();\"></td></tr>\n";

	echo "</form></table>";


	incFooter();
