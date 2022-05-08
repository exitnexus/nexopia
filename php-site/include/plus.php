<?

function validUsername($user){
	return (bool)getUserID($user); //returns true if getUserID returns a userid
}

function addPremium($userid, $duration){ //duration in months
	global $db, $cache, $messaging;

	if(empty($userid))
		return "Bad userid: $userid";

	if(!is_numeric($userid)){
		$userid = getUserID($userid);
		if(!is_numeric($userid))
			return "Bad userid: $userid";
	}

	if(empty($duration))
		return "Bad duration";

	$seconds = $duration * (86400*31); //convert to seconds in a 31 day month

	$db->prepare_query("UPDATE users SET premiumexpiry = GREATEST(premiumexpiry,?) + ? WHERE userid = ?", time(), $seconds, $userid);

	$db->prepare_query("INSERT INTO premiumlog SET userid = ?, duration = ?, time = ?", $userid, $seconds, time());

	$cache->remove(array($userid, "userprefs-$userid"));

$subject = "You've got Plus!";
$message = "Thank you for joining the Nexopia Plus service. Your account has now been credited with " . number_format($duration*31,0) . " days of Plus.

Nexopia Plus users gain a whole bunch of new and added features over the standard Nexopia account. Including:

[b][u]Recent Visitors List [/b][/u]
On your menu bar near the top of the page, there is a new option: Recent Visitors. This feature shows who's viewed your page, and if you've been to theirs.

[b][u]Profile Skins [/b][/u]
Change the appearance of your profile. Set background colors, build your own color scheme, and change things regular user can't. Found at the bottom of the profile builder page.

[b][u]Large Gallery [/b][/u]
Show others your photos, the ones of your friends, your pets, your cool graphic arts images in Galleries. Build them using the new link on the top menu bar.

[b][u]Eligible for the Spotlight [/b][/u]
Let the world see your face. With Plus you can now be seen on the Nexopia homepage in the Spotlight section. This spot is selected randomly from among Plus users, and is changed every 5 minutes. To enable this feature, go to preferences, and check off the box that says 'Eligible For Spotlight' under the Profile section.

[b][u]View Profiles Anonymously[/b][/u]
Remain anonymous to others when you're surfing profiles. Other plus users can't see when you've been to their pages unless you enable 'Allow plus members to see that you visited their profile' in the preferences page.

[b][u]Fewer Ads [/b][/u]
Sick and tired of the advertisements cluttering up your pages? Now you can disable them. Under Preferences in the General section, check the 'Show fewer ads' box to disable the advertisements.

[b][u]Extra Pictures [/b][/u]
With Plus now you can have up to 12 profile pictures instead of the usual 8, just upload more.

[b][u]Priority Picture Approval [/b][/u]
Now when you upload a profile picture, automatically that picture jumps ahead of regular users pictures waiting to be moderated. This means that your picture is shown on your profile much sooner.

[b][u]Reset Picture Votes [/b][/u]
Are you tired of downvoters, or just feel that picture isn't really a 3.2? You can now reset the vote

[b][u]Advanced User Search [/b][/u]
New expanded criteria list for you to search.

[b][u]Friends List Notifications[/b][/u]
Find out when someone adds you to their friends list. Now you'll get sent a message notifying you when someone adds you. You can add them to your list, remove yourself from theirs, or do nothing, the choice is yours.

[b][u]Get off other's Friends List [/b][/u]
Someone's added you as a friend, and you don't agree? Remove yourself from their friends lists. Go to your page, click the friends link at the top, who's added you as a friend, and click the little X beside the person you wish to remove from your list.

[b][u]Create a User Forum [/b][/u]
Have a special interest, one that isn't part of any forum? Now you can make your own. Select the User Forums, at the bottom is a link saying 'create forum' and you're off and running.

[b][u]File Uploads and Hosting [/b][/u]
You now can upload files, images, etc, for using them on the Web, quickly host a funny image and then show it off on your profile or in the forums. This service is found under the 'Files' section on the menu.

[b][u]Longer Friends List [/b][/u]
Friends lists now allow for up to 500 people on your friend lists instead of the usual 250

[b][u]Longer Profile Sections[/b][/u]
Doubles the available space, say more about yourself, more likes, dislikes or whatever you want people to know.

[b][u]See Sent Message Status [/b][/u]
Haven't gotten a reply from that message you sent, check to see if that certain person has actually opened your message. In messages, set your filters to either 'Sent' or 'All Folders' and see if your message has been opened and read.

[b][u]Custom Forum Rank[/b][/u]
In the forums, you can now change that little message that goes beside your picture. Tired of being a Newbie, Member, Veteran or an Addict? Customize this in the profile builder page, and show off on the forums.

We are constantly adding new features to the Plus service, so this service is subject to change.";

$messaging->deliverMsg($userid, $subject, $message, 0, "Nexopia", 0);

	return "Plus Added for userid $userid for $duration months\n";
}

