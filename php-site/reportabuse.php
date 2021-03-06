<?

	$login=1;

	require_once("include/general.lib.php");

	$type = getREQval('type', 'int');
	$section = getREQval('section');
	$id = getREQval('id', 'int');
	$uid = getREQval('uid', 'int', 0);
	$reasonBody = getREQval('reasonBody', 'string', '');
	$link = getREQval('link', 'string', '');

	if($action == "Report" && $id && ($reason = getPOSTval('reason')) && $type){

		if($reasonid = getREQval('reasonid', 'int'))
			$reasonText = $abuselog->reasons[$reasonid];
		if($link != '')
		{
			// url encode the link
			$link = str_replace("http://".$wwwdomain, "", $link);
			$linkParts = split("/", $link);
			$encodedLinkParts = array();
			foreach ($linkParts as $linkPart) {
				$encodedLinkParts[] = urlencode($linkPart);
			}
			$link = "http://".$wwwdomain.implode("/", $encodedLinkParts);
		
			$reasonText .= ": <a href='$link' class='body'>$link</a>";
		}
		
		$reasonText .= "\n\n$reason";

		$reasonText = cleanHTML($reasonText);
		$reasonText = parseHTML($reasonText);

		switch($type){
			case MOD_USERABUSE:
				$abuselogid = $abuselog->addAbuse($id, ABUSE_ACTION_USER_REPORT, $reasonid, "User Reported", $reasonText);
				break;

			case MOD_FORUMPOST:
				$abuselogid = 0;

			//don't flag if it's a sig or something, but currently no distinction, so disable for now.

/*				$res = $forums->db->prepare_query("SELECT threadid FROM forumposts WHERE id = #", $id);
				$tid = $res->fetchfield();

				$forums->flagThread($tid);
*/				break;

			default:
				$abuselogid = 0;
				break;

		}

		$db->prepare_query("INSERT INTO abuse SET itemid = #, reason = ?, userid = #, type = ?, time = #, abuselogid = #", $id, $reasonText, $userData['userid'], $type, time(), $abuselogid);

		$mods->newSplitItem($type, array($uid => $id));

		incHeader();
		echo "Thanks for the report.";
		incFooter();
		exit;
	}

	$typeText = null;

	// false: don't show reporting box
	// 0: show reporting box, no abuse reason
	// > 1: show reporting box, w/ given abuse reason code
	$reportReason = false;

	switch ($type) {
		case MOD_GALLERYABUSE:
			$reportReason = 0;
			$typeText = 'gallery';
			break;

		case MOD_FORUMPOST:
			$reportReason = 0;
			$typeText = 'forumpost';
			break;

		case MOD_VIDEO:
			$reportReason = 0;
			$typeText = 'video';
			break;

		case MOD_USERABUSE:
			$typeText = 'user';

			switch ($section) {
				case "abusivemessages":
				case "threats":
				case "hacked":
				case "ignore":
					break;

				case "advertising":
					$reportReason = ABUSE_REASON_ADVERT;
					break;

				case "offensiveprofile":
					$reportReason = ABUSE_REASON_OTHER;
					break;

				case "offensiveblog":
					$reportReason = ABUSE_REASON_BLOG;
					break;

				case "fakepictures":
					$reportReason = ABUSE_REASON_FAKE;
					break;

				case "underage":
					$reportReason = ABUSE_REASON_UNDERAGE;
					break;

				case "otherabuse":
					$reportReason = ABUSE_REASON_OTHER;
					break;
			}

			break;
	}

	$template = new template('reportabuse/index');
	$template->setMultiple(array(
		'reportReason'	=> $reportReason,
		'type'			=> $type,
		'typeText'		=> $typeText,
		'section'		=> $section,
		'id'			=> $id,
		'uid'			=> $uid,
		'reasonBody'	=> $reasonBody,
		'link'			=> $link,
	));
	$template->display();
