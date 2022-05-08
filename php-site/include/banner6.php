<?


/*

-save stats?
  -store/load hourly/daily stats to/from mysql?
  -replay logs??


frequency capping properly
-create an array of the size of the cap, init to 0s
-every hit, put the timestamp in the array, storing the index of the last addition
-if the next index is < time()-freqtime, it is valid.
-use the array as a circular list.

do cleanup anytime a run through the main loop does nothing.

*/

define("BANNER_PORT",	8435);

//sex defines
define("SEX_UNKNOWN",0);
define("SEX_MALE",   1);
define("SEX_FEMALE", 2);

//bannersize defines
define("BANNER_BANNER", 	1);
define("BANNER_LEADERBOARD",2);
define("BANNER_BIGBOX", 	3);
define("BANNER_SKY120", 	4);
define("BANNER_SKY160", 	5);
define("BANNER_BUTTON60",	6);
define("BANNER_VULCAN",		7);
define("BANNER_LINK",		8);

//bannertype defines
define("BANNER_IMAGE",	1);
define("BANNER_FLASH",	2);
define("BANNER_IFRAME",	3);
define("BANNER_HTML",	4);
define("BANNER_TEXT",	5);

//banner payment types
define("BANNER_CPM", 0);
define("BANNER_CPC", 1);
define("BANNER_INHERIT", 2);

define("BANNER_SLIDE_SIZE",	8);	// number of hours to use for the sliding average to calculate CPC -> eCPM rate

define("BANNER_MIN_CLICKTHROUGH", 0.0002);
define("BANNER_MAX_CLICKTHROUGH", 0.005);

define("BANNER_DAILY_HOUR", 12); // noon GMT = 6am MST

//class bannercampaign
class bannercampaign {
	public $id;
	public $clientid;
	public $clienttype;
	public $banners;	// array( $bannerid => $banner, ...)

//targetting
	public $age;		// array( 0 => $default, $age1 => $age1, ...)
	public $sex;		// array( SEX_MALE => t/f, SEX_FEMALE => t/f, SEX_UNKOWN => t/f)
	public $loc;		// array( 0 => $default, $loc1 => $loc1, ...);
	public $page;		// array( 0 => $default, $page1 => t/f, ...)
	public $interests;	// array( 0 => $default, $interest1 => t/f, ...)

	public $sizes; // array ( size => bool );

//limiting
	public $maxviews;
	public $maxclicks;
	public $viewsperday;
	public $minviewsperday;
	public $clicksperday;
	public $viewsperuser;
	public $userviewtimes;
	public $limitbyperiod;
	public $allowedtimes;
	public $limitbyhour; // true => limit by hour, false => limit by day
	public $credits;
	public $charge; //number of views charged to this credit pool that have not yet been committed to the database

	public $startdate;
	public $enddate;	//must be defined, use (2**31-1) for forever

//payment - these are default values, ignored if overridden in a banner
	public $payrate; // either cpm or cpc pricing
	public $paytype; // BANNER_CPM | BANNER_CPC

	public $enabled;

//constructor
	function __construct($vals, $numservers = 1){
		$this->id 			= $vals['id'];
		$this->userviewtimes = array();
		$this->charge = 0;
		$this->update($vals, $numservers);

	}

	function update($vals, $numservers = 1){
		global $banner;


		$this->sizes = array();

		$this->clientid = $vals['clientid'];
		$results = $banner->db->prepare_query("SELECT type FROM bannerclients WHERE id = #", $vals['clientid']);
		if ($line = $results->fetchrow()) {
			$this->clienttype = $line['type'];
		}
		unset($results);

		if($vals['sex'] == ''){
			$this->sex = array(SEX_UNKNOWN => true, SEX_MALE => true, SEX_FEMALE => true);
		}else{
			$this->sex = array(SEX_UNKNOWN => false, SEX_MALE => false, SEX_FEMALE => false);

			$temp = explode(",", $vals['sex']);
			foreach($temp as $v)
				$this->sex[$v] = true;
		}

		if($vals['allowedtimes'] == '') {
			$this->allowedtimes = new timetable('S-Y0-23');
		} else {
			$this->allowedtimes = new timetable($vals['allowedtimes']);
		}

		$this->age = array( 0 => true); //untargetted
		if($vals['age'] != ''){
			if($vals['age'][0] == '0') //includes logged out, is an ALL_EXCEPT
				$vals['age'] = substr($vals['age'], 2); //strip off 0,
			else
				$this->age = array( 0 => false); //is ONLY

			$temp = explode(',', $vals['age']);
			foreach($temp as $v)
				$this->age[$v] = !$this->age[0]; //set to opposite of default
		}

		$this->loc = array( 0 => true);
		if($vals['loc'] != ''){
			if($vals['loc'][0] == '0')
				$vals['loc'] = substr($vals['loc'], 2);
			else
				$this->loc = array( 0 => false);

			$temp = explode(',', $vals['loc']);
			foreach($temp as $v)
				$this->loc[$v] = !$this->loc[0];
		}

		$this->page = array( 0 => true);
		if($vals['page'] != ''){
			if($vals['page'][0] == '0')
				$vals['page'] = substr($vals['page'], 2);
			else
				$this->page = array( 0 => false);

			$temp = explode(',', $vals['page']);
			if(count($temp))
				foreach($temp as $v)
					$this->page[$v] = !$this->page[0];
		}

		$this->interests = array();
		if($vals['interests'] != ''){
			$temp = explode(',', $vals['interests']);
			foreach($temp as $v)
				$this->interests[$v] = true;
		}

		$this->maxviews 	= $vals['maxviews'];
		$this->maxclicks 	= $vals['maxclicks'];
		$this->minviewsperday	= ceil($vals['minviewsperday'] / $numservers);
		$this->viewsperday	= ceil($vals['viewsperday'] / $numservers);
		$this->clicksperday	= ceil($vals['clicksperday'] / $numservers);
		$this->viewsperuser = $vals['viewsperuser'];
		$this->limitbyhour	= ($vals['limitbyhour'] == 'y');
		$this->limitbyperiod = $vals['limitbyperiod'];
		$this->credits = $vals['credits'];
		$this->startdate	= $vals['startdate'];
		$this->enddate		= $vals['enddate'];

		$this->payrate 		= $vals['payrate'];
		$this->paytype		= $vals['paytype'];

		$this->enabled		= ($vals['enabled'] == 'y');

		$this->sizes = array();
		$this->banners = array();
		$result = $banner->db->prepare_query("SELECT * FROM banners WHERE campaignid = #", $this->id);
		while($line = $result->fetchrow()) {
			$newbanner = new banner($line, $numservers, $this);
			$this->banners[$newbanner->id] = $newbanner;
			$this->sizes[$newbanner->size] = 1;
		}
		unset($result);


	}

	function updateSizes() {
		global $banner;
		$this->sizes = array();
		$result = $banner->db->prepare_query("SELECT * FROM banners WHERE campaignid = #", $this->id);
		while($line = $result->fetchrow()) {
			$this->sizes[$line['bannersize']] = 1;
		}
		unset($result);
	}


	function getBannerID($id) {
		return $this->banners[$id];
	}

	function daily($debug) {
		foreach ($this->banners as $banner) {
			$banner->daily($debug);
		}
	}

	function hourly(& $db, $time, $debug) {
		foreach ($this->banners as $banner) {
			$banner->hourly($db, $time, $debug);
		}
		$this->pruneUserViews($time);
	}

	function minutely(& $db, $time, $debug) {
		foreach ($this->banners as $banner) {
			$banner->minutely($db, $time, $debug);
		}

		if($this->charge) {
			$db->prepare_query("UPDATE bannercampaigns SET credits = credits - # WHERE id = #", $this->charge, $this->id);
	}

		$result = $db->prepare_query("SELECT credits FROM bannercampaigns WHERE id = #", $this->id);
		if ($line = $result->fetchrow()) {
			$this->credits = $line['credits'];
		}
		unset($result);
		$this->charge = 0;

	}

