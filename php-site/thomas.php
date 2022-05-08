

<?php

	$login=0;

	require_once("include/general.lib.php");
	
	$leftBlocks = array('incLoginBlock','incSpotlightBlock','incSortBlock','incNewestMembersBlock','incRecentUpdateProfileBlock'); // 'incSkyAdBlock',
	$rightBlocks= array('incTextAdBlock','incPollBlock','incActiveForumBlock','incPlusBlock');

	if($userData['loggedIn']){
		$sexes = $userData['defaultsex'];
		$sex = ($sexes == 'Male' ? 'm' : 'f');
		$minage = $userData['defaultminage'];
		$maxage = $userData['defaultmaxage'];
		$news = 'in';
	}else{
		$sexes = array("Male","Female");
		$sex = 'b';
		$minage = 14;
		$maxage = 30;
		$news = 'out';
	}

function panel($layout, $content){
	$templateX = new template("panel");
	$templateX->set("layout", $layout);
	$templateX->set("content", $content);
	return $templateX->toString();
}

function horiz_panel($content1, $content2){
	$templateX = new template("panel");
	$templateX->set("layout", "horizontal");
	$templateX->set("content1", $content1);
	$templateX->set("content2", $content2);
	return $templateX->toString();
}
function vert_panel($array){
	$templateX = new template("panel");
	$templateX->set("layout", "vertical");
	$templateX->set("contents", $array);
	return $templateX->toString();
}

	$template = new template("thomas");
	
	$template->set("limit_ads", $userData['limitads']);
	if(!$userData['limitads']){
		$bannertext = $banner->getbanner(BANNER_VULCAN);
		$template->set("bannertext", $bannertext);
		

		$bannertext = $banner->getbanner(BANNER_BIGBOX);
		$template->set("big_bannertext", $bannertext);
	}

	
	$arr = array(
		"Safety Safety Safety!", 
		horiz_panel("<IFRAME src=user_display.php frameborder=0 scrolling=no width=700px>INVALID!!!</IFRAME>"),
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello",
		"hello"
	);
	
	$vpane = vert_panel($arr);
	
	
	$template->set("content", panel("center",$vpane));
	$template->display();
	
?>






