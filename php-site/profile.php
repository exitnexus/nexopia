<?

	$login=0;

	require_once("include/general.lib.php");

	$locations = new category( $configdb, "locs");
	$interests = new category( $configdb, "interests");

	// get the request type from the user along with the parameters
	$requestType = getREQval('requestType', 'string');
	$requestParams = getREQval('requestParams', 'array');
	$pageNumber = getREQval('page', 'string');

	if ($pageNumber != "")
		$requestParams['pageNumber'] = getREQval('page', 'string') + 1;

	// init sanitized array
	$sanatizedRequestParams = Array();

	// define which values in requestParams are check boxes (boolean), and correctly sanatize the values in them
	$booleanValues = Array('displayList', 'singleUsers');
	foreach ($booleanValues as $paramKey) {
		if (isset($requestParams[$paramKey]) === true && ($requestParams[$paramKey] == 'y' || $requestParams[$paramKey] == 'on'))
			$sanatizedRequestParams[$paramKey] = true;
		else
			$sanatizedRequestParams[$paramKey] = false;
	}

	// define which values in requestParams are expected to be integers and correctly sanatize values in them
	// (converting to INT types or setting to false if there are no values present)
	$integerValues = Array('ageRangeMin', 'ageRangeMax', 'location', 'interest', 'active', 'pic', 'sexuality', 'pageNumber');
	foreach ($integerValues as $paramKey) {
		if (isset($requestParams[$paramKey]) === true && $requestParams[$paramKey] != "0" && $requestParams[$paramKey] != "") {
			$sanatizedRequestParams[$paramKey] = intval($requestParams[$paramKey]);
		} else {
			$sanatizedRequestParams[$paramKey] = false;
		}
	}

	// define which values in requestParams are strings, and simply pass them into into the sanatized array (this insures
	// that no values which should not be here end up here)
	$stringValues = Array('sex', 'nameScope', 'userName');
	foreach ($stringValues as $paramKey) {
		if (isset($requestParams[$paramKey]) === true && $requestParams[$paramKey] != "") {
			if($paramKey == 'userName'){
				if(isValidEmail($requestParams[$paramKey])){
					$sanatizedRequestParams['userEmail'] = $requestParams[$paramKey];
					$sanatizedRequestParams[$paramKey] = false;
					$requestType = "userEmail";
				}
				else{
					$msgs->clearMsgs();
					$sanatizedRequestParams['userEmail'] = false;
					$sanatizedRequestParams[$paramKey] = $requestParams[$paramKey];
				}
			}
			else{
				$sanatizedRequestParams[$paramKey] = $requestParams[$paramKey];
			}
		} else {
			$sanatizedRequestParams[$paramKey] = false;
		}
	}

	// setup array of the user account flags
	$accountFlags = array();
	$accountFlags['plus'] = $userData['premium'];
	$accountFlags['loggedIn'] = $userData['halfLoggedIn'];
	$accountFlags['debug'] = $userData['debug'];

	function checkDoS()
	{
		global $userData, $cache, $sanatizedRequestParams;
		// handle search DoS protection here before calling processRequest
		if($userData['userid'] != 0){
			$limit = $cache->get("searchlimit-$userData[userid]");

			if($limit){
				noSearchResults("DoSProtection", $sanatizedRequestParams);
				exit;
			}
			$cache->put("searchlimit-$userData[userid]", 1, 1);
		}
	}

	// call processRequest to process the users request
	processRequest($requestType, $sanatizedRequestParams, $accountFlags);

	// terminate script execution here
	exit;


// This function simply contains a switch to call the correct function for the request type
function processRequest (
	$requestType,	// I: type of user request (from POST/GET requestType value)
	$requestParams,	// I: parameters for user request (from POST/GET requestParams array)
	$accountFlags	// I: array containing user account flags (populated if the user happens to be logged in)
) {
	global $userData;
	switch($requestType) {
		case "onlineByPrefs":
			onlineByPrefs($requestType, $requestParams, $accountFlags);
			break;
		case "displayAdvSearch":
			displayAdvSearch($accountFlags);
			break;
		case "spotLightHist":
			userSearchQuery('SPOTLIGHT', $requestType, $requestParams, $accountFlags);
			break;
		case "newUsers":
			userSearchQuery('NEWUSERS', $requestType, $requestParams, $accountFlags);
			break;
		case "bday":
			userSearchQuery('BDAY', $requestType, $requestParams, $accountFlags);
			break;
		case "query":
			checkDoS();
			userSearchQuery('USERSEARCH', $requestType, $requestParams, $accountFlags);
			break;
		case "fullUserName":
			checkDoS();
			userSearchFullUname($requestType, $requestParams);
			break;
		case "userEmail":
			checkDoS();
			userSearchEmail($requestType, $requestParams);
			break;
		case "mine":
			if ($userData['halfLoggedIn'])
			{
				displayProfileByUid($userData['userid']);
				break;
			}
			// deliberate fallthrough, if not logged in just do a random search.
		case "randomByPrefs": // deliberate fallthrough
		default:
			if ($uid = trim(getREQval('uid', 'string', false)))
			{
				if (!is_numeric($uid))
					$uid = getUserID($uid);
				if (!$uid)
					return noSearchResults('', '');

				displayProfileByUid($uid, getREQval('picid', 'integer', 0));
			} else {
				checkDoS();
				randomByPrefs($requestType, $requestParams, $accountFlags);
			}
			break;
	}
}
/* END FUNCTION processRequest */


// This is a function to handle any cases where the request type or parameters are invalid
// presently it will simply re-direct to the 404 handler runpage.php
function invalidRequest () {
	header("Location: /runpage.php");
	return true;
}


// This function is called when theres no search results from a users search and displays the corresponding template
// to handle no search results
function noSearchResults (
	$requestType,		// I: request which returned no results
	$requestParams		// I: request params used to form search
) {
	global $initOutput;

	switch ($requestType) {
		case "spotLightHist":
			$message = "No one has been spotlighted today";
			break;
		case "newUsers":
			$message = "There were no new users today";
			break;
		case "bday":
			$message = "No one has a b-day today";
			break;
		case "query":
			$message = "No results found. Please broaden your search or search again";
			break;
		case "DoSProtection":
			$message = "You are limited to 1 search per second, please wait and try again";
			break;
		default:
			$message = "No results found. Please broaden your search or search again";
			break;
	}

	$template = new template('userSearchQuery/noSearchResults');
	$template->set('message', $message);
	echo $initOutput;
	$template->display();
	return true;
}


/*
 * This simply displays the advanced search query page if the user happens to have plus, and sets the default query values
 * on that pages template before displaying
 */