	function dailyviews() {
		$dailyviews = 0;
		foreach($this->banners as $id => $banner) {
			$dailyviews += $banner->dailyviews;
		}
		return $dailyviews;
	}

	function dailyclicks() {
		$dailyclicks = 0;
		foreach($this->banners as $id => $banner) {
			$dailyclicks += $banner->dailyclicks;
		}
		return $dailyclicks;
	}

	function hit($bannerid, $userid, $time) {
		if ($this->viewsperuser) {
			$this->userviewtimes[$userid][] = $time;
			foreach ($this->userviewtimes[$userid] as $id => $viewtime) {
				if ($viewtime > $time-$this->limitbyperiod) {
					break; //we only need to process old entries
				} else {
					unset($this->userviewtimes[$userid][$id]);
					if (empty($this->userviewtimes[$userid])) {
						unset($this->userviewtimes[$userid]);
					}
				}
			}
		}
		$this->banners[$bannerid]->hit($userid, $time);
	}

	function views() {
		$views = 0;
		foreach($this->banners as $id => $banner) {
			$views += $banner->views;
		}
		return $views;
	}

	function clicks() {
		$clicks = 0;
		foreach($this->banners as $id => $banner) {
			$clicks += $banner->clicks;
		}
		return $clicks;
	}

	function passbacks() {
		$passbacks = 0;
		foreach($this->banners as $id => $banner) {
			$passbacks += $banner->passbacks;
		}
		return $passbacks;
	}

	//takes an array of ids and returns an averaged priority
	//discards an banner ids that are not part of the campaign
	//if no id is provided all banners in the campaign are used.
	function priority($userid, $ids = null, $time=0) {
		$i = 0;
		$totalpriority = 0;
		if ($ids) {
			foreach($ids as $id) {
				if (isset($this->banners[$id])) {
					$i++;
					$totalpriority += $this->banners[$id]->priority($userid, $time);
				}
			}
		} else {
			foreach($this->banners as $banner) {
				$i++;
				$totalpriority += $banner->priority($userid, $time);
			}
		}

		if($this->minviewsperday > $this->dailyviews()) {
			$totalpriority *= (10 - 4*max(0,min(2,(($this->dailyviews()/$this->minviewsperday)/((userdate("G",$time)+1)/24)))));
		}

		return $totalpriority / $i;
	}

	//takes an array of banner ids and returns a banner id based on priority
	//discards any banner ids that are not part of the campaign
	//if no id is provided all banners in the campaign are used.
	function getBanner($userid, $ids = null, $time=0) {
		$priorities = array();
		if ($ids) {
			foreach($ids as $id) {
				if (isset($this->banners[$id])) {
					$priorities[$id] = $this->banners[$id]->priority($userid, $time);
				}
			}
		} else {
			foreach($this->banners as $banner) {
				$priorities[$banner->id] = $banner->priority($userid, $time);
			}
		}
		return chooseWeight($priorities, false);
	}

