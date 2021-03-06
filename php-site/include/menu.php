<?

function makeMenus(){
	global $staticbasedomain;
	$menus = array();

	$menus['main'] = new menu();
	$menus['main']->addItem("Home",			"/",							0);
	$menus['main']->addItem("Users",		"/profile.php",					0);
	$menus['main']->addItem("Forums",		"/forums.php",					0);
	$menus['main']->addItem("Articles",		"/articlelist.php",				0);
//	$menus['main']->addItem("Schedule",		"/schedule.php",				0);
//	$menus['main']->addItem("Sponsors",		"/sponsors.php",				0);
//	$menus['main']->addItem("Personal",		"/manageprofile.php",			1);
	$menus['main']->addItem("<img border='0' src='http://$staticbasedomain/files/legacy/icons/new_icon.gif'/>Find Friends",	'/friends/find',			0);
	$menus['main']->addItem("Music",		'/music',						0);
//	$menus['main']->addItem("Store", 		'http://store.nexopia.com',		0);
//	$menus['main']->addItem("Video",		'/videos',						0);
	$menus['main']->addItem("Plus",			'/plus',					0);
//	$menus['main']->addItem("Store",		'http://shop.nexopia.com',		0);
	$menus['main']->addItem("Logout",		"/logout.php?k=",				0.5,	false,	'_top',	true);
	$menus['main']->addItem("Login",		"/account/login",					-1);
	$menus['main']->addItem("Help",			'/help/',						0);
	$menus['main']->addItem("Join",			"/account",					-1);

	$menus['personal'] = new menu();
//	$menus['personal']->addItem("Messages",		"/messages.php",				1);
//	$menus['personal']->addItem("Friends",		"/friends.php",					1);
//	$menus['personal']->addItem("Bookmarks",	"/bookmarks.php",				1);
	$menus['personal']->addItem("Preferences",	"/prefs.php",					0.5);
	$menus['personal']->addItem("Subscriptions","/managesubscriptions.php",		1);
	$menus['personal']->addItem("Files",		"/managefiles.php",				2);
	$menus['personal']->addItem("Gallery",		"/my/gallery",					0.5);
	$menus['personal']->addItem("Profile Pictures",		"/my/pictures",			0.5);
	$menus['personal']->addItem("Edit Profile",		"/my/profile/edit",			0.5);
//	$menus['personal']->addItem("Feed",			"/my/friends_updates",			0.5);
//	$menus['personal']->addItem("Blog",			"/weblog.php",					1);
	$menus['personal']->addItem("Recent Visitors","/profileviews.php",			2);
	$menus['personal']->addItem("My Page",		"/my/profile",					0.5);

	$menus['admin'] = new menu();
	$menus['admin']->addItem("Admin Log",		'/adminlog.php',			1,	'adminlog');
	$menus['admin']->addItem("Users",			'/adminuser.php',			1,	'listusers');
	$menus['admin']->addItem("User ips",		'/adminuserips.php',		1,	'showip');
	$menus['admin']->addItem("Compare ips",		'/admincompareips.php',		1,	'listusers');
	$menus['admin']->addItem("Deleted Users",	'/admindeletedusers.php',	1,	'listdeletedusers');
	$menus['admin']->addItem("Sessions",		'/adminsessions.php',		1,	'listsessions');
	$menus['admin']->addItem("Abuse Log",		'/adminabuselog.php',		1,	'abuselog');
	$menus['admin']->addItem("Login Log",		'/adminloginlog.php',		1,	'loginlog');
	$menus['admin']->addItem("Ignore",			'/messages.php?action=Ignore+List&uid=0', 1, 'ignoreusers');
	$menus['admin']->addItem("Mods",			'/adminmods.php',			1,	'listmods');
	$menus['admin']->addItem("Mod Log",			'/adminmodlog.php',			1,	'listmods');
	$menus['admin']->addItem("Mod History",		'/adminmodhist2.php',		1,	'listmods');
	$menus['admin']->addItem("Add Pic Mods",	'/adminaddpicmods.php',		1,	'editmods');
//	$menus['admin']->addItem("News",			'/wiki/SiteText/News?edit',	1,	'news');
	$menus['admin']->addItem("Polls",			'/adminpolls.php',			1,	'polls');
	$menus['admin']->addItem("Stats",			'/adminstats.php',			1,	'stats');
	$menus['admin']->addItem("Stats History",	'/adminstatshist.php',		1,	'stats');
	$menus['admin']->addItem("Articles",		'/adminarticle.php',		1,	'articles');
//	$menus['admin']->addItem("FAQ",				'/adminfaq.php',			1,	'faq');
	$menus['admin']->addItem("Forums",			'/forumadmin.php',			1,	'forums');
	$menus['admin']->addItem("Forum Mods",		'/forummods.php?fid=0',		1,	'forummods');
	$menus['admin']->addItem("Forum User Posts",'/forumuserposts.php',		1,	true);
	$menus['admin']->addItem("Forum Del Thread",'/forumviewdelthread.php',	1,	'forums');
	$menus['admin']->addItem("Forum Read Thread",'/forumthreadread.php',	1,	'forums');
	$menus['admin']->addItem("Categories",		'/admincats.php',			1,	'categories');
	$menus['admin']->addItem("Word Filter",		'/adminwordfilter.php',		1,	'wordfilter');
	$menus['admin']->addItem("Banned Users",	'/adminbannedusers.php',	1,	'listbannedusers');
	$menus['admin']->addItem("Banners",			'/adminbanners.php',		1,	'listbanners');
//	$menus['admin']->addItem("Products",		'/adminproducts.php',		1,	'editinvoice');
	$menus['admin']->addItem("PAYG",			'/adminpayg.php',			1,	'payg');
	$menus['admin']->addItem("Plus Log",		'/adminpluslog.php',		1,	'pluslog');
//	$menus['admin']->addItem("Contests",		'/admincontests.php',		1,	'contests');
	$menus['admin']->addItem("Todo",			'/admintodo.php',			1,	'todo');
	$menus['admin']->addItem("Static Pages",	'/adminstaticpages.php',	1,	'staticpages');
	$menus['admin']->addItem("Notifications",	'/adminnotify.php',			1,	true);
	$menus['admin']->addItem("Wiki",			'/wiki/',					1,	true);
	$menus['admin']->addItem("World Domination",'/',						1,	true);

	$menus['bottom'] = new menu();
	$menus['bottom']->addItem("Home",			'/index.php',		0);
	$menus['bottom']->addItem("Terms of Use",	'/terms.php',		0);
	$menus['bottom']->addItem("Privacy Policy",	'/privacy.php',		0);
	$menus['bottom']->addItem("Contact Admin",	'/contactus.php',	0);
	$menus['bottom']->addItem("Change Skin",	'/changeskin.php',	0);
	$menus['bottom']->addItem("Advertise",		'/advertise.php',	0);
	$menus['bottom']->addItem("About",		'/about',			0);
	$menus['bottom']->addItem("Careers",		'/careers',			0);
	$menus['bottom']->addItem("Plus",			'/plus',		1);

	return $menus;
}

