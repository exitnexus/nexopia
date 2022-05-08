<?php
	$login = 1;
	require_once('include/general.lib.php');
	
	$bannerAdmin = $mods->isAdmin($userData['userid'], 'listbanners');
	
	if (!$bannerAdmin) {
		die("You don't have permission to view this.");
	}
	
	if ($action == "submit") {
		$radios = getREQval("radio", "array", array());
		foreach ($radios as $id => $approved) {
			$banner->db->prepare_query("UPDATE banners SET moded = ? WHERE id = #", ($approved ? 'approved' : 'denied'), $id);
		}
	}
	$banners = array();
	$result = $banner->db->query("SELECT * FROM banners WHERE moded = 'unchecked'");
	while ($line = $result->fetchrow()) {
		$line['display'] = $banner->getBannerID($line['id']);
		$banners[] = $line;
	}
	$template = new template('bannerclient/bannermod');
	$template->set('empty', empty($banners));
	$template->set("banners", $banners);
	$template->set("classes", array("body", "body2"));
	$template->display();
	
	