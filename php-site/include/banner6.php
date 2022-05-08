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

define("BANNER_SLIDE_SIZE",	8);	// number of hours to use for the sliding average to calculate CPC -> eCPM rate

define("BANNER_MIN_CLICKTHROUGH", 0.0002);
define("BANNER_MAX_CLICKTHROUGH", 0.005);

define("BANNER_DAILY_HOUR", 12); // noon GMT = 6am MST

//class banner
class banner{
	var $id;
	var $clientid;
	var $size;

//targetting
	var $age;		// array( 0 => $default, $age1 => $age1, ...)
	var $sex;		// array( SEX_MALE => t/f, SEX_FEMALE => t/f, SEX_UNKOWN => t/f)
	var $loc;		// array( 0 => $default, $loc1 => $loc1, ...);
	var $page;		// array( 0 => $default, $page1 => t/f, ...)
	var $interests;	// array( 0 => $default, $interest1 => t/f, ...)

//limiting
	var $maxviews;
	var $maxclicks;
	var $viewsperday;
	var $clicksperday;
	var $viewsperuser;
	var $limitbyhour; // true => limit by hour, false => limit by day

	var $startdate;
	var $enddate;	//must be defined, use (2**31-1) for forever

//priority
	var $payrate; // either cpm or cpc pricing
	var $paytype; // BANNER_CPM | BANNER_CPC
	var $priority;

//stats
	var $hour;

	var $hourlyviews;
	var $hourlyclicks;

	var $dailyviews;
	var $dailyclicks;

	var $views;
	var $clicks;
	var $passbacks; //doesn't need daily/hourly stats, as it isn't used for targetting, freq capping, etc

	var $userviews; //array(userid => views)

	var $enabled;

//constructor
	function banner($vals, $numservers = 1){
		$this->hour = 0;

		$this->hourlyviews 	= array();
		$this->hourlyclicks	= array();

		for($i = 0; $i < BANNER_SLIDE_SIZE; $i++){ //use a BANNER_SLIDE_SIZE hour window to calculate eCPM for CPC ads
			$this->hourlyviews[$i] = 0;
			$this->hourlyclicks[$i] = 0;
		}

		$this->dailyviews 	= 0;
		$this->dailyclicks	= 0;

		$this->userviews 	= array();

		$this->views 		= 0;
		$this->clicks		= 0;
		$this->passbacks	= 0;

		$this->id 			= $vals['id'];
		$this->clientid 	= $vals['clientid'];
		$this->size 		= $vals['bannersize'];

		$this->update($vals, $numservers);
	}

	function update($vals, $numservers = 1){

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
		$this->viewsperday	= $vals['viewsperday'] / $numservers;
		$this->clicksperday	= $vals['clicksperday'] / $numservers;
		$this->viewsperuser = $vals['viewsperuser'];
		$this->limitbyhour	= ($vals['limitbyhour'] == 'y');

		$this->startdate	= $vals['startdate'];
		$this->enddate		= $vals['enddate'];

		$this->payrate 		= $vals['payrate'];
		$this->paytype		= $vals['paytype'];
		$this->priority		= $vals['payrate'];

		$this->enabled		= ($vals['enabled'] == 'y');

		if($this->maxviews && $this->maxviews <= $vals['views'])
			$this->enabled = false;

		if($this->maxclicks && $this->maxclicks <= $vals['clicks'])
			$this->enabled = false;
	}

	function hit($userid){
		$this->hourlyviews[$this->hour]++;
		$this->dailyviews++;
		$this->views++;

		if($this->viewsperuser){
			if(!isset($this->userviews[$userid]))
				$this->userviews[$userid] = 0;
			$this->userviews[$userid]++;
		}
	}

	function click(){
		$this->hourlyclicks[$this->hour]++;
		$this->dailyclicks++;
		$this->clicks++;
	}

	function passback(){
		$this->passbacks++;
	}