function displayAdvSearch (
	$accountFlags	// I: user account flags passed from processRequest
) {
	// INIT global variables
	global $initOutput, $requestType, $requestParams, $rap_pagehandler;

	// check to insure the user has plus
	if ($accountFlags['plus'] !== true) {
		invalidRequest();
		return true;
	}

	// get values for the output template for all the user search options
	$menuOptions = new userSearchMenuOptions(true, 'displayAdvSearch', $requestType, $requestParams);

	// init output template
	$template = new template('userSearchQuery/advancedSearch');

	// set template values
	$template->set('selectNameScope', $menuOptions->nameScopeSelect);
	$template->set('user', $menuOptions->searchName);
	$template->set('minage', $menuOptions->searchMinAge);
	$template->set('maxage', $menuOptions->searchMaxAge);
	$template->set('selectSex', $menuOptions->sexSelect);
	
	$locationAutocomplete = $rap_pagehandler->subrequest(null, "GetRequest", "/autocomplete/location", array("location_id_field_id"=>"requestParams[location]"), "Public");
	
	$template->set('selectLocation', $locationAutocomplete->get_reply_output());
	$template->set('selectInterests', $menuOptions->interestSelect);
	$template->set('selectActivity', $menuOptions->activitySelect);
	$template->set('selectPictures', $menuOptions->pictureSelect);
	$template->set('selectSexuality', $menuOptions->sexualitySelect);
	$template->set('checkSingle', $menuOptions->singleCheck);
	$template->set('checkShowList', $menuOptions->listCheck);

	// send init and template output to browser
	echo $initOutput;
	$template->display();
	return true;
}



// This function handles the search queries of various types using the user search class
function userSearchQuery (
	$searchType,	// I: search type
	$requestType,	// I: request type
	$requestParams,	// I: user search request parameters passed from processRequest
	$accountFlags	// I: user account flags passed from processRequest
) {

	// set the default number of results per page in list mode
	$resultsPerPage = "25";

	// setup how requestParams maps onto the user search object methods for setting request parameters
	// the key is the method used to set the search parameter, while the array of values are the names of keys
	// in the requestParams array
	$paramMap = Array();
	$paramMap['setSingle'] = Array('singleUsers');
	$paramMap['setAgeRange'] = Array('ageRangeMin', 'ageRangeMax');
	$paramMap['setLocation'] = Array('location');
	$paramMap['setInterest'] = Array('interest');
	$paramMap['setActivity'] = Array('active');
	$paramMap['setPictures'] = Array('pic');
	$paramMap['setSexuality'] = Array('sexuality');
	$paramMap['setSex'] = Array('sex');
	$paramMap['setUname'] = Array('userName', 'nameScope');


	// first check of the user has plus, and if so allow them to do a username match type search, if not just simply
	// remove the search parameter from requestParams.
	if ($accountFlags['plus'] !== true) {
		$requestParams['nameScope'] = false;
		$requestParams['userName'] = false;
	}

	if (strtolower($requestParams['sex']) == "both")
		// if sex is set to both, we can also remove that parameter (since this means its not a factor in the search)
		$requestParams['sex'] = false;
	elseif (strtolower($requestParams['sex']) == "male")
		$requestParams['sex'] = "Male";
	elseif (strtolower($requestParams['sex']) == "female")
		$requestParams['sex'] = "Female";
	else
		$requestParams['sex'] = false;

	// set the result type based on if display list is checked or not, as well as setting how the search method is called
	if ($requestParams['displayList'] === true) {
		$resultType = "LIST";
		$searchExecParams = Array($requestParams['pageNumber'], $resultsPerPage);
	} else {
		$resultType = "RAND";
		$searchExecParams = Array('1');
	}

	// now create the correct userSearch object for the requested type of search
	$userSearch = new userSearch($searchType, $resultType, $accountFlags['debug']);


	// set parameters for the search on the object
	foreach ($paramMap as $setMethod => $methodParams) {
		$paramArray = Array();
		$execMethod = true;
		foreach ($methodParams as $paramKey) {
			if ($requestParams[$paramKey] !== false)
				$paramArray[] = $requestParams[$paramKey];
			else
				$execMethod = false;
		}

		if ($execMethod === true)
			call_user_func_array(array(&$userSearch, $setMethod), $paramArray);
	}

	// now exec the search method
	$searchResults = call_user_func_array(array(&$userSearch, 'search'), $searchExecParams);

	// add debug output if we are in debug mode
	if ($searchResults->debugOutput !== false)
		formatDebugOutput($searchResults->getFormattedDebugOutput(), $searchType, $requestType, $requestParams);

	// check if there are no results (if theres no results, searchResults will be set to false
	if ($searchResults->totalResults < 1) {
		noSearchResults($requestType, $requestParams);
		return true;
	}

	// okay now call the correct handler function to handle the search results object for the search type
	if ($resultType == "LIST")
		displayList($searchResults, $requestType, $requestParams);

	if ($resultType == "RAND")
		displayRandom($searchResults, $requestType, $requestParams);

	return true;
}


// This function handles displaying the list type search results
function displayList (
	$searchResults,	// I: returned search results object
	$requestType,	// I: type of request the search results were returned from
	$requestParams	// I: request params used to form search
) {
	// INIT globals
	global $config, $initOutput;

	// decide if the age select on the header should be displayed
	if ($requestType == "bday" || $requestType == "newUsers") {
		$displayAgeSelect = true;
		$menuOptions = new userSearchMenuOptions(true, 'displayList', $requestType, $requestParams);
	} else {
		$displayAgeSelect = false;
		$menuOptions = false;
	}

	if($requestParams['displayList'])
		$requestParams['displayList'] = 'y';

	// re-set displayList to y and remove the page number parameter
	$requestParams['displayList'] = 'y';
	unset($requestParams['pageNumber']);

	// now deal with generating the page list at the bottom
	$pageListParams = Array();
	$pageListParams[] = "requestType=".$requestType;
	foreach($requestParams as $paramName => $paramValue) {
		if ($paramValue !== false)
			$pageListParams[] = "requestParams[".$paramName."]=".$paramValue;
	}

	$pageListParams = implode("&", $pageListParams);
	$pageList = "Page: " . pageList("/profile.php?".$pageListParams, $searchResults->curPageNumber - 1, $searchResults->pages, 'header');

	// init output template
	$template = new template('userSearchQuery/searchResultList');

	// set template values
	$template->set('requestType', $requestType);
	$template->set('menuOptions', $menuOptions);
	$template->set('requestParams', $requestParams);
	$template->set('displayAgeSelect', $displayAgeSelect);
	$template->set('thumbWidth', $config['thumbWidth']);
	$template->set('searchResults', $searchResults);
	$template->set('col1size', ceil(count($searchResults->results)/2));
	$template->set('pageList', $pageList);

	// send init and template output to browser
	echo $initOutput;
	$template->display();
	return true;
}

// This function handles displaying single random search results (re-direct streight to users profile)
function displayRandom (
	$searchResults,	// I: returned search results object
	$requestType,	// I: type of request the search results were returned from
	$requestParams	// I: request params used to form search
) {

	// there should be one result in the result object, fetch it
	$result = $searchResults->getResult();

	// display the profile for the UID
	displayProfileByUid($result['userId']);

	return true;
}


