<?

	include('include/general.lib.php');

//check google ip?
//check google cookie?
//check https?

	$id  = getREQval('id');

	if($id){
		$uid = $google->decuserid($id);
	}else{
		requireLogin(REQUIRE_LOGGEDIN);

		if(!in_array($userData['userid'], array(1, 5))) // , 912943, 2528247
			die("Bad request");

		$uid = getREQval('uid');
	}


	header("Content-Type: text/xml");
	echo '<?'.'xml version="1.0"?'.'>'; //stupid pspad syntax highlighter


//get info
	$user = getUserInfo($uid);

//	$res = $usersdb->prepare_query("SELECT * FROM users WHERE userid = %", $uid);
//	$user = $res->fetchrow();

	if(!$user || ($user['state'] == 'frozen')){
		echo "<UserProfile />";
		exit;
	}


//get profile text
	$user2 = $cache->get("profile-$uid");

	if(!$user2){
		$res = $usersdb->prepare_query("SELECT msn, icq, yahoo, aim, skin, showbday, showjointime, showactivetime, showprofileupdatetime, showpremium, profile, profileupdatetime, views, showlastblogentry FROM profile WHERE userid = %", $uid);
		$user2 = $res->fetchrow();

		$blocks = new profileBlocks($uid);
		$profBlocks = $blocks->getBlocks();

		foreach ($profBlocks as $index => $profBlock) {
			$profBlocks[$index]['nBlocktitle'] = removeHTML(trim($profBlock['blocktitle']));
			$profBlocks[$index]['nBlockcontent'] = nl2br(wrap(parseHTML(smilies($profBlock['blockcontent']))));
			unset($profBlocks[$index]['blockcontent']);
			unset($profBlocks[$index]['blocktitle']);
		}

		sortCols($profBlocks, SORT_ASC, SORT_NUMERIC, 'blockorder');
		$user2['profBlocks'] = $profBlocks;
	}

	$user = $user2 + $user;
	unset($user2);



	echo "<UserProfile>";
	
		echo "<EncryptedUserId>" . $google->encuserid($uid) . "</EncryptedUserId>";
		echo "<Hashcode>$user[googlehash]</Hashcode>";

		echo "<Gender>$user[sex]</Gender>";
		echo "<Birthdate>" . gmdate("Y-m-d", $user['dob']) . "</Birthdate>";
		echo "<SignupDate>" . gmdate("Y-m-d", $user['jointime']) . "</SignupDate>";
		echo "<LastUpdateTime>" . gmdate("Y-m-d\TH:i:s\Z", $user['profileupdatetime']) . "</LastUpdateTime>";
//		echo "<LastLoginTime>" . gmdate("Y-m-d\TH:i:s\Z", $user['activetime']) . "</LastLoginTime>";