	function minutely(& $db, $time){
		bannerDebug("minutely " . $this->id . " " . $this->views . " " . $this->clicks);

//		$db->prepare_query("UPDATE banners SET lastupdatetime = #, views = #, clicks = # WHERE id = #", $time, $this->totalviews, $this->totalclicks, $this->id);
		$db->prepare_query("UPDATE banners SET lastupdatetime = #, views = views + #, clicks = clicks + #, passbacks = passbacks + # WHERE id = #", $time, $this->views, $this->clicks, $this->passbacks, $this->id);

		$this->views = 0;
		$this->clicks = 0;
		$this->passbacks = 0;
	}

	function hourly(& $db, $time){
		bannerDebug("hourly " . $this->id);

//		$db->prepare_query("INSERT INTO bannerstats SET bannerid = ?, clientid = ?, time = ?, priority = ?, views = ?, clicks = ?", $this->id, $this->clientid, $time, $this->priority, $this->totalviews, $this->totalclicks);
//		$db->prepare_query("INSERT IGNORE INTO bannerstats (bannerid, clientid, time, views, clicks) SELECT id, clientid, lastupdatetime, views, clicks FROM banners WHERE id = ?", $this->id);
		$db->prepare_query("REPLACE INTO bannerstats (bannerid, clientid, time, views, clicks, passbacks) SELECT id, clientid, lastupdatetime, views, clicks, passbacks FROM banners WHERE id = ?", $this->id); //bannerstats is UNIQUE on bannerid,time


		if($this->paytype == BANNER_CPC){
			$sumviews = array_sum($this->hourlyviews);

			if($sumviews){
				$clickthrough = array_sum($this->hourlyclicks)*1000/$sumviews;

				$this->priority = $this->payrate*$clickthrough; //effective CPM rate last hour
				$this->priority = max($this->priority, $this->payrate * BANNER_MIN_CLICKTHROUGH); //min clickthrough
				$this->priority = min($this->priority, $this->payrate * BANNER_MAX_CLICKTHROUGH); //max clickthrough
			}
		}

		$this->hour = ($this->hour + 1) % BANNER_SLIDE_SIZE;
		$this->hourlyviews[$this->hour] = 0;
		$this->hourlyclicks[$this->hour]= 0;

		if($this->limitbyhour)
			$this->userviews = array();
	}

	function daily(){
		$this->dailyviews = 0;
		$this->dailyclicks= 0;

		$this->userviews = array();
	}

	function valid($userid, $age, $sex, $loc, $interests, $page, $time){
		if(!$this->enabled)
			return false;
//date
		if($this->startdate >= $time || ($this->enddate && $this->enddate <= $time))
			return false;

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
		if(!$this->sex[$sex])
			return false;

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

//frequency capping
	//this period (day/hour)
		if($this->viewsperday && $this->dailyviews >= $this->viewsperday)
			return false;

		if($this->clicksperday && $this->dailyclicks >= $this->clicksperday)
			return false;

	//views per user
		if($this->viewsperuser && isset($this->userviews[$userid]) && $this->userviews[$userid] >= $this->viewsperuser)
			return false;

//all else works
		return true;
	}
}

class bannerstats{

	var $starttime;

	var $total;

	var $agesex;
	var $loc;
	var $page;
	var $interests;

	function bannerstats(){
		$this->total = 0;
		$this->starttime = time();

		for($i=0; $i < 80; $i++)
			$this->agesex[$i] = array( SEX_UNKNOWN => 0, SEX_MALE => 0, SEX_FEMALE => 0);

		$this->loc = array(0 => 0);
		$this->page = array();
	}

