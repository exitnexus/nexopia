#!/usr/bin/env ruby

class String
    def hdget(*args)
        return "";
    end
end

def cache()
    return "";
end

def incPollBlock(side)

	if($session == nil)
	    return "";
	end

	#	poll = polls.getPoll();
	poll = nil;

	if(!poll)
	    return "";
	end

	voted = polls.pollVoted(poll.id);
	template = Template.new("include/blocks/poll_block");

	if(!voted)
		poll.key = makeKey(poll.id);
	else
		maxval=0;
		for ans in poll.answers
			if(ans.votes>maxval)
				maxval = ans.votes;
			end
		end

		for ans in poll.answers
                    width = poll.tvotes==0 ? 1 : ans.votes*config.maxpollwidth/maxval.to_i;
                    percent = number_format(poll.tvotes==0 ? 0 : ans["votes"]/poll.tvotes*100,1);
                    ans.width = width;
		end
	end

	template.set('voted', voted);
	template.set("poll", poll);
	template.set("config", config);
	block_contents = template.toString();
	return blockContainer('Polls',side, block_contents);
end

def incBookmarksBlock(side)
        output = "";
	if($session == nil)
		return;
	end

	res = db.prepare_query("SELECT id,name,url FROM bookmarks WHERE userid = # ORDER BY name", $userData.userid);

	output += openBlock('Bookmarks',side);

	output += "<table width=100%>\n";
	output += "<tr><td class=header><b><a href=\"/bookmarks.php\">Bookmarks</a></b></td></tr>";
	while(line = res.fetchrow())
		output += "<tr><td class=side><a href=\"line[url]\" target=_blank>line[name]</a></td></tr>\n";
	end
	output += "</table>\n";

	output += closeBlock();
	return output;
end

def htmlentities(*arg)
    return arg.to_s
end

def incSortBlock(side)
	searchNameScope = [];		# default selected value (STRING: starts/includes/ends)
	searchName = []; 			# default output value (STRING)
	searchMinAge = [];			# default output value (INT)
	searchMaxAge = [];			# default output value (INT)
	searchSex = [];				# default selected value (STRING: Male/Female/Both)
	searchLocation = [];			# default selected value (INT)
	searchSexuality = [];		# default selected value (INT)
	searchInterest = [];			# default selected value (INT)
	searchActivity = [];			# default selected value (INT)
	searchPicture = [];			# default selected value (INT)
	searchSingleOnly = [];		# default selected value (BOOL)
	searchShowList = [];			# default selected value (BOOL)
	nameScopeSelect = [];		# HTML output
	sexSelect = [];				# HTML output
	locationSelect = [];			# HTML output
	interestSelect = [];			# HTML output
	activitySelect = [];			# HTML output
	pictureSelect = [];			# HTML output
	sexualitySelect = [];		# HTML output
	singleCheck = [];			# HTML output
	listCheck = [];				# HTML output
	
	# get values for the output template for all the user search options
	#menuOptions = new userSearchMenuOptions(true, 'incSortBlock', requestType, requestParams);

	template =  Template.new("include/blocks/sort_block");
	template.set('minage', searchMinAge);
	template.set('maxage', searchMaxAge);
	template.set('user', searchName);
	template.set('sex_select_list', sexSelect);
	template.set("loc_select_list", locationSelect);
	template.set("interest_select_list", interestSelect);
	template.set("activity_select_list",  activitySelect);
	template.set("picture_select_list", pictureSelect);
	template.set("sexuality_select_list", sexualitySelect);
	template.set("single_only_checkbox", singleCheck);
	template.set("show_list_checkbox", listCheck);
	template.set("has_plus",$session != nil);# && $userData.premium );
	block_contents = template.result();

	return blockContainer('User Search', side, block_contents);
end

def incMsgBlock(side)
	template =  Template.new("include/blocks/msg_block");
	if($session == nil)
		return;
	end

	newmsgs = array();
	if($userData.newmsgs>0)

		newmsgs = cache.get("newmsglist-$userData[userid]");

		if(newmsgs === false)

			res = messaging.db.prepare_query("SELECT id, `from`, fromname, subject, date FROM msgs WHERE userid = % && folder = # && status='new'", $userData.userid, MSG_INBOX);

			newmsgs = array();
			while(line = res.fetchrow())
				newmsgs[] = line;
			end
			

			cache.put("newmsglist-$userData[userid]", newmsgs, 3600);
		end

		if(count(newmsgs))
			for line in newmsgs
				if(strlen(line.subject) <= 20)
					subject = line.subject;
				else
					subject = substr(line.subject,0,18) + "...";
				end
				line.subject = subject;
			end
		end
	end
	template.set('newmsg_count', count(newmsgs));
	template.set('newmsgs', newmsgs);

	block_contents = template.toString();
	return blockContainer('Messages',side, block_contents);

