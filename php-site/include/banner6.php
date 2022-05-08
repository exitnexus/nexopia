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
		$this->minviewsperday	= ceil($vals['viewsperday'] / $numservers);
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

		
	}

	function updateSizes() {
		global $banner;
		$this->sizes = array();
		$result = $banner->db->prepare_query("SELECT * FROM banners WHERE campaignid = #", $this->id);
		while($line = $result->fetchrow()) {
			$this->sizes[$line['bannersize']] = 1;
		}
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
		$this->charge = 0;
		
	}

	function dailyviews() {
		//return 0; //CHECK
		$dailyviews = 0;
		foreach($this->banners as $id => $banner) {
			$dailyviews += $banner->dailyviews;
		}
		return $dailyviews;
	}

	function dailyclicks() {
		//return 0; //CHECK
		$dailyclicks = 0;
		foreach($this->banners as $id => $banner) {
			$dailyclicks += $banner->dailyclicks;
		}
		return $dailyclicks;
	}

	function hit($bannerid, $userid, $time) {
		if ($this->viewsperuser) {
			$this->userviewtimes[$userid][] = $time;
			if (count($this->userviewtimes[$userid]) > BANNER_SLIDE_SIZE*$this->viewsperuser) {
				reset($this->userviewtimes[$userid]);
				unset($this->userviewtimes[$userid][key($this->userviewtimes[$userid])]);
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
	function valid($userid, $age, $sex, $loc, $interests, $page, $time, $size, $usertime){
		//if(!$this->enabled)
		//	return false;
		//date
		//if($this->startdate >= $time || ($this->enddate && $this->enddate <= $time))
		//	return false;

		//targetting
		//age
		if($this->age[0]){ //default true
			if( $age &&  isset($this->age[$age]) && !$this->age[$age]) //is explicitely untargetted
				return false;
		}else{ //default false
			if(!$age || !isset($this->age[$age]) || !$this->age[$age]) //is explicitely targetted
				return false;
		}

		//sex
		//if(!$this->sex[$sex])
		//	return false;

		//location
		if($this->loc[0]){ //default true
			if( $loc &&  isset($this->loc[$loc]) && !$this->loc[$loc])
				return false;
		}else{ //default false
			if(!$loc || !isset($this->loc[$loc]) || !$this->loc[$loc])
				return false;
		}

		//page
		if($this->page[0]){ //default true
			if( $page &&  isset($this->page[$page]) && !$this->page[$page])
				return false;
		}else{ //default false
			if(!$page || !isset($this->page[$page]) || !$this->page[$page])
				return false;
		}

		//interests
		if(count($this->interests)){
			if(count($interests) == 0)
				return false;
			$found = false;

			foreach($interests as $i){
				if(isset($this->interests[(int)$i])){
					$found = true;
					break;
				}
			}

			if(!$found)
				return false;
		}
		
		//time
		$day = gmdate("w", $usertime);
		$hour = gmdate("G", $usertime);
		
		if (!$this->allowedtimes->validHours[$day][$hour]) {
			return false;
		}
		
	//frequency capping at the campaign level
	//this period (day/hour)
		if($this->viewsperday && $this->dailyviews() >= $this->viewsperday)
			return false;

		if($this->clicksperday && $this->dailyclicks() >= $this->clicksperday)
			return false;

	//views per user
		if($this->viewsperuser && isset($this->userviewtimes[$userid])) {
			end($this->userviewtimes[$userid]);
			$keyToCheck = key($this->userviewtimes[$userid]) - $this->viewsperuser + 1;
			if (isset($this->userviewtimes[$userid][$keyToCheck]) && $this->userviewtimes[$userid][$keyToCheck] >= ($time-$this->limitbyperiod)) {
				return false;
			}
		}
		//bannerDebug("Campaign valid: $this->id");
		$validBanners = false;
		//check individual banners for validity
		foreach($this->banners as $id => &$banner) {
			if ($banner->valid($userid, $age, $sex, $loc, $interests, $page, $time, $size, $usertime)) {
				//bannerDebug("Banner valid: $banner->id");
				$validBanners[] = $banner->id;
			}
		}
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
				if ($viewtime > $time-(BANNER_SLIDE_SIZE*$this->limitbyperiod)) {
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
			if (count($this->userviewtimes[$userid]) > BANNER_SLIDE_SIZE*$this->viewsperuser) {
				reset($this->userviewtimes[$userid]);
				unset($this->userviewtimes[$userid][key($this->userviewtimes[$userid])]);
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
			while (count($this->userviewtimes[$userid]) > BANNER_SLIDE_SIZE*$this->viewsperuser) {
				reset($this->userviewtimes[$userid]);
				unset($this->userviewtimes[$userid][key($this->userviewtimes[$userid])]);
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
				if ($viewtime > $time-(BANNER_SLIDE_SIZE*$this->limitbyperiod)) {
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

	function valid($userid, $age, $sex, $loc, $interests, $page, $time, $size, $usertime){
		//bannerDebug("Testing banner: $this->id");
		if(!$this->enabled) {
			return false;
		}
		
		if(!$this->moded) {
			return false;
		}
		
		//date
		if($this->startdate >= $time || ($this->enddate && $this->enddate <= $time)) {
			return false;
		}
		//bannerDebug("testing size: $this->size == $size");
		//size
		if($this->size != $size) {
			return false;
		}

		//targetting
		//age
		//bannerDebug("testing age");
		if($this->age[0]){ //default true
			if( $age &&  isset($this->age[$age]) && !$this->age[$age]) //is explicitely untargetted
			return false;
		}else{ //default false
			if(!$age || !isset($this->age[$age]) || !$this->age[$age]) //is explicitely targetted
			return false;
		}

		//sex
		//bannerDebug("testing sex");
		if(!$this->sex[$sex]) {
			return false;
		}

		//location
		//bannerDebug("testing location");
		if($this->loc[0]){ //default true
			if( $loc &&  isset($this->loc[$loc]) && !$this->loc[$loc]) {
				return false;
			}
		}else{ //default false
			if(!$loc || !isset($this->loc[$loc]) || !$this->loc[$loc]) {
				return false;
			}
		}

		//page
		//bannerDebug("testing page");
		if($this->page[0]){ //default true
			if( $page &&  isset($this->page[$page]) && !$this->page[$page]) {
				return false;
			}
		}else{ //default false
			if(!$page || !isset($this->page[$page]) || !$this->page[$page]) {
				return false;
			}
		}

		//interests
		//bannerDebug("testing interests");
		if(count($this->interests)){
			if(count($interests) == 0)
			return false;
			//*
			$found = false;

			foreach($interests as $i){
				if(isset($this->interests[(int)$i])){
					$found = true;
					break;
				}
			}

			if(!$found)
			return false;
			/*/
			$intersect = array_intersect($this->interests, $interests);
			if(count($intersect) == 0)
			return false;
			//*/
		}
		
		//time
		//bannerDebug("testing time");
		$day = gmdate("w", $usertime);
		$hour = gmdate("G", $usertime);
		if (!$this->allowedtimes->validHours[$day][$hour]) {
			return false;
		}
		
		//payment available
		if ($this->campaign->clienttype == "payinadvance") {
			if ($this->credits + $this->campaign->credits < $this->payrate()) {
				return false;
			}
		}
		
		//frequency capping
		//this period (day/hour)
		//bannerDebug("testing frequency capping");
		if($this->viewsperday && $this->dailyviews >= $this->viewsperday) {
			return false;
		}

		if($this->clicksperday && $this->dailyclicks >= $this->clicksperday) {
			return false;
		}

		//views per user
		if($this->viewsperuser && isset($this->userviewtimes[$userid])) {
			end($this->userviewtimes[$userid]);
			$keyToCheck = key($this->userviewtimes[$userid]) - $this->viewsperuser + 1;
			
			if (isset($this->userviewtimes[$userid][$keyToCheck]) && $this->userviewtimes[$userid][$keyToCheck] >= ($time-$this->limitbyperiod)) {
				return false;
			}
		}

		//bannerDebug("valid banner: $this->id");
		//all else works
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

		if ($campaign) {
			if (isset($this->bannercampaigns[$id])) { //update the campaign
				$this->bannercampaigns[$id]->update($campaign);
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

	function getBanner($usertime, $size, $userid, $age, $sex, $loc, $interests, $page, $id = null){
		if($id === null){
			$valid = array();
			foreach($this->bannercampaigns as &$campaign){
				//check campaign size and campaign enabled
				if(isset($campaign->sizes[$size]) && $campaign->enabled) {
					//check valid start/end date and valid sex
					if(!($campaign->startdate >= $this->time || ($campaign->enddate && $campaign->enddate <= $this->time)) && $campaign->sex[$sex]) {
						//bannerDebug("Passed prescreening for $campaign->id");
						if($validBanners = $campaign->valid($userid, $age, $sex, $loc, $interests, $page, $this->time, $size, $usertime)){
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
			
			if(count($valid) == 0)
				return 0;

			
			$campaignID = chooseWeight($valid, false);
			$id = $this->bannercampaigns[$campaignID]->getBanner($userid, $banners[$campaignID], $this->time);

		}


		$this->dailyviews[$size]->hit($age, $sex, $loc, $interests, $page, $usertime);

		$this->bannercampaigns[$campaignID]->hit($id, $userid, $this->time);


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
	}
}

class bannerclient{

	public $db;

	public $sock;
	public $hosts;
	public $dead;

	public $persistant;
	public $timeout;

	public $sizes;
	public $types;
	public $clienttypes = array("agency", "local", "affiliate", "payinadvance");

	public $userid;
	public $age;
	public $sex;
	public $loc;
	public $page;
	public $server;
	public $skin;
	public $interests;


	function __construct( & $db, $hosts, $persist = false){

		$this->db = & $db;

		$this->hosts = $hosts; //shuffle($hosts);

		$this->dead = false;

		$this->timeout = 0.05;
		$this->persistant = $persist;

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

		register_shutdown_function(array(&$this, "disconnect"));
	}

	function connect(){
		if($this->sock)
			return true;

		if($this->dead)
			return false;

		$numhosts = count($this->hosts);
		$hostnum = abs($this->userid) % $numhosts;

		for($i = 0; $i < $numhosts; $i++){
			$host = $this->hosts[($i + $hostnum) % $numhosts];

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
		$this->sock = null;
	}

	function getVariables(){
		global $userData, $config, $skin;

		$this->page = strtolower(substr($_SERVER['PHP_SELF'], 1, -4)); //take off leading / and trailing .php
		if(empty($this->page))
			$this->page = 'index';

		if($userData['loggedIn']){
			$this->userid = $userData['userid'];
			$this->age = $userData['age'];
			$this->sex = ($userData['sex'] == 'Male' ? SEX_MALE : SEX_FEMALE);
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
				$this->userid = getCOOKIEval('userid', 'int');
			$this->age = 0;
			$this->sex = SEX_UNKNOWN;
			$this->loc = 0;
			$this->interests = '0';
		}
		$this->server = $config['bannerloc'];
		$this->skin = $skin;
	}

	function simulateGetBanner($size, $usertime, $userid, $age, $sex, $loc, $interests, $page, $refresh = false, $passback = 0){
		global $cache;


		$this->getVariables();

		if(!$this->connect())
			return "";

		fwrite($this->sock, "get $usertime $size $userid $age $sex $loc $interests $page $passback\n");

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
			if ($banner['refresh'] < 0) {
				$res = $this->db->prepare_query("SELECT refresh FROM bannercampaigns WHERE id = #", $banner['campaignid']);
				$campaign = $res->fetchrow();
				$banner['refresh'] = $campaign['refresh'];
			}

			$cache->put("banner-$id", $banner, 3600);
		}


		if($refresh === true)
			$refresh = $banner['refresh'];

		return $this->getCode($id, $size, $banner['bannertype'], $banner['image'], $banner['link'], $banner['alt'], $refresh);
	}
	
	
	function getBanner($size, $refresh = false, $passback = 0){
		global $cache;


		$this->getVariables();

		if(!$this->connect())
			return "";

		$usertime = time() + getusertimeoffset();
		fwrite($this->sock, "get $usertime $size $this->userid $this->age $this->sex $this->loc $this->interests $this->page $passback\n");

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
			if ($banner['refresh'] < 0) {
				$res = $this->db->prepare_query("SELECT refresh FROM bannercampaigns WHERE id = #", $banner['campaignid']);
				$campaign = $res->fetchrow();
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

		if(!$line)
			return "";

		return $this->getCode($line['id'], $line['bannersize'], $line['bannertype'], $line['image'], $line['link'], $line['alt'], false);
	}

	function click($id, $page){
		if(!$this->connect())
			return;
			
		$time = time() + getusertimeoffset();
		
		fwrite($this->sock, "click $id $this->age $this->sex $this->loc $this->interests $page $time\n");

		$res = $this->db->prepare_query("SELECT link FROM banners WHERE id = #", $id);
		return $res->fetchfield();
	}

	function addBanner($id){
		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "add $id\n");
				fclose($sock);
			}
		}
		return true;
	}

	function updateBanner($id){
		global $cache;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "update $id\n");
				fclose($sock);
			}
		}

		$cache->remove("banner-$id");

		return true;
	}

	function deleteBanner($id){
		global $cache;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "del $id\n");
				fclose($sock);
			}
		}

		$cache->remove("banner-$id");

		return true;
	}

	function addCampaign($id){
		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "addcampaign $id\n");
				fclose($sock);
			}
		}
		return true;
	}

	function updateCampaign($id){
		global $cache;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "updatecampaign $id\n");
				fclose($sock);
			}
		}

		return true;
	}

	function deleteCampaign($id){
		global $cache;

		foreach($this->hosts as $host){
			$sock = @fsockopen($host, BANNER_PORT, $errno, $errstr, $this->timeout*4);

			if($sock){
				fwrite($sock, "delcampaign $id\n");
				fclose($sock);
			}
		}

		return true;
	}

	function getCode($id, $size, $type, $image, $link, $alt, $refresh = false){
		global $config;

		$str = "";

		if($refresh)
			$str .= "<script>parent.settime($refresh);parent.starttimer();</script>\n";

		switch($type){
			case BANNER_IMAGE:
				list($width, $height) = explode('x', $this->sizes[$size]);
				if(empty($image))
					$image = $id . '.jpg';
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				if($link=='')
					return "$str<img src=\"$image\" width='$width' height='$height'" . ($alt == "" ? "" : " alt=\"$alt\"" ) . ">";
				else
					return "$str<a href=\"/bannerclick.php?id=$id\" target=_blank><img src=\"$image\" width='$width' height='$height' border=0" . ($alt == "" ? "" : " alt=\"$alt\"" ) . "></a>";

			case BANNER_FLASH:
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;
				if(substr($alt,0,7) != "http://")
					$alt = $config['bannerloc'] . $alt;

				list($width, $height) = explode('x', $this->sizes[$size]);
				return "$str<script src=$config[jsloc]banner.js></script><script>flashbanner('$alt', '$image', '$link', $width, $height, '#000000');</script>";

			case BANNER_IFRAME:
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				list($width, $height) = explode('x', $this->sizes[$size]);
				return "$str<iframe src='$image' width='$width' height='$height' frameborder=no border=0 marginwidth=0 marginheight=0 scrolling=no></iframe>";

			case BANNER_HTML:
				if(strpos($alt, "%") !== false){
					$this->getVariables();

					$rand = rand();
					$alt = str_replace("%rand%",$rand,$alt);
					$alt = str_replace("%page%",$this->page,$alt);
					$alt = str_replace("%age%", $this->age, $alt);
					$alt = str_replace("%sex%", $this->sex, $alt);
					$alt = str_replace("%skin%", $this->skin, $alt);
					$alt = str_replace("%id%", $id, $alt);
					$alt = str_replace("%size%", $size, $alt); //banner size (ie BANNER_BANNER, BANNER_LEADERBOARD)
					$alt = str_replace("%server%", $this->server, $alt);
				}

				return $str . $alt;

			case BANNER_TEXT:
				if($link == "")
					return "$str<b>$image</b></a><br>$alt";
				else
					return "$str<a class=" . $this->linkclass . " href=\"/bannerclick.php?id=$id\" target=_blank><b>$image</b></a><br>$alt";
		}
		return "$str";
	}

}

function bannerDebug($msg){
	echo "[" . gmdate("G:i:s") . "] $msg\n";
//	trigger_error("banner: [" . gmdate("G:i:s") . "] $msg", E_USER_NOTICE);
}
