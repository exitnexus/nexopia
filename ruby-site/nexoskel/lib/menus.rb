
class Menu
	
	@@main = [["Preferences", "/prefs.php"],
		["Subscriptions", "/managesubscriptions.php"],
		["Files", "/managefiles.php"],
		["Gallery", "/my/gallery"],
		["Pictures", "/managepicture.php"],
		["Profile", "/manageprofile.php"],
		["Feed", "/my/friends_updates"],
		["Recent Visitors", "/profileviews.php"],
		["My Page", "/profile.php?requestType=mine"]]
		
	class << self
		def menu(m)
			entries = []
			m.each{|(name,addr)|
				entries << "<a href=\"#{addr}\">#{name}</a>"
			}
			return entries.join("&nbsp;|&nbsp;")
		end
	end
	
	attr :items, true
	
	def initialize()
		@items = [];
	end
	
	class MenuItem
		attr :name, true
		attr :addr, true
		attr :logged_in, true
		attr :admin, true
		attr :target, true
		attr :lock, true
		def initialize(aname, aaddr, alogin, aadmin, atarget, alock)
			@name = aname;
			@addr = aaddr;
			@logged_in = alogin;
			@admin = aadmin;
			@target = atarget;
			@lock = alock;
		end
	end
		
	def addItem(name, addr, login=0, admin=nil, target=nil, lock=nil)
		@items << MenuItem.new(name, addr, login, admin, target, lock)
	end
	
	def make_key(userid, myuserid = false)
		
		user = PageRequest.current.session.user;
		
		if (!myuserid)
			if (user.anonymous?)
				raise "Must have a userid to generate a key";
			else
				myuserid = user.userid;
			end
		end
	
		result = MD5.new("#{myuserid}:blah:#{id}").to_s[0, 10];
		return result;
	end

	def generate
		user = PageRequest.current.session.user;
		state = :notloggedin
		state = :loggedin if (!user.anonymous? && user.state.to_s == "active")
		state = :halfloggedin if (!user.anonymous? && user.state.to_s != "active")
		state = :premium if (!user.anonymous? && user.plus?)
		
		html = [];
		@items.each{|item|
			if ((item.logged_in == -1 && state == :notloggedin) ||
				(item.logged_in == 0) ||
				(item.logged_in == 0.5 && state != :notloggedin) ||
				(item.logged_in == 1 && (state == :loggedin || state == :premium)) ||
				(item.logged_in == 2 && state == :premium))

				if(item.lock)
					item.addr << make_key(user.userid);
				end
	
				continue if(!item.admin && PageRequest.current.session.has_priv?(CoreModule, item.admin))
	
				html << [item.name, item.addr]
			end
		}
		return html
	end
	
	def to_html
		return Menu.menu(generate())
	end

	$menus = {}	
	$menus['main'] = Menu.new();
	$menus['main'].addItem("Home",			"/",							0);
	$menus['main'].addItem("Users",			"/profile.php",					0);
	$menus['main'].addItem("Forums",		"/forums.php",					0);
	$menus['main'].addItem("Articles",		"/articlelist.php",				0);
	$menus['main'].addItem("Music",			'/music',						0);
	$menus['main'].addItem("Video",			'/videos',						0);
	$menus['main'].addItem("Plus",			'/plus.php',					0);
	$menus['main'].addItem("Logout",		"/logout.php?k=",				0.5,	false,	'_top',	true);
	$menus['main'].addItem("Login",			"/login.php",					-1);
	$menus['main'].addItem("Help",			'/help/',						0);
	$menus['main'].addItem("Join",			"/accountcreate",				-1);

	$menus['personal'] = Menu.new();
	$menus['personal'].addItem("Preferences",	"/prefs.php",					0.5);
	$menus['personal'].addItem("Subscriptions",	"/managesubscriptions.php",		1);
	$menus['personal'].addItem("Files",			"/managefiles.php",				2);
	$menus['personal'].addItem("Gallery",		"/my/gallery",					0.5);
	$menus['personal'].addItem("Pictures",		"/managepicture.php",			0.5);
	$menus['personal'].addItem("Profile",		"/manageprofile.php",			0.5);
	$menus['personal'].addItem("Feed",			"/my/friends_updates",			0.5);
	$menus['personal'].addItem("Recent Visitors","/profileviews.php",			2);
	$menus['personal'].addItem("My Page",		"/profile.php?requestType=mine",0.5);

	$menus['admin'] = Menu.new();
	$menus['admin'].addItem("Admin Log",		'/adminlog.php',			1,	'adminlog');
	$menus['admin'].addItem("Users",			'/adminuser.php',			1,	'listusers');
	$menus['admin'].addItem("User ips",			'/adminuserips.php',		1,	'showip');
	$menus['admin'].addItem("Compare ips",		'/admincompareips.php',		1,	'listusers');
	$menus['admin'].addItem("Deleted Users",	'/admindeletedusers.php',	1,	'listdeletedusers');
	$menus['admin'].addItem("Sessions",			'/adminsessions.php',		1,	'listsessions');
	$menus['admin'].addItem("Abuse Log",		'/adminabuselog.php',		1,	'abuselog');
	$menus['admin'].addItem("Login Log",		'/adminloginlog.php',		1,	'loginlog');
	$menus['admin'].addItem("Ignore",			'/messages.php?action=Ignore+List&uid=0', 1, 'ignoreusers');
	$menus['admin'].addItem("Mods",				'/adminmods.php',			1,	'listmods');
	$menus['admin'].addItem("Mod Log",			'/adminmodlog.php',			1,	'listmods');
	$menus['admin'].addItem("Mod History",		'/adminmodhist2.php',		1,	'listmods');
	$menus['admin'].addItem("Add Pic Mods",		'/adminaddpicmods.php',		1,	'editmods');
	$menus['admin'].addItem("Polls",			'/adminpolls.php',			1,	'polls');
	$menus['admin'].addItem("Stats",			'/adminstats.php',			1,	'stats');
	$menus['admin'].addItem("Stats History",	'/adminstatshist.php',		1,	'stats');
	$menus['admin'].addItem("Articles",			'/adminarticle.php',		1,	'articles');
	$menus['admin'].addItem("Forums",			'/forumadmin.php',			1,	'forums');
	$menus['admin'].addItem("Forum Mods",		'/forummods.php?fid=0',		1,	'forummods');
	$menus['admin'].addItem("Forum User Posts",	'/forumuserposts.php',		1,	true);
	$menus['admin'].addItem("Forum Del Thread",	'/forumviewdelthread.php',	1,	'forums');
	$menus['admin'].addItem("Forum Read Thread",'/forumthreadread.php',		1,	'forums');
	$menus['admin'].addItem("Categories",		'/admincats.php',			1,	'categories');
	$menus['admin'].addItem("Word Filter",		'/adminwordfilter.php',		1,	'wordfilter');
	$menus['admin'].addItem("Banned Users",		'/adminbannedusers.php',	1,	'listbannedusers');
	$menus['admin'].addItem("Banners",			'/adminbanners.php',		1,	'listbanners');
	$menus['admin'].addItem("PAYG",				'/adminpayg.php',			1,	'payg');
	$menus['admin'].addItem("Plus Log",			'/adminpluslog.php',		1,	'pluslog');
	$menus['admin'].addItem("Todo",				'/admintodo.php',			1,	'todo');
	$menus['admin'].addItem("Static Pages",		'/adminstaticpages.php',	1,	'staticpages');
	$menus['admin'].addItem("Notifications",	'/adminnotify.php',			1,	true);
	$menus['admin'].addItem("Wiki",				'/wiki/',					1,	true);
	$menus['admin'].addItem("World Domination",	'/',						1,	true);

	$menus['bottom'] = Menu.new();
	$menus['bottom'].addItem("Home",			'/index.php',		0);
	$menus['bottom'].addItem("Lost Password",	'/lostpass.php',	-1);
	$menus['bottom'].addItem("Terms of Use",	'/terms.php',		0);
	$menus['bottom'].addItem("Privacy Policy",	'/privacy.php',		0);
	$menus['bottom'].addItem("Contact Admin",	'/contactus.php',	0);
	$menus['bottom'].addItem("Change Skin",		'/changeskin.php',	0);
	$menus['bottom'].addItem("Advertise",		'/advertise.php',	0);
	$menus['bottom'].addItem("Jobs",			'/jobapp.php',		0);
	$menus['bottom'].addItem("Plus",			'/plus.php',		1);
		

end