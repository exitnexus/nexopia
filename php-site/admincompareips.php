<?
	$login = 1;
	require_once('include/general.lib.php');
	
	if(!$mods->isAdmin($userData['userid'], 'listusers')){
		header("location: /");
		exit;
	}
	
	$username1 = getPOSTval('username1');
	$username2 = getPOSTval('username2');
	
	if(empty($username1)){
		$username1 = getREQval('username1');

		if($username1 && !checkKey($username1, getREQval('k')))
			$username1 = "";
	}
	if(empty($username2)){
		$username2 = getREQval('username2');

		if($username2 && !checkKey($username2, getREQval('k')))
			$username2 = "";
	}
	
	$userid1 = getUserID($username1);
	$userid2 = getUserID($username2);
	
	if ($username1 && !$userid1)
		$msgs->addMsg("'$username1' is not a valid user.");
	if ($username2 && !$userid2)
		$msgs->addMsg("'$username2' is not a valid user.");
		
	$ipMatches = array();
	if ($userid1 && $userid2) {
		$mods->adminlog('compare ips',"Compare ips for $username1 and $username2");
		$result1 = $usersdb->prepare_query("SELECT userid, activetime, ip, hits FROM userhitlog WHERE userid = %", $userid1);
		$result2 = $usersdb->prepare_query("SELECT userid, activetime, ip, hits FROM userhitlog WHERE userid = %", $userid2);
		
		$rows1=$result1->fetchrowset();
		$rows2=$result2->fetchrowset();
		
		foreach($rows1 as $line1) {
			foreach($rows2 as $line2) {
				if($line1['ip'] == $line2['ip']){
					$ipMatches[] = array(
							'ip' => long2ip($line1['ip']), 
							'activetime1' => userDate("F j, Y, g:i a", $line1['activetime']),
							'activetime2' => userDate("F j, Y, g:i a", $line2['activetime']),
							'hits1' => $line1['hits'],
							'hits2' => $line2['hits'],
							'ipkey' => makeKey(long2ip($line1['ip']))
							);
					continue;
				}
			}
		}
	}
	
	$template = new template('admin/ipcompare');
	$template->set('ipAdmin', $mods->isAdmin($userData['userid'], 'showips'));
	$template->set('username1', $username1);
	$template->set('username2', $username2);
	$template->set('key1', makeKey($username1));
	$template->set('key2', makeKey($username2));
	$template->set('matchAttempted', ($userid1 && $userid2));
	$template->set('ipMatches', $ipMatches);
	$template->display();