	function hit($age, $sex, $loc, $interests, $page){
		$this->total++;

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

	function factor($ages, $sexs, $locs, $pages){
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
	var $db;

	var $sizes;

	var $numservers;

	var $banners;
	var $bannerids;
	var $bannersizes;

	var $dailyviews;
	var $dailyclicks;

	var $time;

	function bannerserver( & $db, $numservers = 1){

		$this->db = & $db;

		$this->numservers = $numservers;

		$this->sizes = array(	"468x60"	=> BANNER_BANNER,
								"728x90"	=> BANNER_LEADERBOARD,
								"300x250"	=> BANNER_BIGBOX,
								"120x600"	=> BANNER_SKY120,
								"160x600"	=> BANNER_SKY160,
								"120x60"	=> BANNER_BUTTON60,
								"Vulcan"	=> BANNER_VULCAN,
								"Link"		=> BANNER_LINK,
							);


		$this->banners = array();
		foreach($this->sizes as $size){
			$this->banners[$size] = array();
			$this->bannersizes[$size] = array();
		}

		$this->bannerids = array();

		$this->dailyviews = array();
		$this->dailyclicks= array();

		foreach($this->sizes as $size){
			$this->dailyviews[$size] = & new bannerstats();
			$this->dailyclicks[$size]= & new bannerstats();
		}

		$this->time = time();

		$this->getBanners();
	}

	function addBanner($id){
		$this->db->prepare_query("SELECT * FROM banners WHERE id = ?", $id);

		$banner = $this->db->fetchrow();

		$this->db->freeresult();

		if($banner){
			$this->banners[$banner['bannersize']][$banner['id']] = & new banner($banner, $this->numservers);
			$this->bannerids[$banner['id']] = $banner['bannersize'];
			$this->bannersizes[$banner['bannersize']][$banner['id']] = $banner['id'];
			return true;
		}else
			return false;
	}

	function updateBanner($id){
		$this->db->prepare_query("SELECT * FROM banners WHERE id = ?", $id);

		$banner = $this->db->fetchrow();

		$this->db->freeresult();

		if($banner){
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
			return true;
		}else
			return false;
	}

	function deleteBanner($id){
		$this->banners[$this->bannerids[$id]][$id]->minutely($this->db, $this->time); //update stats
		unset($this->banners[$this->bannerids[$id]][$id]);
		unset($this->bannersizes[$this->bannerids[$id]][$id]);
		unset($this->bannerids[$id]);
	}

	function passbackBanner($size, $id){
		if(isset($this->banners[$size][$id]))
			$this->banners[$size][$id]->passback();
	}

	function getBanner($size, $userid, $age, $sex, $loc, $interests, $page, $id = null){
		if($id === null){
			$valid = array();
			if(isset($this->bannersizes[$size])){
				foreach($this->bannersizes[$size] as $id){
					if($this->banners[$size][$id]->valid($userid, $age, $sex, $loc, $interests, $page, $this->time)){
					//adjust by priority^2
						$valid[$id] = (1 + ($this->banners[$size][$id]->priority));// * $ad->priority)*0.25);

					//adjust by number of times this user has seen it. Frequency-capped ads have a higher priority than non-frequency-capped ads
						if($this->banners[$size][$id]->viewsperuser)
							$valid[$id] *= (2 - (isset($this->banners[$size][$id]->userviews[$userid]) ? ($this->banners[$size][$id]->userviews[$userid] / $this->banners[$size][$id]->viewsperuser) : 0));

					//adjust by number of times it has been seen today. Limited ads have higher priority than non-limited ads
						if($this->banners[$size][$id]->viewsperday)
							$valid[$id] *= (3 - ($this->banners[$size][$id]->dailyviews / $this->banners[$size][$id]->viewsperday ));

					//adjust by number of times it has been clicked today. Limited ads have higher priority than non-limited ads
						if($this->banners[$size][$id]->clicksperday)
							$valid[$id] *= (3 - ($this->banners[$size][$id]->dailyclicks / $this->banners[$size][$id]->clicksperday ));
					}
				}
			}

			/*
				weighting based on
					(1 + priority) *
					(2 - ((views by this user today)/(views per user per day))) *
					(3 - ((views today)/(max views per day))) *
					(3 - ((clicks today)/(max clicks per day)))
			*/

			if(count($valid) == 0)
				return 0;

			$id = chooseWeight($valid, false);
		}

//		echo "return $id\n";

		$this->dailyviews[$size]->hit($age, $sex, $loc, $interests, $page);

		$this->banners[$size][$id]->hit($userid);

		return $id;
	}

	function clickBanner($id, $age, $sex, $loc, $interests, $page){
//		echo "id: $id, age: $age, sex: $sex, loc: $loc, page: $page\n";

		if(!isset($this->bannerids[$id])){
			echo "banner doesn't exist!\n";
			return;
		}
		$this->banners[$this->bannerids[$id]][$id]->click();

		$this->dailyclicks[$this->bannerids[$id]]->hit($age, $sex, $loc, $interests, $page);
	}

	function minutely(){
		foreach($this->sizes as $size)
			foreach($this->bannersizes[$size] as $id)
				$this->banners[$size][$id]->minutely($this->db, $this->time);

		return 1;
	}

	function hourly(){
		foreach($this->sizes as $size)
			foreach($this->bannersizes[$size] as $id)
				$this->banners[$size][$id]->hourly($this->db, $this->time);

		return 2;
	}

	function daily(){
		bannerDebug("daily");

		foreach($this->sizes as $size){
			foreach($this->bannersizes[$size] as $id)
				$this->banners[$size][$id]->daily($this->time);

			$viewsdump = gzcompress(serialize($this->dailyviews[$size]));
			$clicksdump= gzcompress(serialize($this->dailyclicks[$size]));

			$this->db->prepare_query("INSERT INTO bannertypestats SET size = ?, time = ?, views = ?, clicks = ?, viewsdump = ?, clicksdump = ?", $size, $this->time, $this->dailyviews[$size]->total, $this->dailyclicks[$size]->total,  $viewsdump, $clicksdump);

			unset($viewsdump, $clicksdump);

			$this->dailyviews[$size] = & new bannerstats();
			$this->dailyclicks[$size]= & new bannerstats();
		}

		return 4;
	}

	function settime($time){
		$this->time = $time;

		$ret = 0;

		if($time % 60 == 0)
			$ret |= $this->minutely();

		if($time % 3600 == 0)
			$ret |= $this->hourly();

		if($time % 86400 == 3600*BANNER_DAILY_HOUR)
			$ret |= $this->daily();

		return $ret;
	}

	function getBanners(){
		$this->db->prepare_query("SELECT * FROM banners WHERE moded = 'y'");

		while($line = $this->db->fetchrow()){
			if(isset($this->banners[$line['bannersize']][$line['id']]))
				break;

			bannerDebug("init $line[id]");

			$this->bannerids[$line['id']] = $line['bannersize'];
			$this->banners[$line['bannersize']][$line['id']] = & new banner($line, $this->numservers);
			$this->bannersizes[$line['bannersize']][$line['id']] = $line['id'];
		}
		$this->db->freeresult();
	}
}

class bannerclient{

	var $db;

	var $sock;
	var $hosts;
	var $dead;

	var $persistant;
	var $timeout;

	var $sizes;
	var $types;
	var $clienttypes = array("agency", "local", "affiliate");

	var $userid;
	var $age;
	var $sex;
	var $loc;
	var $page;
	var $server;


	function bannerclient( & $db, $hosts, $persist = false){

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

		global $userData, $config;

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

	function getBanner($size, $refresh = false, $passback = 0){
		global $cache;

		if(!$this->connect())
			return "";

		fwrite($this->sock, "get $size $this->userid $this->age $this->sex $this->loc $this->interests $this->page $passback\n");

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
		if(!$id)
			return "";

//return $id;

		$banner = $cache->get(array($id,"banner-$id"));

		if(!$banner){
			$this->db->prepare_query("SELECT bannertype, image, link, alt, refresh FROM banners WHERE id = ?", $id);

			$banner = $this->db->fetchrow();

			$cache->put(array($id,"banner-$id"), $banner, 3600);
		}

		if($refresh === true)
			$refresh = $banner['refresh'];

		return $this->getCode($id, $size, $banner['bannertype'], $banner['image'], $banner['link'], $banner['alt'], $refresh);
	}

	function getBannerID($id){
		$this->db->prepare_query("SELECT id, bannersize, bannertype, image, link, alt FROM banners WHERE id = ?", $id);

		if(!$this->db->numrows())
			return "";

		$line = $this->db->fetchrow();

		return $this->getCode($line['id'], $line['bannersize'], $line['bannertype'], $line['image'], $line['link'], $line['alt'], false);
	}

	function click($id, $page){
		if(!$this->connect())
			return;

		fwrite($this->sock, "click $id $this->age $this->sex $this->loc $this->interests $page\n");

		$this->db->prepare_query("SELECT link FROM banners WHERE id = ?", $id);
		return $this->db->fetchfield();
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

		$cache->remove(array($id,"banner-$id"));

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

		$cache->remove(array($id,"banner-$id"));

		return true;
	}

	function getCode($id, $size, $type, $image, $link, $alt, $refresh=false){
		global $config;

		if($refresh)
			echo "<script>parent.settime($refresh);parent.starttimer();</script>";

		switch($type){
			case BANNER_IMAGE:
				list($width, $height) = explode('x', $this->sizes[$size]);
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				if($link=='')
					return "<img src=\"$image\" width='$width' height='$height'" . ($alt == "" ? "" : " alt=\"$alt\"" ) . ">";
				else
					return "<a href=\"bannerclick.php?id=$id\" target=_blank><img src=\"$image\" width='$width' height='$height' border=0" . ($alt == "" ? "" : " alt=\"$alt\"" ) . "></a>";

			case BANNER_FLASH:
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;
				if(substr($alt,0,7) != "http://")
					$alt = $config['bannerloc'] . $alt;

				list($width, $height) = explode('x', $this->sizes[$size]);
				return "<script src=$config[imgserver]/skins/banner.js></script><script>flashbanner('$alt', '$image', '$link', $width, $height, '#000000');</script>";

			case BANNER_IFRAME:
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				list($width, $height) = explode('x', $this->sizes[$size]);
				return "<iframe src='$image' width='$width' height='$height' frameborder=no border=0 marginwidth=0 marginheight=0 scrolling=no></iframe>";

			case BANNER_HTML:
				if(strpos($alt, "%") !== false){
					$rand = rand();
					$alt = str_replace("%rand%",$rand,$alt);
					$alt = str_replace("%page%",$this->page,$alt);
					$alt = str_replace("%age%", $this->age, $alt);
					$alt = str_replace("%sex%", $this->sex, $alt);
					$alt = str_replace("%id%", $id, $alt);
					$alt = str_replace("%size%", $size, $alt); //banner size (ie BANNER_BANNER, BANNER_LEADERBOARD)
					$alt = str_replace("%server%", $this->server, $alt);
				}

				return $alt;

			case BANNER_TEXT:
				if($link == "")
					return "<b>$image</b></a><br>$alt";
				else
					return "<a class=" . $this->linkclass . " href=\"bannerclick.php?id=$id\" target=_blank><b>$image</b></a><br>$alt";
		}
		return "";
	}

}

if(!function_exists('chooseWeight')){
	function chooseWeight($items, $int = true){ //array($id => $weight);
		$totalweight = 0;

		foreach($items as $weight)
			$totalweight += $weight;

		if($totalweight == 0)
			return false;

		if($int)
			$rand = rand(0,$totalweight-1);
		else
			$rand = (rand() / (double)getrandmax()) * $totalweight;

		foreach($items as $id => $weight){
			$rand -= $weight;
			if($rand < 0)
				return $id;
		}

		return false;
	}
}

function bannerDebug($msg){
	echo "[" . gmdate("G:i:s") . "] $msg\n";
}
