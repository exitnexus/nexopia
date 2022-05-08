<?php

function showNotification(){
	global $wiki;
	if (!isset($wiki))
		return;
	global $cache, $usersdb, $userData;

	if ($userData['userid'] < 0){
		return;
	}
	
	$interstitial = $cache->hdget('revision', 1, 'getRevision');
	if($interstitial['rev'] > $userData['lastnotification']){
		$usersdb->prepare_query("UPDATE users SET `lastnotification` = ? WHERE userid = %", $interstitial['rev'], $userData['userid']);
		$template = new template("sitenotifications/interstitial");
		$template->set("text", $interstitial['output']);
		$template->display();
	}
}
function getRevision(){
	global $wiki;

	$text = $wiki->getPage("/SiteText/sitenotifications");

	return array('output' => $text['output'], 'rev' => $text['revision']);
}