end


def incFriendsBlock(side)
	template =  Template.new("include/blocks/friends_block");
	if($session == nil)
		return;
	end

	friends = $userData.friends;
	friendnames = getUserName(friends);


	natcasesort(friendnames);

	template.set('friendsonline', $userData.friendsonline);
	template.set('friends', friendnames);
	block_contents = template.toString();
	return blockContainer('Friends',side, block_contents);
end

def incModBlock(side)

	if($session == nil)
		return;
	end

#	print_r(isMod($userData.userid));

	if(!mods.isMod($userData.userid))
		return;
	end

	def getAdminsOnline()
		global mods;

		moduids = mods.getAdmins('visible');

		users = getUserInfo(moduids);

		rows = array();
		for line in users
			if(line.online == 'y')
				rows[line.userid] = line.username;
			end
		end
		

		uasort(rows, 'strcasecmp');

		return rows;
	end

	def getNumModsOnline()
		global mods;

		moduids = mods.getMods();


		users = getUserInfo(moduids);

		count = 0;
		for line in users
			if(line.online == 'y')
				count+=1;
			end
		end
		return count;
	end

	def getGlobalModsOnline()
		global forums, cache, mods;

		uids = cache.get("globalmods");

		if(!uids)
			res = forums.db.prepare_query("SELECT userid FROM forummods WHERE forumid = 0");

			uids = array();
			while(line = res.fetchrow())
				uids[line.userid] = line.userid;
			end

			cache.put("globalmods", uids, 3600);
		end

		adminuids = mods.getAdmins('visible');

		for id in adminuids
			if(isset(uids[id]))
				unset(uids[id]);
			end
		end

		users = getUserInfo(uids);

		rows = array();
		for line in users
			if(line.online == 'y')
				rows[line.userid] = line.username;
			end
		end
		
		uasort(rows, 'strcasecmp');

		return rows;
	end

	adminsonline = cache.get('adminsonline',60,'getAdminsOnline', 0);
	modsonline = cache.get('modsonline',60,'getNumModsOnline', 0) - count(adminsonline);
	globalmodsonline = cache.get('gmodsonline',60,'getGlobalModsOnline');

	for uid in adminsonline.keys
            username = adminsonline[uid];
	    unset(globalmodsonline[uid]);
	end

	moditemcounts = mods.getModItemCounts();

	template =  Template.new("include/blocks/mod_block");

	types = array();
	for type in moditemcounts
            num = moditemcounts[type];
#		if (num > 0)
			types[type] = array(
				'nbsp'		=> str_repeat('&nbsp;',	5 - strlen(num)),
				'num'		=> num,
				'modtype'	=> mods.modtypes[type]
			);
#		end
	end
	
	template.set('types_count', count(types));
	template.set('types', types);

	template.set('modsonline', modsonline);
	template.set('adminsonline_count', count(adminsonline));
	template.set('adminsonline', adminsonline);
	template.set('globalmodsonline', globalmodsonline);
	template.set('globalmodsonline_count', count(globalmodsonline));
	block_contents = template.toString();
	return blockContainer('Moderator',side, block_contents);
end

def incLoginBlock(side)

	if($session != nil)
		return "";
	end
	template = Template.new('include/blocks/login_block');
	template.set('secure_session_checkbox', makeCheckBox('lockip', " Secure Session", false));
	template.set('remember_me_checkbox',makeCheckBox('cachedlogin', " Remember Me",  false) );
	block_content = template.result();
	output = blockContainer('Login', side, block_content);
	
	return output;
end

def incSkinBlock(side)
        output = "";
	output += openBlock('Skin',side);
	output += "<table><form action=_SERVER[PHP_SELF] method=post>";
	output += "<tr><td class=side><select name=newskin>" . make_select_list_key(skins,skin) +"</select><input type=submit name=chooseskin value=Go></td></tr>";
	output += "</form></table>";
	output += closeBlock();
	return output;
end

