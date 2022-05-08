<?

	$login=1;

	require_once("include/general.lib.php");

	$type = getREQval('type', 'int');
	$section = getREQval('section');

	if($action == "Report" && ($id = getREQval('id', 'int')) && ($reason = getPOSTval('reason')) && $type){

		if($reasonid = getREQval('reasonid', 'int'))
			$reason = $abuselog->reasons[$reasonid] . "\n\n$reason";

		$reason2 = removeHTML($reason);
		$reason2 = parseHTML($reason2);
		$reason2 = nl2br($reason2);

		$db->prepare_query("INSERT INTO abuse SET itemid = #, reason = ?, userid = #, type = ?, time = #", $id, $reason2, $userData['userid'], $type, time());

		$mods->newItem($type, $id);

		if($type == MOD_USERABUSE)
			$abuselog->addAbuse($id, ABUSE_ACTION_USER_REPORT, $reasonid, "User Reported", $reason);

		incHeader();
		echo "Thanks for the report.";
		incFooter();
		exit;
	}

	incHeader();

	switch($type){
		case MOD_GALLERYABUSE:
			echo "Can you give a description of why you think this is abuse?<br>";
			echo "Please give as much information as possible to make it easier for us to deal with.<br>\n";

			reportBox();
			break;

		case MOD_FORUMPOST:
			echo "Can you give a description of why you think this post is abuse?<br>";
			echo "Please give as much information as possible to make it easier for us to deal with.<br>\n";

			reportBox();
			break;

		case MOD_USERABUSE:

			switch($section){

case "abusivemessages":
?>
<font size=3><b>Step 2: Read our advice</b></font><br>
In a lot of cases, there is no need to Report Abuse, as the matter can be dealt with by using the tools we provide. Please read the advice we provide before continuing.<br>
<br>
<b>"A User is sending me abusive/insulting Messages or Comments"</b><br>
<br>
If someone is sending you Messages of leaving you Comments that you do not with to receive, you may use the <b>Ignore</b> function to stop them from ever sending you Messages or Comments again.<br>
To use the <b>Ignore</b> function, go to your <b>Messages</b> page, click the <b>Ignore List</b> at the top of the page and add them to it.<br>
<br>
In your <b>Preferences</b> you also have two more options to control who sends you Messages. You can choose to <b>Ignore Messages From Users Outside your Age Range</b> and <b>Only Accept Messages From Friends</b> by ticking the box next to the option and pressing <b>Update Preferences</b>.<br>
<br>
If you feel someone has broken the law with a message they have sent to you, please contact the Police or School Authorities. If they get in contact with us, we will work with them however we can.<br>
<br>
<a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=otherabuse"?>>If you <b>still wish to make an Abuse Report</b>, Click Here</a>
<?
	break;

case "advertising":
?>
<font size="3"><b>Step 2: Report Advertising</b></font><br>
<br>
<b>"A User is sending me advertising by Messages or Comments"</b><br>
<br>
Nexopia does not tolerate people using our Message or Comments system to send Advertising for commercial products or services.<br>
Please use the form below to Report a user to us for doing this.<br>
<br>
<b>Please Copy and Paste exact message here:</b><br>
<?
	reportBox(ABUSE_REASON_ADVERT);
	break;

case "threats":
?>
<font size="3"><b>Step 2: Reporting Threats to Your Safety</b></font><br>
<br>
<b>"A User has made threats towards you and your safety"</b><br>
<br>
At Nexopia we take threats towards individuals very seriously however we may not be the best able to handle these.<br>
If you are seriously concerned about a threat to your safety a user, please contact the police or school authorities. Any threats via comments or messages from this user should not be deleted so as to have proof of your allegations of threats. To prevent any further harassment on the site you may wish to use the <a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=ignore"?>><b>ignore</b></a> feature.<br>
<br>
If you still wish to have this matter examined by Nexopia administrators, then please fill out the <a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=otherabuse"?>><b>following form</b></a> putting in as many details as you can and an administrator will examine this matter shortly.
<?
	break;

case "offensiveprofile":
?>
<font size="3"><b>Step 2: Reporting Offensive Profile Content</b></font><br>
<br>
<b>"A User has offensive material on the Profile or in their Pictures"</b><br>
<br>
The following are some things not allowed on User's Profiles or Pictures:
<ul>
<li> Nudity
<li> Weapons and Violence
<li> Blood/Gore
<li> Racism
<li> Personal Information (Full Names, Phone Numbers, Addresses, etc)
</ul>
Please use the form below to Report a user to us for doing this.<br>
<br>
<b>Please give details of content to assist us in removing it:</b><br>
<?
	reportBox(ABUSE_REASON_OTHER);
	break;

case "fakepictures":
?>
<font size="3"><b>Step 2: Reporting Fake Pictures</b></font><br>
<br>
<b>"A User is using Fake Pictures"</b><br>
<br>
If you think this person is using pictures that are not them please take your accusations to the <a class=body href="forumthreads.php?fid=39"><b>Fakers Forum</b></a>. <b>YOU MUST HAVE PROOF</b>.<br>
<br>
<br>
Create a new thread there posting links to the Profile of the "faker" and that of the real person or website from which they got the pictures as proof (indicating which user you think is fake if they are taking another users pictures) and the Mods in that forum will deal with the issue.<br>
<br>
If the user is using pictures of you and you have not already done so, please make a picture of yourself (<b>a Sign Pic</b>) holding a piece of paper with your <b>Username and Nexopia</b> written by hand on it. Your whole face, and hand(s) holding the piece of paper must be visible. We use this to prove that you are you.<br>
<br>
If you are reporting a celebrity please provide the celebrity name and if possible link to celebrity website and/or picture.<br>
<?
	reportBox(ABUSE_REASON_FAKE);
	break;

case "underage":
?>
<font size="3"><b>Step 2: Reporting a User for being under 14</b></font><br>
<br>
<b>"A User is under 14 years of age"</b><br>
<br>
To use Nexopia, user's must be 14 years old or older.<br>
Before we can do anything about a User that you suspect is under 14, we <b>must have proof</b>. The only forms of proof we accept is the User admitting their age either on their <b>Profile</b> or in someone's <b>Comments</b>.<br>
<br>
Just knowing someone is under 14 is not enough proof - if we were to delete everyone who anyone claims is under 14, anyone could have anyone else deleted.<br>
<br>
If you have <b>proof</b>, please use the form below to report someone for being under 14:<br>
<br>
<b>Please give details of where the User admits to being under 14:</b><br>
<?
	reportBox(ABUSE_REASON_UNDERAGE);
	break;

case "hacked":
?>
<font size="3"><b>Step 2: Read our advice</b></font><br>
<br>
<b>"A User has "hacked" in to my account"</b><br>
<br>
There is <b>No Way</b> to "hack" in to someone's Nexopia Account. The <b>only way</b> to get in is with the <b>password</b>.<br>
<br>
<b>If you feel someone has gained access to your Account, follow these steps:</b><br>
<ul>
<li> Change your <b>Nexopia password</b> (make it something random - not your name, date of birth, phone number, etc)
<li> Change the password to your <b>Email Account</b> - it's possible someone knows that.
</ul>
<b>If someone has control of your account and has changed your password:</b>
<ul>
<li> If they haven't changed the email address on the Account, go to the <a class=body href="login.php">Login page</a> and click the <b>Lost Password</b> button.
<li> Enter your Username in the first box and press <b>Resend Activation</b>
<li> Check your email. If you do not get an email within 15 minutes, the Email Address has probably been changed on the account. See below
<li> If you get an email, go back to the Login Page and fill in the second part of the page, <b>Your Username:</b>, <b>Your Activation Key:</b>, <b>New Password:</b> and <b>Retype New Password:</b> and press <b>Change Password.</b>.
<li> Be sure to choose a <b>NEW</b> password that know one knows
<li> Change the password to your <b>Email Account</b> - it's possible someone knows that
</ul>
<br>
<b>If someone has control of your account and has already changed the email address:</b><br>
Sorry, you have lost the account - you will need to make a new one. The only way they could have got into your account was with the password.<br>
Once you have made a new account, make a Sign Pic and <a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=fakepictures"?>>report the other account as Fake</a>

<?
	break;

case "ignore":
?>
<font size="3"><b>Step 3 ignoring a user</b></font><br>
<br>
<b>"I want to stop someone from sending me messages or comments"</b><br>
<br>
If someone is sending you Messages of leaving you Comments that you do not wish to receive, you may use the <b>Ignore</b> function to stop them from ever sending you Messages or Comments again.
<br>
To use the <b>Ignore</b> function, go to your <b>Messages</b> page, click the <b>Ignore List</b> at the top of the page and add them to it.<br>
<br>
In your <b>Preferences</b> you also have two more options to control who sends you Messages. You can choose to <b>Ignore Messages From Users Outside your Age Range</b> and <b>Only Accept Messages From Friends</b> by ticking the box next to the option and pressing <b>Update Preferences</b>.<br>
<br>
If you feel someone has broken the law with a message they have sent to you, please contact the Police or School Authorities. If they get in contact with us, we will work with them however we can.<br>
<br>
<a class=body href="<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=otherabuse"?>">If you <b>still wish to make an Abuse Report</b>, Click Here</a>
<?
	break;

case "otherabuse":
?>
<font size="3"><b>Reporting any other type of Abuse</b></font><br>
<br>
<b>Use the form below to Report any type of Abuse not covered by this page</b><br>
<br>
<b>PLEASE NOTE:</b> If your type of abuse has been covered on this page, and you choose to ignore our advice, your Report will probably be ignored and deleted. Please re-read this page before using the form below.<br>
<br>
<b>Please give as many details about the Abuse as possible:</b><br>
<?
	reportBox(ABUSE_REASON_OTHER);
	break;

default:
?>
<font size=4><b>Report Abuse</b></font><br>
This page is designed to help you deal with abuse, and if needed, report it to the Nexopia Administrators.<br>
<b>Please read the page VERY CAREFULLY before continuing</b><br>
<br>
<font size=3><b>Step 1: Select type of Abuse</b></font><br>
Please select the type of Abuse you have suffered, this will help us to better assist you in resoving this Abuse. If you feel the need to select more than one option, please select the one that you feel is worst.<br>
<br>

<b>Messages and Comments</b>
<ul>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=abusivemessages"?>>A User is sending me abusive/insulting Messages or Comments, or you do not wish to have further contact with this individual</a>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=advertising"?>>A User is sending me advertising by Messages or Comments</a>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=threats"?>> If you have recieved threats to your personal well being or safety</a>
</ul>
<br>

<b>Profiles and Pictures</b>
<ul>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=offensiveprofile"?>>A User has offensive material on the Profile or in their Pictures</a>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=fakepictures"?>>A User is using Fake Pictures</a>
</ul>
<br>

<b>General</b>
<ul>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=underage"?>>A User is under 14 years of age</a>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=hacked"?>>A User has "hacked" in to my account</a>
<li><a class=body href=<?="$_SERVER[PHP_SELF]?type=$type&id=$id&section=otherabuse"?>>I wish to report some other form of Abuse</a>
</ul>
<?
			}

			break;

		default:
			echo "Bad Abuse type";
	}

	incFooter();

////////////////////////////////////////

function reportBox($reasonid = 0){
	global $id, $type;

	echo "<form action=$_SERVER[PHP_SELF] method=post>";
	echo "<textarea class=body cols=70 rows=10 name=reason></textarea><br>";
	echo "<input type=hidden name=id value=$id>";
	echo "<input type=hidden name=type value=$type>";
	if($reasonid)
		echo "<input type=hidden name=reasonid value=$reasonid>";
	echo "<input class=body type=submit name=action accesskey='s' value=Report>";
	echo "</form>";
}

