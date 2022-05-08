<?

define("SEX_UNKOWN", 0);
define("SEX_MALE",   1);
define("SEX_FEMALE", 2);


class banner{
	var $id;
	var $clientid;
	var $size;

//targetting
	var $age;	// array( 0 => $default, $age1 => $age1, ...)
	var $sex;	// array( SEX_MALE => t/f, SEX_FEMALE => t/f, SEX_UNKOWN => t/f)
	var $loc;	// array( 0 => $default, $loc1 => $loc1, ...);
	var $page;	// array( 0 => $default, $page1 => t/f, ...)


//limiting
	var $maxviews;
	var $viewsperday;
	var $viewsperuserperday;

	var $startdate;
	var $enddate;	//must be defined, use 2**31 for forever

//priority
	var $payrate;

//stats
	var $hourlyviews;
	var $hourlyclicks;

	var $dailyviews;
	var $dailyclicks;

	var $totalviews;
	var $totalclicks;

	var $userviews; //array(userid => views)

//rendering
	var $type;
	var $title;
	var $image;
	var $link;
	var $alt;

//other
	var $dateadded;
	var $modded;

	function banner(){

		$this->hourlyviews = array();
		$this->hourlyclicks= array();

		for($i = 0; $i < 24; $i++){
			$this->hourlyviews[$i] = & new bannerstats();
			$this->hourlyclicks[$i]= & new bannerstats();
		}

		$this->dailyviews = & new bannerstats();
		$this->dailyclicks= & new bannerstats();

		$this->viewstotal = & new bannerstats();
		$this->clickstotal= & new bannerstats();

		$this->userviews = array();
	}

	function hit($userid, $age, $sex, $loc, $page, $curhour){
		$this->hourlyviews[$curhour]->hit($age, $sex, $loc, $page);
		$this->hourlyclicks[$curhour]->hit($age, $sex, $loc, $page);

		$this->dailyviews->hit($age, $sex, $loc, $page);
		$this->dailyclicks->hit($age, $sex, $loc, $page);

		$this->totalviews->hit($age, $sex, $loc, $page);
		$this->totalclicks->hit($age, $sex, $loc, $page);

		if(!isset($this->userviews[$userid]))
			$this->userviews[$userid] = 0;
		$this->userviews[$userid]++;
	}

	function refreshstats($curhour){

		$this->hourlyviews[$curhour] = & new bannerstats();
		$this->hourlyclicks[$curhour]= & new bannerstats();

		if(!$curhour){ //daily stuff
			$this->dailyviews = & new bannerstats();
			$this->dailyclicks= & new bannerstats();

			$this->totalviews = & new bannerstats();
			$this->totalclicks= & new bannerstats();

			$this->userviews = array();
		}
	}

	function valid($userid, $age, $sex, $loc, $page, $time){
		if(!$this->moded)
			return false;

//targetting
	//age
		if($this->age[0]){ //default true
			if($age && isset($this->age[$age]) && !$this->age[$age])
				return false;
		}else{ //default false
			if(!$age || !isset($this->age[$age]) || !$this->age[$age])
				return false;
		}
	//sex
		if(!$this->sex[$sex])
			return false;
	//location
		if($this->loc[0]){ //default true
			if($loc && isset($this->loc[$loc]) && !$this->loc[$loc])
				return false;
		}else{ //default false
			if(!$loc || !isset($this->loc[$loc]) || !$this->loc[$loc])
				return false;
		}
	//page
		if($this->page[0]){ //default true
			if($loc && isset($this->page[$page]) && !$this->page[$page])
				return false;
		}else{ //default false
			if(!$loc || !isset($this->page[$page]) || !$this->page[$page])
				return false;
		}

//date
		if($this->startdate > $time || $this->enddate < $time)
			return false;

//frequency capping
	//total views
		if($this->totalviews->total > $this->maxviews)
			return false;

	//views per day
		if($this->dailyviews->total > $this->viewsperday)
			return false;

	//views per day per user
		if($this->viewsperdayperuser && isset($this->userviews[$userid]) && $this->userviews[$userid] > $this->viewsperuserperday)
			return false;

//if all else works
		return true;
	}
}