def incActiveForumBlock(side)

	def getActiveForumThreads()
		global forums;
		res = forums.db.prepare_query("SELECT forumthreads.id,forumthreads.title FROM forumthreads,forums WHERE
                                              forums.id=forumthreads.forumid && forums.official='y' && forums.public = 'y'
                                            && forumthreads.time > '" + (Time.now - 1800) + "' ORDER BY forumthreads.time DESC LIMIT 5");

		rows = array();
		while(line = res.fetchrow())
			rows[line.id] = line.title;
		end
		
		return rows;
	end

	rows = cache.hdget('activethread',30,'getActiveForumThreads');

	template = Template.new('include/blocks/active_forum_block');

	for id in rows
            title = rows[id]
	    title = wrap(title,15);
	end
	
	template.set('rows', rows);
	block_contents = template.toString();

	return blockContainer('Recent Posts', side, block_contents);
end


def incSubscribedThreadsBlock(side)

	if($session == nil || $userData.posts == 0)
		return;
	end



	res = forums.db.prepare_query("SELECT forumthreads.id,forumthreads.title FROM forumread,forumthreads WHERE forumread.subscribe='y' && forumread.userid = # && forumread.threadid=forumthreads.id && forumread.time < forumthreads.time", $userData.userid);
	lines = res.fetchrowset();

	template = Template.new('include/blocks/subscriptions_block');
	for line in lines
		line.title = wrap(line.title,20);
	end
	
	template.set('lines', lines);
	block_contents = template.toString();
	return blockContainer('Subscriptions', side, block_contents);

end

def range(*args)
    return "";
end

def incNewestMembersBlock(side)

=begin	queryParts = array();
	queryParams = array();
	mcparams = array();
	query = "SELECT userid FROM newestusers";
	queryParams[0] = query;

	queryParts[0] = "sex = ?";
	queryParams[1] = $userData.defaultsex;
	mcparams[0] = "sex:$userData.defaultsexend";

	queryParts = "age IN (#)";
	queryParams = range($userData.defaultminage, $userData.defaultmaxage);
	mcparams = 'age:' + implode(',', range($userData.defaultminage, $userData.defaultmaxage));

	query += " WHERE " + implode(" && ", queryParts);

	query += " ORDER BY id DESC LIMIT 5";

	cachekey = 'search-new-user-' + implode('-', mcparams);
	if (!(resultSet = cache.get(cachekey)))
	
		query = call_user_func_array(array(db, 'prepare'), queryParams);

		queryResult = db.query(query);
		resultSet = Array();
		while(row = queryResult.fetchrow())
			resultSet[] = row.userid;
		end
		

		cache.put(cachekey, resultSet, 30);
	end

	users = getUserName(resultSet);

	template = Template.new('include/blocks/list_users_block');
	template.set('users', users);
	block_contents = template.toString();
=end
	
	return blockContainer('New Members', side, "Intentionally blank");
end

def incRecentUpdateProfileBlock(side)

=begin	queryParts = Array();
	queryParams = Array();
	mcparams = array();
	query = "SELECT userid FROM newestprofile";
	queryParams[0] = query;

	queryParts[0] = "sex = ?";
	queryParams[1] = $userData.defaultsex;
	mcparams[0] = "sex:$userData.defaultsexend";

	queryParts[] = "age IN (#)";
	queryParams[] = range($userData.defaultminage, $userData.defaultmaxage);
	mcparams[] = 'age:' . implode(',', range($userData.defaultminage, $userData.defaultmaxage));

	query += " WHERE " . implode(" && ", queryParts);

	query += " ORDER BY id DESC LIMIT 5";

	cachekey = 'search-new-profile-' . implode('-', mcparams);
	if (!(resultSet = cache.get(cachekey)))
	
		query = call_user_func_array(Array(db, 'prepare'), queryParams);

		queryResult = db.query(query);
		resultSet = Array();
		while(row = queryResult.fetchrow())
			resultSet[] = row.userid;
		end

		cache.put(cachekey, resultSet, 30);
	end

	users = getUserName(resultSet);

	template = Template.new('include/blocks/list_users_block');
	template.set('users', users);
	block_contents = template.toString();
=end
	return blockContainer('Updated Profiles', side, "Intentionally blank");
end

def incTextAdBlock(side)

	if($userData.limitads)
		return;
	end

	banner.linkclass = 'sidelink';
	bannertext = banner.getbanner(BANNER_BUTTON60);

	if(bannertext == "")
		return;
	end

	output += openBlock("Great Links",side);

	output += "<table width=100%><tr><td class=side>";
	output += bannertext;
	output += "</td></tr></table>";

	output += closeBlock();
	
	return output;
end


def incSkyAdBlock(side)
        output = "";
        
	if($userData.limitads)
	    return "";
	end
	

	#bannertext = banner.getbanner(BANNER_SKY120);
	bannertext = "SKY BANNER";

	if(bannertext == "")
	    return "";
	end

	output += openBlock("Sponsor",side);

	output += "<br><center>bannertext</center><br>";

	output += closeBlock();

	return output;

end

def incPlusBlock(side)

	if($userData.limitads)
		return "";
	end

	template = Template.new('include/blocks/plus_block');
	block_contents = template.toString();
	return blockContainer('Nexopia Plus', side, block_contents);
end

def incSpotlightBlock(side)

	#user = cache.hdget("spotlight",300,'getSpotlight');

	#if(!user)
        #    return "";
	#end


	#user['pic_url'] = config.thumbloc + floor(user.userid/1000) + "/" + weirdmap(user.userid) + "/user[pic].jpg";
        
	#template = Template.new('include/blocks/spotlight_block');
	#template.set('user', user);
	#block_contents = template.toString();
	
	return blockContainer('Plus Spotlight', side, "Intentionally blank.");

end

=begin
#/*********************** PROBABLY NOT USED ******************/
def incShoppingCartMenu(side)
	global mods, $userData;

	openBlock("Shopping Cart",side);

	output += "&nbsp;<a href=/cart.php>Shopping Cart</a><br>";
	output += "&nbsp;<a href=/checkout.php>Checkout</a><br>";
	output += "&nbsp;<a href=/invoicelist.php>Invoice List</a><br>";
	output += "&nbsp;<a href=/plus.php>Nexopia Plus</a><br>";
	output += "&nbsp;<a href=/paymentinfo.php>Payment Info</a>";

	if($session != nil && mods.isAdmin($userData.userid,'viewinvoice'))
		output += "<hr>";
		output += "&nbsp;<a href=/invoicereport.php>Reports</a><br>";
		output += "<center>";

		output += "<form action=/invoice.php>";
		output += "Invoice ID:<br><input type=text size=10 name=id>";
		output += "<input type=submit value=Go>";
		output += "</form>";

		output += "<form action=/profile.php>";
		output += "Show Username:<br><input type=text size=10 name=uid>";
		output += "<input type=submit value=Go>";
		output += "</form>";

		output += "</center>";
	end

	closeBlock();
end


def msgFoldersBlock(side)
	global $userData;

	openBlock("Message Folders",side);

	output += "&nbsp;<a href=/messages.php?action=folders>Manage Folders</a><br>";

	folders = getMsgFolders();

	foreach(folders as id => name)
		output += "&nbsp;- <a href=/messages.php?folder=id>name</a><br>";

	closeBlock();
end

def incScheduleBlock(side)
	global db;

	res = db.prepare_query("SELECT title,timeoccur FROM schedule WHERE timeoccur > # && scope='global' && moded='y' ORDER BY timeoccur DESC LIMIT 5", time());

	openBlock('Events',side);

	output += "<table>";

	while(line = res.fetchrow())
		output += "<tr><td class=side><a href='/schedule.php?action=showday&month=" . gmdate('n',line.timeoccur) . "&year=" . gmdate('Y',line.timeoccur) . "&day=" . gmdate('j',line.timeoccur) . "&calsort[scope]=global'>line[title]</a></td></tr>";

	output += "</table>";
	closeBlock();
end


=end

def blockContainer(header, side, blockContents)

	template =  Template.new("include/blocks/block_container");
	template.set('background', $skinloc + "/" + (side=='l' ? "left" : "right") + $skindata.blockheadpic);
	template.set('align', (side=='l' ? "left" : "right"));
	template.set('header', header);
	template.set('block_contents', blockContents);
	skindata = Hash[];
	for var in $skindata.instance_variables
            #Hi Graham, this next piece of code is for you <3 -Thomas
            skindata[var.unpack("xa99999").to_s] = eval("$skindata." + var.unpack("xa99999").to_s);
	end
	template.set('skindata', skindata);
	template.set('width', ($skindata.sideWidth - 2*$skindata.blockBorder));
	
	return template.result();

end
