<?
	$simplepage = 1;
	$simpleauth = true;
	$forceserver = true;

	require_once("include/general.lib.php");

	$template = new template('pictures/imgframe');
	if(isset($_GET['imgurl']) && isset($_GET['picid'])){
		$template->set('pictureData', true);
		$template->set('imgurl', htmlentities(getREQval('imgurl')));
		$template->set('picid', getREQval('picid', 'int'));
		// Todo: validate?
		$template->set('fullurl', htmlentities(getREQval('fullurl')));
	}else{
		$template->set('pictureData', false);
	}

	$template->display();

