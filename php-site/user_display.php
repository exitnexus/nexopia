<?php
	
	require_once("include/general.lib.php");
	
	$userSearch = new userSearch('ALLUSERS', 'RAND', $accountFlags['debug']);
	$searchResults = $userSearch->search('10');
	
	$templateS = new template('user_display');
	$templateS->set('requestType', $requestType);
	$templateS->set('menuOptions', $menuOptions);
	$templateS->set('requestParams', $requestParams);
	$templateS->set('displayAgeSelect', $displayAgeSelect);
	$templateS->set('thumbWidth', $config['thumbWidth']);
	$templateS->set('searchResults', $searchResults);
	$templateS->set('pageList', $pageList);

	$templateS->display();

?>
