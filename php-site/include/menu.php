<?

	$mainMenu = & new menu();
	$mainMenu->addItem("Home","/",0,false,false);
	$mainMenu->addItem("Users","profile.php",0,false,false);
	$mainMenu->addItem("Vote","profile.php?sort[mode]=rate",0,false,false);
	$mainMenu->addItem("Forums","forums.php",0,false,false);
	$mainMenu->addItem("User Forums","forumsusercreated.php",0,false,false);
	$mainMenu->addItem("Articles","articlelist.php",0,false,false);
//	$mainMenu->addItem("Schedule","schedule.php",0,false,false);
//	$mainMenu->addItem("Sponsors","sponsors.php",0,false,false);
//	$mainMenu->addItem("Personal","manageprofile.php",1,false,false);
	$mainMenu->addItem("Help",'faq.php',0,false,false);
//	$mainMenu->addItem("PLUS",'product.php?id=1',0,false,false);
	$mainMenu->addItem("Logout","logout.php",1,false,false);
	$mainMenu->addItem("Login","login.php",-1,false,false);
	$mainMenu->addItem("Join","create.php",-1,false,false);

	$personalMenu = & new menu();
//	$personalMenu->addItem("Messages","messages.php",1,false,false);
//	$personalMenu->addItem("Friends","friends.php",1,false,false);
//	$personalMenu->addItem("Bookmarks","bookmarks.php",1,false,false);
	$personalMenu->addItem("Preferences","prefs.php",1,false,false);
	$personalMenu->addItem("Subscriptions","managesubscriptions.php",1,false,false);
	$personalMenu->addItem("Files","managefiles.php",2,false,false);
	$personalMenu->addItem("Gallery","managegallery.php",2,false,false);
	$personalMenu->addItem("Pictures","managepicture.php",1,false,false);
	$personalMenu->addItem("Profile","manageprofile.php",1,false,false);
	$personalMenu->addItem("Journal","manageweblog.php",1,false,false);
	$personalMenu->addItem("Recent Visitors","profileviews.php",2,false,false);
	$personalMenu->addItem("My Page","profile.php?sort[mode]=mypage",1,false,false);

	$adminMenu = & new menu();
	$adminMenu->addItem("Admin Log",'adminlog.php',1,false,'adminlog');
	$adminMenu->addItem("Mirrors",'adminmirrors.php',1,false,'mirror');
	$adminMenu->addItem("Users",'adminuser.php',1,false,'listusers');
	$adminMenu->addItem("User ips",'adminuserips.php',1,false,'listusers');
	$adminMenu->addItem("Deleted Users",'admindeletedusers.php',1,false,'listdeletedusers');
	$adminMenu->addItem("Mods",'adminmods.php',1,false,'listmods');
	$adminMenu->addItem("Mod Log",'adminmodlog.php',1,false,'listmods');
	$adminMenu->addItem("News",'adminnews.php',1,false,'news');
	$adminMenu->addItem("Polls",'adminpolls.php',1,false,'polls');
	$adminMenu->addItem("Stats",'adminstats.php',1,false,'stats');
	$adminMenu->addItem("Hit History",'adminhithist.php',1,false,'stats');
	$adminMenu->addItem("Articles",'adminarticle.php',1,false,'articles');
	$adminMenu->addItem("FAQ",'adminfaq.php',1,false,'faq');
	$adminMenu->addItem("Forums",'forumadmin.php',1,false,'forums');
	$adminMenu->addItem("Forum Mods",'forummods.php?fid=0',1,false,'forummods');
	$adminMenu->addItem("Smilies",'adminsmilies.php',1,false,'smilies');
	$adminMenu->addItem("Categories",'admincats.php',1,false,'config');
	$adminMenu->addItem("Config",'adminconfig.php',1,false,'config');
	$adminMenu->addItem("Word Filter",'adminwordfilter.php',1,false,'wordfilter');
	$adminMenu->addItem("Banned Users",'adminbannedusers.php',1,false,'listbannedusers');
	$adminMenu->addItem("Error Log",'adminerrorlog.php',1,false,'errorlog');
	$adminMenu->addItem("Banners",'bannerclient.php',1,false,'listbanners');
	$adminMenu->addItem("Todo",'admintodo.php',1,false,'todo');


	$menu2 = & new menu();
	$menu2->addItem("Home","/",0,false,false);
	$menu2->addItem("FAQ",'faq.php',0,false,false);
	$menu2->addItem("Lost Password",'lostpass.php',-1,false,false);
	$menu2->addItem("Terms and Conditions",'terms.php',0,false,false);
	$menu2->addItem("Contact Administration",'contactus.php',0,false,false);
	$menu2->addItem("Change Skin",'changeskin.php',0,false,false);
//	$menu2->addItem("Donate",'donate.php',0,false,false);
//	$menu2->addItem("Advertise",'ad.php',0,false,false);





class menuItem{
	var $name;
	var $addr;
	var $loggedIn;	// -1 - can't be, 0 - doesn't matter, 1 - must be, 2 - premium
	var $target;	// false - same, str - target window
	var $admin;		// false - doesn't matter, "" - must be, str - type

	function menuItem($nname,$naddr,$nloggedIn,$ntarget,$nadmin){
		$this->name=$nname;
		$this->addr=$naddr;
		$this->target=$ntarget;
		$this->admin=$nadmin;
		$this->loggedIn=$nloggedIn;

		if($this->admin===true)
			$this->admin="";
		if($this->admin!==false)
			$this->loggedIn=1;
	}
}

class menu{
	var $menu;

	function menu(){
	}

	function addItem($name,$addr,$loggedIn=0,$target=false,$admin=""){
		$this->menu[] = & new menuItem($name,$addr,$loggedIn,$target,$admin);
	}

	function getMenu(){
		global $userData, $mods;

		$ret = array();

		foreach($this->menu as $item){
			if(($item->loggedIn == -1 && $userData['loggedIn']) || ($item->loggedIn >= 1 && !$userData['loggedIn']) || ($item->loggedIn == 2 && !$userData['premium']))
				continue;

			if($item->admin!==false && !$mods->isAdmin($userData['userid'],$item->admin))
				continue;

			$ret[] = array('name'=>($item->name),'addr'=>($item->addr),'target'=>($item->target));
		}
		return $ret;
	}
}

