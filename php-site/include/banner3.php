<?

class banner{

	var $db;

	var $prev = array();

	var $bannersizes = array("468x60", "120x60", "120x240", "120x600", "300x250");
	var $bannertypes = array("image", "flash", "iframe", "html", "text");
	var $clienttypes = array("agency", "local", "affiliate");

	var $linkclass = "side";

	function banner(){
		global $fastdb;
		$this->db = & $fastdb;
	}

	function getBanner($size){
		global $cache, $config;

		if(!$config['enablebanners'])
			return "";


		$banners = $cache->get("banners-$size");

		if(!$banners){
			$this->db->prepare_query("SELECT id, bannertype, clientid, weight, image, link, alt FROM banners WHERE weight > 0 && bannersize = ?", $size);

			$banners = array();
			while($line = $this->db->fetchrow())
				$banners[$line['id']] = $line;

			$cache->put("banners-$size", $banners, 3600);
		}

		if(!count($banners))
			return "";

		$bannerids = array();
		foreach($banners as $line){
			if(!isset($this->prev[$line['id']]))
				$bannerids[$line['id']] = $line['weight'];
		}

		$id = chooseWeight($bannerids, false);

		$this->prev[$id] = $id;

//could do this without the join (grab statsid is select above, update banners.views hourly), but the resulting race condition probably isn't worth it.
//		$this->db->prepare_query("UPDATE banners, bannerstats SET banners.views = banners.views + 1, bannerstats.views = bannerstats.views + 1 WHERE banners.id = ? && bannerstats.id = banners.statsid", $id);

//		$this->updateBannerHits($id);

		return $this->getCode($id, $size, $banners[$id]['bannertype'], $banners[$id]['image'], $banners[$id]['link'], $banners[$id]['alt']);
	}

	function updateBannerHits($id = false){
		if(!$id)
			$id = $this->prev;
		$this->db->prepare_query("UPDATE banners, bannerstats SET banners.views = banners.views + 1, bannerstats.views = bannerstats.views + 1 WHERE banners.id IN (?) && bannerstats.id = banners.statsid", $id);
	}

	function getBannerID($id){
		$this->db->prepare_query("SELECT id, bannersize, bannertype, image, link, alt FROM banners WHERE id = ?", $id);

		if(!$this->db->numrows())
			return "";

		$line = $this->db->fetchrow();

		return $this->getCode($line['id'], $line['bannersize'], $line['bannertype'], $line['image'], $line['link'], $line['alt']);
	}

	function click($id){
		$this->db->prepare_query("UPDATE banners, bannerstats SET banners.clicks = banners.clicks + 1, bannerstats.clicks = bannerstats.clicks + 1 WHERE banners.id = ? && bannerstats.id = banners.statsid", $id);

		$this->db->prepare_query("SELECT link FROM banners WHERE id = ?", $id);
		return $this->db->fetchfield();
	}

	function getCode($id, $size, $type, $image, $link, $alt){
		global $config;

		switch($type){
			case "image":
				list($width, $height) = explode('x',$size);
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				if($link=='')
					return "<img src=\"$image\" width='$width' height='$height'" . ($alt == "" ? "" : " alt=\"$alt\"" ) . ">";
				else
					return "<a href=\"bannerclick.php?id=$id\" target=_blank><img src=\"$image\" width='$width' height='$height' border=0" . ($alt == "" ? "" : " alt=\"$alt\"" ) . "></a>";

			case "flash":
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;
				if(substr($alt,0,7) != "http://")
					$alt = $config['bannerloc'] . $alt;

				list($width, $height) = explode('x',$size);
				return "<script src=$config[imgserver]/skins/banner.js></script><script>flashbanner('$alt', '$image', '$link', $width, $height, '#000000');</script>";

			case "iframe":
				if(substr($image,0,7) != "http://")
					$image = $config['bannerloc'] . $image;

				list($width, $height) = explode('x',$size);
				return "<iframe src='$image' width='$width' height='$height' frameborder=no border=0 marginwidth=0 marginheight=0 scrolling=no></iframe>";

			case "html":
				if(strpos($alt, "%") !== false){
					global $userData, $PHP_SELF;
					$rand = rand();
					$page = ucfirst(substr($PHP_SELF, 1, -4));
					if($userData['loggedIn']){
						$age = $userData['age'];
						$sex = strtolower(substr($userData['sex'], 0, 1));
					}else{
						$age = 0;
						$sex = 'u';
					}
					$alt = str_replace("%page%",$page,$alt);
					$alt = str_replace("%rand%",$rand,$alt);
					$alt = str_replace("%age%", $age, $alt);
					$alt = str_replace("%sex%", $sex, $alt);
				}

				return $alt;

			case "text":
				if($link == "")
					return "<b>$image</b></a><br>$alt";
				else
					return "<a class=" . $this->linkclass . " href=\"bannerclick.php?id=$id\" target=_blank><b>$image</b></a><br>$alt";
		}
		return "";
	}