class bannerstats{
	var total;

	var agesex;
	var loc;
	var page;

	function bannerstats(){
		$this->total = 0;

		for($i=0; $i < 80; $i++)
			$this->agesex[$i] = array( SEX_UNKNOWN => 0, SEX_MALE => 0, SEX_FEMALE => 0);

		$this->loc = array(0 => 0);
		$this->page = array();
	}

	function hit($age, $sex, $loc, $page){
		$this->total++;

		$this->agesex[$age][$sex]++;

		if(!isset($this->loc[$loc]))
			$this->loc[$loc] = 0;
		$this->loc[$loc]++;

		if(!isset($this->page[$page]))
			$this->page[$page] = 0;
		$this->page[$page]++;
	}

	function factor($ages, $sexs, $locs, $pages){
		if(!$total)
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

class banners{
	var $sizes;

	var $banners;

	var $hourlyviews;
	var $hourlyclicks;

	var $dailyviews;
	var $dailyclicks;

	var $time;
	var $curday;
	var $curhour;

	function banners(){

		$this->sizes = array(	"468x60",
								"728x90",
								"300x250",
								"120x60",
								"120x600",
								"160x600");


		$this->hourlyviews = array();
		$this->hourlyclicks= array();

		$this->dailyviews = array();
		$this->dailyclicks= array();

		foreach($this->sizes as $size){
			$this->hourlyviews[$size] = array();
			$this->hourlyclicks[$size]= array();

			for($i = 0; $i < 24; $i++){
				$this->hourlyviews[$size][$i] = & new bannerstats();
				$this->hourlyclicks[$size][$i]= & new bannerstats();
			}

			$this->dailyviews[$size] = array();
			$this->dailyclicks[$size]= array();

			for($i = 0; $i < 7; $i++){
				$this->dailyviews[$size][$i] = & new bannerstats();
				$this->dailyclicks[$size][$i]= & new bannerstats();
			}
		}

		$this->time = time();
		$this->curday = gmdate("w", $this->time);	//cur day of the week
		$this->curhour= gmdate("G", $this->time);	//cur hour of the day
	}

	function addBanner(& $banner){
		$this->banners[$banner->size][$banner->id] = & $banner;
	}

	function deleteBanner($size, $id){
		unset($this->banners[$size][$id]);
	}

	function getBanner($size, $userid, $age, $sex, $loc, $page){

		$validids = array();
		foreach($this->banners[$size] as $id => $v)
			if($this->banners[$size][$id]->valid($userid, $age, $sex, $loc, $page, $this->time))
				$validids[] = $id;

		if(count($validids) == 0)
			return 0;

	weighting based on
		priority
		(views by this user today)/(views per user per day)
		((views today)/(max views per day)) / ((total views today)/(total expected views today))




		//routine to figure out which banner to show


		$this->hourlyviews[$size][$curhour]->hit($age, $sex, $loc, $page);
		$this->dailyviews[$size][$curday]->hit($age, $sex, $loc, $page);

		$this->banners[$size][$id]->hit($userid, $age, $sex, $loc, $page, $curhour);

		return $id;
	}

	function hourly(){
		$this->curday = gmdate("w", $this->time);	//cur day of the week
		$this->curhour= gmdate("G", $this->time);	//cur hour of the day

		foreach($this->sizes as $size){
			foreach($this->banners[$size] as $id => $v)
				$this->banners[$size][$id]->refreshstats($this->curhour);

			$this->hourlyviews[$size][$this->curhour] = & new bannerstats();
			$this->hourlyclicks[$size][$this->curhour]= & new bannerstats();

			if(!$this->curhour){ //daily stuff
				$this->dailyviews[$size][$this->curday] = & new bannerstats();
				$this->dailyclicks[$size][$this->curday]= & new bannerstats();
			}
		}
	}
}

//add stuff to accept connections, return bannerid
//store/load hourly/daily stats to/from mysql?