/*	//locations
		$locations = new category( $configdb, "locs");
		$loc = $locations->getCatName($user['loc']);
		echo "<Location><City>$loc</City></Location>";
*/

	//relationship status
		$profilequestions = getProfileQuestions();
		$prof = decodeProfile($user['profile']);

		if($prof[3] != '0')
			echo "<RelationshipStatus>" . $profilequestions[3]['answers'][$prof[3]] . "</RelationshipStatus>";


	//profile body
		$profileparts = array();
		foreach($user['profBlocks'] as $index => $profBlock)
			if($profBlock['nBlockcontent'] && $profBlock['permission'] == 'anyone')
				$profileparts[] = htmlentities($profBlock['nBlockcontent']);

		if($profileparts)
			echo "<AboutMe>" . implode("", $profileparts) . "</AboutMe>";



	//interests
		$res = $usersdb->prepare_query("SELECT interestid FROM userinterests WHERE userid = %", $uid);

		$userinterests = array();
		while($line = $res->fetchrow())
			$userinterests[$line['interestid']] = $line['interestid'];


		if($userinterests){
			$interests = new category($configdb, "interests");
			$cats = $interests->makebranch(); //only main categories

			$subcats = array();
			foreach($cats as $item)
				if(isset($userinterests[$item['id']]) && $item['depth'] > 1)
					$subcats[] = $item['name'];

			echo "<Interests>" . implode(", ", $subcats) . "</Interests>";
		}


	//friends
		$friends = getMutualFriendsList($uid);

		$friends = getFriendsListIDs($uid, USER_FRIENDS);
		$friendof = getFriendsListIDs($uid, USER_FRIENDOF);

		echo "<Friends>";
			echo "<FriendCount>" . count($friends) . "</FriendCount>";
			echo "<FriendList>";

			foreach($friends as $id){
				echo "<FriendProfile>";
					echo "<EncryptedUserId>" . $google->encuserid($id) . "</EncryptedUserId>";
					echo "<FriendRelationship>" . (isset($friendof[$id]) ? '0' : '1' ) . "</FriendRelationship>";
				echo "</FriendProfile>";
			}
			foreach($friendof as $id){
				if(!isset($friends[$id])){
					echo "<FriendProfile>";
						echo "<EncryptedUserId>" . $google->encuserid($id) . "</EncryptedUserId>";
						echo "<FriendRelationship>2</FriendRelationship>";
					echo "</FriendProfile>";
				}
			}

			echo "</FriendList>";
		echo "</Friends>";


	//comments
		if($user['enablecomments']=='y'){
			$res = $usercomments->db->prepare_query("SELECT SQL_CALC_FOUND_ROWS authorid, time, nmsg FROM usercomments WHERE userid = % ORDER BY time DESC LIMIT 5", $uid);

			$total = $res->totalrows();

			if($total){
				echo "<Comments>";
					echo "<CommentCount>$total</CommentCount>";
					echo "<CommentList>";

					while($line = $res->fetchrow()){
						echo "<Comment>";
							echo "<CommentTimestamp>" . gmdate("Y-m-d\TH:i:s\Z", $line['time']) . "</CommentTimestamp>";
							echo "<CommentUserID>" . $google->encuserid($line['authorid']) . "</CommentUserID>";
							echo "<CommentText>" . htmlentities($line['nmsg']) . "</CommentText>";
						echo "</Comment>";
					}
					echo "</CommentList>";
				echo "</Comments>";
			}
		}


	//media
	if($user['firstpic'] || $user['gallery'] == 'anyone'){
		echo "<UserMedia>";

		//pics
			$pics = getUserPics($uid);
	
			if(count($pics)){
				echo "<Media>";
					echo "<MediaType>ProfilePics</MediaType>";
					echo "<MediaCount>" . count($pics) . "</MediaCount>";
					echo "<MediaList>";

					foreach($pics as $pic){
						echo "<MediaEntry>";
							echo "<MediaDescription>" . htmlentities($pic['description']) . "</MediaDescription>";
							echo "<MediaId>pic:" . $google->encuserid($uid) . ":$pic[id]</MediaId>";
						echo "</MediaEntry>";
					}

					echo "</MediaList>";
				echo "</Media>";
			}

		//gallery
			$userGalleries = new usergalleries($galleries, $uid);
			$galleryids = $userGalleries->getGalleryList('anyone');

			if(count($galleryids)){
				$galleryobjs = $userGalleries->getGalleries($galleryids);

				echo "<Media>";
					echo "<MediaType>Gallery</MediaType>";
					//echo "<MediaCount>...</MediaCount>";

					echo "<MediaList>";

					foreach($galleryobjs as & $galleryobj){
						$ids = $galleryobj->getPictureList();
						$pics = $galleryobj->getPics($ids);

						foreach($pics as & $pic){
							echo "<MediaEntry>";
								echo "<MediaName>" . htmlentities($galleryobj->name) . "</MediaName>";
								echo "<MediaDescription>" . htmlentities($pic->parseDescription(true)) . "</MediaDescription>";
								echo "<MediaId>gallery:" . $google->encuserid($uid) . ":$galleryobj->id:$pic->id</MediaId>";
							echo "</MediaEntry>";
						}
					}

					echo "</MediaList>";
				echo "</Media>";
			}

		echo "</UserMedia>";
	}


	//blogs
		$userblog = new userblog($weblog, $uid);
		$lastpage = $userblog->getPostList(0, WEBLOG_PUBLIC);

		if($lastpage){
			echo "<Blogs>";
				echo "<BlogCount>1</BlogCount>";
				echo "<BlogList>";
					echo "<Blog>";
						echo "<BlogId>" . $google->encuserid($uid) . "</BlogId>";
						echo "<BlogEntryCount>" . $userblog->getPostCount(WEBLOG_PUBLIC) . "</BlogEntryCount>";
						
						echo "<BlogEntries>";

						foreach($lastpage as $entryid => $entrytime){
							$lastentry = new blogpost($userblog, $entryid, $userData);

							echo "<BlogEntry>";
								echo "<EntryName>" . htmlentities($lastentry->title) . "</EntryName>";
								echo "<EntryDate>" . gmdate("Y-m-d\TH:i:s\Z", $lastentry->time) . "</EntryDate>";
								echo "<EntryContents>" . htmlentities($lastentry->getParsedText()) . "</EntryContents>";
							echo "</BlogEntry>";
						}
						echo "</BlogEntries>";
					echo "</Blog>";
				echo "</BlogList>";
			echo "</Blogs>";
		}


	echo "</UserProfile>";