// This function handles displaying single random search results (re-direct streight to users profile)
function userSearchFullUname (
	$requestType,	// I: type of request the search results were returned from
	$requestParams	// I: request params used to form search
) {
	if ($requestParams['userName'] === false) {
		noSearchResults($requestType, $requestParams);
		return true;
	}

	$userId = getUserId($requestParams['userName']);
	if ($userId !== false)
		displayProfileByUid($userId);
	else
		noSearchResults($requestType, $requestParams);

	return true;
}

function userSearchEmail($requestType, $requestParams)
{
	if($requestParams['userEmail'] === false){
		noSearchResults($requestType, $requestParams);
		return true;
	}
	
	$userId = getUserIDByEmail($requestParams['userEmail']);
	
	if($userId !== false){
		$userInfo = getUserInfo($userId);
	}
	
	if($userId !== false && $userInfo['searchemail'] == 'y')
		displayProfileByUid($userId);
	else
		noSearchResults($requestType, $requestParams);
		
	return true;
}


// This function handles displaying a users profile by UID, presently it redirects to profile.php passing UID
function displayProfileByUid (
	$uid,	// I: UID of profile to display
	$picid = 0
) {
	displayUser($uid, $picid);
	return true;
}


// This function handles displaying random users when a user clicks on Users on the top bar
// it will check to see if their logged in, and if so than it will use their preferences to form
// part of the search, otherwise it will just simply select a random user
function randomByPrefs (
	$requestType,	// I: type of request thats being processed
	$requestParams,	// I: request params used to form search
	$accountFlags	// I: account flags array
) {

	// make sure display list is set to false
	$requestParams['displayList'] = false;

	// use the userSearchMenuOptions object to get defaults for parameters since they tend to be set here
	if ($accountFlags['loggedIn'] === true) {
		$menuDefaults = new userSearchMenuOptions(false, 'randomByPrefs', $requestType, $requestParams);
		$requestParams['ageRangeMin'] = $menuDefaults->searchMinAge;
		$requestParams['ageRangeMax'] = $menuDefaults->searchMaxAge;
		$requestParams['sex'] = $menuDefaults->searchSex;
		$requestParams['location'] = $menuDefaults->searchLocation;
		$requestParams['active'] = 1; // active recently.
		userSearchQuery('USERSEARCH', $requestType, $requestParams, $accountFlags);
		return true;
	}

	// okay if the user is not logged in, just simply search the entire user base for random users
	// with no constraints
	randomUserNoParams($requestType, $requestParams, $accountFlags);

	return true;
}


// This function simply returns a random user from the database using no search parameters
function randomUserNoParams (
	$requestType,	// I: type of request thats being processed
	$requestParams,	// I: request parameters
	$accountFlags   // I: account flags array
) {
	$userSearch = new userSearch('ALLUSERS', 'RAND', $accountFlags['debug']);
	$searchResults = $userSearch->search('1');

	if ($searchResults === false) {
		noSearchResults($requestType, $requestParams);
		return true;
	}

	displayRandom($searchResults, $requestType, $requestParams);
	return true;
}


// This function handles users who are online, filtering by preferences if the user is actually infact logged on
function onlineByPrefs (
	$requestType,	// I: type of request thats being processed
	$requestParams,	// I: request params used to form search
	$accountFlags	// I: account flags array
) {

	// make sure display list is set to true and active set to 2
	$requestParams['displayList'] = true;
	$requestParams['active'] = 2;


	// use the userSearchMenuOptions object to get defaults for parameters since they tend to be set here
	if ($accountFlags['loggedIn'] == true) {
		$menuDefaults = new userSearchMenuOptions(false, 'onlineByPrefs', $requestType, $requestParams);
		$requestParams['ageRangeMin'] = $menuDefaults->searchMinAge;
		$requestParams['ageRangeMax'] = $menuDefaults->searchMaxAge;
		if ($requestParams['sex'] === false)
			$requestParams['sex'] = $menuDefaults->searchSex;
		$requestParams['location'] = $menuDefaults->searchLocation;
	}

	// now re-export the requestParams variable to the global scope so they get set on the side bar
	$newRequestParams = $requestParams;
	global $requestParams;
	$requestParams = $newRequestParams;

	// proceed with the query
	userSearchQuery('USERSEARCH', $requestType, $requestParams, $accountFlags);

	return true;
}


// This function takes the debug output, and formats in into an array which can be added as an item
// to the inline debug object
// it proceeds to pass ... [sic]
function formatDebugOutput (
	$debugOutput,		// I: debug output as returned in the result object
	$searchType,		// I: type of search that was executed in the module
	$requestType,		// I: request type string
	$requestParams		// I: request params array
) {
	// INIT global inlineDebug object in local scope
	global $inlineDebug;

	// Create frontend parameter dump array
	$paramDebug = Array();
	$paramDebug[] = "*** [userSearch construct search type] ==> $searchType";
	foreach ($requestParams as $paramName => $paramValue) {
		if ($paramValue === false)
			$paramValue = "&lt;NULL&gt;";
		$paramDebug[] = "[$paramName] ==> $paramValue";
	}

	$moduleSection = Array('title' => "Front End Params", 'lines' => $paramDebug);
	array_unshift($debugOutput, $moduleSection);

	// now add the item to the inline debug object and return
	$inlineDebug->addItem("User search front end module (requestType: $requestType)", $debugOutput);
	return true;
}
/* END FUNCTION formatDebugOutput */