class menuItem{
	public $name;
	public $addr;
	public $loggedIn;	// -1 - can't be, 0 - doesn't matter, 1 - must be, 2 - plus
	public $target;	// false - same, str - target window
	public $admin;		// false - doesn't matter, true - any type, str - type
	public $lock;		// false - normal, true - add makekey($userid) to the end of the $addr, must be logged in

	function __construct($nname, $naddr, $nloggedIn, $ntarget, $nadmin, $nlock){
		$this->name = $nname;
		$this->addr = $naddr;
		$this->target = $ntarget;
		$this->admin = $nadmin;
		$this->loggedIn = $nloggedIn;
		$this->lock = $nlock;

		if($this->admin === true)
			$this->admin="";
		if($this->admin !== false)
			$this->loggedIn=1;
		if($this->lock && $this->loggedIn <= 0)
			$this->lock = false;
	}
}

class menu{
	public $menu;

	function __construct(){
		$this->menu = array();
	}

	function addItem($name, $addr, $loggedIn = 0, $admin = false, $target = false, $lock = false){
		$this->menu[] = new menuItem($name, $addr, $loggedIn, $target, $admin, $lock);
	}

	function getMenu(){
		global $userData, $mods;

		$ret = array();

		foreach($this->menu as $item){
//			var_dump(Array($item->loggedIn, $userData['halfLoggedIn'], ($item->loggedIn > 0 && !$userData['halfLoggedIn'])));
			if(	($item->loggedIn == -1 && $userData['halfLoggedIn']) ||
				($item->loggedIn == 0.5 && !$userData['halfLoggedIn']) ||
				($item->loggedIn >= 1 && !$userData['loggedIn']) ||
				($item->loggedIn == 2 && !$userData['premium']))
				continue;

			if($item->lock)
				$item->addr .= makekey($userData['userid']);

			if($item->admin!==false && !$mods->isAdmin($userData['userid'],$item->admin))
				continue;

			$ret[] = array('name' => $item->name, 'addr' => $item->addr, 'target' => $item->target);
		}
		return $ret;
	}
}