	//Returns an array of the banners within the campaign which satisfy the criteria.
	//Returns false if campaign constraint fails, an empty array if campaign passes but no banners do.
	//First it checks campaign constraints and then checks individual banner constraints
	function valid($userid, $age, $sex, $loc, $interests, $page, $time, $size, $usertime, $debug){
		$debugLog = "";
		if ($debug) $debugLog .= "Checking campaign: $this->id";
		//if(!$this->enabled)
		//	return false;
		//date
		//if($this->startdate >= $time || ($this->enddate && $this->enddate <= $time))
		//	return false;

		//targetting
		//age
		if($this->age[0]){ //default true
			if( $age &&  isset($this->age[$age]) && !$this->age[$age]) {//is explicitely untargetted
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}else{ //default false
			if(!$age || !isset($this->age[$age]) || !$this->age[$age]) {//is explicitely targetted
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 1";
		//sex
		//if(!$this->sex[$sex])
		//	return false;

		//location
		if($this->loc[0]){ //default true
			if( $loc &&  isset($this->loc[$loc]) && !$this->loc[$loc]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}else{ //default false
			if(!$loc || !isset($this->loc[$loc]) || !$this->loc[$loc]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 2";
		//page
		if($this->page[0]){ //default true
			if( $page &&  isset($this->page[$page]) && !$this->page[$page]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}else{ //default false
			if(!$page || !isset($this->page[$page]) || !$this->page[$page]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 3";
		//interests
		if(count($this->interests)){
			if(count($interests) == 0) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
			$found = false;

			foreach($interests as $i){
				if(isset($this->interests[(int)$i])){
					$found = true;
					break;
				}
			}

			if(!$found) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 4";
		//time
		$day = gmdate("w", $usertime);
		$hour = gmdate("G", $usertime);

		if (!$this->allowedtimes->validHours[$day][$hour]) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 5";
	//frequency capping at the campaign level
	//this period (day/hour)
		if($this->viewsperday && $this->dailyviews() >= $this->viewsperday) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 6";
		if($this->clicksperday && $this->dailyclicks() >= $this->clicksperday) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 7";
	//views per user
		if($this->viewsperuser && isset($this->userviewtimes[$userid])) {
			end($this->userviewtimes[$userid]);
			$keyToCheck = key($this->userviewtimes[$userid]) - $this->viewsperuser + 1;
			if (isset($this->userviewtimes[$userid][$keyToCheck]) && $this->userviewtimes[$userid][$keyToCheck] >= ($time-$this->limitbyperiod)) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 8";
		//bannerDebug("Campaign valid: $this->id");
		$validBanners = false;
		//check individual banners for validity
		foreach($this->banners as $id => &$banner) {
			if ($banner->valid($userid, $age, $sex, $loc, $interests, $page, $time, $size, $usertime, $debug)) {
				//bannerDebug("Banner valid: $banner->id");
				$validBanners[] = $banner->id;
			}
		}
		if ($debug) bannerDebug($debugLog);
		return $validBanners;
	}

	function addBanner($banner) {
		if (isset($banner)) {
			$this->banners[$banner->id] = $banner;
		}
		$this->updateSizes();
	}
	function deleteBanner($id) {
		unset($this->banners[$id]);
		$this->updateSizes();
	}

	function pruneUserViews($time) {
		foreach ($this->userviewtimes as $uid => $userview) {
			foreach ($userview as $id => $viewtime) {
				if ($viewtime > $time-$this->limitbyperiod) {
					break; //we only need to process old entries
				} else {
					unset($this->userviewtimes[$uid][$id]);
					if (empty($this->userviewtimes[$uid])) {
						unset($this->userviewtimes[$uid]);
					}
				}
			}
		}
	}

}

//class banner
class banner{
	public $id;
	public $campaignid;
	public $campaign;  //reference to campaign this banner belongs to
	public $size;

//targetting
	public $age;		// array( 0 => $default, $age1 => $age1, ...)
	public $sex;		// array( SEX_MALE => t/f, SEX_FEMALE => t/f, SEX_UNKOWN => t/f)
	public $loc;		// array( 0 => $default, $loc1 => $loc1, ...);
	public $page;		// array( 0 => $default, $page1 => t/f, ...)
	public $interests;	// array( 0 => $default, $interest1 => t/f, ...)
	public $allowedtimes; // timetable object

//limiting - these increase specificity over campaigns, but cannot reduce it
	public $maxviews;
	public $maxclicks;
	public $viewsperday;
	public $minviewsperday;
	public $clicksperday;
	public $viewsperuser;
	public $limitbyhour; // true => limit by hour, false => limit by day
	public $limitbyperiod; //number of seconds in a viewing period
	public $startdate;
	public $enddate;	//must be defined, use (2**31-1) for forever
	public $credits;
	public $charge; //number of views charged to this credit pool that have not yet been committed to the database

//priority
	public $payrate; // either cpm or cpc pricing
	public $paytype; // BANNER_CPM | BANNER_CPC
	private $priority; //this should now be accessed through the priority function

//stats
	public $hour;

	public $hourlyviews;
	public $hourlyclicks;

	public $userviewtimes; //a two dimensional window of userid, window position => view time (used for frequency checks)

	public $dailyviews;
	public $dailyclicks;

	public $views;
	public $potentialviews;
	public $clicks;
	public $passbacks; //doesn't need daily/hourly stats, as it isn't used for targetting, freq capping, etc

	//public $userviews; //array(userid => views)

	public $enabled;
	public $moded;

//constructor
	function __construct($vals, $numservers = 1, $camp = null){
		$this->hour = 0;

		$this->hourlyviews 	= array();
		$this->hourlyclicks	= array();
		$this->userviewtimes = array();

		for($i = 0; $i < BANNER_SLIDE_SIZE; $i++){ //use a BANNER_SLIDE_SIZE hour window to calculate eCPM for CPC ads
			$this->hourlyviews[$i] = 0;
			$this->hourlyclicks[$i] = 0;
		}

		$this->dailyviews 	= 0;
		$this->dailyclicks	= 0;


		$this->views 		= 0;
		$this->potentialviews = 0;
		$this->clicks		= 0;
		$this->passbacks	= 0;
		$this->charge		= 0;

		$this->id 			= $vals['id'];
		$this->size 		= $vals['bannersize'];

		$this->update($vals, $numservers, $camp);
	}

	function update($vals, $numservers = 1, $camp = null){

		$this->campaignid = $vals['campaignid'];
		$this->campaign = $camp;

		if ($vals['allowedtimes'] != '') {
			$this->allowedtimes = new timetable($vals['allowedtimes']);
		} else {
			$this->allowedtimes = new timetable("S-Y0-23");
		}

		if($vals['sex'] == ''){
			$this->sex = array(SEX_UNKNOWN => true, SEX_MALE => true, SEX_FEMALE => true);
		}else{
			$this->sex = array(SEX_UNKNOWN => false, SEX_MALE => false, SEX_FEMALE => false);

			$temp = explode(",", $vals['sex']);
			foreach($temp as $v)
				$this->sex[$v] = true;
		}

		$this->age = array( 0 => true); //untargetted
		if($vals['age'] != ''){
			if($vals['age'][0] == '0') //includes logged out, is an ALL_EXCEPT
				$vals['age'] = substr($vals['age'], 2); //strip off 0,
			else
				$this->age = array( 0 => false); //is ONLY

			$temp = explode(',', $vals['age']);
			foreach($temp as $v)
				$this->age[$v] = !$this->age[0]; //set to opposite of default
		}

		$this->loc = array( 0 => true);
		if($vals['loc'] != ''){
			if($vals['loc'][0] == '0')
				$vals['loc'] = substr($vals['loc'], 2);
			else
				$this->loc = array( 0 => false);

			$temp = explode(',', $vals['loc']);
			foreach($temp as $v)
				$this->loc[$v] = !$this->loc[0];
		}

		$this->page = array( 0 => true);
		if($vals['page'] != ''){
			if($vals['page'][0] == '0')
				$vals['page'] = substr($vals['page'], 2);
			else
				$this->page = array( 0 => false);

			$temp = explode(',', $vals['page']);
			if(count($temp))
				foreach($temp as $v)
					$this->page[$v] = !$this->page[0];
		}

		$this->interests = array();
		if($vals['interests'] != ''){
			$temp = explode(',', $vals['interests']);
			foreach($temp as $v)
				$this->interests[$v] = true;
		}

		$this->maxviews 	= $vals['maxviews'];
		$this->maxclicks 	= $vals['maxclicks'];
		$this->viewsperday	= ceil($vals['viewsperday'] / $numservers);
		$this->minviewsperday	= ceil($vals['minviewsperday'] / $numservers);
		$this->clicksperday	= ceil($vals['clicksperday'] / $numservers);
		$this->viewsperuser = $vals['viewsperuser'];

		$this->limitbyhour	= ($vals['limitbyhour'] == 'y');
		$this->limitbyperiod = $vals['limitbyperiod'];

		$this->credits 		= $vals['credits'];
		$this->startdate	= $vals['startdate'];
		$this->enddate		= $vals['enddate'];

		$this->payrate 		= $vals['payrate'];
		$this->paytype		= $vals['paytype'];
		$this->priority		= $this->payrate();

		$this->enabled		= ($vals['enabled'] == 'y');
		$this->moded		= ($vals['moded'] == 'approved');

		if($this->maxviews && $this->maxviews <= $vals['views'])
			$this->enabled = false;

		if($this->maxclicks && $this->maxclicks <= $vals['clicks'])
			$this->enabled = false;
	}

	function hit($userid, $time){
		if ($this->viewsperuser) {
			$this->userviewtimes[$userid][] = $time;
			foreach ($this->userviewtimes[$userid] as $id => $viewtime) {
				if ($viewtime > $time-$this->limitbyperiod) {
					break; //we only need to process old entries
				} else {
					unset($this->userviewtimes[$userid][$id]);
					if (empty($this->userviewtimes[$userid])) {
						unset($this->userviewtimes[$userid]);
					}
				}
			}
		}

		if ($this->campaign->clienttype == "payinadvance" && $this->paytype() == BANNER_CPM) {
			if ($this->credits > $this->charge + $this->payrate()) { //charge the banner
				$this->charge += $this->payrate();
			} else { //charge the campaign everything the banner can't cover and then charge the banner the remainder
				$this->campaign->charge += $this->payrate() - ($this->credits - $this->charge);
				$this->charge += $this->credits - $this->charge;
			}
		}

		$this->hourlyviews[$this->hour]++;
		$this->dailyviews++;
		$this->views++;
	}

	function potentialHit() {
		$this->potentialviews++;
	}

	function click(){
		if ($this->campaign->clienttype == "payinadvance" && $this->paytype() == BANNER_CPC) {
			if ($this->credits > $this->charge + $this->payrate()) { //charge the banner
				$this->charge += $this->payrate();
			} else { //charge the campaign everything the banner can't cover and then charge the banner the remainder
				$this->campaign->charge += $this->payrate() - ($this->credits - $this->charge);
				$this->charge += $this->credits - $this->charge;
			}
		}
		$this->hourlyclicks[$this->hour]++;
		$this->dailyclicks++;
		$this->clicks++;
	}

	function passback($userid, $time){
		$this->passbacks++;
		if($this->viewsperuser) {
			for ($i=0; $i < $this->viewsperuser; $i++) {
				$this->userviewtimes[$userid][] = $time; //ie it's hit its limit, stop showing them this ad.
			}
			foreach ($this->userviewtimes[$userid] as $id => $viewtime) {
				if ($viewtime > $time-$this->limitbyperiod) {
					break; //we only need to process old entries
				} else {
					unset($this->userviewtimes[$userid][$id]);
					if (empty($this->userviewtimes[$userid])) {
						unset($this->userviewtimes[$userid]);
					}
				}
			}
		}
	}

	function minutely(& $db, $time, $debug){
		if($debug)
			bannerDebug("minutely " . $this->id . " " . $this->views . " " . $this->clicks . " " . $this->passbacks);



		//only update banners that have new info.
		if($this->views || $this->clicks || $this->passbacks || $this->charge) {
			$db->prepare_query("UPDATE banners SET lastupdatetime = #, views = views + #, potentialviews = potentialviews + #, clicks = clicks + #, passbacks = passbacks + #, credits = credits - # WHERE id = #", $time, $this->views, $this->potentialviews, $this->clicks, $this->passbacks, $this->charge, $this->id);
		}

		$result = $db->prepare_query("SELECT credits FROM banners WHERE id = #", $this->id);
		if ($line = $result->fetchrow()) {
			$this->credits = $line['credits'];
		}
		unset($result);

		$this->charge = 0;
		$this->views = 0;
		$this->potentialviews = 0;
		$this->clicks = 0;
		$this->passbacks = 0;
	}

	function hourly(& $db, $time, $debug){
		if($debug)
			bannerDebug("hourly " . $this->id);

//		$db->prepare_query("INSERT INTO bannerstats SET bannerid = ?, clientid = ?, time = ?, priority = ?, views = ?, clicks = ?", $this->id, $this->clientid, $time, $this->priority, $this->totalviews, $this->totalclicks);
//		$db->prepare_query("INSERT IGNORE INTO bannerstats (bannerid, clientid, time, views, clicks) SELECT id, clientid, lastupdatetime, views, clicks FROM banners WHERE id = ?", $this->id);
		$db->prepare_query("REPLACE INTO bannerstats (bannerid, time, views, potentialviews, clicks, passbacks) SELECT id, lastupdatetime, views, potentialviews, clicks, passbacks FROM banners WHERE id = #", $this->id); //bannerstats is UNIQUE on bannerid,time

		if($this->paytype() == BANNER_CPC){
			$sumviews = array_sum($this->hourlyviews);

			if($sumviews){
				$clickthrough = array_sum($this->hourlyclicks)*1000/$sumviews;

				$this->priority = $this->payrate()*$clickthrough; //effective CPM rate last hour
				$this->priority = max($this->priority, $this->payrate() * BANNER_MIN_CLICKTHROUGH); //min clickthrough
				$this->priority = min($this->priority, $this->payrate() * BANNER_MAX_CLICKTHROUGH); //max clickthrough
			}
		}

		$this->hour = ($this->hour + 1) % BANNER_SLIDE_SIZE;
		$this->hourlyviews[$this->hour] = 0;
		$this->hourlyclicks[$this->hour]= 0;

		$this->pruneUserViews($time);

		//if($this->limitbyhour)
		//	$this->userviews = array();
	}

	function pruneUserViews($time) {
		foreach ($this->userviewtimes as $uid => $userview) {
			foreach ($userview as $id => $viewtime) {
				if ($viewtime > $time- $this->limitbyperiod) {
					break; //we only need to process old entries
				} else {
					unset($this->userviewtimes[$uid][$id]);
					if (empty($this->userviewtimes[$uid])) {
						unset($this->userviewtimes[$uid]);
					}
				}
			}
		}
	}
	function daily($debug){
		$this->dailyviews = 0;
		$this->dailyclicks= 0;

		//$this->userviews = array();
	}

	//calculate the priority of the banner based on revenue, and view/click limits
	function priority($userid, $time){

		/*
			weighting based on
				(1 + priority) *
				(2 - (1-(time since last view/max view rate)) *
				(3 - ((views today)/(max views per day))) *
				(3 - ((clicks today)/(max clicks per day)))
		*/
		$modifiedPriority = $this->priority;
		//adjust by priority^2
		$modifiedPriority = 1 + $modifiedPriority;
		//adjust by number of times this user has seen it. Frequency-capped ads have a higher priority than non-frequency-capped ads
		if($this->viewsperuser) {
			$modifiedPriority *= (2 - (isset($this->userviewtimes[$userid]) && $this->limitbyperiod ? max(0, 1-((($time - end($this->userviewtimes[$userid]))/$this->limitbyperiod)*$this->viewsperuser)) : 0));
		}
		//adjust by number of times it has been seen today. Limited ads have higher priority than non-limited ads
		if($this->viewsperday) {
			$modifiedPriority *= (3 - ($this->dailyviews / $this->viewsperday ));
		}
		//adjust by number of times it has been clicked today. Limited ads have higher priority than non-limited ads
		if($this->clicksperday) {
			$modifiedPriority *= (3 - ($this->dailyclicks / $this->clicksperday ));
		}
		if($this->minviewsperday > $this->dailyviews) {
			$modifiedPriority *= (10 - 4*max(0,min(2,(($this->dailyviews/$this->minviewsperday)/((userdate("G",$time)+1)/24)))));
		}

		return $modifiedPriority;
	}

	function payrate() {
		if ($this->payrate >= 0) {
			return $this->payrate;
		} else {
			return $this->campaign->payrate;
		}
	}

	function paytype() {
		if ($this->paytype == BANNER_INHERIT) {
			return $this->campaign->paytype;
		} else {
			return $this->paytype;
		}
	}

	function valid($userid, $age, $sex, $loc, $interests, $page, $time, $size, $usertime, $debug = 0){
		$debugLog = "";
		if ($debug) $debugLog .= "Checking banner $this->id:";

		//bannerDebug("Testing banner: $this->id");
		if(!$this->enabled) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 1";
		//if(!$this->moded) {
		//	return false;
		//}

		//date
		if($this->startdate >= $time || ($this->enddate && $this->enddate <= $time)) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 2";
		//bannerDebug("testing size: $this->size == $size");
		//size
		if($this->size != $size) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 3";

		//targetting
		//age
		//bannerDebug("testing age");
		if($this->age[0]){ //default true
			if( $age &&  isset($this->age[$age]) && !$this->age[$age]) {//is explicitely untargetted
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}else{ //default false
			if(!$age || !isset($this->age[$age]) || !$this->age[$age]) { //is explicitely targetted
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 4";

		//sex
		//bannerDebug("testing sex");
		if(!$this->sex[$sex]) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 5";
		//location
		//bannerDebug("testing location");
		if($this->loc[0]){ //default true
			if( $loc &&  isset($this->loc[$loc]) && !$this->loc[$loc]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}else{ //default false
			if(!$loc || !isset($this->loc[$loc]) || !$this->loc[$loc]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 6";
		//page
		//bannerDebug("testing page");
		if($this->page[0]){ //default true
			if( $page &&  isset($this->page[$page]) && !$this->page[$page]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}else{ //default false
			if(!$page || !isset($this->page[$page]) || !$this->page[$page]) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 7";
		//interests
		//bannerDebug("testing interests");
		if(count($this->interests)){
			if(count($interests) == 0) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
			//*
			$found = false;

			foreach($interests as $i){
				if(isset($this->interests[(int)$i])){
					$found = true;
					break;
				}
			}

			if(!$found) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
			/*/
			$intersect = array_intersect($this->interests, $interests);
			if(count($intersect) == 0)
			return false;
			//*/
		}
		if ($debug) $debugLog .= " 8";
		//time
		//bannerDebug("testing time");
		$day = gmdate("w", $usertime);
		$hour = gmdate("G", $usertime);
		if (!$this->allowedtimes->validHours[$day][$hour]) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 9";
		//payment available
		//if ($this->campaign->clienttype == "payinadvance") {
		//	if ($this->credits + $this->campaign->credits < $this->payrate()) {
		//		return false;
		//	}
		//}

		//frequency capping
		//this period (day/hour)
		//bannerDebug("testing frequency capping");
		if($this->viewsperday && $this->dailyviews >= $this->viewsperday) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 10";
		if($this->clicksperday && $this->dailyclicks >= $this->clicksperday) {
			if ($debug) bannerDebug($debugLog);
			return false;
		}
		if ($debug) $debugLog .= " 11";
		//views per user
		if($this->viewsperuser && isset($this->userviewtimes[$userid])) {
			end($this->userviewtimes[$userid]);
			$keyToCheck = key($this->userviewtimes[$userid]) - $this->viewsperuser + 1;

			if (isset($this->userviewtimes[$userid][$keyToCheck]) && $this->userviewtimes[$userid][$keyToCheck] >= ($time-$this->limitbyperiod)) {
				if ($debug) bannerDebug($debugLog);
				return false;
			}
		}
		if ($debug) $debugLog .= " 12";
		//bannerDebug("valid banner: $this->id");
		//all else works
		if ($debug) bannerDebug($debugLog);
		return true;
	}
}

class bannerstats{

	public $starttime;

	public $total;

	public $agesex;
	public $loc;
	public $page;
	public $interests;
	public $hittimes; //hittimes[0-6 weekdays][0-23 hours] = hits

	function __construct(){
		$this->total = 0;
		$this->starttime = time();

		for($i=0; $i < 80; $i++)
			$this->agesex[$i] = array( SEX_UNKNOWN => 0, SEX_MALE => 0, SEX_FEMALE => 0);

		$this->loc = array(0 => 0);
		$this->page = array();
		$this->interests = array();
		$this->hittimes = array();
	}

	function hit($age, $sex, $loc, $interests, $page, $time){
		$this->total++;

		$day = userdate("w", $time);
		$hour = userdate("G", $time);
		if (!isset($this->hittimes[$day]))
			$this->hittimes[$day] = array();
		if (!isset($this->hittimes[$day][$hour]))
			$this->hittimes[$day][$hour] = 0;
		$this->hittimes[$day][$hour]++;

		if (!isset($this->agesex[$age]))
			$this->agesex[$age] = array();
		if (!isset($this->agesex[$age][$sex]))
			$this->agesex[$age][$sex] = 0;
		$this->agesex[$age][$sex]++;

		if(!isset($this->loc[$loc]))
			$this->loc[$loc] = 0;
		$this->loc[$loc]++;

		if(!isset($this->page[$page]))
			$this->page[$page] = 0;
		$this->page[$page]++;

		if(is_array($interests) && count($interests)){
			foreach($interests as $interest){
				if(!isset($this->interests[$interest]))
					$this->interests[$interest] = 0;
				$this->interests[$interest]++;
			}
		}
	}

	function factor($ages, $sexes, $locs, $pages){
		if(!$this->total)
			return 0;

		$factor = 1;

		if($ages || $sexes){

			if(!$ages)
				$ages = range(0,80);
			if(!$sexes)
				$sexes = array( SEX_UNKNOWN, SEX_MALE, SEX_FEMALE );

			settype($ages, "array");
			settype($sexes, "array");

			$temp = 0;
			foreach($ages as $age)
				foreach($sexes as $sex)
					$temp += $this->agesex[$age][$sex];

			$factor *= (double)$temp/$this->total;
		}

		if($locs){
			settype($locs, "array");
			$temp = 0;
			foreach($locs as $loc)
				if(isset($this->loc[$loc]))
					$temp += $this->loc[$loc];

			$factor *= (double)$temp/$this->total;
		}

		if($pages){
			settype($pages, "array");
			$temp = 0;
			foreach($pages as $page)
				if(isset($this->page[$page]))
					$temp += $this->page[$page];

			$factor *= (double)$temp/$this->total;
		}

		return $factor;
	}
	
	function loadXML($xmlstring){
		$xml = simplexml_load_string($xmlstring);
	
		$this->total = $xml->total;
		$this->starttime = $xml->starttime;

		foreach($xml->agesex as $k => $v)
			$this->agesex[$k] = array(SEX_UNKNOWN => $v[SEX_UNKNOWN],
			                          SEX_MALE => $v[SEX_MALE],
			                          SEX_FEMALE => $v[SEX_FEMALE]);

		foreach($xml->loc as $k => $v)
			if($v)
				$this->loc[$k] = $v;

		foreach($xml->interests as $k => $v)
			if($v)
				$this->interests[$k] = $v;

		foreach($xml->hittimes as $daynum => $day)
			foreach($day as $hour => $hits)
				$this->hittimes[$daynum][$hour] = $hits;

		foreach($xml->pages as $line)
//			$this->page[$line->string] = $line->integer->i;
//			$this->page[$line[0]] = $line[1][0];
			$this->page[$line['string']] = $line['integer'][0];
	}
}

class bannerserver{
	public $db;

	public $sizes;

	public $numservers;

	//public $banners;
	//public $bannerids;
	//public $bannersizes;
	public $bannercampaigns;
	public $campaignids; // array( bannerid => campaignid );
	public $dailyviews;
	public $dailyclicks;

	public $time;

	function __construct( & $db, $numservers = 1){

		$this->db = & $db;

		$this->numservers = $numservers;

		$this->sizes = array(	"468x60"	=> BANNER_BANNER,
								"728x90"	=> BANNER_LEADERBOARD,
								"300x250"	=> BANNER_BIGBOX,
								"120x600"	=> BANNER_SKY120,
								"160x600"	=> BANNER_SKY160,
								"120x60"	=> BANNER_BUTTON60,
								"Voken"		=> BANNER_VULCAN,
								"Link"		=> BANNER_LINK,
							);


		/*$this->banners = array();
		foreach($this->sizes as $size){
			$this->banners[$size] = array();
			$this->bannersizes[$size] = array();
		}

		$this->bannerids = array();
*/
		$this->dailyviews = array();
		$this->dailyclicks= array();

		foreach($this->sizes as $size){
			$this->dailyviews[$size] = new bannerstats();
			$this->dailyclicks[$size]= new bannerstats();
		}

		$this->time = time();

		$this->getBanners();
	}

	function addCampaign($id){
		$res = $this->db->prepare_query("SELECT * FROM bannercampaigns WHERE id = #", $id);

		$campaign = $res->fetchrow();
		unset($res);
		if($campaign){
			$this->bannercampaigns[$campaign['id']] = new bannercampaign($campaign, $this->numservers);
			foreach($this->bannercampaigns[$campaign['id']]->banners as $banner) {
				$this->campaignids[$banner->id] = $campaign['id'];
			}
			return true;
		}else {
			return false;
		}
	}

	function updateCampaign($id) {
		$res = $this->db->prepare_query("SELECT * FROM bannercampaigns WHERE id = #", $id);
		$campaign = $res->fetchrow();
		unset($res);
		if ($campaign) {
			if (isset($this->bannercampaigns[$id])) { //update the campaign
				$this->bannercampaigns[$id]->update($campaign, $this->numservers);
			} else { //add the campaign
				$this->addCampaign($id);
			}
			return true;
		} else {
			return false;
		}
	}

	function addBanner($id){
		$res = $this->db->prepare_query("SELECT * FROM banners WHERE id = #", $id);
		$banner = $res->fetchrow();
		unset($res);

		if($banner){
			$newBanner = new banner($banner, $this->numservers, $this->bannercampaigns[$banner['campaignid']]);
			$this->bannercampaigns[$banner['campaignid']]->addBanner($newBanner);
			$this->campaignids[$banner['id']] = $banner['campaignid'];
			return true;
		}else
			return false;
	}


	function updateBanner($id){
		$res = $this->db->prepare_query("SELECT * FROM banners WHERE id = #", $id);
		$banner = $res->fetchrow();
		unset($res);

		if($banner){
			if ($banner['campaignid'] == $this->campaignids[$id]) { //same campaign just update the banner in it
				$this->bannercampaigns[$this->campaignids[$id]]->getBannerID($id)->update($banner, $this->numservers, $this->bannercampaigns[$this->campaignids[$id]]);
			} else { //moved to a new campaign
				$this->bannercampaigns[$this->campaignids[$id]]->deleteBanner($id);
				$newBanner = new banner($banner, $this->numservers, $this->bannercampaigns[$banner['campaignid']]);
				$this->bannercampaigns[$banner['campaignid']]->addBanner($newBanner);
				$this->campaignids[$banner['id']] = $banner['campaignid'];
			}
			return true;
		/*
			if(isset($this->banners[$banner['bannersize']][$banner['id']])){ //exists where expected
				$this->banners[$banner['bannersize']][$banner['id']]->update($banner, $this->numservers);
			}else{
				if(isset($this->bannerids[$banner['id']]) && isset($this->banners[$this->bannerids[$banner['id']]][$banner['id']])){  //changed sizes
					$this->banners[$banner['bannersize']][$banner['id']] = & $this->banners[$this->bannerids[$banner['id']]][$banner['id']];

					unset($this->banners[$this->bannerids[$banner['id']]][$banner['id']]);
					unset($this->bannersizes[$this->bannerids[$banner['id']]][$banner['id']]);

					$this->bannerids[$banner['id']] = $banner['bannersize'];
					$this->bannersizes[$banner['bannersize']][$banner['id']] = $banner['id'];

					$this->banners[$banner['bannersize']][$banner['id']]->update($banner, $this->numservers);
				}else{ //doesn't exist, add it instead of updating it.
					$this->addBanner($banner['id']);
				}
			}
			return true;*/
		}else
			return false;
	}

	function deleteBanner($id){
		$banner = $this->bannercampaigns[$this->campaignids[$id]]->getBannerID($id);
		$banner->minutely($this->db, $this->time, false); //update stats
		$this->bannercampaigns[$this->campaignids[$id]]->deleteBanner($id);
		unset($this->campaignids[$id]);
	}

	function deleteCampaign($id) {
		if (isset($this->bannercampaigns[$id])) {
			$this->bannercampaigns[$id]->minutely($this->db, $this->time, false);
			foreach($this->bannercampaigns[$id]->banners as $banner) {
				unset($this->campaignids[$banner->id]);
			}
			unset($this->bannercampaigns[$id]);
		}
	}

	function passbackBanner($id, $userid){
		if(isset($this->campaignids[$id])) {
				$this->bannercampaigns[$this->campaignids[$id]]->getBannerID($id)->passback($userid, $this->time);
		}
	}

	function getBanner($usertime, $size, $userid, $age, $sex, $loc, $interests, $page, $debug = 0, $id = null){
		$banners = array();
		$valid = array();
		$validBanners = false;
		$debugLog = "";
		if ($debug) $debugLog .= "$usertime, $size, $userid, $age, $sex, $loc, $page, $debug";
		if($id === null){
			$valid = array();
			foreach($this->bannercampaigns as &$campaign){
				//check campaign size and campaign enabled
				if ($debug) $debugLog .= "\nPre-checking campaign $campaign->id:";
				if(isset($campaign->sizes[$size]) && $campaign->enabled) {
					//check valid start/end date and valid sex
					if ($debug) $debugLog .= " 1";
					if(!($campaign->startdate >= $this->time || ($campaign->enddate && $campaign->enddate <= $this->time)) && $campaign->sex[$sex]) {
						if ($debug) $debugLog .= " 2";
						//bannerDebug("Passed prescreening for $campaign->id");
						if($validBanners = $campaign->valid($userid, $age, $sex, $loc, $interests, $page, $this->time, $size, $usertime, $debug)){
							$valid[$campaign->id] = $campaign->priority($userid, $validBanners, $this->time);
							$banners[$campaign->id] = $validBanners;
							foreach ($validBanners as $bannerid) {
								$this->bannercampaigns[$campaign->id]->getBannerID($bannerid)->potentialHit();
							}
						}
					} else {
						//bannerDebug("Failed date check for campaign:$campaign->id");
					}
				} else {
					//bannerDebug("Failed size check for campaign:$campaign->id");
				}
			}

			if(count($valid) == 0) {
				if ($debug) bannerDebug($debugLog);
				return 0;
			}


			$campaignID = chooseWeight($valid, false);
			$id = $this->bannercampaigns[$campaignID]->getBanner($userid, $banners[$campaignID], $this->time);

		}


		$this->dailyviews[$size]->hit($age, $sex, $loc, $interests, $page, $usertime);

		$this->bannercampaigns[$campaignID]->hit($id, $userid, $this->time);

		if ($debug) $debugLog .= "\nChose banner $id";
		if ($debug) bannerDebug($debugLog);

		return $id;
	}

	function clickBanner($id, $age, $sex, $loc, $interests, $page, $time){
//		echo "id: $id, age: $age, sex: $sex, loc: $loc, page: $page\n";

		if(!isset($this->campaignids[$id])){
			echo "banner doesn't exist!\n";
			return;
		}

		$this->bannercampaigns[$this->campaignids[$id]]->getBannerID($id)->click();
		$this->dailyclicks[$this->bannercampaigns[$this->campaignids[$id]]->getBannerID($id)->size]->hit($age, $sex, $loc, $interests, $page, $time);
	}

	function minutely($debug){
		foreach($this->bannercampaigns as $campaign) {
			$campaign->minutely($this->db, $this->time, $debug);
		}

		return 1;
	}

	function hourly($debug){
		foreach($this->bannercampaigns as $campaign) {
			$campaign->hourly($this->db, $this->time, $debug);
		}
		return 2;
	}

	function daily($debug){
		if($debug)
		bannerDebug("daily");

		foreach($this->bannercampaigns as $campaign){
			$campaign->daily($debug);
		}
		foreach($this->sizes as $size) {
			$viewsdump = gzcompress(serialize($this->dailyviews[$size]));
			$clicksdump= gzcompress(serialize($this->dailyclicks[$size]));

			$this->db->prepare_query("INSERT INTO bannertypestats SET size = #, time = #, views = #, clicks = #, viewsdump = ?, clicksdump = ?", $size, $this->time, $this->dailyviews[$size]->total, $this->dailyclicks[$size]->total,  $viewsdump, $clicksdump);

			unset($viewsdump, $clicksdump);

			$this->dailyviews[$size] = new bannerstats();
			$this->dailyclicks[$size]= new bannerstats();
		}

		return 4;
	}

	function settime($time, $debug){
		$this->time = $time;

		$ret = 0;

		if($time % 60 == 0)
			$ret |= $this->minutely($debug);

		if($time % 3600 == 0)
			$ret |= $this->hourly($debug);

		if($time % 86400 == 3600*BANNER_DAILY_HOUR)
			$ret |= $this->daily($debug);

		return $ret;
	}

	function getBanners(){
		/*$res = $this->db->prepare_query("SELECT * FROM banners WHERE moded = 'y'");

		while($line = $res->fetchrow()){
			if(isset($this->banners[$line['bannersize']][$line['id']]))
				break;

			bannerDebug("init $line[id]");

			$this->bannerids[$line['id']] = $line['bannersize'];
			$this->banners[$line['bannersize']][$line['id']] = new banner($line, $this->numservers);
			$this->bannersizes[$line['bannersize']][$line['id']] = $line['id'];
		}

		*/
		$result = $this->db->prepare_query('SELECT * FROM bannercampaigns');
		while ($line = $result->fetchrow()) {
			$this->bannercampaigns[$line['id']] = new bannercampaign($line, $this->numservers);
			foreach($this->bannercampaigns as $campaign) {
				foreach($campaign->banners as $banner) {
					$this->campaignids[$banner->id] = $campaign->id;
				}
			}
		}
		unset($result);
	}
}

class bannerclient{

	public $db;

	public $sock;
	public $logsock;
	public $hosts;
	public $dead;
	public $rehash;

	public $persistant;
	public $timeout;

	public $sizes;
	public $types;
	public $clienttypes = array("agency", "local", "affiliate", "payinadvance");

	public $userid;
	public $age;
	public $sex;
	public $sexuality;
	public $loc;
	public $page;
	public $server;
	public $skin;
	public $interests;

	public $pageid;
	public $zone;
	
	public $linkclass;


	function __construct( & $db, $hosts, $persist = false, $rehash = false){

		$this->db = & $db;

		$this->hosts = $hosts; //shuffle($hosts);

		$this->dead = false;

		$this->timeout = 0.05;
		$this->persistant = $persist;
		$this->rehash = $rehash;

		$this->sizes = array(	BANNER_BANNER 		=> "468x60",
								BANNER_LEADERBOARD 	=> "728x90",
								BANNER_BIGBOX 		=> "300x250",
								BANNER_SKY120 		=> "120x600",
								BANNER_SKY160 		=> "160x600",
								BANNER_BUTTON60 	=> "120x60",
								BANNER_VULCAN	 	=> "Vulcan",
								BANNER_LINK			=> "Link",
							);

		$this->types = array(	BANNER_IMAGE	=> 'image',
								BANNER_FLASH	=> 'flash',
								BANNER_IFRAME	=> 'iframe',
								BANNER_HTML		=> 'html',
								BANNER_TEXT		=> 'text' );

		$this->pageid = rand(10000, 99999); //always be 5 digits
		$this->zone = 0;

		$this->linkclass = 'body';

		register_shutdown_function(array(&$this, "disconnect"));
	}

	function connect(){
		//connect to the log server to log attempts
		if(!$this->logsock){
			global $config;
			list($host, $port) = explode(':', $config['bannerlogserver']); //assume in the form: ip:port

			$this->logsock = null;

			if($host && $port){
				if($this->logsock = fsockopen("udp://$host", $port, $errno, $errstr, 0.05)){
		//			stream_set_timeout($sock, 0.02);
		//			stream_set_blocking($sock, 0); //non blocking
				}else{
					$this->logsock = null;
				}
			}
		}

		if($this->sock)
			return true;

		if($this->dead)
			return false;

		$numhosts = count($this->hosts);
		$hostnum = abs($this->userid) % $numhosts;

		$numtries = ($this->rehash ? $numhosts : 1);

		for($i = 0; $i < $numtries; $i++){
			$host = $this->hosts[($i + $hostnum) % $numhosts];
			$errno = 0;
			$errstr = "";
			if($this->persistant)
				$this->sock = @pfsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout);
			else
				$this->sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout);

			if($this->sock) //else try next host
				break;
		}

		if(!$this->sock){ //if no host found, mark as dead, don't try again
			$this->dead = true;
			return false;
		}

		stream_set_timeout($this->sock, 2);  //timeout of 2 seconds

		return true;
	}

	function disconnect(){
		if($this->sock)
			fclose($this->sock);
		if($this->logsock)
			fclose($this->logsock);
		$this->sock = null;
	}

	function getVariables(){
		if($this->page)
			return;
	
		global $userData, $config, $skin;

		$this->page = strtolower(substr($_SERVER['PHP_SELF'], 1, -4)); //take off leading / and trailing .php
		if(empty($this->page))
			$this->page = 'index';

		if($userData['loggedIn']){
			$this->userid = $userData['userid'];
			$this->age = $userData['age'];
			$this->sex = ($userData['sex'] == 'Male' ? SEX_MALE : SEX_FEMALE);
			$this->sexuality = $userData['sexuality'];
			$this->loc = $userData['loc'];
			if($userData['interests'])
				$this->interests = $userData['interests'];
			else
				$this->interests = '0';
		}else{
			$this->userid = 0;
			if(isset($userData['userid']))
				$this->userid = $userData['userid']; //negative userid
			if(!$this->userid) //used for frequency capping for users that aren't logged in
			{	
				$temp = getCOOKIEval('sessionkey', 'string');
				if($temp && length($temp) > 0)
					$temp_parts = split(':', $temp, 2);
					
				if($temp_parts)
					$this->userid = (int)$temp_parts[1];
				else
					$this->userid = -1;
			}	
			$this->age = 0;
			$this->sex = SEX_UNKNOWN;
			$this->sexuality = SEX_UNKNOWN;
			$this->loc = 0;
			$this->interests = '0';
		}
		$this->server = $config['bannerloc'];
		$this->skin = $skin;
	}

	function getZone(){
		if(!$this->page)
			$this->getVariables();

		switch($this->page){
			case 'index':               return 2;
			case 'usercomments':        return 3;
			case 'messages':            return 4;
			case 'friends':             return 5;

			case 'articlelist':         return 6;
			case 'article':             return 7;

			case 'forums':              return 8;
			case 'forumthreads':        return 9;
			case 'forumviewthread':     return 10;
			case 'managesubscriptions': return 11;

			case 'manageprofile':       return 12;
			case 'managepicture':       return 13;
			case 'managegallery':       return 14;

			case 'profileviews':        return 15;
			case 'prefs':               return 16;
			case 'weblog':              return 17;


			case 'profile':
				if(isset($_REQUEST['uid']) && $_REQUEST['uid'])
					return 50;

				if(isset($_REQUEST['requestType'])){
					switch($_REQUEST['requestType']){
						case 'mine':
							return 51;
						case 'onlineByPrefs':
							return 52;
						case 'query':
							if(isset($POST['requestParams']['displayList']))
								return 53;
							else
								return 54;
						default:
							return 55;
					}
				}
				return 56;
				//end profile
		}

		return 1; //default, unknown zone
	}

	function getpageid(){
		if(!$this->zone)
			$this->zone = $this->getzone();

		return $this->zone . str_pad($this->pageid, 5, "0", STR_PAD_LEFT); //zone is 1 or 2 digits, pageid is 5 digits
	}

	function getIFrameBanner($size) {
		$bannersizes = array(
			BANNER_BANNER => array(484, 76),
			BANNER_LEADERBOARD => array(736, 106),
			BANNER_BIGBOX => array(330, 280),
			BANNER_SKY120 => array(136, 616),
			BANNER_SKY160 => array(176, 616)
		);
		$bannerWidth = $bannersizes[$size][0];
		$bannerHeight = $bannersizes[$size][1];
		return "<script>YAHOO.util.Event.on(window, 'load', function() {document.getElementById('banner_iframe_$size').src = '/bannerview.php?size=$size&pageid=".$this->getpageid()."'});</script><iframe id='banner_iframe_$size' frameBorder='0' width='".$bannerWidth."' height='".$bannerHeight."'></iframe>";
	}

	function getBanner($size, $refresh = false, $passback = 0, $debug = 0, $pageid = false){
		global $cache;
		// return $this->getCode(1, 1, BANNER_HTML, 'x', 'y', '<script type="text/javascript" LANGUAGE="JavaScript1.1">
		// 		<!-- Nexopia.com@Bigbox (250x250 & 300x250 & 300x300) 
		// 		if (typeof OAS_rns != "string" || OAS_rns.length != 9 || OAS_rns.search(/[^0-9]/) != -1) OAS_rns = new String (Math.random()).substring(2, 11); document.write(\'<scr\'+\'ipt LANGUAGE="JavaScript1.1" SRC="http://network-ca.247realmedia.com/RealMedia/ads/adstream_jx.ads/Nexopia.com/1\'+OAS_rns+\'@x15?JX"></scr\'+\'ipt>\'); // --> </script>', 30);

		$this->getVariables();

	//tries to connect, and opens the logsock if needed
		$connected = $this->connect();

	//tries to log the attempt, even if it failed to connectd
		if($this->logsock)
			fwrite($this->logsock, 'a');

		if(!$connected)
			return "";

	//get the pageid and zone
		if($pageid === false){ //set from this page
			$pageid = $this->pageid;
		}else{ //passed in from the frame
			$this->zone = substr($pageid, 0, -5);
			$pageid = ltrim(substr($pageid, -5), '0'); //cut off the zone stuff
		}
		
		if(!$pageid) //if no ad was shown this page (but would be for the leaderboard?)
			$pageid = -1; //-1 is a special value to the banner server meaning no banner on the page below.


		$time = time();
		$usertime = $time + getusertimeoffset($time);
		fwrite($this->sock, "get $usertime $size $this->userid $this->age $this->sex $this->loc $this->interests $this->page $passback $debug $pageid\n");

//*
		$buf = "";
		$id = "";
		while($buf = fgets($this->sock, 256)){
			$id .= $buf;
			if(strchr($id, "\n") !== false)
				break;
			if(feof($this->sock)){ //timed out?
				$this->dead = true;
				return "";
			}

			usleep(1000); //wait a millisecond
		}

		$id = trim($id);
/*/
		$id = trim(fgets($this->sock));
//*/
	if(!$id) {
		return "";
	}


//return $id;

		$banner = $cache->get("banner-$id");

		if(!$banner){
			$res = $this->db->prepare_query("SELECT bannertype, image, link, alt, refresh, campaignid FROM banners WHERE id = #", $id);
			$banner = $res->fetchrow();
			unset($res);

			if ($banner['refresh'] < 0) {
				$res = $this->db->prepare_query("SELECT refresh FROM bannercampaigns WHERE id = #", $banner['campaignid']);
				$campaign = $res->fetchrow();
				unset($res);
				$banner['refresh'] = $campaign['refresh'];
			}

			$cache->put("banner-$id", $banner, 3600);
		}


		if($refresh === true)
			$refresh = $banner['refresh'];

		return $this->getCode($id, $size, $banner['bannertype'], $banner['image'], $banner['link'], $banner['alt'], $refresh);
	}

	function getBannerID($id){
		$res = $this->db->prepare_query("SELECT id, bannersize, bannertype, image, link, alt FROM banners WHERE id = #", $id);
		$line = $res->fetchrow();
		unset($res);

		if(!$line)
			return "";

		return $this->getCode($line['id'], $line['bannersize'], $line['bannertype'], $line['image'], $line['link'], $line['alt'], false);
	}

	function click($id, $page){
		if(!$this->connect())
			return;

		$time = time();
		$time = $time + getusertimeoffset($time);

		fwrite($this->sock, "click $id $this->age $this->sex $this->loc $this->interests $page $time\n");

		$res = $this->db->prepare_query("SELECT link FROM banners WHERE id = #", $id);
		$field = $res->fetchfield();
		unset($res);
		return $field;
	}

	function addBanner($id){
		global $msgs;
		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "add $id\n");
				fclose($sock);
			} else {
				$msgs->addMsg("Failed to update banner server $host, please retry.");
			}
		}
		return true;
	}

	function updateBanner($id){
		global $cache, $msgs;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "update $id\n");
				fclose($sock);
			} else {
				$msgs->addMsg("Failed to update banner server $host, please retry.");
			}
		}

		$cache->remove("banner-$id");

		return true;
	}

	function deleteBanner($id){
		global $cache, $msgs;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "del $id\n");
				fclose($sock);
			} else {
				$msgs->addMsg("Failed to update banner server $host, please retry.");
			}
		}

		$cache->remove("banner-$id");

		return true;
	}

	function addCampaign($id){
		global $msgs;
		
		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "addcampaign $id\n");
				fclose($sock);
			} else {
				$msgs->addMsg("Failed to update banner server $host, please retry.");
			}
		}
		return true;
	}

	function updateCampaign($id){
		global $cache, $msgs;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "updatecampaign $id\n");
				fclose($sock);
			} else {
				$msgs->addMsg("Failed to update banner server $host, please retry.");
			}
		}

		return true;
	}

	function deleteCampaign($id){
		global $cache, $msgs;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "delcampaign $id\n");
				fclose($sock);
			} else {
				$msgs->addMsg("Failed to update banner server $host, please retry.");
			}
		}