//untested
function remindAlmostExpiredPlus(){
	global $db, $messaging;

	$time = time();

	$db->prepare_query("SELECT userid FROM users WHERE premiumexpiry BETWEEN # AND #", $time + 86400*7, $time + 86400*8);

	$to = array();
	while($line = $db->fetchrow())
		$to[] = $line['userid'];

$subject = "Not Much Plus Left!";
$message = "Your Nexopia Plus is going to run out soon!
If you want to keep enjoying the great features of Plus, you're going to have to renew.
[url=plus.php]Click Here[/url] to order more Plus.

Thanks for using Plus, and we hope you choose to keep using it!";

	$messaging->deliverMsg($to, $subject, $message, 0, "Nexopia", 0);
}

function deletePremium($userid){
	global $db, $config, $mods;

//reset forumrank, anonymousviews
	$db->prepare_query("UPDATE users SET forumrank = '', anonymousviews = 'n' WHERE userid = ?", $userid);

//pending forumranks
	$db->prepare_query("SELECT id FROM forumrankspending WHERE userid = ?", $userid);
	if($db->numrows() > 0){
		$id = $db->fetchfield();
		$db->prepare_query("DELETE FROM forumrankspending WHERE userid = ?", $userid);
		$mods->deleteItem('forumrank',$id);
	}

/*
//pics
	$result = $db->prepare_query("SELECT id FROM pics WHERE itemid = ? && priority > ?", $userid, $config['maxpics']);

	while($line = $db->fetchrow($result))
		removePic($line['id']);
*/
//gallery
	$result = $db->prepare_query("SELECT id FROM gallery WHERE userid = ?", $userid);

	while($line = $db->fetchrow($result))
		removeGalleryPic($id);

	$db->prepare_query("DELETE FROM gallerycats WHERE userid = ?", $uid);

//files
	rmdirrecursive( $masterserver . $config['basefiledir'] . floor($userid/1000) . "/" . $userid );

	//custom forums?

}

function transferPremium($from, $to){
	global $db, $msgs;

	if(empty($from) || empty($to)){
		$msgs->addMsg("Missing from ($from) or to ($to)");
		return;
	}

	$db->begin();

	$db->prepare_query("UPDATE premiumlog SET userid = ? WHERE userid = ?", $to, $from);

	if($db->affectedrows() == 0){
		$msgs->addMsg("User $from had no plus");
		return;
	}

	$db->prepare_query("UPDATE users SET premiumexpiry = 0 WHERE userid = ?", $from);

	fixPremium($to);

	$db->commit();
}


function fixPremium($userid){
	global $db, $msgs;

	$expiry = gmmktime(7,0,0,6,1,2004); // end of trial period

	$db->prepare_query("SELECT userid,duration,time FROM premiumlog WHERE userid = ? ORDER BY id ASC", $userid);

	while($line = $db->fetchrow()){
		if($expiry < $line['time'])
			$expiry = $line['time'];

		$expiry += $line['duration'];
	}

	$db->prepare_query("UPDATE users SET premiumexpiry = ? WHERE userid = ?", $expiry, $userid);

	$msgs->addMsg("Plus for $userid set expire " . userDate("D M j, Y g:i a", $expiry));
}


