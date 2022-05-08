<?php

function showNotification(){
	global $wiki, $rap_pagehandler;
	if (!isset($wiki))
		return;
	global $cache, $usersdb, $userData;

	if ($userData['userid'] < 0){
		return;
	}
	
	$interstitial = $cache->hdget('revision', 1, 'getRevision');
	if($interstitial['rev'] && $interstitial['rev'] > $userData['lastnotification']){
		$usersdb->prepare_query("UPDATE users SET `lastnotification` = ? WHERE userid = %", $interstitial['rev'], $userData['userid']);
		$cache->remove("userinfo-".$userData['userid']);
		$template = new template("sitenotifications/interstitial");
		$template->set("text", $interstitial['output']);
		$template->display();
	}
}

function user_notification()
{
	global $userData, $wiki, $cache;
	
	if (!isset($wiki))
		return false;
	
	if ($userData['userid'] < 0)
	{
		return false;
	}
	
	$interstitial = $cache->hdget('revision', 1, 'getRevision');
	if($interstitial['rev'] && $interstitial['rev'] > $userData['lastnotification'])
	{
		return true;
	}
	else
	{
		return false;
	}	
}

function getRevision(){
	global $wiki;

	$text = $wiki->getPage("/SiteText/sitenotifications");

	return array('output' => $text['output'], 'rev' => $text['revision']);
}