		return true;
	}

	function getCode($id, $size, $type, $image, $link, $alt, $refresh = false){
		global $config, $wwwdomain;

		$str = "";
		if(strpos($this->sizes[$size], 'x') !== false)
			list($width, $height) = explode('x', $this->sizes[$size]);
		else
			$width = $height = 0;

		if($refresh)
			$str .= "<script>if(parent&&parent.settime){parent.settime($refresh);parent.starttimer();}</script>";

		switch($type){
			case BANNER_IMAGE:
				if(empty($image))
					$image = $id . '.jpg';
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				if($link=='')
					return "<div id='banner_$size'>$str<img src=\"$image\" width='$width' height='$height'" . ($alt == "" ? "" : " alt=\"$alt\"" ) . "></div>";
				else
					return "<div id='banner_$size'>$str<a href=\"/bannerclick.php?id=$id\" target=_blank><img src=\"$image\" width='$width' height='$height' border=0" . ($alt == "" ? "" : " alt=\"$alt\"" ) . "></a></div>";

			case BANNER_FLASH:
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;
				if(substr($alt,0,7) != "http://")
					$alt = $config['bannerloc'] . $alt;

				$alt = str_replace("%link%", "http://$wwwdomain/bannerclick.php?id=$id", $alt);

				return "<div id='banner_$size'>$str<script src=$config[jsloc]banner.js></script><script>flashbanner('$alt', '$image', '$link', $width, $height, '#000000');</script></div>";

			case BANNER_IFRAME:
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				return "<div id='banner_$size'>$str<iframe src='$image' width='$width' height='$height' frameborder=no border=0 marginwidth=0 marginheight=0 scrolling=no></iframe></div>";

			case BANNER_HTML:
				if(strpos($alt, "%") !== false){
					$this->getVariables();

					$rand = rand();
					$alt = str_replace("%rand%",$rand, $alt);
					$alt = str_replace("%width%",$width, $alt);
					$alt = str_replace("%height%",$height, $alt);
					$alt = str_replace("%page%",$this->page, $alt);
					$alt = str_replace("%age%", $this->age, $alt);
					$alt = str_replace("%sex%", $this->sex, $alt);
					$alt = str_replace("%skin%", $this->skin, $alt);
					$alt = str_replace("%id%", $id, $alt);
					$alt = str_replace("%size%", $size, $alt); //banner size (ie BANNER_BANNER, BANNER_LEADERBOARD)
					$alt = str_replace("%server%", $this->server, $alt);
					$alt = str_replace("%link%", "http://$wwwdomain/bannerclick.php?id=$id", $alt);
					$alt = str_replace("%passback%", "http://$wwwdomain/bannerview.php?size=$size&pass=$id", $alt);
					$alt = str_replace("%passbackjs%", "http://$wwwdomain/bannerview.php?size=$size&pass=$id&js=1", $alt);

					if(strpos($alt, "%ampp_") !== false){
						include_once("include/ampp_mappings.php");

						$alt = str_replace("%ampp_size%", ampp_map_size($size), $alt);
						$alt = str_replace("%ampp_age%", ampp_map_age($this->age), $alt);
						$alt = str_replace("%ampp_sex%", ampp_map_sex($this->sex), $alt);
						$alt = str_replace("%ampp_sexuality%", ampp_map_sexuality($this->sexuality), $alt);
						$alt = str_replace("%ampp_interests%", ampp_map_interests($this->interests), $alt);
						$alt = str_replace("%ampp_tile%", ampp_map_tile($size), $alt);
						$alt = str_replace("%ampp_zone%", ampp_map_zone($this->zone), $alt);
					}

					if(strpos($alt, "%google_") !== false){
						global $google, $userData;

						if(strpos($alt, "%google_cust_age") !== false){
							if(!$userData['loggedIn']){
								$alt = str_replace("%google_cust_age%", 0, $alt);
							}elseif($userData['age'] <= 17){
								$alt = str_replace("%google_cust_age%", 1000, $alt);
							}elseif($userData['age'] <= 24){
								$alt = str_replace("%google_cust_age%", 1001, $alt);
							}elseif($userData['age'] <= 34){
								$alt = str_replace("%google_cust_age%", 1002, $alt);
							}elseif($userData['age'] <= 44){
								$alt = str_replace("%google_cust_age%", 1003, $alt);
							}elseif($userData['age'] <= 54){
								$alt = str_replace("%google_cust_age%", 1004, $alt);
							}elseif($userData['age'] <= 64){
								$alt = str_replace("%google_cust_age%", 1005, $alt);
							}else{
								$alt = str_replace("%google_cust_age%", 1006, $alt);
							}
						}
						$alt = str_replace("%google_cust_gender%", $this->sex, $alt); //0 for unknown, 1 for male, 2 for female 

						if($userData['loggedIn']){
							$alt = str_replace("%google_cust_l%", $google->encuserid($userData['userid']), $alt);
							$alt = str_replace("%google_cust_lh%", $userData['googlehash'], $alt);
//							$alt = str_replace("%google_ed%", $google->ed(), $alt);
						}else{
							$alt = str_replace("%google_cust_l%", 0, $alt);
							$alt = str_replace("%google_cust_lh%", 0, $alt);
						}
					}
				}


				$text = $str.$alt;
				// $text = urlencode($text);
				// 			$text = str_replace("+", "%20", $text);
				// 
				// 			$delayed_load_ad = '<script>
				// 				<!--
				// 				YAHOO.util.Event.on(window, \'load\', function() {
				// 					var banner = document.getElementById("banner_'.$size.'");
				// 					banner.contentDocument.open();
				// 					var html = decodeURIComponent("'.$text.'");
				// 					Nexopia.Utilities.addContent(html, banner);
				// 					banner.contentDocument
				// 					
				// 				});
				// 				//-->
				// 			</script>';
				return "<div id='banner_$size'>$text</div>";

			case BANNER_TEXT:
				if($link == "")
					return "<div id='banner_$size'>$str<b>$image</b></a><br>$alt";
				else
					return "<div id='banner_$size'>$str<a class=" . $this->linkclass . " href=\"/bannerclick.php?id=$id\" target=_blank><b>$image</b></a><br>$alt";
		}
		return "<div id='banner_$size'>$str</div>";
	}

}

function bannerDebug($msg){
	echo "[" . gmdate("G:i:s") . "] $msg\n";
//	trigger_error("banner: [" . gmdate("G:i:s") . "] $msg", E_USER_NOTICE);
}