	function calculateWeightings($size = false){
		global $cache;
		if(!$size){
			foreach($this->bannersizes as $size)
				$this->calculateWeightings($size);
			return;
		}

		$time = time();
		$ret = array();

		$totalviews = $viewsremaining = $this->viewsperday($size);

		$this->db->prepare_query("SELECT id, clientid, payrate, maxviewsperday, startdate, enddate, maxviews, views, moded, statsid FROM banners WHERE bannersize = ? ORDER BY payrate DESC", $size);

		$statsids = array();
		$banners = array();
		while($line = $this->db->fetchrow()){
			$banners[$line['payrate']][] = $line;
			$statsids[] = $line['statsid'];
		}

		foreach($banners as $banners2){
			$totalViewsAtPayRate = 0;
			foreach($banners2 as $banner)
				if(($banner['maxviews'] == 0 || $banner['views'] < $banner['maxviews']) && $banner['startdate'] <= $time && ($banner['enddate'] <= 0 || $banner['enddate'] > $time) && $banner['moded'] == 'y')
					$totalViewsAtPayRate += $banner['maxviewsperday'];

			$factor = 1;
			if($totalViewsAtPayRate > $viewsremaining)
				$factor = $viewsremaining / $totalViewsAtPayRate;

			foreach($banners2 as $banner){
				if(($banner['maxviews'] == 0 || $banner['views'] < $banner['maxviews']) && $banner['startdate'] <= $time && ($banner['enddate'] <= 0 || $banner['enddate'] > $time) && $banner['moded'] == 'y')
					$weight = $factor * $banner['maxviewsperday'] / $totalviews;
				else
					$weight = 0;

				$this->db->prepare_query("INSERT INTO bannerstats SET bannerid = ?, clientid = ?, time = ?, payrate = ?, weight = ?",
										$banner['id'], $banner['clientid'], $time, $banner['payrate'], $weight);
				$statsid = $this->db->insertid();
				$this->db->prepare_query("UPDATE banners SET weight = ?, statsid = ? WHERE id = ?", $weight, $statsid, $banner['id']);

				$this->db->prepare_query("UPDATE bannerclients, bannerstats SET bannerclients.owed = bannerclients.owed + ((bannerstats.payrate / 100) * (bannerstats.views / 1000)) WHERE bannerclients.id = bannerstats.clientid && bannerstats.id = ?", $banner['statsid']);
				$ret[$banner['id']] = $weight;
			}

			$viewsremaining -= $totalViewsAtPayRate;
			if($viewsremaining < 0)
				$viewsremaining = 0;
		}

		if($viewsremaining > 0)
			deliverMsg(1, "Empty Impressions", "Size = $size\nTotal: $totalviews\nRemaining: $viewsremaining", 0, "Nexopia", 0);

		$this->db->prepare_query("INSERT INTO bannertypestats SELECT 0, ?, ?, SUM(views), SUM(clicks), SUM(views*payrate)/SUM(views) FROM bannerstats WHERE id IN (?)", $size, $time, $statsids);
		$cache->remove("banners-$size");
		return $ret;
	}

	function viewsperday($size){
		switch($size){
			case "468x60":		return 3500000;
			case "120x60":		return 2000000;
			case "120x240":		return 200000;
			case "120x600":		return 2000000;
			case "300x250":		return 450000;
		}

		//get avg num views/day from that size for the past 3 weeks
	}

	function addBanner($bannertype, $bannersize, $clientid, $maxviewsperday, $payrate, $maxviews, $startdate, $enddate, $title, $image, $link, $alt){
		global $mods;

		if(empty($title))	$title = "";
		if(empty($image))	$image = "";
		if(empty($link))	$link = "";
		if(empty($alt))		$alt = "";

		$this->db->prepare_query("INSERT INTO banners SET bannertype = ?, bannersize = ?, clientid = ?, maxviewsperday = ?, payrate = ?, maxviews = ?, startdate = ?, enddate = ?, title = ?, image = ?, link = ?, alt = ?, dateadded = ?",
										$bannertype, $bannersize, $clientid, $maxviewsperday, $payrate, $maxviews, $startdate, $enddate, $title, $image, $link, $alt, time());


		$id = $this->db->insertid();

		$mods->newItem(MOD_BANNER,$id);
	}

	function setModed($id){
		$this->db->prepare_query("UPDATE banners SET moded = 'y' WHERE id IN (?)", $id);
	}

	function updateBanner($id, $maxviewsperday, $payrate, $maxviews, $startdate, $enddate){
		$this->db->prepare_query("UPDATE banners SET maxviewsperday = ?, payrate = ?, maxviews = ?, startdate = ?, enddate = ? WHERE id = ?",
										$maxviewsperday, $payrate, $maxviews, $startdate, $enddate, $id);
	}

	function deleteBanner($id){
		$this->db->prepare_query("SELECT bannerstats.weight FROM banners, bannerstats WHERE banners.id = ? && banners.statsid = bannerstats.id", $id);

		if($this->db->fetchfield() != 0)
			return false;

		$this->db->prepare_query("DELETE FROM banners WHERE id = ?", $id);
	}

	function addClient($userid, $name, $type, $notes){
		$this->db->prepare_query("INSERT INTO bannerclients SET userid = ?, clientname = ?, type = ?, dateadded = ?, notes = ?", $userid, $name, $type, time(), $notes);
	}

	function updateClient($id, $notes){
		$this->db->prepare_query("UPDATE bannerclients SET notes = ? WHERE id = ?", $notes, $id);
	}

	function deleteClient($id){
		global $msgs;
		$this->db->prepare_query("SELECT id FROM banners WHERE clientid = ?", $id);

		if($this->db->numrows()){
			$msgs->addMsg("Client has banners remaining. Remove them first.");
			return false;
		}

		$this->db->prepare_query("SELECT owed FROM bannerclients WHERE id = ?", $id);

		if($this->db->fetchfield() != 0){
			$msgs->addMsg("Client still owes money");
			return false;
		}

		$this->db->prepare_query("DELETE FROM bannerclients WHERE id = ?", $id);
	}

// how is amount owed tracked??

}