function displayUser($uid, $picid = 0){ // either userid and picnum, or userid or picid
	global $config, $sort, $userData, $db, $usersdb, $usersdb, $cache, $mods, $locations, $weblog, $useraccounts, $wwwdomain;

	if($uid == 0)
		return false;
	
	//http redirect to the new profile in ruby-site
	$user = getUserInfo($uid);
	
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: http://". $wwwdomain . "/users/". urlencode($user["username"]));
	exit;
	
	//this code is no longer run as the profile display is taken care of by ruby-site
	$picnum = 0;

	if($picid != 0){ //gives picid
		$result = $usersdb->prepare_query("SELECT priority FROM pics WHERE userid = % AND id = #", $uid, $picid);
		$pic = $result->fetchrow();

		if($pic)
			$picnum = $pic['priority']-1; //$picnum is 0 based
	}

//$uid,$picnum are set now;


	$time = time();

//get info
	$user = getUserInfo($uid);

	if(!$user || ($user['state'] == 'frozen' && !($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))){
		incHeader(true,array('incSortBlock'));
		echo "No results Found";
		incFooter();
		exit;
	}

//ignored user
	if($user['hideprofile'] =='y' && (!$userData['loggedIn'] || isIgnored($uid, $userData['userid'], false, 0, true))){
		incHeader(true,array('incSortBlock'));

		if($userData['loggedIn'])
			echo "This user is ignoring you. If this user is harassing you, or otherwise breaking site rules, <a class=body href=/reportabuse.php?type=" . MOD_USERABUSE . "&id=$uid>Report Abuse</a>.";
		else
			echo "You must <a class=body href=/login.php?referer=profile.php?uid=$uid>login</a> to see this user's profile.";

		incFooter();
		exit;
	}

	$friends = getFriendsList($uid);

	$viewskey = "";

//update profile views
	if($userData['loggedIn']){
		if($userData['userid'] != $uid){
// check to see if the views are to be registered as anonymous
			$anonymousValue = 0;

// if the user has anonymousviews set to y, the view will be anonymous in all cases
// if its set to f, the views will be anonymous in all cases except for the user being on their friends list
			if($userData['premium'] && ($userData['anonymousviews'] == "y" || ($userData['anonymousviews'] == "f" && !isset($friends[$userData['userid']]))))
				$anonymousValue = 1;


		//frequency cap page view counting within the first view. Beyond that, only update on a second request with a key
			$limit = $cache->get("profileviewslimit-$uid");

			if(!$limit){

				// finally update the database along with the data in cache
				$usersdb->prepare_query("INSERT IGNORE INTO profileviews SET hits = 1, time = #, userid = %, viewuserid = #, anonymous = #", time(), $uid, $userData['userid'], $anonymousValue);
				if($usersdb->affectedrows()){
					$usersdb->prepare_query("UPDATE profile SET views = views + 1 WHERE userid = %", $uid);
					$user['views'] = $cache->incr("profileviews-$uid");
				}else{
					$usersdb->prepare_query("UPDATE profileviews SET hits = hits + 1, time = #, anonymous = # WHERE userid = % && viewuserid = #", time(), $anonymousValue, $uid, $userData['userid']);
					$user['views'] = $cache->get("profileviews-$uid");
				}

			}else{
			//if you hit the frequency cap, have a second page request update your stats
				$user['views'] = $cache->get("profileviews-$uid");

			//don't bother updating the profile update time if you've been there recently
				$userlimit = $cache->get("profileviewsuserlimit-$uid-$userData[userid]");
				if(!$userlimit)
					$viewskey = "<script src=/spacer.php?uid=$uid&anon=$anonymousValue&k=" . makeKey("$uid:secret:$anonymousValue:$time") . "&t=$time></script>";
			}

			$cache->put("profileviewslimit-$uid", 1, 120);
			$cache->put("profileviewsuserlimit-$uid-$userData[userid]", 1, 120);

		}else{
			$user['views'] = $cache->get("profileviews-$uid");

			if($userData['newcomments']){ // $userData['userid'] == $uid
				$usersdb->prepare_query("UPDATE users SET newcomments = 0 WHERE userid = %", $uid);
				$userData['newcomments']=0;

	//			$cache->put("newcomments-$userData[userid]", 0, $config['maxAwayTime']);
			}
		}
	}else{
		$user['views'] = $cache->get("profileviews-$uid");
	}

//get profile text
	$user2 = $cache->get("profile-$uid");

	if(!$user2 || !$user['views']){
		if(!$user2){
			$res = $usersdb->prepare_query("SELECT msn, icq, yahoo, aim, skin, showbday, showjointime, showactivetime, showprofileupdatetime, showpremium, profile, profileupdatetime, views, showlastblogentry, firstnamevisibility, lastnamevisibility FROM profile WHERE userid = %", $uid);
			$user2 = $res->fetchrow();

//			$sthBlocks = $usersdb->prepare_query("SELECT blocktitle, blockcontent, blockorder, permission FROM profileblocks WHERE userid = %", $uid);
//			$profBlocks = $sthBlocks->fetchrowset();

			$blocks = new profileBlocks($uid);
			$profBlocks = $blocks->getBlocks();

			foreach ($profBlocks as $index => $profBlock) {
				$profBlocks[$index]['nBlocktitle'] = removeHTML(trim($profBlock['blocktitle']));
				$profBlocks[$index]['nBlockcontent'] = wrap(parseHTML(smilies(cleanHTML($profBlock['blockcontent']))));
				unset($profBlocks[$index]['blockcontent']);
				unset($profBlocks[$index]['blocktitle']);
			}
			sortCols($profBlocks, SORT_ASC, SORT_NUMERIC, 'blockorder');
			$user2['profBlocks'] = $profBlocks;

//			$user2['nabout'] = nl2br(wrap(parseHTML(smilies($user2['about']))));
//			$user2['nlikes'] = nl2br(wrap(parseHTML(smilies($user2['likes']))));
//			$user2['ndislikes'] = nl2br(wrap(parseHTML(smilies($user2['dislikes']))));
			$user['views'] = $user2['views'];

			unset($user2['about'], $user2['likes'], $user2['dislikes'], $user2['views']);

			$cache->put("profile-$uid", $user2, 86400*7);
			$cache->put("profileviews-$uid", $user['views'], 86400*7);
		}else{
			$res = $usersdb->prepare_query("SELECT views FROM profile WHERE userid = %", $uid);
			$user['views'] = $res->fetchfield();

			$cache->put("profileviews-$uid", $user['views'], 86400*7);
		}
	}


	$user = $user2 + $user;
	unset($user2);


	if($userData['loggedIn'] && $userData['premium'] && $userData['userid'] != $uid && count($friends))
		$myfriends = getFriendsListIDs($userData['userid']);

	$isFriend = $userData['loggedIn'] && (isset($friends[$userData['userid']]) || $userData['userid']==$user['userid']);

//start output
	incHeader(600,array('incSortBlock','incSkyAdBlock','incNewestMembersBlock','incRecentUpdateProfileBlock'));

	echo injectSkin($user, 'profile');

echo "<script src=$config[jsloc]profile.js></script>";

//the profileview freq cap thing
echo $viewskey;

echo "<table border=0 width=100% align=center cellspacing=0 cellpadding=0>\n";

	echo "<tr>";
	echo "<td class=body colspan=2>";
	echo incProfileHead($user);
	echo "</td></tr>";


	echo "<tr><td class=body colspan=2>";

	$ignoring = $ignored = $enablecomments = false;
	$cols = 2;

	if($userData['loggedIn']){
		$ignored = isIgnored($uid, $userData['userid'], false);
		$ignoring = isIgnored($userData['userid'], $uid, false);
	}

	if (! $ignored)
		$cols++;

	if ($user['enablecomments'] == 'y' && (! $ignored || $mods->isAdmin($userData['userid'], 'listusers'))) {
		$enablecomments = true;
		$cols++;
	}

	if ($user['userid'] != $userData['userid'])
		$cols++;

	$width = floor(100.0 / $cols);

	echo "<table border=0 width=100%>";
	$form_key = secure_form_encrypt($userData['userid'], time());
	echo "<tr><td class=header align=center width=$width%><a class=header href=\"/users/".getUserName($userData['userid'])."/friends/add/$user[userid]?form_key=$form_key\"><b>Add as Friend</b></a></td>";

	if (! $ignored)
		echo "<td class=header align=center width=$width%><a class=header href=\"/messages.php?action=write&to=$user[userid]\"><b>Send Message</b></a></td>";

	if ($enablecomments)
		echo "<td class=header align=center width=$width%><a class=header href=/usercomments.php?id=$user[userid]#reply><b>Add Comment</a></td>";

	if ($user['userid'] != $userData['userid']) {
		if ($ignoring)
			echo "<td class=header align=center width=$width%><a class=header href=\"javascript:confirmLink('/messages.php?action=unignore&id=$user[userid]&k=" . makeKey($user['userid']) . "','unignore this user')\"><b>Unignore User</b></a>";
		else
			echo "<td class=header align=center width=$width%><a class=header href=\"javascript:confirmLink('/messages.php?action=ignore&id=$user[userid]&k=" . makeKey($user['userid']) . "','ignore this user')\"><b>Ignore User</b></a>";
	}

	echo "<td class=header align=center width=$width%><a class=header href=/reportabuse.php?type=" . MOD_USERABUSE . "&id=$user[userid]><b>Report Abuse</b></a></td></tr>";
	echo "</table>";


	echo "</td></tr>";

echo 	"<tr>\n";
echo 		"<td valign=top width=80 class=body>\n";


echo 			"<table border=0 width=100% height=100%>\n";

//Status
	echo "<tr><td align=center class=" . ($user['online']=='y' ? "online>Online" : "offline>Offline") . "<br></td></tr>";

	echo "<tr><td class=body align=center>";
	if(!$user['plus'] || !isset($user['hidehits']) || $user['hidehits'] == 'n' || $mods->isAdmin($userData['userid'])) // !isset there so no need to flush the cache
		echo "<b>" . number_format($user['views']) . " Hits</b><br>";
	if($user['plus'] && ($user['showpremium'] == 'y' || $mods->isAdmin($userData['userid'])))
		echo "<a class=body href=/plus.php>Plus Member</a><br>";
	if($user['signpic'] == 'y')
		echo "<a class=body href=/faq.php?cat=8&q=78>Verified User</a><br>";

	$tag = $mods->getAdminTag($uid);
	if($tag)
		echo implode("<br>", $tag) . "<br>";

	if(!$tag && $mods->isAdmin($userData['userid']) && $mods->isMod($uid, MOD_PICS))
		echo "Pic Mod<br>";

	echo "&nbsp;</td></tr>";


//admin
	if($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')){
		echo "<tr><td class=header><b>Admin</b></td></tr>";
		echo "<tr><td class=body>";

		if($user['state'] == 'frozen')
			echo "<b>Account Frozen!</b><br><br>";

		echo "<a class=body href=/adminuser.php?type=userid&search=$uid&k=" . makeKey($uid) . ">User Search</a><br>";
		if($mods->isAdmin($userData['userid'],"showip"))
			echo "<a class=body href=/adminuserips.php?uid=$uid&type=userid&k=" . makeKey($uid) . ">IP Search</a><br>";
		if($mods->isAdmin($userData['userid'],"loginlog"))
			echo "<a class=body href=/adminloginlog.php?col=userid&val=$uid&k=" . makeKey($uid) . ">Logins</a><br>";
		echo "<a class=body href=/adminabuselog.php?uid=" . urlencode($user['username']) . ">Abuse: $user[abuses]</a><br>";
		if($mods->isAdmin($userData['userid'],"editprofile"))
			echo "<a class=body href=/manageprofile.php?uid=$uid>Profile</a><br>";
		if($mods->isAdmin($userData['userid'],"editpictures"))
			echo "<a class=body href=/managepicture.php?uid=$uid>Pictures</a><br>";
		if($mods->isAdmin($userData['userid'])){
			echo "<a class=body href=/moderate.php?mode=1&uid=$uid>Mod Pics</a><br>";
			echo "<a class=body href=/moderate.php?mode=4&uid=$uid>Mod Ques</a><br>";
		}
		if($mods->isAdmin($userData['userid'],"editpreferences"))
			echo "<a class=body href=/prefs.php?uid=$uid>Prefs</a><br>";
		if($mods->isAdmin($userData['userid'],"listdeletedusers"))
			echo "<a class=body href=/admindeletedusers.php?type=username&uid=" . urlencode($user['username']) . ">Deleted</a><br>";

		echo "<br></td></tr>";
	}


//friendslist
	echo "<tr><td class=header><b>Friends</b></td></tr>";
	echo "<tr><td class=body>";
	if($userData['loggedIn'] && $userData['premium'] && $userData['userid'] != $uid){
		$friendsInCommon = 0;
		foreach($friends as $friendid => $username){
			if(isset($myfriends[$friendid]) || $friendid == $userData['userid']){
				echo "<a class=body href=/profile.php?uid=$friendid><b>$username</b></a><br>";
				$friendsInCommon++;
			}else{
				echo "<a class=body href=/profile.php?uid=$friendid>$username</a><br>";
			}
		}
	}else{
		foreach($friends as $friendid => $username)
			echo "<a class=body href=/profile.php?uid=$friendid>$username</a><br>";
	}
	echo "</td></tr>";

//End Friends list

echo 			"</table>";
echo 		"</td>\n";
echo 		"<td class=body valign=top>\n";
echo 			"<table border=0 cellspacing=0 width=100%>\n";
echo 				"<tr>";
echo 					"<td class=body align=center>";

//Name, Age, Sex


	echo "<font size=3><b>$user[username] ($user[age] year old $user[sex])</b></font><br>";



//End Name, Age, Sex

//Pic


	if($user['firstpic']){

		$pics = getUserPics($uid);

		if(count($pics)){
			$pics = array_values($pics); //reset to 0 based indexes

			if($user['firstpic'] != $pics[0]['id']){
				//correct firstpic
				//invalidate userinfo
			}

			echo "<div id=userpicdiv name=userpicdiv style=\"align: center\">";
			echo "<span id=hidediv style=\"position: absolute; width: 0px; height: 0px;\"><img src='$config[imageloc]empty.png' width=100% height=100%></span>";
			echo "<img name=userpic id=userpic src='" . $config['picloc'] . floor($user['userid']/1000) . "/" . weirdmap($user['userid']) . "/" . $pics[$picnum]['gallerypicid'] . ".jpg'>";
			echo "</div>";

			echo "<div id=picdesc name=picdesc>" . ($pics[$picnum]['description'] ? "<br>" . $pics[$picnum]['description'] : '' ) . "</div>";

			echo "<div id=piclinks name=piclinks>";
			for($i = 1; $i <= count($pics); $i++){
				if($i == $picnum+1)
					echo "Pic $i ";
				else
					echo "<a class=body href=/profile.php?uid=$uid&picid=" . $pics[$i-1]['id'] . " onClick=\"changepic(" . ($i-1) . "); return false;\">Pic $i</a> ";
			}
			echo "</div>";

			echo "<script>";

			foreach($pics as $line)
				echo "addPic('$line[id]', '" . $config['picloc'] . floor($user['userid']/1000) . "/" . weirdmap($user['userid']) . "/$line[id].jpg','" . addslashes($line['description']) . "');";
			
//			echo "document.images.userpic.onload = hidepics;";

			echo "</script>";
		}else{

			$usersdb->prepare_query("UPDATE users SET firstpic = 0 WHERE userid = %", $uid);
			$cache->remove("userinfo-$uid");

			echo "No pic available";
		}
	}else{
		echo "No pic available";
	}



//End Pic

echo 					"</td>";
echo 				"</tr>\n";
echo 				"<tr>";
echo 					"<td class=body valign=top>\n";

//profile

	$classes = array('body2','body');
	$i=0;

	echo "<table border=0 width=100%>\n";


//////////////////////////////////////////////
///////////// HACK!!!!!!!!!!! ////////////////
//////////////////////////////////////////////
	if($uid == 2225696){
		global $wiki;

		$musicstuff = $wiki->getPage("/SiteText/music/$user[username]");

		if($musicstuff['output']){
			echo "<tr><td class=body colspan=2>\n";
			echo $musicstuff['output'];
			echo "\n</td></tr>\n";
		}
	}
//////////////////////////////////////////////
///////////// /HACK!!!!!!!!!!! ///////////////
//////////////////////////////////////////////

	echo "<tr><td colspan=2 class=header align=center><b>Basics</b></td></tr>\n";

	echo "<tr><td class=" . $classes[$i = !$i] . " width=30%><b>Username:</b></td><td class=" . $classes[$i] . " width=70%>$user[username]</td></tr>\n";
	
	$firstNameVisible = isVisibleTo($user['userid'], $userData['userid'], $user['firstnamevisibility']);
	$lastNameVisible = isVisibleTo($user['userid'], $userData['userid'], $user['lastnamevisibility']);
	// Real names
	$realName = array();
	if ($firstNameVisible)
		$realName[] = $user['firstname'];
	if ($lastNameVisible)
		$realName[] = $user['lastname'];
		
	if ($firstNameVisible || $lastNameVisible)
	{
		echo "<tr><td class=" . $classes[$i = !$i] . " width=30%><b>Real Name:</b></td><td class=" . $classes[$i] . 
			" width=70%>".implode(" ", $realName)."</td></tr>\n";
	}
	
	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Age:</b></td><td class=" . $classes[$i] . ">$user[age]</td></tr>\n";
	if($user['showbday']=='y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Date of Birth:</b></td><td class=" . $classes[$i] . ">" . gmdate("F j, Y",$user['dob']) ."</td></tr>\n";
	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Sex:</b></td><td class=" . $classes[$i] . ">$user[sex]</td></tr>\n";
//	echo "<tr><td class=" . $classes[$i = !$i] . "><b>Location:</b></td><td class=" . $classes[$i] . ">" . $locations->getCatName($user['loc']) . "</td></tr>\n";

	// $locs = $locations->makeroot($user['loc']);

	// $locnames = array();
	// foreach($locs as $loc)
		// $locnames[] = $loc['name'];
	// array_shift($locnames); //get rid of Home

	// echo "<tr><td class=" . $classes[$i = !$i] . "><b>Location:</b></td><td class=" . $classes[$i] . ">" . implode(" > ", $locnames) . "</td></tr>\n";

	if($userData['loggedIn'] && $userData['premium'] && $userData['userid'] != $uid)
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Friends in Common:</b></td><td class=" . $classes[$i] . ">$friendsInCommon</td></tr>\n";

	if($user['showjointime'] == 'y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers')))
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Join Date:</b></td><td class=" . $classes[$i] . ">" . userdate("M j, Y g:i:s a",$user['jointime']) . "</td></tr>\n";

	if($user['profileupdatetime'] && ($user['showprofileupdatetime'] == 'y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers'))))
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Profile Update Time:</b></td><td class=" . $classes[$i] . ">" . userdate("M j, Y g:i:s a", $user['profileupdatetime']) . "</td></tr>\n";

	if($user['showactivetime'] == 'y' || ($userData['loggedIn'] && $mods->isAdmin($userData['userid'],'listusers'))){
		echo "<tr><td class=" . $classes[$i = !$i] . "><b>Active Time:</b></td><td class=" . $classes[$i] . ">";
		if($user['online'] == 'y'){
			echo "User is online";
		}elseif($user['activetime'] == 0){
				echo "Never";
		}else{
			$deltat = time() - $user['activetime'];
			if($deltat < 3600){
				echo "Within the past hour";
			}elseif($deltat < 3600*1.5){
				echo "1 hour ago";
			}elseif($deltat < 86400){
				echo round($deltat/3600) . " hours ago";
			}elseif($deltat < 86400*30){
				echo round($deltat/86400) . " days ago";
			}else{
				$months = round($deltat/(86400*30.5));
				echo "$months month" . ($months > 1 ? "s" : "") . " ago";
			}
		}
		echo "</td></tr>\n";
	}

	if($userData['loggedIn']){
		if($mods->isAdmin($userData['userid'],'listusers') && $mods->isAdmin($userData['userid'],'showemail')){
			echo "<tr><td colspan=2 class=header align=center><b>Admin</b></td></tr>\n";

			echo "<tr><td class=" . $classes[$i = !$i] . "><b>Email:</b></td><td class=" . $classes[$i] . ">" . $useraccounts->getEmail($user['userid']) . "</td></tr>\n";

			if($user['plus']){
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>Plus Time remaining:</b></td><td class=" . $classes[$i] . ">" . number_format(($user['premiumexpiry'] - time())/86400,2) . " Days</td></tr>\n";
				echo "<tr><td class=" . $classes[$i = !$i] . "><b>Plus Expiry Date:</b></td><td class=" . $classes[$i] . ">" . userDate("F j, Y, g:i a", $user['premiumexpiry']) . "</td></tr>\n";
			}
		}

		if($isFriend || $mods->isAdmin($userData['userid'],'listusers')){
			$statuses = $usersdb->prepare_query("SELECT * FROM status WHERE userid = % AND expiry > #", $user['userid'], time())->fetchrowset();
			if(count($statuses)){
				sortCols($statuses, SORT_DESC, SORT_NUMERIC, 'creation');
				$used = array();
				$names = getStatusNames();

				echo "<tr><td colspan=2 class=header align=center><b>Status</b></td></tr>\n";
				foreach($statuses as $status){
					if(!isset($used[$status['type']]))
						echo "<tr><td class=" . $classes[$i = !$i] . "><b>" . $names[$status['type']] . "</b></td><td class=" . $classes[$i] . ">$status[message]</td></tr>\n";
					$used[$status['type']] = true;
				}
			}
		}

		if(!empty($user['icq']) || !empty($user['msn']) || !empty($user['yahoo']) || !empty($user['aim'])){

			echo "<tr><td colspan=2 class=header align=center><b>Contact</b></td></tr>\n";

			if($isFriend || $mods->isAdmin($userData['userid'],'listusers')){
				if(!empty($user['icq']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>ICQ:</b></td><td class=" . $classes[$i] . ">$user[icq]</td></tr>\n";
				if(!empty($user['msn']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>MSN:</b></td><td class=" . $classes[$i] . ">$user[msn]</td></tr>\n";
				if(!empty($user['yahoo']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>Yahoo:</b></td><td class=" . $classes[$i] . ">$user[yahoo]</td></tr>\n";
				if(!empty($user['aim']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>AIM:</b></td><td class=" . $classes[$i] . ">$user[aim]</td></tr>\n";
			}else{
				if(!empty($user['icq']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>ICQ:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
				if(!empty($user['msn']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>MSN:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
				if(!empty($user['yahoo']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>Yahoo:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
				if(!empty($user['aim']))
					echo "<tr><td class=" . $classes[$i = !$i] . "><b>AIM:</b></td><td class=" . $classes[$i] . ">You must be a friend to see this.</td></tr>\n";
			}
		}
	}

	// Social Groups
	global $groupmembers;
	
	$visibleUserGroups = array();
	$userGroups = $groupmembers->orderByGroupType($groupmembers->getGroupMembersForUser($user['userid']));
	foreach ($userGroups as $userGroup)
	{
		if ($userGroup->isVisibleTo($userData['userid']))
		{
			$visibleUserGroups[] = $userGroup;
		}
	}
	
	if (!empty($visibleUserGroups))
	{
		echo "<tr><td colspan=2 class=header align=center><b>Groups</b></td></tr>\n";
		
		$groupType = -1;
		$displayGroupType = "";
		foreach ($visibleUserGroups as $userGroup)
		{
			if ($userGroup->groupType() != $groupType)
			{
				$groupType = $userGroup->groupType();
				$displayGroupType = $userGroup->groupTypeName();
			}
	
			// $groupLocation = $locations->makeroot($userGroup->groupLocation());

			// $groupLocNames = array();
			// 			foreach($groupLocation as $loc)
			// 				$groupLocNames[] = $loc['name'];
			// 			array_shift($groupLocNames); //get rid of Home
	
			// echo "<tr valign='top'><td class=" . $classes[$i = !$i] . "><b>".$displayGroupType."</b></td><td class=" . $classes[$i] . ">".$userGroup->groupName()."<br />".
			// 	implode(", ", array_reverse($groupLocNames))."&#160;&#160;&#160;<i>".$userGroup->fromDate()." - ".$userGroup->toDate()."</i></td></tr>\n";
			// $displayGroupType = "";
		}
	}

	$profilequestions = getProfileQuestions();

	$prof = decodeProfile($user['profile']);

	$first = true;
	foreach($profilequestions as $qnum => $val){
		if($prof[$qnum] != '0'){
			if($first){
				$first = false;
				echo "<tr><td colspan=2 class=header align=center><b>Profile</b></td></tr>\n";
			}
			echo "<tr><td class=" . $classes[$i = !$i] . "><b>$val[question]:</b></td><td class=" . $classes[$i] . ">" . $val['answers'][$prof[$qnum]] . "</td></tr>\n";
		}
	}






	$userinterests = $cache->get("userinterests-$uid");

	if($userinterests === false){
		$res = $usersdb->prepare_query("SELECT interestid FROM userinterests WHERE userid = %", $uid);

		$userinterests = array();
		while($line = $res->fetchrow())
			$userinterests[] = $line['interestid'];

		$userinterests = implode(',', $userinterests); //could be blank

		$cache->put("userinterests-$uid", $userinterests, 86400*7);
	}

	if($userinterests){
		$userinterests = explode(',', $userinterests);
		$userinterests = array_combine($userinterests, $userinterests);

		echo "<tr><td colspan=2 class=header align=center><b>Interests</b></td></tr>\n";


		global $interests;

		$cats = $interests->makebranch(); //only main categories

		$first = true;
		$subcats = array();
		foreach($cats as $item){
			if(!isset($userinterests[$item['id']]))
				continue;

			if($item['depth'] == 1){
				if(!$first)
					echo implode(", ", $subcats) . "</td></tr>";
				$first = false;
				$subcats = array();

				echo "<tr><td class=" . $classes[$i = !$i] . " colspan=2>";
				echo "<b>$item[name]:</b> ";
			}else{
				$subcats[] = $item['name'];
			}
		}
		echo implode(", ", $subcats) . "</td></tr>";
	}

/*	if($user['nabout']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>About Me</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[nabout]</td></tr>\n";
	}

	if($user['nlikes']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>Likes</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[nlikes]</td></tr>\n";
	}

	if($user['ndislikes']!=""){
		echo "<tr><td class=header colspan=2 align=center><b>Dislikes</b></td></tr>";
		echo "<tr><td class=body colspan=2>$user[ndislikes]</td></tr>\n";
	}
*/


	// profile blocks - display blocks for which the user has permission to view.
	foreach ($user['profBlocks'] as $index => $profBlock) {
		if (
			strlen($profBlock['nBlockcontent']) && (
				$mods->isAdmin($userData['userid'], 'editprofile') ||
				$profBlock['permission'] == 'anyone' ||
				($profBlock['permission'] == 'loggedin' && $userData['loggedIn']) ||
				($profBlock['permission'] == 'friends' && ($userData['userid'] == $uid || isset($friends[$userData['userid']])))
			)
		) {
			echo "<tr><td class=\"header\" colspan=\"2\" align=\"center\"><strong>{$profBlock['nBlocktitle']}</strong></td></tr>";
			echo "<tr><td class=\"body\" colspan=\"2\">{$profBlock['nBlockcontent']}</td></tr>";
		}
	}


	$blogscope = WEBLOG_PUBLIC;
	if ($userData['loggedIn'])
		$blogscope = WEBLOG_LOGGEDIN;
	if ($isFriend)
		$blogscope = WEBLOG_FRIENDS;
	if ($userData['userid'] == $user['userid'])
		$blogscope = WEBLOG_PRIVATE;

	$lastentry = false;

	if ($user['showlastblogentry'] == 'y')
	{
		$userblog = new userblog($weblog, $user['userid']);
		$lastpage = $userblog->getPostList(0, $blogscope);
		foreach ($lastpage as $entryid => $entrytime) // only iterates at most once
		{
			if ($entrytime > (time() - 2*7*24*60*60))
				$lastentry = new blogpost($userblog, $entryid, $userData);
			break;
		}
	}

	if ($lastentry)
	{
		echo "<tr><td class=header colspan=2 align=center><b>Latest Blog Entry: <a class=header href=\"/weblog.php?uid=$user[userid]&id={$lastentry->entryid}\">{$lastentry->title}</a></b></td></tr>";
		echo "<tr><td class=body colspan=2>" . truncate($lastentry->getParsedText(), 800);
		echo "<br /><br />[<a class=body href=\"/weblog.php?uid=$user[userid]\">See more...</a>]</td></tr>\n";
	}

	echo "</table>\n";



//End profile

echo 					"</td>";
echo 				"</tr>";
echo 			"</table>\n";

echo 		"</td>";
echo 	"</tr>\n";

//start comments
	if($user['enablecomments']=='y'){

echo 	"<tr>";
echo 		"<td colspan=3 class=body>\n";




	echo "<table width=100% cellpadding=3>";
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=/usercomments.php?id=$user[userid]>Add/Display Comments</a></b></td></tr>\n";

	if($userData['loggedIn']){
		$comments = $cache->get("comments5-$uid");

		if($comments === false){
			global $usercomments;
			$res = $usercomments->db->prepare_query("SELECT authorid, time, nmsg FROM usercomments WHERE userid = % ORDER BY time DESC LIMIT 5", $uid);

			$comments = array();
			while($line = $res->fetchrow()){
				$comments[] = $line;
			}

			$cache->put("comments5-$uid", $comments, 86400*7);
		}

		$authorids = array();
		foreach ($comments as $line){
			$authorids[$line['authorid']] = $line['authorid'];
		}

		// get the userinfo objects of the users who have left comments.
		$info = getUserInfo($authorids);

		$missing_usernames = array();
		foreach($comments as $k => $v)
		{
			if (isset($info[$v['authorid']])){
				$comments[$k]['authorinfo'] = $info[$v['authorid']];
				$comments[$k]['author'] = $comments[$k]['authorinfo']['username'];
			} else {
				$missing_usernames[] = $v['authorid'];
			}
		}

		// if any of them didn't have userinfo (thus were deleted), get their username
		if ($missing_usernames)
		{
			$names = getUserName($missing_usernames);
			foreach ($comments as $k => $v)
			{
				if (!isset($comments[$k]['authorinfo']))
				{
					$comments[$k]['authorid'] = false;

					if ($names[$v['authorid']])
						$comments[$k]['author'] = $names[$v['authorid']];
					else {
						// make it obvious this didn't work.
						$comments[$k]['author'] = "unknown";
					}
				}
			}
		}

		if(count($comments)){
			foreach($comments as $line){

				echo "<tr>";
				echo "<td class=header>";
					if ($line['authorid'])
						echo "<a class=header href=/profile.php?uid=$line[authorid]>$line[author]</a>";
					else
						echo "$line[author]";
				echo "</td>";
				echo "<td class=header>";

				echo "<table width=100% class=header cellspacing=0 cellpadding=0><tr>";
				echo "<td class=header>";
					echo userdate("M j, Y g:i:s a", $line['time']);

				echo "</td><td class=header align=right>";
					if($line['authorid'])
						echo "<a class=header href=/userconversation.php?uid1=$user[userid]&uid2=$line[authorid]>Conversation</a>";
				echo "</td>";
				echo "</tr></table>";

				echo "</td>";
				echo "</tr>";

				$thumbwidth = $config['thumbWidth']; // /2;
				echo "<tr>";
				echo "<td class=body width=$thumbwidth valign=top>";
					if($line['authorid'] && isset($line['authorinfo']) && $line['authorinfo']['firstpic']){
						$imgloc = $config['thumbloc'] . floor($line['authorid']/1000) . "/" . weirdmap($line['authorid']) . "/{$line['authorinfo']['firstpic']}.jpg";
//						$imgloc = "http://images.nexopia.com/userpicsthumb/1809/1809018/71.jpg";
						echo "<a href=/profile.php?uid=$line[authorid]><img " . ($thumbwidth != $config['thumbWidth'] ? "width=$thumbwidth " : '') . "border=0 src=$imgloc /></a>";
					}else{
						echo "&nbsp;";
					}
				echo "</td>";
				echo "<td valign=top class=body>";
					echo $line['nmsg'] . "&nbsp;";
				echo "</td>";
				echo "</tr>";


/*
//similar to usercomments.php
				$thumbwidth = $config['thumbWidth']; // /2
				echo "<tr>";
				echo "<td class=body width=$thumbwidth valign=top>";

				if($line['authorid']){
					echo "<a class=body href=/profile.php?uid=$line[authorid]>$line[author]</a><br />";
					if($line['authorinfo'] && $line['authorinfo']['firstpic']){
						$imgloc = $config['thumbloc'] . floor($line['authorid']/1000) . "/" . weirdmap($line['authorid']) . "/{$line['authorinfo']['firstpic']}.jpg";
						$imgloc = "http://images.nexopia.com/userpicsthumb/1809/1809018/71.jpg";
						echo "<a href=/profile.php?uid=$line[authorid]><img width=$thumbwidth border=0 src=$imgloc /></a><br />";
					}
				}else{
					echo "$line[author]";
				}
				echo "</td>";
				echo "<td class=body valign=top>";
					echo $line['nmsg'] . "&nbsp;";
				echo "</td>";
				echo "</tr>";

				echo "<tr><td class=body2 colspan=2>";
				echo "<table width=100% class=body2 cellspacing=0 cellpadding=0><tr>";
				echo "<td align=left class=body2>";
					echo userdate("M j, Y g:i:s a",$line['time']);
				echo "</td><td align=right class=body2>";
					if($line['authorid'])
						echo "<a class=body href=/userconversation.php?uid1=$user[userid]&uid2=$line[authorid]><b>conversation</b></a>";
				echo "</td>";
				echo "</tr></table>";
				echo "</td>";
				echo "</tr>";
*/

/*
//original
				echo "<tr><td class=header>By: ";

				if($line['authorid'])	echo "<a class=header href=/profile.php?uid=$line[authorid]>$line[author]</a>";
				else					echo "$line[author]";

				echo "</td><td class=header>Date: " . userdate("M j, Y g:i:s a",$line['time']) . "</td>";
				echo "</tr>";

				echo "<tr>";
				echo "<td class=body colspan=2>";

				echo $line['nmsg'] . "&nbsp;";

				echo "</td></tr>\n";
		//		echo "<tr><td colspan=2 class=header2>&nbsp;</td></tr>";*/
			}
		}else{
			echo "<tr><td class=body colspan=2 align=center>No Comments</td>\n";
		}
	}else{
		echo "<tr><td class=body colspan=2 align=center>You must be logged in to see comments.</td>\n";
	}
	echo "<tr><td class=header colspan=2 align=center><b><a class=header href=/usercomments.php?id=$user[userid]>Add/Display Comments</a></b></td></tr>\n";
	echo "</table>\n";


echo 		"</td>";
echo 	"</tr>";
	}
//End comments

echo "</table>\n";



	incFooter();
	exit;
}
