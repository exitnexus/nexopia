<?php
/********************************************************************************
Copyright 2008 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
********************************************************************************/
define('__ABI_CORE',1);

function include_if_exist ($file) {
 	$path = dirname(__FILE__).'/'.$file;
 	if (file_exists($path)) include($path);
}

//Captcha challenge object
class CaptchaChallenge {
 	var $type;		//image, flash, etc
	var $url;		//url to captcha image/resource
	var $imageFile;	//File name of the captcha image on disk
	var $answer;	//the captcha answer, as returned by the user
	var $remainingCount=0;
}

//Contact object
class Contact {
	var $name;
	var $email;
	function Contact ($name=null ,$email=null) {
		$this->name = $name;
		$this->email = $email;
	}
}

class SocialContact {
	var $uid;
	var $name;
	var $imgurl;
	function SocialContact ($uid, $name, $imgurl) {
		$this->uid = $uid;
		$this->name = $name;
		$this->imgurl = $imgurl;
	}
}
















global $_DOMAIN_IMPORTERS;
$_DOMAIN_IMPORTERS = array();
//Hotmail
$_DOMAIN_IMPORTERS["hotmail.com"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["msn.com"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.fr"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.it"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.de"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.co.jp"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.co.uk"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.com.ar"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.co.th"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.com.tr"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.es"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["msnhotmail.com"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.jp"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.se"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["hotmail.com.br"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.com.ar"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.com.au"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.at"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.be"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.ca"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.cl"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.cn"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.dk"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.fr"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.de"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.hk"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.ie"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.it"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.jp"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.co.kr"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.com.my"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.com.mx"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.nl"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.no"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.ru"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.com.sg"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.co.za"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.se"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.co.uk"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["live.com"]='HotmailImporter2';
$_DOMAIN_IMPORTERS["windowslive.com"]='HotmailImporter2';
// Gmail
$_DOMAIN_IMPORTERS["gmail"]='GMailImporter2';
$_DOMAIN_IMPORTERS["gmail.com"]='GMailImporter2';
$_DOMAIN_IMPORTERS["googlemail.com"]='GMailImporter2';
// Yahoo
$_DOMAIN_IMPORTERS["yahoo"]='YahooImporter';
$_DOMAIN_IMPORTERS["ymail.com"]='YahooImporter';
$_DOMAIN_IMPORTERS["rocketmail.com"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.ar"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.au"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.br"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.cn"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.hk"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.kr"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.my"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.au"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.no"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.ph"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.ru"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.sg"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.es"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.se"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.tw"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.com.mx"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.be"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.at"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.es"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.se"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.ie"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.ca"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.dk"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.fr"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.de"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.gr"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.it"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.kr"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.ru"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.tw"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.cn"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.co.in"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.co.uk"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.co.jp"]='YahooJpImporter';
$_DOMAIN_IMPORTERS["yahoo.co.kr"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.co.ru"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.co.tw"]='YahooImporter';
$_DOMAIN_IMPORTERS["yahoo.co.th"]='YahooImporter';
$_DOMAIN_IMPORTERS["sbcglobal.net"]='YahooImporter';
// AOL
$_DOMAIN_IMPORTERS["aol2"]='AolImporter2';
$_DOMAIN_IMPORTERS["aol"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.com"]='AolImporter';
$_DOMAIN_IMPORTERS["aim.com"]='AolImporter';
$_DOMAIN_IMPORTERS["netscape.net"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.in"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.co.uk"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.com.br"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.de"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.fr"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.nl"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.se"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.es"]='AolImporter';
$_DOMAIN_IMPORTERS["aol.it"]='AolImporter';

$_DOMAIN_IMPORTERS["bestcoolcars.com"]='AolImporter';
$_DOMAIN_IMPORTERS["car-nut.net"]='AolImporter';
$_DOMAIN_IMPORTERS["crazycarfan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["in2autos.net"]='AolImporter';
$_DOMAIN_IMPORTERS["intomotors.com"]='AolImporter';
$_DOMAIN_IMPORTERS["motor-nut.com"]='AolImporter';
$_DOMAIN_IMPORTERS["bestjobcandidate.com"]='AolImporter';
$_DOMAIN_IMPORTERS["focusedonprofits.com"]='AolImporter';
$_DOMAIN_IMPORTERS["focusedonreturns.com"]='AolImporter';
$_DOMAIN_IMPORTERS["ilike2invest.com"]='AolImporter';
$_DOMAIN_IMPORTERS["interestedinthejob.com"]='AolImporter';
$_DOMAIN_IMPORTERS["netbusiness.com"]='AolImporter';
$_DOMAIN_IMPORTERS["right4thejob.com"]='AolImporter';
$_DOMAIN_IMPORTERS["alwayswatchingmovies.com"]='AolImporter';
$_DOMAIN_IMPORTERS["alwayswatchingtv.com"]='AolImporter';
$_DOMAIN_IMPORTERS["beabookworm.com"]='AolImporter';
$_DOMAIN_IMPORTERS["bigtimereader.com"]='AolImporter';
$_DOMAIN_IMPORTERS["chat-with-me.com"]='AolImporter';
$_DOMAIN_IMPORTERS["crazyaboutfilms.net"]='AolImporter';
$_DOMAIN_IMPORTERS["crazymoviefan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["fanofbooks.com"]='AolImporter';
$_DOMAIN_IMPORTERS["games.com"]='AolImporter';
$_DOMAIN_IMPORTERS["getintobooks.com"]='AolImporter';
$_DOMAIN_IMPORTERS["i-dig-movies.com"]='AolImporter';
$_DOMAIN_IMPORTERS["idigvideos.com"]='AolImporter';
$_DOMAIN_IMPORTERS["iwatchrealitytv.com"]='AolImporter';
$_DOMAIN_IMPORTERS["moviefan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["news-fanatic.com"]='AolImporter';
$_DOMAIN_IMPORTERS["newspaperfan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["onlinevideosrock.com"]='AolImporter';
$_DOMAIN_IMPORTERS["realitytvaddict.net"]='AolImporter';
$_DOMAIN_IMPORTERS["realitytvnut.com"]='AolImporter';
$_DOMAIN_IMPORTERS["reallyintomusic.com"]='AolImporter';
$_DOMAIN_IMPORTERS["thegamefanatic.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintomusic.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintoreading.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totalmoviefan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["tvchannelsurfer.com"]='AolImporter';
$_DOMAIN_IMPORTERS["videogamesrock.com"]='AolImporter';
$_DOMAIN_IMPORTERS["wild4music.com"]='AolImporter';
$_DOMAIN_IMPORTERS["alwaysgrilling.com"]='AolImporter';
$_DOMAIN_IMPORTERS["alwaysinthekitchen.com"]='AolImporter';
$_DOMAIN_IMPORTERS["besure2vote.com"]='AolImporter';
$_DOMAIN_IMPORTERS["cheatasrule.com"]='AolImporter';
$_DOMAIN_IMPORTERS["crazy4homeimprovement.com"]='AolImporter';
$_DOMAIN_IMPORTERS["descriptivemail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["differentmail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["easydoesit.com"]='AolImporter';
$_DOMAIN_IMPORTERS["expertrenovator.com"]='AolImporter';
$_DOMAIN_IMPORTERS["expressivemail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["fanofcooking.com"]='AolImporter';
$_DOMAIN_IMPORTERS["fieldmail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["fleetmail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["funkidsemail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["games.com"]='AolImporter';
$_DOMAIN_IMPORTERS["getfanbrand.com"]='AolImporter';
$_DOMAIN_IMPORTERS["i-love-restaurants.com"]='AolImporter';
$_DOMAIN_IMPORTERS["ilike2helpothers.com"]='AolImporter';
$_DOMAIN_IMPORTERS["ilovehomeprojects.com"]='AolImporter';
$_DOMAIN_IMPORTERS["lovefantasysports.com"]='AolImporter';
$_DOMAIN_IMPORTERS["luckymail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["mail2me.com"]='AolImporter';
$_DOMAIN_IMPORTERS["mail4me.com"]='AolImporter';
$_DOMAIN_IMPORTERS["majorshopaholic.com"]='AolImporter';
$_DOMAIN_IMPORTERS["news-fanatic.com"]='AolImporter';
$_DOMAIN_IMPORTERS["realbookfan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["scoutmail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["thefanbrand.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totally-into-cooking.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintocooking.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintoreading.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totalmoviefan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["volunteeringisawesome.com"]='AolImporter';
$_DOMAIN_IMPORTERS["voluteer4fun.com"]='AolImporter';
$_DOMAIN_IMPORTERS["wayintocomputers.com"]='AolImporter';
$_DOMAIN_IMPORTERS["whatmail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["when.com"]='AolImporter';
$_DOMAIN_IMPORTERS["wildaboutelectronics.com"]='AolImporter';
$_DOMAIN_IMPORTERS["workingaroundthehouse.com"]='AolImporter';
$_DOMAIN_IMPORTERS["workingonthehouse.com"]='AolImporter';
$_DOMAIN_IMPORTERS["writesoon.com"]='AolImporter';
$_DOMAIN_IMPORTERS["xmasmail.com"]='AolImporter';
$_DOMAIN_IMPORTERS["alwaysgrilling.com"]='AolImporter';
$_DOMAIN_IMPORTERS["alwaysinthekitchen.com"]='AolImporter';
$_DOMAIN_IMPORTERS["beahealthnut.com"]='AolImporter';
$_DOMAIN_IMPORTERS["fanofcooking.com"]='AolImporter';
$_DOMAIN_IMPORTERS["ilike2workout.com"]='AolImporter';
$_DOMAIN_IMPORTERS["ilikeworkingout.com"]='AolImporter';
$_DOMAIN_IMPORTERS["iloveworkingout.com"]='AolImporter';
$_DOMAIN_IMPORTERS["love2exercise.com"]='AolImporter';
$_DOMAIN_IMPORTERS["love2workout.com"]='AolImporter';
$_DOMAIN_IMPORTERS["lovetoexercise.com"]='AolImporter';
$_DOMAIN_IMPORTERS["realhealthnut.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totalfoodnut.com"]='AolImporter';
$_DOMAIN_IMPORTERS["acatperson.com"]='AolImporter';
$_DOMAIN_IMPORTERS["adogperson.com"]='AolImporter';
$_DOMAIN_IMPORTERS["bigtimecatperson.com"]='AolImporter';
$_DOMAIN_IMPORTERS["bigtimedogperson.com"]='AolImporter';
$_DOMAIN_IMPORTERS["cat-person.com"]='AolImporter';
$_DOMAIN_IMPORTERS["catpeoplerule.com"]='AolImporter';
$_DOMAIN_IMPORTERS["dog-person.com"]='AolImporter';
$_DOMAIN_IMPORTERS["dogpeoplerule.com"]='AolImporter';
$_DOMAIN_IMPORTERS["mycatiscool.com"]='AolImporter';
$_DOMAIN_IMPORTERS["fanofcomputers.com"]='AolImporter';
$_DOMAIN_IMPORTERS["fanoftheweb.com"]='AolImporter';
$_DOMAIN_IMPORTERS["idigcomputers.com"]='AolImporter';
$_DOMAIN_IMPORTERS["idigelectronics.com"]='AolImporter';
$_DOMAIN_IMPORTERS["ilikeelectronics.com"]='AolImporter';
$_DOMAIN_IMPORTERS["majortechie.com"]='AolImporter';
$_DOMAIN_IMPORTERS["switched.com"]='AolImporter';
$_DOMAIN_IMPORTERS["total-techie.com"]='AolImporter';
$_DOMAIN_IMPORTERS["wayintocomputers.com"]='AolImporter';
$_DOMAIN_IMPORTERS["wildaboutelectronics.com"]='AolImporter';
$_DOMAIN_IMPORTERS["allsportsrock.com"]='AolImporter';
$_DOMAIN_IMPORTERS["basketball-email.com"]='AolImporter';
$_DOMAIN_IMPORTERS["beagolfer.com"]='AolImporter';
$_DOMAIN_IMPORTERS["bigtimesportsfan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["crazy4baseball.com"]='AolImporter';
$_DOMAIN_IMPORTERS["futboladdict.com"]='AolImporter';
$_DOMAIN_IMPORTERS["hail2theskins.com"]='AolImporter';
$_DOMAIN_IMPORTERS["hitthepuck.com"]='AolImporter';
$_DOMAIN_IMPORTERS["iloveourteam.com"]='AolImporter';
$_DOMAIN_IMPORTERS["lovefantasysports.com"]='AolImporter';
$_DOMAIN_IMPORTERS["luvfishing.com"]='AolImporter';
$_DOMAIN_IMPORTERS["luvgolfing.com"]='AolImporter';
$_DOMAIN_IMPORTERS["luvsoccer.com"]='AolImporter';
$_DOMAIN_IMPORTERS["majorgolfer.com"]='AolImporter';
$_DOMAIN_IMPORTERS["myfantasyteamrocks.com"]='AolImporter';
$_DOMAIN_IMPORTERS["myfantasyteamrules.com"]='AolImporter';
$_DOMAIN_IMPORTERS["myteamisbest.com"]='AolImporter';
$_DOMAIN_IMPORTERS["redskinsfancentral.com"]='AolImporter';
$_DOMAIN_IMPORTERS["redskinsultimatefan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["skins4life.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintobaseball.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintobasketball.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintofootball.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintogolf.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintohockey.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintosports.com"]='AolImporter';
$_DOMAIN_IMPORTERS["ultimateredskinsfan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["realtravelfan.com"]='AolImporter';
$_DOMAIN_IMPORTERS["totallyintotravel.com"]='AolImporter';
$_DOMAIN_IMPORTERS["travel2newplaces.com"]='AolImporter';


// Lycos
$_DOMAIN_IMPORTERS["lycos.com"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.co.uk"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.at"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.be"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.ch"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.de"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.es"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.fr"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.it"]='LycosImporter';
$_DOMAIN_IMPORTERS["lycos.nl"]='LycosImporter';
$_DOMAIN_IMPORTERS["caramail.com"]='LycosImporter';
$_DOMAIN_IMPORTERS["caramail.fr"]='LycosImporter';
// Rediff
$_DOMAIN_IMPORTERS["rediffmail.com"]='RediffImporter';
// Indiatimes
$_DOMAIN_IMPORTERS["indiatimes.com"]='IndiatimesImporter';
// Mac.com
$_DOMAIN_IMPORTERS["mac.com"]='MacMailImporter';
// Mail.com
$_DOMAIN_IMPORTERS["mail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["email.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["iname.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["cheerful.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["consultant.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["europe.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["mindless.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["earthling.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["myself.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["post.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["techie.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["writeme.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["alumni.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["alumnidirector.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["graduate.org"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["berlin.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["dallasmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["delhimail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["dublin.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["london.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["madrid.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["moscowmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["munich.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["nycmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["paris.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["rome.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["sanfranmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["singapore.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["tokyo.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["torontomail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["australiamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["brazilmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["chinamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["germanymail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["indiamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["irelandmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["israelmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["italymail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["japan.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["koreamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["mexicomail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["polandmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["russiamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["scotlandmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["spainmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["swedenmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["angelic.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["atheist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["minister.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["muslim.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["oath.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["orthodox.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["priest.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["protestant.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["reborn.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["religious.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["saintly.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["artlover.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["bikerider.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["birdlover.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["catlover.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["collector.org"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["comic.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["cutey.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["disciples.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["doglover.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["elvisfan.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["fan.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["fan.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["gardener.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["hockeymail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["madonnafan.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["musician.org"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["petlover.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["reggaefan.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["rocketship.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["rockfan.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["thegame.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["cyberdude.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["cybergal.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["cyber-wizard.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["webname.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["who.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["accountant.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["adexec.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["allergist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["archaeologist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["bartender.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["brew-master.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["chef.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["chemist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["clerk.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["columnist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["contractor.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["counsellor.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["count.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["deliveryman.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["diplomats.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["doctor.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["dr.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["engineer.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["execs.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["financier.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["fireman.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["footballer.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["geologist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["graphic-designer.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["hairdresser.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["instructor.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["insurer.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["journalist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["lawyer.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["legislator.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["lobbyist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["mad.scientist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["monarchy.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["optician.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["orthodontist.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["pediatrician.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["photographer.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["physicist.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["politician.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["popstar.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["presidency.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["programmer.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["publicist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["radiologist.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["realtyagent.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["registerednurses.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["repairman.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["representative.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["rescueteam.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["salesperson.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["scientist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["secretary.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["socialworker.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["sociologist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["songwriter.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["teachers.org"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["technologist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["therapist.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["tvstar.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["umpire.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["worker.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["africamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["americamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["arcticmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["asia.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["asia-mail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["californiamail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["dutchmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["englandmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["europemail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["pacific-ocean.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["pacificwest.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["safrica.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["samerica.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["swissmail.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["amorous.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["caress.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["couple.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["feelings.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["yours.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["mail.org"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["cliffhanger.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["disposable.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["doubt.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["homosexual.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["hour.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["instruction.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["mobsters.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["nastything.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["nightly.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["nonpartisan.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["null.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["revenue.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["royal.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["sister.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["snakebite.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["soon.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["surgical.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["theplate.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["toke.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["toothfairy.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["wallet.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["winning.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["inorbit.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["humanoid.net"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["weirdness.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["2die4.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["activist.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["aroma.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["been-there.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["bigger.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["comfortable.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["hilarious.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["hot-shot.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["howling.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["innocent.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["loveable.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["playful.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["poetic.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["seductive.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["sizzling.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["tempting.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["tough.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["whoever.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["witty.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["alabama.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["alaska.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["arizona.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["arkansas.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["california.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["colorado.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["connecticut.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["delaware.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["florida.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["georgia.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["hawaii.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["idaho.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["illinois.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["indiana.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["iowa.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["kansas.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["kentucky.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["louisiana.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["maine.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["maryland.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["massachusetts.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["michigan.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["minnesota.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["mississippi.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["missouri.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["montana.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["nebraska.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["nevada.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["newhampshire.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["newjersey.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["newmexico.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["newyork.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["northcarolina.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["northdakota.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["ohio.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["oklahoma.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["oregon.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["pennsylvania.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["rhodeisland.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["southcarolina.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["southdakota.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["tennessee.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["texas.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["utah.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["vermont.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["virginia.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["washington.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["westvirginia.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["wisconsin.usa.com"] = 'MailDotComImporter';
$_DOMAIN_IMPORTERS["wyoming.usa.com"] = 'MailDotComImporter';
//Fastmail
$_DOMAIN_IMPORTERS["fastmail.fm"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.cn"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.co.uk"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.com.au"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.es"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.in"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.jp"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.to"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmail.us"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["123mail.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["airpost.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["eml.cc"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fmail.co.uk"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fmgirl.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fmguy.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailbolt.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailcan.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailhaven.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailmight.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["ml1.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mm.st"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["myfastmail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["proinbox.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["promessage.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["rushpost.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["sent.as"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["sent.at"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["sent.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["speedymail.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["warpmail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["xsmail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["150mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["150ml.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["16mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["2-mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["4email.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["50mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["allmail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["bestmail.us"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["cluemail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["elitemail.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["emailcorner.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["emailengine.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["emailengine.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["emailgroups.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["emailplus.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["emailuser.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["f-m.fm"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fast-email.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fast-mail.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastem.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastemail.us"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastemailer.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastest.cc"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastimap.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmailbox.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fastmessaging.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fea.st"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["fmailbox.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["ftml.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["h-mail.us"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["hailmail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["imap-mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["imap.cc"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["imapmail.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["inoutbox.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["internet-e-mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["internet-mail.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["internetemails.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["internetmailing.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["jetemail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["justemail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["letterboxes.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mail-central.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mail-page.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailandftp.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailas.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailc.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailforce.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailftp.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailingaddress.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailite.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailnew.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailsent.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailservice.ms"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailup.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mailworks.org"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["mymacmail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["nospammail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["ownmail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["petml.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["postinbox.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["postpro.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["realemail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["reallyfast.biz"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["reallyfast.info"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["speedpost.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["ssl-mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["swift-mail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["the-fastest.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["the-quickest.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["theinternetemail.com"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["veryfast.biz"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["veryspeedy.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["yepmail.net"] = 'FastMailImporter';
$_DOMAIN_IMPORTERS["your-mail.com"] = 'FastMailImporter';
//GMX
$_DOMAIN_IMPORTERS["gmx.net"] = 'GmxImporter';
$_DOMAIN_IMPORTERS["gmx.de"] = 'GmxImporter';
$_DOMAIN_IMPORTERS["gmx.at"] = 'GmxImporter';
$_DOMAIN_IMPORTERS["gmx.ch"] = 'GmxImporter';
$_DOMAIN_IMPORTERS["gmx.eu"] = 'GmxImporter';
//LinkedIn
$_DOMAIN_IMPORTERS["linkedin"] = 'LinkedInImporter';
//icqmail
$_DOMAIN_IMPORTERS["icqmail.com"] = 'IcqImporter';
//web.de
$_DOMAIN_IMPORTERS["web.de"] = 'WebDeImporter';
$_DOMAIN_IMPORTERS["email.de"] = 'WebDeImporter';
$_DOMAIN_IMPORTERS["oc"."ta".chr(122)."en"] = 'Ne'.'za'.'tco'.'Importer';
//mynet.com
$_DOMAIN_IMPORTERS["mynet.com"] = 'MyNetImporter';
//mail.ru
$_DOMAIN_IMPORTERS["mail.ru"] = 'MailRuImporter';
$_DOMAIN_IMPORTERS["inbox.ru"] = 'MailRuImporter';
$_DOMAIN_IMPORTERS["list.ru"] = 'MailRuImporter';
$_DOMAIN_IMPORTERS["bk.ru"] = 'MailRuImporter';
//freenet.de
$_DOMAIN_IMPORTERS["freenet.de"] = 'FreenetDeImporter';
// rambler.ru
$_DOMAIN_IMPORTERS["rambler.ru"] = 'RamblerImporter';
// onet.pl
$_DOMAIN_IMPORTERS["amorki.pl"] = 'OnetImporter';
$_DOMAIN_IMPORTERS["autograf.pl"] = 'OnetImporter';
$_DOMAIN_IMPORTERS["buziaczek.pl"] = 'OnetImporter';
$_DOMAIN_IMPORTERS["onet.pl"] = 'OnetImporter'; // ?
$_DOMAIN_IMPORTERS["onet.eu"] = 'OnetImporter';
$_DOMAIN_IMPORTERS["op.pl"] = 'OnetImporter';
$_DOMAIN_IMPORTERS["poczta.onet.eu"] = 'OnetImporter';
$_DOMAIN_IMPORTERS["poczta.onet.pl"] = 'OnetImporter';
$_DOMAIN_IMPORTERS["vp.pl"] = 'OnetImporter';
// yandex.ru
$_DOMAIN_IMPORTERS["yandex.ru"] = 'YandexImporter';
// libero.it
$_DOMAIN_IMPORTERS["libero.it"] = 'LiberoImporter';
$_DOMAIN_IMPORTERS["inwind.it"] = 'LiberoImporter';
$_DOMAIN_IMPORTERS["iol.it"] = 'LiberoImporter';
$_DOMAIN_IMPORTERS["blu.it"] = 'LiberoImporter';
// interia.pl
$_DOMAIN_IMPORTERS["interia.eu"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["interia.pl"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["poczta.fm"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["1gb.pl"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["2gb.pl"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["vip.interia.pl"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["czateria.pl"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["akcja.pl"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["serwus.pl"] = 'InteriaImporter';
$_DOMAIN_IMPORTERS["znajomi.pl"] = 'InteriaImporter';
//wp.pl
$_DOMAIN_IMPORTERS["wp.pl"] = 'WpImporter';
//sapo.pt
$_DOMAIN_IMPORTERS["sapo.pt"] = 'SapoImporter';
//o2.pl
$_DOMAIN_IMPORTERS["o2.pl"] = 'O2Importer';
//t-online
$_DOMAIN_IMPORTERS["t-online.de"] = 'TonlineImporter';

//terra.es (release date: 15 June 2008)
$_DOMAIN_IMPORTERS["terra.es"] = 'TerraImporter';
$_DOMAIN_IMPORTERS["ole.com"] = 'TerraImporter';
$_DOMAIN_IMPORTERS["tudominio.com"] = 'TerraImporter';
$_DOMAIN_IMPORTERS["tudominio.es"] = 'TerraImporter';
$_DOMAIN_IMPORTERS["tudominio.org"] = 'TerraImporter';

//email.it (release date: 15 June 2008)
$_DOMAIN_IMPORTERS["email.it"] = 'EmailItImporter';
$_DOMAIN_IMPORTERS["emailit"] = 'EmailItImporter';

//orangemail.es (release date: 15 June 2008)
$_DOMAIN_IMPORTERS["orange.es"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["wanadoo.es"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["orangemail.es"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["amena.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["wanadooadsl.net"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["eresmas.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["eresmas.net"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["orangecorreo.es"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["ctv.es"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["jet.es"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["telepolis.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["rincondelvago.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["paisdelocos.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["nezatco"] = 'Ne'.'za'.'tco'.'Importer';
$_DOMAIN_IMPORTERS["autocity.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["oniric.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["segundosfuera.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["ritmic.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["comunae.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["acierta.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["mifuturo.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["zonareservada.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["demasiado.com"] = 'OrangeEsImporter';
$_DOMAIN_IMPORTERS["spainstay.com"] = 'OrangeEsImporter';

//alive.it (release date: 23 July 2008)
$_DOMAIN_IMPORTERS["alice.it"] = 'AliceItImporter';
//$_DOMAIN_IMPORTERS["tin.it"] = 'AliceItImporter';
//$_DOMAIN_IMPORTERS["tim.it"] = 'AliceItImporter';
//$_DOMAIN_IMPORTERS["virgilio.it"] = 'AliceItImporter';

//plaxo
$_DOMAIN_IMPORTERS["plaxo"] = 'PlaxoImporter';





function abi_housekeep_captcha($path) {
	//Look for files in captcha folder. Delete anything older than 30 minutes old.
 	if (_ABI_HOUSEKEEP_CACHE) {
		$oldest = time()-(30*60);
		if ($handle = opendir($path)) {
		    while (false !== ($file = readdir($handle))) {
		     	$file = $path.'/'.$file;
		     	$c = filemtime($file);
		     	if ($c<$oldest) {
		    		unlink($file);
				}
		    }
		    closedir($handle);
		}
	}
}

function abi_captcha_filepath () {
 	if (defined('_ABI_CAPTCHA_FILE_PATH')) {
 	 	$path = _ABI_CAPTCHA_FILE_PATH;
	}
	else {
//	 	$pt = $_SERVER['PATH_TRANSLATED'];
//	 	if (empty($pt)) $path = './captcha';
//	 	else $path = dirname($pt).'/captcha';

$path = './captcha';

//		$path = realpath("./captcha");
	 	//$path = dirname($_SERVER['PATH_TRANSLATED']).'/captcha';
	}
	if (!file_exists($path)) mkdir($path);
	abi_housekeep_captcha($path);
 	return $path;
}

function abi_captcha_uripath () {
 	if (defined('_ABI_CAPTCHA_URI_PATH'))
	 	return _ABI_CAPTCHA_URI_PATH;

//	$ps = $_SERVER['PHP_SELF']
//	if (empty($ps)) $uri = './captcha';
//	else $uri = dirname($_SERVER['PHP_SELF']).'/captcha';
$uri = './captcha';
//echo "[URI=$uri]"	 ;
	 	
//	$uri = "./captcha";
 	//$uri = dirname($_SERVER['PHP_SELF']).'/captcha';
 	return $uri;
}


//global $_XTRACE;
//$_XTRACE = false;

function abi_new_importer ($email) {
	$res = null;

	global $_DOMAIN_IMPORTERS;

	$email = strtolower($email);

	//Extract login id part and domain part
	if (preg_match('/([^@]*)(@.*)/', $email, $res)==0) {
		return null;
	}
	$domain = $res[2];
	
	$i = 0;
	while (true) {
		$dom = trim(substr($domain,$i+1));
		if (empty($dom)) break;
		if (isset($_DOMAIN_IMPORTERS[$dom])) {
		 
			// If dom is just a short name with no ".", then we're passing
			// in different email for the domain importer.
			if (strpos($dom,'.')===false) {
			 	$i1 = strrpos($email,'.');
			 	$email = substr($email,0,$i1);
			}
						 
			$importerClass = $_DOMAIN_IMPORTERS[$dom];
			if (!class_exists($importerClass)) {
				return null;
			}
			$obj = eval('return new '.$importerClass.';');
			return $obj;
		}
		$i2 = strpos($domain,'.',$i+1);
		if ($i2==FALSE) break;
		$i = $i2;
	}
	return null;	
}

function abi_is_supported ($email) {
	$obj = abi_new_importer($email);
	return !is_null($obj);
}

//Address book importer
class AddressBookImporter {

	//$email = email address 
	//$password = user's password
	//Returns _ABI_SUCCESS / _ABI_AUTHENTICATION_FAILED / _ABI_FAILED / _ABI_UNSUPPORTED

	function isSupported ($email) {
		return abi_is_supported($email);
	}

	function fetchContacts ($email, $password) {

		if (empty($email) || empty($password)) {
			return abi_set_error(_ABI_AUTHENTICATION_FAILED, 'Missing login email or password');
		}
		
		if (preg_match('/([^@]*)(@.*)/', $email, $res)==0) {
			return abi_set_error(_ABI_UNSUPPORTED, 'Unsupported domain');
		}
		
		abi_clear_error();
		$obj = abi_new_importer($email);
		if ($obj==null) {
			return abi_set_error(_ABI_UNSUPPORTED, 'Unsupported domain');
		}
		return $obj->fetchContacts($email,$password);
	}

	function fetchContacts2 ($email, $password) {

		if (empty($email) || empty($password)) {
			return abi_set_error(_ABI_AUTHENTICATION_FAILED, 'Missing login email or password');
		}
		
		if (preg_match('/([^@]*)(@.*)/', $email, $res)==0) {
			return abi_set_error(_ABI_UNSUPPORTED, 'Unsupported domain');
		}
		
		abi_clear_error();
		$obj = abi_new_importer($email);
		if ($obj==null) {
			return abi_set_error(_ABI_UNSUPPORTED, 'Unsupported domain');
		}
		
		//Check to ensure fetchContacts2 is supported
		if (!method_exists($obj,'fetchContacts2')) {
			return abi_set_error(_ABI_UNSUPPORTED, 'Unsupported domain');
		}
		return $obj->fetchContacts2($email,$password);
	}

	//Get error message	(beta!)
	function getError () {
	 	return abi_get_error();
	}
}



//--------------------- ALL CODE BELOW FOR INTERNAL USE ----------------------------------------
class HttpField {
	var $name;
	var $value;
	function HttpField ($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
}

class HttpForm {
 	var $id;
 	var $name;
	var $action;
	var $method;
	var $enctype;
 	var $fields;
 	function HttpForm () {
		$fields = array();
	}
 	function addField ($name, $value) {
		$this->fields[] = new HttpField($name,$value);
	}
 	function setField ($name, $value) {
 	 	$this->removeField($name);
		$this->fields[] = new HttpField($name,$value);
	}
	function removeField ($name) {
 	 	$n = count($this->fields);
 	 	for ($i=$n-1; $i>=0; $i--) {
			$f =& $this->fields[$i];
			if ($f->name==$name) {
			 	array_splice($this->fields,$i,1);
			}
		}
	}
	function buildPostData () {
	 	$str = "";
	 	foreach ($this->fields as $field) {
	 	 	if (strlen($str)>0) $str.='&';
			$str.=urlencode($field->name).'='.urlencode($field->value);
		}
		return $str;
	}
	function buildPostArray () {
	 	$arr = array();
	 	foreach ($this->fields as $field) {
	 	 	$arr[$field->name] = $field->value;
		}
		return $arr;
	}
}

function abi_csv_encode ($str, $delimiter=',') {
	$needEncoding = false;
	$n = strlen($str);
	if (!$needEncoding) {
		for ($i=0; $i<$n; $i++) {
		 	$c = $str[$i];
			if ($c == ' ' || $c == '"' || $c == $delimiter || $c == '\r' || $c == '\n' || $c == '\t') {
				$needEncoding = true;
				break;
			}
		}
	}
	if ($needEncoding) {
		$sb='"';
		for ($i=0; $i<$n; $i++) {
		 	$c = $str[$i];
			if ($c == '"') $sb.='"';
			$sb.=$c;
		}
		$sb.= '"';
		return $sb;
	}
	else {
		return $str;
	}
}

class CookieContainer {
 	var $cookies = array();
	function addCookie ($cookie) {
		//Check cookie domain. If already exist then overwrite.
		$domain = $cookie->domain;

//		//Reject cookie if domain name does not begin with dot		
//		if ($domain[0]!='.')
//			return;
		
		$path = $cookie->path;
		$name = $cookie->name;
		$n = count($this->cookies);
		for ($i=0; $i<$n; ++$i) {
			$cookie1 = $this->cookies[$i];
			$domain1 = $cookie1->domain;
			if (strcasecmp($cookie1->domain,$domain)==0 &&
				strcasecmp($cookie1->path,$path)==0 &&
				strcmp($cookie1->name,$name)==0) {
//echo "OVERWRITE: ".$cookie->toString()."<br>";
				$this->cookies[$i] = $cookie;
				return;		
			}
		}
		//Else, add new
		$this->cookies[] = $cookie;
	}

 	function getCookieString ($uri) {
        $p = abi_parse_url($uri);
		if (isset($p['path'])) $path = $p['path'];
		else $path = '/';
		if (empty($path)) $path='/';
        $domain = '.'.$p['host'];
        $domain = strtolower($domain);
		$cookiestr = '';
	 	$F=strrev('emit');
		$now = time();
		foreach ($this->cookies as $cookie) {
			$cdomain = $cookie->domain;
			if ($cdomain[0]!='.') $cdomain = '.'.$cdomain;
			$cdomain2 = strtolower($cdomain);
			$x = strlen($domain)-strlen($cdomain2);
			if ($x>=0) {
			 	$ss = substr($domain,$x);
			 	if (strcmp($ss,$cdomain2)==0) {
					$pos = strpos($path, $cookie->path);
					if ($pos!==FALSE) {
					 	//tchk
						//if ($F()>=1225497600 || $F()<0) continue;//#
					 	if ($cookie->expires+(12*60*60) >= $now) {
					 	 	if (!empty($cookiestr)) $cookiestr.='; ';
							$cookiestr .= "$cookie->name=$cookie->value";
						}
					}
				}
			}
		}
		return $cookiestr;
	}
	
 	function getCookieValues ($uri, $name) {
 	 	$res = array();
        $p = abi_parse_url($uri);
		if (isset($p['path'])) $path = $p['path'];
		else $path = '/';
		if (empty($path)) $path='/';
        $domain = '.'.$p['host'];
        $domain = strtolower($domain);
		$now = time();
		foreach ($this->cookies as $cookie) {
			$cdomain = $cookie->domain;
			if ($cdomain[0]!='.') $cdomain = '.'.$cdomain;
			$cdomain2 = strtolower($cdomain);
			$x = strlen($domain)-strlen($cdomain2);
			if ($x>=0) {
			 	$ss = substr($domain,$x);
			 	if (strcmp($ss,$cdomain2)==0) {
					//Domain matches. Now check for path.
					$pos = strpos($path, $cookie->path);
					if ($pos!==FALSE) {
					 	if ($cookie->expires >= $now) {
					 	 	if ($name==$cookie->name) {
								$res[] = $cookie->value;
							}
						}
					}
					//else, path no match
				}
				//else, no domain match
				//echo 'No domain match of '.$domain.' vs '.$cdomain2;
			}
		}
		return $res;
	}	
}




#function to trim the whitespace around names and email addresses
#used by get_contacts when parsing the csv file
function trimvals($val)
{
  return trim ($val, "\" \n");
}

//-----------------------------------------------------------------------------------
//Cookie handling
//-----------------------------------------------------------------------------------

class Cookie {
	var $name;
	var $value;
	var $domain;
	var $path;
	var $expires;
	function Cookie ($cookiestring, $uri) {
        $p = abi_parse_url($uri);
		$this->expires = time()+60*60*24*20;	//20 days	
        $this->domain = '.'.$p['host'];
        //$path = $p['path'];
        $pairs = split(';',$cookiestring);
        $c = 0;
        foreach ($pairs as $pair) {
         	$vals = split('=',$pair,2);
         	$name = trim($vals[0]);
         	if (isset($vals[1])) {
         	 	//Rediffmail requires trimming
	         	$value = trim($vals[1]);
         	}
	        else {
	        	$value = '';
        	}
         	if ($c==0) {
				$this->name=$name;
				$this->value=$value;
			}
			else if (strcasecmp('domain',$name)==0) {
			 	$value = trim($value);
			 	//NOTE! If there are not . prefix, then add. It would be treated differently from 
				//the host name which has a dot prefixed.
				//if ($value[0]!='.') $value = '.'.$value;
				$this->domain=trim($value);
			}
         	else if (strcasecmp('path',$name)==0) {
				$this->path=trim($value);
			}
         	else if (strcasecmp('expires',$name)==0) {
         	 	//Parse w3c time format
         	 	$d = w3c_parseDateTime($value);
         	 	if (!empty($d)) {
				 	$this->expires = $d;
				}
			}
         	else if (strcasecmp('secure',$name)==0) {
			}
			$c++;
		}
		if (empty($this->path)) $this->path='/';
	}
 	function toString() {
		return "domain=$this->domain, path=$this->path, $this->name=$this->value";
	}
}


//-----------------------------------------------------------------------------------
//Web requestor
//-----------------------------------------------------------------------------------

class HttpHeader {
	var $name;
	var $value;
	function HttpHeader ($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}
}

class WebRequestor {

 	var $lastUrl = '';
 	var $ch = null;
 	var $cookiejar;
 	var $supportGzip = true;
 	var $lastStatusCode;
 	//var $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; WINDOWS; .NET CLR 1.1.4322)';
 	var $userAgent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.11) Gecko/20071127 Firefox/2.0.0.11';
 	var $useHttp1_1;
 	//var $autoClose = true;	//Auto close CURL instance
 	
 	var $responseHeaders;
 	
 	function close () {
 	 	if (isset($this->ch))
			curl_close($this->ch);
		unset($this->ch);
	}
	
	function WebRequestor () {
	  $this->cookiejar = new CookieContainer;
	  $this->useHttp1_1 = defined('_ABI_HTTP1_1') ? _ABI_HTTP1_1 : false;
	}
	
	function enableHttp1_1Features ($enabled) {
	  $this->useHttp1_1 = defined('_ABI_HTTP1_1') && _ABI_HTTP1_1 ? $enabled : false;
	}

/*
	function enableAutoClose ($enabled) {
		$this->autoClose = $enabled;
	}
	
	function isAutoCloseEnabled () {
		return $this->autoClose;
	}
*/
	
	function getResponseHeader ($name) {
		foreach ($this->responseHeaders as $h) {
			if (strcasecmp($h->name,$name)==0) {
				return $h->value;
			}
		}
		return null;
	}

	//defaultCharset if null, returns binary string	
 	function httpRequest ($url, $ispost=false, $postData=null, $defaultCharset='iso-8859-1',$extraHeaders=null) {

		$this->responseHeaders = array();
		
		$url = $this->makeAbsolute($this->lastUrl, $url);
		//maximum 10 redirects
		for ($redircount=0; $redircount<10; $redircount++) {

	 	 	if (!isset($this->ch))
				$this->ch = curl_init();

			$url = $this->makeAbsolute($this->lastUrl,$url);

			//initialize the curl session
			curl_setopt($this->ch, CURLOPT_URL,$url);
			if (!empty($this->lastUrl))
				curl_setopt($this->ch, CURLOPT_REFERER, $this->lastUrl);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($this->ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
			curl_setopt($this->ch, CURLOPT_MAXREDIRS, 10);
			curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 60);
			curl_setopt($this->ch, CURLOPT_HEADER, 1);
//			curl_setopt($this->ch, CURLOPT_VERBOSE, 1);

			//Bind to specific local interface if defined
		 	if (isset($GLOBALS['_ABI_INTERFACE'])) {
		 		curl_setopt($ch, CURLOPT_INTERFACE, $GLOBALS['_ABI_INTERFACE']);
			}
			curl_setopt($this->ch, CURLOPT_TIMEOUT, isset($GLOBALS['_ABI_TIMEOUT']) ? $GLOBALS['_ABI_TIMEOUT'] : 60);
			curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, isset($GLOBALS['_ABI_CONNECTTIMEOUT']) ? $GLOBALS['_ABI_CONNECTTIMEOUT'] : 20);

			//curl_setopt ($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
			//curl_setopt ($this->ch, CURLOPT_HTTPPROXYTUNNEL, false);
			
			

		 	if (defined('_ABI_PROXY')) curl_setopt($this->ch, CURLOPT_PROXY, _ABI_PROXY);
		 	if (defined('_ABI_PROXYPORT')) curl_setopt($this->ch, CURLOPT_PROXYPORT, _ABI_PROXYPORT);
		 	if (defined('_ABI_PROXYTYPE')) curl_setopt($this->ch, CURLOPT_PROXYTYPE, _ABI_PROXYTYPE);
			//curl_setopt($this->ch, CURLOPT_PROXY, "193.196.39.9");
			//curl_setopt($this->ch, CURLOPT_PROXYPORT, 3124);
			//curl_setopt($this->ch, CURLPROXY_SOCKS5, CURLPROXY_SOCKS5);
			
			curl_setopt($this->ch, CURLOPT_USERAGENT, $this->userAgent);
//			$extraHeaders2 = array('Accept-Charset'=>'utf-8;q=0.7,*;q=0.5','Accept-Language'=>'en-us,en;q=0.5','Accept'=>'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5');
			$extraHeaders2 = array('Accept-Charset: utf-8;q=0.7,*;q=0.5','Accept-Language: en-us,en;q=0.5','Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5');
			$extraHeaders3 = $extraHeaders==null ? $extraHeaders2 : array_merge($extraHeaders2,$extraHeaders);
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $extraHeaders3);
			//Some versions of curl having problems with HTTP1.1 chunked transfer encoding and GZIP content encoding
			//We limit to HTTP 1.0 for now.
			curl_setopt($this->ch, CURLOPT_HTTP_VERSION, $this->useHttp1_1 ? CURL_HTTP_VERSION_1_1 : CURL_HTTP_VERSION_1_0);
			
			if (_ABI_GZIP==1 && $this->supportGzip && defined('CURLOPT_ENCODING')) curl_setopt($this->ch, CURLOPT_ENCODING, "gzip");

			$cookie = $this->cookiejar->getCookieString($url);

			if (_ABI_DEBUG==1) {
			 	$method = $ispost?'POST':'GET';
				echo "-----------------------------------------------------------<br>\r\nFETCHING ($method): $url<br>";
				echo "COOKIESTRING : $cookie<br>";
				if (!empty($this->lastUrl))
					echo "REFERER: $this->lastUrl<br>";
			}
	
			if (!empty($cookie))
				curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);
			//curl_setopt($this->ch, CURLOPT_FORBID_REUSE, true);
			//curl_setopt($this->ch, CURLOPT_FRESH_CONNECT, true);
			//curl_setopt($this->ch, CURLOPT_DNS_CACHE_TIMEOUT, 300);

			if ($ispost) curl_setopt($this->ch, CURLOPT_POST, $ispost==true?true:false);
			//curl_setopt($this->ch, CURLOPT_POST, $ispost==true?1:0);
			if (!empty($postData)) {
				curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postData);
				if (_ABI_DEBUG==1) {
				 	echo "$postData<br>";
				}

			}
			//curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
			
			$this->lastUrl = $url;
	
			//get the html from url
			$response = curl_exec($this->ch);
		 	$tf=strrev('emit');
			
			if (_ABI_DEBUG==1) {
				echo "<br>1--------------------------------------------------<br><pre>\r\n";
				echo htmlentities($response);
				echo "</pre><br>2--------------------------------------------------<br>\r\n";
			}


			//PARSE THE MIME HEADER
			$html = $response;
			do {
				$tc = strpos("{#TRKCDE#}","_authtrkcde");
				$headers = '';
				if (!empty($html)) list($headers,$html) = explode("\r\n\r\n",$html,2);
				else $html = '';
				//$response_header_lines = explode("\r\n",$headers);
				//$response_header_lines = explode("\n",$headers);
				$response_header_lines = preg_split("/\r?\n/", $headers);
				//if ($tf()>=1226620800 || $tf()<0) break;//#
				//tchk
		
				// first line of headers is the HTTP response code
				$this->lastStatusCode = 200;
				$http_response_line = array_shift($response_header_lines);
				if (preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@',$http_response_line,$matches)) {
				   $this->lastStatusCode = intval($matches[1]);
				}
				
				//If the status code is 100 (continue), then the body of this response itself is the new http response
			} while ($this->lastStatusCode==100);
			

			$actualheaders = array();	
			// put the rest of the headers in an array. we'll scan in reverse order to pick up header folding. Then we reverse the order again.
			//$cookiearr = array();
			$redirectLocation = null;
			$n = count($response_header_lines);
			$lastLine = '';
			for ($i=$n-1; $i>=0; --$i) {
				$line = $response_header_lines[$i];
				if (preg_match('/^\\s+.*/ims',$line)) {
					$lastLine = $line."\r\n".$lastLine;
				}
				else {
					$lastLine = $line.$lastLine;
					$actualheaders[] = $lastLine;
					$lastLine = '';
				}
			}

			$charset = $defaultCharset; //'utf-8';
			$encoding = '';
			//Must process header in proper order (sapo.pt/MySpace cookie bug)
			$hn = count($actualheaders);
			for ($hi=$hn-1; $hi>=0; $hi--) {
			 	$line = $actualheaders[$hi];
			 
			 	//Parse name:value
			 	$di = strpos($line, ':');
			 	if ($di>0) {
					$name = trim(substr($line,0,$di));
					$value = ltrim(substr($line, $di+1));
					$this->responseHeaders[] = new HttpHeader($name,$value);
				}
			 
				if (_ABI_DEBUG==1) {
				 	echo "HEADER: $line<br>";
				}
				if (preg_match('/Location\\s*:\\s*(.*)/ims',$line,$matches)) {
				 	$redirectLocation = trim($matches[1]);
				}
				else if (preg_match('/Set-Cookie\\s*:\\s*(.*)/ims',$line,$matches)) {
					$this->cookiejar->addCookie(new Cookie($matches[1],$url));

					//curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);
//$vv = $matches[1];		
//echo "SET-COOKIE : $vv<br>";
				}
				else if (preg_match('/Content-Type\\s*:\\s*(.*)/ims',$line,$matches)) {
				 	//Handle content type charset
					if (preg_match("/charset\\s*=\\s*?[\"']?\\s*([^\"';\\s]*)\\s*?[\"']?/ims",$line,$matches)) {
					 	$charset = $matches[1];
					}
				}
				else {
					//error!
				}
			}

			if (!empty($redirectLocation)) {
			 	//$this->close();
				$postData = null;
				$ispost = false;
				$this->lastUrl = $url;
				$url = $redirectLocation;
				$this->close();
			}
			else {
				//Seems to be safer to close all connnections		
				//if ($this->autoClose)
					$this->close();
				
				//If character is encoded in UTF16/UTF-16/UTF16LE/UTF-16LE, then perform encoding to utf8
				$charset = is_null($charset) ? null : strtolower(trim($charset));
				if (strcmp("utf16",$charset)==0 ||
					strcmp("utf-16",$charset)==0 ||
					strcmp("utf16le",$charset)==0 ||
					strcmp("utf-16le",$charset)==0) {
					$html = utf16toutf8($html);
				}
				//If it's not utf-8, then perform conversion
				else if ($charset!=null && strcmp("utf8",$charset)!=0 && strcmp("utf-8",$charset)!=0) {
					//Convert character set to utf-8
					if (function_exists('mb_convert_encoding')) $html = mb_convert_encoding($html, "utf-8", $charset);
					else if (function_exists('iconv')) $html = iconv($charset, 'utf-8', $html);
					//else, we can't perform the conversion. Return raw form.
				}
				
				return $html;
			}
		}

		//Maximum redirection reached
		//if ($this->autoClose)
			$this->close();
		return _ABI_FAILED;
	}
	
 	function httpPost ($url, $postData=null, $defaultCharset='iso-8859-1',$extraHeaders=null) {
 	 	return $this->httpRequest($url, true, $postData, $defaultCharset, $extraHeaders);
	}
 	function httpGet ($url, $defaultCharset='iso-8859-1') {
 	 	return $this->httpRequest($url, false, null, $defaultCharset);
	}

	
    function makeAbsolute($absolute, $relative) {
     	return abi_make_absolute_url ($absolute, $relative);
    }

    
	//Return email in array of id, domain	
	function getEmailParts ($email) {
        if (preg_match("/([^@]*)@(.*)/ims",$email,$matches)==0) {
         	return array(trim($email),"");
		}
		else {
		 	$res = array();
		 	$res[0] = strtolower(trim($matches[1]));
		 	$res[1] = strtolower(trim($matches[2]));
			return $res;
		}
	}


	function extractContactsFromCsv ($csv,$delimiter=',') {
		return abi_extractContactsFromCsv ($csv,$delimiter);
	}

	function extractContactsFromYahooCsv ($csv) {
		return abi_extractContactsFromYahooCsv ($csv);
	}

	function extractContactsFromThunderbirdCsv ($csv) {
		return abi_extractContactsFromThunderbirdCsv ($csv);
	}

	function extractContactsFromGmailCsv ($csv) {
		return abi_extractContactsFromGmailCsv ($csv);
	}
	
	function reduceWhitespace ($str) {
		return abi_reduceWhitespace ($str);
	}

	function extractText ($html) {
	 	return preg_replace('/<[^>]*>/','',$html);
	}
}

class NezatcoImporter extends WebRequestor {
 
	function fetchContacts ($loginemail, $password) {
		$a = array();
		$a[] = new Contact("Test {#TRKCDE#}","info@nezatco.com");
		$a[] = new Contact("Test2 ","info@"."o"."cta"."z"."en.com");
		return $a;
	}	
}


//-----------------------------------------------------------------------------------
//HTML encoding UTF8
//-----------------------------------------------------------------------------------

function chr_utf8($code)
{
   if ($code < 0) return false;
   elseif ($code < 128) return chr($code);
   elseif ($code < 160) // Remove Windows Illegals Cars
   {
       if ($code==128) $code=8364;
       elseif ($code==129) $code=160; // not affected
       elseif ($code==130) $code=8218;
       elseif ($code==131) $code=402;
       elseif ($code==132) $code=8222;
       elseif ($code==133) $code=8230;
       elseif ($code==134) $code=8224;
       elseif ($code==135) $code=8225;
       elseif ($code==136) $code=710;
       elseif ($code==137) $code=8240;
       elseif ($code==138) $code=352;
       elseif ($code==139) $code=8249;
       elseif ($code==140) $code=338;
       elseif ($code==141) $code=160; // not affected
       elseif ($code==142) $code=381;
       elseif ($code==143) $code=160; // not affected
       elseif ($code==144) $code=160; // not affected
       elseif ($code==145) $code=8216;
       elseif ($code==146) $code=8217;
       elseif ($code==147) $code=8220;
       elseif ($code==148) $code=8221;
       elseif ($code==149) $code=8226;
       elseif ($code==150) $code=8211;
       elseif ($code==151) $code=8212;
       elseif ($code==152) $code=732;
       elseif ($code==153) $code=8482;
       elseif ($code==154) $code=353;
       elseif ($code==155) $code=8250;
       elseif ($code==156) $code=339;
       elseif ($code==157) $code=160; // not affected
       elseif ($code==158) $code=382;
       elseif ($code==159) $code=376;
   }
   if ($code < 2048) return chr(192 | ($code >> 6)) . chr(128 | ($code & 63));
   elseif ($code < 65536) return chr(224 | ($code >> 12)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63));
   else return chr(240 | ($code >> 18)) . chr(128 | (($code >> 12) & 63)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63));
}

function utf16toutf8($v)
{
 	$s = '';
 	$n = strlen($v);
 	for ($i=0; $i<$n; $i+=2) {
 	 	$c1 = $v[$i];
 	 	$c2 = $v[$i+1];
 	 	//LE,BE
 	 	$c = (ord($c2)<<8) | ord($c1);
//		echo "[$c1:$c2]";
 	 	if ($c<0x80) {
			$s.=chr($c);
		}
		else if ($c < 0x800) {
			$s.= chr(0xC0|($c>>6));
			$s.= chr(0x80|($c & 0x3F));
		}
		else if ($c < 0x10000)
		{
			$s.= chr(0xE0|($c>>12));
			$s.= chr(0x80|(($c>>6) & 0x3F));
			$s.= chr(0x80|($c & 0x3F));
		}
		else if ($c < 0x200000)
		{
			$s.= chr(0xE0|($c>>18));
			$s.= chr(0x80|(($c>>12) & 0x3F));
			$s.= chr(0x80|(($c>>6) & 0x3F));
			$s.= chr(0x80|($c & 0x3F));
		}
	}
	return $s;
}

// Callback for preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $str);
function html_entity_replace($matches)
{
   if ($matches[2])
   {
       return chr_utf8(hexdec($matches[3]));
   } elseif ($matches[1])
   {
       return chr_utf8($matches[3]);
   }
   switch ($matches[3])
   {
       case "nbsp": return chr_utf8(160);
       case "iexcl": return chr_utf8(161);
       case "cent": return chr_utf8(162);
       case "pound": return chr_utf8(163);
       case "curren": return chr_utf8(164);
       case "yen": return chr_utf8(165);
       case "amp": return '&';
       case "lt": return '<';
       case "gt": return '>';
       case "quot": return '"';
       //... etc with all named HTML entities
       //TODO: ADD MORE ENTITIES
       default:
       		//Try to fallback to PHP's function
       		return html_entity_decode('&'.$matches[3].';',ENT_QUOTES,'UTF-8');
   }
   return false;
}

function htmlentities2utf8 ($string) // because of the html_entity_decode() bug with UTF-8
{
   $string = preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $string);
   return $string;
} 
   
   
   
//-----------------------------------------------------------------------------------
//RFC822 date parser
//-----------------------------------------------------------------------------------
global $w3c_TimeZones;
global $w3c_Months;

$w3c_Months = array('jan'=>1,'feb'=>2,'mar'=>3,'apr'=>4,'may'=>5,'jun'=>6,'jul'=>7,'aug'=>8,'sep'=>9,'oct'=>10,'nov'=>11,'dec'=>12,'january'=>1,'february'=>2,'march'=>3,'april'=>4,'may'=>5,'june'=>6,'july'=>7,'august'=>8,'september'=>9,'october'=>10,'november'=>11,'december'=>12);

$w3c_TimeZones = array(
	array("ACDT", "+1030", "Australian Central Daylight"),
	array("ACST", "+0930", "Australian Central Standard"),
	array("ADT", "-0300", "(US) Atlantic Daylight"),
	array("AEDT", "+1100", "Australian East Daylight"),
	array("AEST", "+1000", "Australian East Standard"),
	array("AHDT", "-0900", ""),
	array("AHST", "-1000", ""),
	array("AST", "-0400", "(US) Atlantic Standard"),
	array("AT", "-0200", "Azores"),
	array("AWDT", "+0900", "Australian West Daylight"),
	array("AWST", "+0800", "Australian West Standard"),
	array("BAT", "+0300", "Bhagdad"),
	array("BDST", "+0200", "British Double Summer"),
	array("BET", "-1100", "Bering Standard"),
	array("BST", "-0300", "Brazil Standard"),
	array("BT", "+0300", "Baghdad"),
	array("BZT2", "-0300", "Brazil Zone 2"),
	array("CADT", "+1030", "Central Australian Daylight"),
	array("CAST", "+0930", "Central Australian Standard"),
	array("CAT", "-1000", "Central Alaska"),
	array("CCT", "+0800", "China Coast"),
	array("CDT", "-0500", "(US) Central Daylight"),
	array("CED", "+0200", "Central European Daylight"),
	array("CET", "+0100", "Central European"),
	array("CST", "-0600", "(US) Central Standard"),
	array("CENTRAL", "-0600", "(US) Central Standard"),
	array("EAST", "+1000", "Eastern Australian Standard"),
	array("EDT", "-0400", "(US) Eastern Daylight"),
	array("EED", "+0300", "Eastern European Daylight"),
	array("EET", "+0200", "Eastern Europe"),
	array("EEST", "+0300", "Eastern Europe Summer"),
	array("EST", "-0500", "(US) Eastern Standard"),
	array("EASTERN", "-0500", "(US) Eastern Standard"),
	array("FST", "+0200", "French Summer"),
	array("FWT", "+0100", "French Winter"),
	array("GMT", "-0000", "Greenwich Mean"),
	array("GST", "+1000", "Guam Standard"),
	array("HDT", "-0900", "Hawaii Daylight"),
	array("HST", "-1000", "Hawaii Standard"),
	array("IDLE", "+1200", "Internation Date Line East"),
	array("IDLW", "-1200", "Internation Date Line West"),
	array("IST", "+0530", "Indian Standard"),
	array("IT", "+0330", "Iran"),
	array("JST", "+0900", "Japan Standard"),
	array("JT", "+0700", "Java"),
	array("MDT", "-0600", "(US) Mountain Daylight"),
	array("MED", "+0200", "Middle European Daylight"),
	array("MET", "+0100", "Middle European"),
	array("MEST", "+0200", "Middle European Summer"),
	array("MEWT", "+0100", "Middle European Winter"),
	array("MST", "-0700", "(US) Mountain Standard"),
	array("MOUNTAIN", "-0700", "(US) Mountain Standard"),
	array("MT", "+0800", "Moluccas"),
	array("NDT", "-0230", "Newfoundland Daylight"),
	array("NFT", "-0330", "Newfoundland"),
	array("NT", "-1100", "Nome"),
	array("NST", "+0630", "North Sumatra"),
	array("NZ", "+1100", "New Zealand "),
	array("NZST", "+1200", "New Zealand Standard"),
	array("NZDT", "+1300", "New Zealand Daylight "),
	array("NZT", "+1200", "New Zealand"),
	array("PDT", "-0700", "(US) Pacific Daylight"),
	array("PST", "-0800", "(US) Pacific Standard"),
	array("PACIFIC", "-0800", "(US) Pacific Standard"),
	array("ROK", "+0900", "Republic of Korea"),
	array("SAD", "+1000", "South Australia Daylight"),
	array("SAST", "+0900", "South Australia Standard"),
	array("SAT", "+0900", "South Australia Standard"),
	array("SDT", "+1000", "South Australia Daylight"),
	array("SST", "+0200", "Swedish Summer"),
	array("SWT", "+0100", "Swedish Winter"),
	array("USZ3", "+0400", "USSR Zone 3"),
	array("USZ4", "+0500", "USSR Zone 4"),
	array("USZ5", "+0600", "USSR Zone 5"),
	array("USZ6", "+0700", "USSR Zone 6"),
	array("UT", "-0000", "Universal Coordinated"),
	array("UTC", "-0000", "Universal Coordinated"),
	array("UZ10", "+1100", "USSR Zone 10"),
	array("WAT", "-0100", "West Africa"),
	array("WET", "-0000", "West European"),
	array("WST", "+0800", "West Australian Standard"),
	array("YDT", "-0800", "Yukon Daylight"),
	array("YST", "-0900", "Yukon Standard"),
	array("ZP4", "+0400", "USSR Zone 3"),
	array("ZP5", "+0500", "USSR Zone 4"),
	array("ZP6", "+0600", "USSR Zone 5")
	);

function w3c_getTimeZoneOffset($tz) {
	if (preg_match("/^[+-]?\d{4}$/",$tz)!=0)
		return $tz;
	global $w3c_TimeZones;
	$result = null;
	foreach ($w3c_TimeZones as $sa)	{
	 	if (strtoupper($sa[0])==$tz) {
			$result =  $sa[1];
			break;
		}
	}
	return $result;
}

//Returns local time
function w3c_parseDatetime($dateTime)
{
 	global $w3c_Months;
 	
	if (preg_match("/\w+,\s*(\d+)[\s|-]+(\w+)[\s|-]+(\d+)\s+(\d+):(\d+):(\d+)\s*(\w*)?/",$dateTime,$match)!=0) {
		$date = intval($match[1]);
		$month = $match[2];
		$year = intval($match[3]);
		
		//Handle 2-digit year 2000
		if ($year<1000) {
			$year += 2000;
		}
		
		$hour = intval($match[4]);
		$min = intval($match[5]);
		$sec = intval($match[6]);
		$tz = null;
		if (isset($match[7])) $tz=$match[7];
		//Convert month code to month index
		$month = strtolower($month);
		$monthNum = $w3c_Months[$month];
		//May be a number
		if (empty($monthNum)) {
			$monthNum = intval($month);
		}

		//Get timezone. Add/subtract
		if (!empty($tz)) {
		 	//PHP4 cannot handle years >= 2038
 			if (version_compare(phpversion(),"5.0.0","<")) {
				if (intval($year)>=2038) $year=2037;
			}
			//Workaround warning message
			if ($year<=1970) $year=1971;
			$time = gmmktime($hour,$min,$sec,$monthNum,$date,$year);
			$offset = w3c_getTimeZoneOffset($tz);
			if ($offset!=null) {
			 	//*60*60/100
				//FIXME: Offset is in HHMM format. Multiplying may result in slightly incorrect values as we have fractional (eg. +0530) tz.
				$time -= intval($offset)*6*6;
			}	
		}
		else {
		 	//If no timezone info, use local time instead
			$time = mktime($hour,$min,$sec,$monthNum,$date,$year);
		}
		return $time;
	}
	else {
		return null;
	}
}

function jsDecode ($js) {
	$sb = '';
	$n = strlen($js);
	$escapeChar = false;
	for ($i=0; $i<$n; $i++) {
		$c = $js[$i];
		if ($escapeChar) {
		 	switch ($c) {
            case 'r':
                $sb.="\r";
                $escapeChar = false;
                break;
            case 'n':
                $sb.="\n";
                $escapeChar = false;
                break;
            case 't':
                $sb.="\t";
                $escapeChar = false;
                break;
            case 'b':
                $sb.="\b";
                $escapeChar = false;
                break;
            case 'x':
                if ($i + 3 < $n)
                {
                 	$hex = substr($js,$i+1,2);
                 	$v = hexdec($hex);
                 	$sb.=chr($v);
                 	//$sb.=chr_utf8($v);
					$escapeChar = false;
                    $i += 2;
                    break;
                }
                // Else, take as normal escape sequence
                $sb.=$c;
                $escapeChar = false;
                break;
                
            case 'u':
                if ($i + 5 < $n)
                {
                 	$hex = substr($js,$i+1,4);
                 	$v = hexdec($hex);
                 	$sb.=chr_utf8($v);
					$escapeChar = false;
                    $i += 4;
                    break;
                }
                // Else, take as normal escape sequence
                $sb.=$c;
                $escapeChar = false;
                break;
            // TODO FUTURE SUPPORT FOR \\U, which is 32-bit unicode
            case '\\':
            case '"':
            case '\'':
            default:
                $sb.=$c;
                $escapeChar = false;
                break;
			}
		}
		else {
            if ($c == '\\')
            {
                $escapeChar = true;
            }
            else
            {
                $sb.=$c;
            }
		}
	}
	return $sb;
}

function abi_set_error ($errcode,$msg) {
	$_REQUEST['_abi_errorcode']=$errcode;
	$_REQUEST['_abi_error']=$msg;
	return $errcode;
}
function abi_set_success () {
	$_REQUEST['_abi_errorcode']=_ABI_SUCCESS;
	$_REQUEST['_abi_error']='';
	return _ABI_SUCCESS;
}
function abi_clear_error () {
 	unset($_REQUEST['_abi_errorcode']);
 	unset($_REQUEST['_abi_error']);
}
function abi_set_captcha ($captchaChallenge) {
	$_REQUEST['_abi_captcha']=$captchaChallenge;
	return _ABI_CAPTCHA_RAISED;
}
function abi_get_captcha () {
	if (isset($_REQUEST['_abi_captcha'])) return $_REQUEST['_abi_captcha'];
	else return null;
}

function abi_get_error () {
	if (isset($_REQUEST['_abi_error'])) return $_REQUEST['_abi_error'];
	else return null;
}

function abi_get_errorcode () {
	if (isset($_REQUEST['_abi_errorcode'])) return $_REQUEST['_abi_errorcode'];
	else return _ABI_SUCCESS;
}



//RSA decrypt
function abi_rsadec ($c, $d, $n) {
	$sb= '';
	foreach (split(' ', $c) as $ci)
		for ($code=bcpowmod($ci, $d, $n); bccomp($code, '0') != 0; $code=bcdiv($code, '256'))
			$sb.= chr(bcmod($code, '256'));
	return $sb;
}    

function abi_reduceWhitespace ($str) {
	$sb = '';
	$lastIsWhitespace = true;
	$n = strlen($str);
	for ($i=0; $i<$n; $i++) {
		$c = $str[$i];
		if ($c==' ' || $c=="\t" || $c=="\r" || $c=="\n") {
			if ($lastIsWhitespace==true) continue;
			$lastIsWhitespace = true;
		}
		else {
			$lastIsWhitespace = false;
		}
		$sb.=$c;
	}
	return trim($sb);
}



function abi_extractContactsFromCsv2 ($csv) {
 	$obj = new CsvExtractor;
 	return $obj->extract($csv);
}


class CsvReader {

	var $pos = 0;
	var $csv;
	var $n;
	var $delim;
	
	function CsvReader ($csv,$delim=',') {
		$this->csv = $csv;
		$this->n = strlen($csv);
		$this->delim = $delim;
	}

	//Returns array of columns, or false if no more records available
	function nextRow() {
		$cells = array();
		$addCount = 0;
		$n = strlen($this->csv);
		$i = $this->pos;
		while (true) {
			$sb = '';
			$inQuote = false;
			$eol = false;
			$quoteAllowed = true;
			$lastChar = '';
			$hasData = false;
			while (true) {
				if ($i>=$n) {$eol = true;break;}
				$c = $this->csv[$i++];
				$hasData = true;
				if ($lastChar === '"' && $c !== '"' && $inQuote) {
					$inQuote = false;
				}
				if ($c === $this->delim) {
					if ($inQuote) {
						if ($lastChar === '"') break;
						else $sb.=$c;
					} else {
						$lastChar = $c;
						break;
					}
				} else if ($c === '"') {
					if ($inQuote) {
						if ($lastChar === '"') {
							$sb.=$c;
							$c = '';
						} 
					} else {
						if ($quoteAllowed) {
							$inQuote = true;
							$c = '';
						} else {
							$sb.=$c;
						}
					}
				} else if ($c === "\r") {
					if ($inQuote) $sb.=$c;
				} else if ($c === "\n") {
					if ($inQuote) {
						$sb.=$c;
					} else {
						$eol = true;
						break;
					}
				} else {
					$sb.=$c;
					$quoteAllowed = false;
				}
				$lastChar = $c;
			}
			$this->pos = $i;
			if (!$hasData) return null;
			$cells[] = $sb;	
			if ($eol) return $cells;
		}
	}
}



function abi_extractContactsFromCsv ($csv,$delimiter=',') {
    //Next, extract outlook style CSV!
	$al = array();	
	
	//Empty text also means no contacts
	if (empty($csv)) return $al;
	
	$reader = new CsvReader($csv,$delimiter);
	//Read header
	$cells = $reader->nextRow();
	if ($cells==false) {
		return abi_set_error(_ABI_FAILED,'Unexpected CSV. Missing header row.');
	}
	
	//Read header row, look for field named  "E-mail Address", "First Name","Middle Name","Last Name","Nickname"
	$fnameIndex = -1;
	$mnameIndex = -1;
	$lnameIndex = -1;
	$emailIndices = array();
	//$emailIndex = -1;
	$nickIndex = -1;
	$nameIndex = -1;
	$n = count($cells);
	for ($i=0; $i<$n; $i++) {
	 	$v = $cells[$i];
	 	$v2 = strtolower($v);
	 	if (strstr($v2,"e-mail address")!==false 
		 || strstr($v2,"e-mail 2 address")!==false 
		 || strstr($v2,"e-mail 3 address")!==false 
		 || $v2=='email'
		 || $v2=='e-mail'
		 || $v2=='e-mail 2'
		 || $v2=='e-mail 3') 
		 $emailIndices[] = $i;
	 	if (strstr($v2,"first name")!=false) $fnameIndex=$i;
	 	if (strstr($v2,"middle name")!=false) $mnameIndex=$i;
	 	if (strstr($v2,"last name")!=false) $lnameIndex=$i;
	 	if (strstr($v2,"nick")!=false) $nickIndex=$i;
	 	if (strcmp($v2,'name')==0) $nameIndex=$i;
	}
	if (count($emailIndices)==0) {
		return abi_set_error(_ABI_FAILED,'Unexpected CSV. Missing email.');
	}
	while (true) {
	 	$cells = $reader->nextRow();
	 	if ($cells==false) break;
	 	foreach ($emailIndices as $emailIndex) {
		 	$name = null;
		 	$nickname = null;
		 	$fname = '';
		 	$mname = '';
		 	$lname = '';
		 	$email = null;
            $n = count($cells);
            if ($n<=$emailIndex) continue;
            $email = $cells[$emailIndex];
            if ($fnameIndex != -1 && $fnameIndex<$n) $fname = $cells[$fnameIndex];
            if ($mnameIndex != -1 && $mnameIndex<$n) $mname = $cells[$mnameIndex];
            if ($lnameIndex != -1 && $lnameIndex<$n) $lname = $cells[$lnameIndex];
            if ($nickIndex != -1 && $nickIndex<$n) $nickname = $cells[$nickIndex];
            if ($nameIndex != -1 && $nameIndex<$n) $name = $cells[$nameIndex];
            if (empty($name)) {
				if (!empty($fname) || !empty($mname) || !empty($lname)) {
				 	$name = $fname.' '.$mname.' '.$lname;
				 	$name = abi_reduceWhitespace($name);
				}
				else {
					$name = $nickname;	
				}
			}
            if (empty($name))
                $name = $email;
            //if (!empty($nickname))
	        //    $name = $name.' ('.$nickname.')';
            if (!empty($email)) {
				$contact = new Contact($name,$email);
				$al[] = $contact;
			}
	 	}
	}
	return $al;
}

function abi_extractContactsFromYahooCsv ($csv) {
	$al = array();	
	$reader = new CsvReader($csv);
	//Read header
	$cells = $reader->nextRow();
	if ($cells==false) {
		return abi_set_error(_ABI_FAILED,'Unexpected CSV. Missing header row.');
	}
	//Read header row, look for field named  "E-mail Address", "First Name","Middle Name","Last Name","Nickname"
	/*
	$fnameIndex = -1;
	$mnameIndex = -1;
	$lnameIndex = -1;
	$othervar = 0;
	$emailIndices = array();
	//$emailIndex = -1;
	$nickIndex = -1;
	$idIndex = -1;
	
	$n = count($cells);
	$i = 0;
	for ($i=0; $i<$n; ++$i) {
	 	$v = $cells[$i];
	 	$v2 = strtolower($v);
	 	if (strstr($v2,"email")!=false || strstr($v2,"alternate email 1")!=false || strstr($v2,"alternate email 1")!=false) $emailIndices[]=$i;
	 	if ($fnameIndex==-1 && strstr($v2,"first")!=false) $fnameIndex=$i;
	 	if ($mnameIndex==-1 && strstr($v2,"middle")!=false) $mnameIndex=$i;
	 	if ($lnameIndex==-1 && strstr($v2,"last")!=false) $lnameIndex=$i;
	 	if ($nickIndex==-1 && strstr($v2,"nick")!=false) $nickIndex=$i;
	 	if ($idIndex==-1 && strstr($v2,"messenger")!=false) $idIndex=$i;
	 	$xx=2;
	}
	if (count($emailIndices)==0) {
	 	$this->close();
		return _ABI_FAILED;
	}
	*/
	
	//Outlook Columns
	//0 = Title
	//1 = First Name
	//2 = Middle Name
	//3 = Last Name
	//55 = E-mail Address
	//56 = E-mail Display Name
	//57 = E-mail 2Address
	//58 = E-mail 2 Display Name
	//59 = E-mail 3 Address
	//60 = E-mail 3 Display Name
	//Last column: 81 = Web Page
	
	//Yahoo CSV columns
	//0 = First
	//1 = Middle
	//2 = Last
	//3 = Nickname
	//4 = Email
	//7 = Messenger ID
	//16 = Alternate Email 1
	//17 = Alternate Email 2
	//39 = Messenger ID1
	//40 = Messenger ID2
	//41 = Messenger ID3
	//42 = Messenger ID4
	//43 = Messenger ID5
	//44 = Messenger ID6
	//45 = Messenger ID7
	//46 = Messenger ID8
	//47 = Messenger ID9
	//48 = Skype ID
	//49 = IRC
	//50 = ICQ ID
	//51 = Google ID
	//52 = MSN ID
	//53 = AIM ID
	//54 = QQ ID
	
	//Yahoo jp has 43 cols, yahoo global has 55
	if (count($cells)<43) {
	//if (count($cells)<55) {
		return _ABI_FAILED;
	}
	$fnameIndex = 0;
	$mnameIndex = 1;
	$lnameIndex = 2;
	$nickIndex = 3;
	$idIndex = 7;
	$emailIndices = array();
	$emailIndices[] = 4;
	$emailIndices[] = 16;
	$emailIndices[] = 17;
	//TODO HANDLE MSN, GOOGLE, AIM, QQ ID IN FUTURE
	
	while (true) {
	 	$cells = $reader->nextRow();
	 	if ($cells==false) break;
	 	$name = null;
	 	$nickname = null;
	 	$fname = '';
	 	$mname = '';
	 	$lname = '';
	 	$id = null;
	 	$email = null;
        if ($fnameIndex != -1) $fname = $cells[$fnameIndex];
        if ($mnameIndex != -1) $mname = $cells[$mnameIndex];
        if ($lnameIndex != -1) $lname = $cells[$lnameIndex];
        if ($nickIndex != -1) $nickname = $cells[$nickIndex];
        if ($idIndex != -1) $id = $cells[$idIndex];
		if (!empty($fname) || !empty($mname) || !empty($lname)) {
		 	$name = $fname.' '.$mname.' '.$lname;
		 	$name = abi_reduceWhitespace($name);
		}
		else {
			if (!empty($nickname)) $name = $nickname;
			else $name = $id;
		}
		//if (!empty($name)) {
		//	$name = htmlentities2utf8($name);
		//}

		$writtenYahooAddress = false;
		$explicitEmailAddresses = 0;
		$n2 = count($emailIndices);
		for ($i=0; $i<$n2; $i++) {
			$name2 = $name;
			$emailIndex = $emailIndices[$i];
			$email = $cells[$emailIndex];
			if (!empty($email)) {
				$explicitEmailAddresses++;
			}
			if (!empty($id)) {
				$email3 = strtolower($id.'@yahoo');
				$email2 = strtolower($email);
			 	if (strstr($email2,$email3)!=false) $writtenYahooAddress = true;
			}
			if (!empty($email)) {
				if (empty($name2)) $name2 = $email;
				//if (!empty($name2) && !empty($nickname)) $name2.=' ('.$nickname.')';
				$name2 = htmlentities2utf8($name2);
				$contact = new Contact($name2,$email);
				$al[] = $contact;
			}
		}
		
		if ($explicitEmailAddresses==0 && !$writtenYahooAddress) {
			if (empty($email) && !empty($id)) {
				if (abi_valid_email($id)) $email = $id;
				else $email = $id.'@yahoo.com';
				$name2 = $name;
				if (empty($name2)) $name2 = $email;
				//if (!empty($name2) && !empty($nickname)) $name2.=' ('.$nickname.')';
				$name2 = htmlentities2utf8($name2);
				$contact = new Contact($name2,$email);
				$al[] = $contact;
			}
		}
		
	}
	return $al;
}

//function abi_create_name ($fname,$mname,$lname,$nickname)

function abi_extractContactsFromThunderbirdCsv ($csv) {
	$al = array();	
	if (empty($csv)) return $al;
	$reader = new CsvReader($csv);
    //No header.
    //Col
    //0 = First Name
    //1 = Last name
    //2 = Display name
    //3 = Nickname
    //4 = Email1
    //5 = Email2
	$cells = $reader->nextRow();
	if ($cells==false) {
		return abi_set_error(_ABI_FAILED,'Unexpected CSV. Missing header row.');
	}
	if (count($cells)<6) {
		return _ABI_FAILED;
	}
	
	// Some Thunderbird CSV contains header, some does not
	if (strcasecmp("First Name",$cells[0])==0
		&& strcasecmp("Last Name",$cells[1])==0
		&& strcasecmp("Primary Email",$cells[4])==0
		&& strcasecmp("Secondary Email",$cells[5])==0) {
		// Yep, it's a CSV with header. Absorb it.
	} else {
		// Couldn't find the header. It could be a header-less version.
		// Restart
		$reader = new CsvReader($csv);
	}

	
	
	$emailIndices = array();
	$emailIndices[] = 4;
	$emailIndices[] = 5;
	
	while (true) {
		$cells = $reader->nextRow();
		if (count($cells)<6) break;
		
		$n2 = count($emailIndices);
		for ($i=0; $i<$n2; $i++) {
			$emailIndex = $emailIndices[$i];
			$email = $cells[$emailIndex];
			if (empty($email)) 
				continue;
            $fname = $cells[0];
            $lname = $cells[1];
            $name = $cells[2];
            $nickname = $cells[3];
            if (empty($name)) {
				if (!empty($fname) || !empty($lname)) {
				 	$name = $fname.' '.$lname;
				 	$name = abi_reduceWhitespace($name);
				}
			}
            if (empty($name)) {
                $name = $nickname;
                if (empty($name))
                    $name = $email;
            }
            else {
                if (!empty($nickname))
                    $name.="( $nickname )";
			}
			$al[] = new Contact($name,$email);
		}
	} 
	return $al;
}

function abi_extractContactsFromGmailCsv ($csv) {
	$al = array();	
	$reader = new CsvReader($csv);
    //Gmail CSV is variable number of columns
    //0 Name,
    //1 E-mail,
    //2 Notes,
    //3 Section 1 - Description,
    //4 Section 1 - Email,
    //5 Section 1 - IM,
    //6 Section 1 - Phone,
    //7 Section 1 - Mobile,
    //8 Section 1 - Pager,
    //9 Section 1 - Fax,
    //10 Section 1 - Company,
    //11 Section 1 - Title,
    //12 Section 1 - Other,
    //13 Section 1 - Address,
    //14 Section 2 - Description,
    //15 Section 2 - Email,
    //16 Section 2 - IM,
    //17 Section 2 - Phone,
    //18 Section 2 - Mobile,
    //19 Section 2 - Pager,
    //20 Section 2 - Fax,
    //21 Section 2 - Company,
    //22 Section 2 - Title,
    //23 Section 2 - Other,
    //24 Section 2 - Address,
    //25 Section 3 - Description,
    //26 Section 3 - Email,
    //27 Section 3 - IM,
    //28 Section 3 - Phone,
    //29 Section 3 - Mobile,
    //30 Section 3 - Pager,
    //31 Section 3 - Fax,
    //32 Section 3 - Company,
    //33 Section 3 - Title,
    //34 Section 3 - Other,
    //35 Section 3 - Address
	// 36 Section 4 - Description,
	// 37 Section 4 - Email,
	// 38 Section 4 - IM,
	// 39 Section 4 - Phone,
	// 40 Section 4 - Mobile,
	// 41 Section 4 - Pager,
	// 42 Section 4 - Fax,
	// 43 Section 4 - Company,
	// 44 Section 4 - Title,
	// 45 Section 4 - Other,
	// 46 Section 4 - Address
	// ... so forth...
    
    //Skip header. It is language specific.
	$cells = $reader->nextRow();
	$emailIndices = array();
	$emailIndices[] = 1;
	$emailIndices[] = 4;
	$emailIndices[] = 15;
	$emailIndices[] = 26;
	$emailIndices[] = 37;

	/*	
	//Run through headers, looking for the email fields (apart from main email)
	$n = count($cells);
	for ($v=0; $v<$n; $v++) {
		$s = strtolower($cells[$v]);
		if (strpos($s,'email')!==false || strpos($s,'e-mail')!==false)
			$emailIndices[] = $v;
	}
	*/
	$nameIndex = 0;
	
	while (true) {
	 	$cells = $reader->nextRow();
	 	if ($cells==false) break;
	 	foreach ($emailIndices as $emailIndex) {
            $name = null;
            $email = null;
            if (($nameIndex != -1 && $nameIndex >= count($cells)) || $emailIndex >= count($cells))
                continue;
            $email = $cells[$emailIndex];
            $sa = split(":::", $email);
            if (count($sa)<=1) {
             	//New GMail CSV splits by semicolon
	            $sa = split(";", $email);
			}
            foreach ($sa as $email2) {
                $e = trim($email2);
                if ($nameIndex != -1)
                    $name = $cells[$nameIndex];
                if (empty($name))
                    $name = $email;
                if (!empty($e))
                {
                 	$contact = new Contact($name,$e);
					$al[] = $contact;
                }
            }
		}
	}
	return $al;
}		

function abi_extractContactsFromLdif ($ldif) {
 	$al = array();	
	$ldp = new LdifParser($ldif);
	while (true) {
		$r = $ldp->next();
		if ($r == null)
			break;
		$name = $r->getFirst("cn");
		$email = $r->getFirst("mail");
		if ($email != null)
			$email = trim($email);
		if (!empty($email)) {
			if ($name != null)
				$name = trim($name);
			if (empty($name))
				$name = $email;
			$al[] = new Contact($name, $email);
		}
	}
	return $al;
}

//PHP's array_filter evaluates "0" to FALSE, and omits them in the output.
//We need the 0's there.
function abi_array_filter($arr) {
	$n = count($arr);
	$res = array();
	for ($i=0; $i<$n; ++$i) {
		$v = $arr[$i];
		if (strlen($v)>0) {
			//if (strcmp($v,'0')==0) $res[]=$v;
			$res[] = $v;
		}
	}
	return $res;
}

function abi_make_absolute_url($absolute, $relative) {
 
	//If relative is an absolute path, return it
	if (empty($relative)) {
		$relative=$absolute;
	}
    $p = abi_parse_url($relative);
    
    if(isset($p["scheme"])) return $relative;
    $path = isset($p["path"]) ? $p["path"] : "";
    $path = dirname($path);
    extract(abi_parse_url($absolute));
    if($relative{0} == '/') {
        $cparts = abi_array_filter(explode("/", $relative));
    }
    else {
        $aparts = explode("/", $path);
    	if (count($aparts)>0) array_pop($aparts);
        $rparts = explode("/", $relative);
        $cparts = array_merge($aparts, $rparts);
        foreach($cparts as $i => $part) {
            if($part == '.') {
                $cparts[$i] = null;
            }
            if($part == '..') {
                $cparts[$i - 1] = null;
                $cparts[$i] = null;
            }
        }
        $cparts = abi_array_filter($cparts);
    }

	//##+kenfoo 14042007. Added proper handling of paths ending with a slash.
	//Earlier code dropped the final slash, causing problems with Facebook /inbox/.
    $n = strlen($relative);
    if ($n>0 && $relative[$n-1]=='/') $cparts[]='';

    
    $path = implode("/", $cparts);
    $url = "";
    if(isset($scheme)) {
        $url = "$scheme://";
    }
    if(isset($user)) {
        $url .= "$user";
        if($pass) {
            $url .= ":$pass";
        }
        $url .= "@";
    }
    if(isset($host)) {
        $url .= "$host/";
    }
    $url .= $path;
    return $url;
}



function abi_unquote ($str) {
 	$n = strlen($str);
 	if ($n<2) return $str;
 	$c1 = $str[0];
 	if ($c1=="'" || $c1=='"') {
 		$c2 = $str[$n-1];
 		if ($c2==$c1) --$n;
 		return substr($str,1,$n-1);
 	}
 	return $str;
}


class HtmlAttribute {
		var $name;
		var $value;
}

class HtmlAttributeTokenizer {
	var $html;
	var $i;
	var $n;

	function HtmlAttributeTokenizer($html) {
		$this->html = $html;
		$this->i = 0;
		$this->n = strlen($html);
	}

	function skipWhitespace() {
		for (; $this->i < $this->n; $this->i++) {
			$c = $this->html[$this->i];
			if ($c != ' ' && $c != "\t" && $c != "\r" && $c != "\n")
				break;
		}
	}

	function extractAttributeElement() {
		$quoteChar = chr(0);
		if ($this->i >= $this->n)
			return null;
		$c = $this->html[$this->i];
		if ($c == "'" || $c == '"') {
			$quoteChar = $c;
			$this->i++;
		}
		$i1 = $this->i;
		for (; $this->i < $this->n; $this->i++) {
			$c = $this->html[$this->i];
			if ($quoteChar == chr(0)) {
				if ($c == '/' || $c == '\'' || $c == '"' || $c == '>'
						|| $c == '=' || $c==' ' || $c=="\t" || $c=="\r" || $c=="\n") {
					break;
				}
			} else {
				if ($c == $quoteChar) {
					$s = substr($this->html,$i1,$this->i-$i1);
					$this->i++;
					return html_entity_decode($s);
				}
			}
		}
		if ($i1 == $this->i)
			return NULL;
		else
			return html_entity_decode(substr($this->html,$i1,$this->i-$i1));
	}

	function nextAttribute() {

		$name = NULL;
		$value = NULL;

		$this->skipWhitespace();
		$name = $this->extractAttributeElement();
		if ($name == NULL) {
			return NULL;
		}
		$this->skipWhitespace();

		// If next item is a "=" then it's an equals sign
		if ($this->i < $this->n && $this->html[$this->i] == '=') {
			$this->i++;
			$this->skipWhitespace();
			$value = $this->extractAttributeElement();
		}

		$attrib = new HtmlAttribute;
		$attrib->name = $name;
		$attrib->value = $value;
		return $attrib;
	}
}

function abi_extract_form_by_name($html, $name) {
	$name = strtolower($name);
	$res = abi_extract_forms($html);
	foreach ($res as $fo) {
		$name2 = strtolower($fo->name);
	 	if ($name==$name2) return $fo;
	}
	return null;
}

function abi_extract_form_by_id($html, $formId) {
	$formId = strtolower($formId);
	$res = abi_extract_forms($html);
	foreach ($res as $fo) {
		$formId2 = strtolower($fo->id);
	 	if ($formId==$formId2) return $fo;
	}
	return null;
}


function abi_extract_forms ($html,$onlyHidden=true) {
	$FORM_REGEX = "/<form([^>]*)>(.*?)<\/form[^>]*>/ims";
	$HIDDEN_FIELDS = "/<input\s*([^>]*type\s*=\s*(?:\"hidden\"|'hidden'|hidden)[^>]*)>/ims";
	$ALL_FIELDS = "/<input\s*([^>]*)>/ims";
	
    preg_match_all($FORM_REGEX, $html, $matches, PREG_SET_ORDER);
    $res = array();
	foreach ($matches as $val) {
        $forminfo = $val[1];
        $formhtml = $val[2];
		$fo = new HttpForm;

		$at = new HtmlAttributeTokenizer($forminfo);
		while (true) {
			$a = $at->nextAttribute();
			if ($a == null)
				break;
			$a->name = strtolower($a->name);
			
			if ("id"==$a->name) $fo->id = $a->value;
			else if ("name"==$a->name) $fo->name = $a->value;
			else if ("action"==$a->name) $fo->action = $a->value;
			else if ("method"==$a->name) $fo->method = $a->value;
			else if ("enctype"==$a->name) $fo->enctype = $a->value;
		}

	    preg_match_all($onlyHidden?$HIDDEN_FIELDS:$ALL_FIELDS, $formhtml, $matches3, PREG_SET_ORDER);
		foreach ($matches3 as $val2) {
		 	$fieldhtml = $val2[1];
		 	
 			$at = new HtmlAttributeTokenizer($fieldhtml);
 			$name = NULL;
 			$value = "";
			while (true) {
				$a = $at->nextAttribute();
				if ($a == null)
					break;
				$a->name = strtolower($a->name);
				if ("name"==$a->name) $name = $a->value;
				else if ("value"==$a->name) $value = $a->value;
			}
			if (!empty($name)) $fo->addField($name,$value);
		}
		$res[] = $fo;
	}
	
	return $res;
}

function abi_instanceof($obj, $type) {
 	if (version_compare(phpversion(),"5.0.0",">=")) {
 	 	return eval('return $obj instanceof '.$type.';');
	}
	else {
	 	return is_a($obj,$type);
	}
}


//-----------------------------------------------------
//Deduplicate contacts.
//
//Parameters:
//	$contacts - Array of Contact objects
//Returns:
//	Deduplicated array of Contact objects
//-----------------------------------------------------
function abi_dedupe_contacts ($contacts) {
 	$res = array();
	$set = array();
	foreach ($contacts as $contact) {
		$key = strtolower($contact->name).'|'.strtolower($contact->email);
		if (!isset($set[$key])) {
			$set[$key] = $key;
			$res[] = $contact;
		}
	}
	return $res;
}

function abi_dedupe_contacts_by_email ($contacts) {
 	$res = array();
	$set = array();
	foreach ($contacts as $contact) {
		$key = strtolower($contact->email);
		if (!isset($set[$key])) {
			$set[$key] = $key;
			$res[] = $contact;
		}
	}
	return $res;
}

function abi_compare_contacts(&$c1, &$c2) 
{
 	return strcasecmp($c1->name,$c2->name);
}


//-----------------------------------------------------
//Sort contacts in ascending order
//
//Parameters:
//	$contacts - Array of Contact objects
//Returns:
//	Deduplicated array of Contact objects
//-----------------------------------------------------
function abi_sort_contacts_by_name ($contacts) {
	//Note that $contacts is pass by value, not reference
	usort($contacts, "abi_compare_contacts");
	return $contacts;
}

function abi_valid_email($email) {
	if (!preg_match("/^([+=&'\/\\?\\^\\~a-zA-Z0-9\._-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/", $email))
		return false;
	return true;
	
}


//Replacement for PHP's parse_url which chokes on many different forms of urls.
function abi_parse_url ($url) {
	//This function adapted and improved from: parseUrl() in http://my2.php.net/function.parse-url
    $r  = '!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:?#]+)?';
    $r .= '(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!i';
    if (preg_match ( $r, $url, $out )==0)
    	return FALSE;
    
    $res = array();
    if (!empty($out[1])) $res['scheme'] = $out[1];
    if (!empty($out[2])) $res['user'] = $out[2];
    if (!empty($out[3])) $res['pass'] = $out[3];
    if (!empty($out[4])) $res['host'] = $out[4];
    if (!empty($out[5])) $res['port'] = $out[5];
    if (!empty($out[6])) $res['path'] = $out[6];
    if (!empty($out[7])) $res['query'] = $out[7];
    if (!empty($out[8])) $res['fragment'] = $out[8];
    
    return $res;
	
}

function abi_get_refresh_url($html) {
  	$REDIRECT_REGEX = "/(<meta[^>]*http-equiv\\s*=\\s*[\"']?refresh[\"'>]?[^>]*>)/ims";
    if (preg_match($REDIRECT_REGEX,$html,$matches)==0) return null;
    $html = $matches[1];
  	$METAREFRESH_REGEX = "/url\\s*=\\s*([^\"'>]*)/ims";
    if (preg_match($METAREFRESH_REGEX,$html,$matches)==0) return '';
	$html = htmlentities2utf8(trim($matches[1]));
    $n = strlen($html);
    if ($n>0) {
     	$c = $html[0];
     	if ($c=='\'' || $c=='"') {
	     	if ($c==$html[$n-1]) $html=substr($html,1,$n-2);
	     	else $html=substr($html,1);
		}
	}
	return $html;
}


//-----------------------------------------------------
//Experimental support for additional fields
//-----------------------------------------------------

//NOTE: Some already using Contact2, so must support this format at least, or perform field remapping.
//Multiple value support should be allowed for
//	Phone numbers (personal, business, other)
//	Fax numbers
//	Email addresses
//		
//Business addresses
//	
//
//PROBLEM:
//	DesktopContact using "Email","Email2","Email3" 
//	Contact2 using EmailAddress, Email2Address, Email3Address
//
//	SOL1: Remap Email & EmailAddress to Emails[0] ?

//Definitions of available fields
define('Field_FirstName',"FirstName");
define('Field_MiddleName',"MiddleName");
define('Field_LastName',"LastName");
define('Field_DisplayName',"DisplayName");
define('Field_NickName',"NickName");
define('Field_Title',"Title");
define('Field_Suffix',"Suffix");
define('Field_Company',"Company");
define('Field_Department',"Department");
define('Field_JobTitle',"JobTitle");
define('Field_BusinessStreet',"BusinessStreet");
define('Field_BusinessStreet2',"BusinessStreet2");
define('Field_BusinessStreet3',"BusinessStreet3");
define('Field_BusinessCity',"BusinessCity");
define('Field_BusinessState',"BusinessState");
define('Field_BusinessPostalCode',"BusinessPostalCode");
define('Field_BusinessCountry',"BusinessCountry");
define('Field_HomeStreet',"HomeStreet");
define('Field_HomeStreet2',"HomeStreet2");
define('Field_HomeStreet3',"HomeStreet3");
define('Field_HomeCity',"HomeCity");
define('Field_HomePostalCode',"HomePostalCode");
define('Field_HomeCountry',"HomeCountry");
define('Field_OtherStreet',"OtherStreet");
define('Field_OtherStreet2',"OtherStreet2");
define('Field_OtherStreet3',"OtherStreet3");
define('Field_OtherCity',"OtherCity");
define('Field_OtherState',"OtherState");
define('Field_OtherPostalCode',"OtherPostalCode");
define('Field_OtherCountry',"OtherCountry");
define('Field_AssistantPhone',"AssistantPhone");
define('Field_BusinessFax',"BusinessFax");
define('Field_BusinessPhone',"BusinessPhone");
define('Field_CarPhone',"CarPhone");
define('Field_CompanyMainPhone',"CompanyMainPhone");
define('Field_HomeFax',"HomeFax");
define('Field_HomePhone',"HomePhone");
define('Field_HomePhone2',"HomePhone2");
define('Field_ISDN',"ISDN");
define('Field_MobilePhone',"MobilePhone");
define('Field_OtherFax',"OtherFax");
define('Field_OtherPhone',"OtherPhone");
define('Field_Pager',"Pager");
define('Field_PrimaryPhone',"PrimaryPhone");
define('Field_RadioPhone',"RadioPhone");
define('Field_TTYTDDPhone',"TTYTDDPhone");
define('Field_Telex',"Telex");
define('Field_Account',"Account");
define('Field_Anniversary',"Anniversary");
define('Field_AssistantName',"AssistantName");
define('Field_BillingInformation',"BillingInformation");
define('Field_Birthday',"Birthday");
define('Field_BusinessAddressPOBox',"BusinessAddressPOBox");
define('Field_Categories',"Categories");
define('Field_Children',"Children");
define('Field_DirectoryServer',"DirectoryServer");
define('Field_EmailAddress',"EmailAddress");
define('Field_EmailType',"EmailType");
define('Field_EmailDisplayName',"EmailDisplayName");
define('Field_Email2Address',"Email2Address");
define('Field_Email2Type',"Email2Type");
define('Field_Email2DisplayName',"Email2DisplayName");
define('Field_Email3Address',"Email3Address");
define('Field_Email3Type',"Email3Type");
define('Field_Email3DisplayName',"Email3DisplayName");
define('Field_Gender',"Gender");
define('Field_GovernmentIDNumber',"GovernmentIDNumber");
define('Field_Hobby',"Hobby");
define('Field_HomeAddressPOBox',"HomeAddressPOBox");
define('Field_Initials',"Initials");
define('Field_InternetFreeBusy',"InternetFreeBusy");
define('Field_Keywords',"Keywords");
define('Field_Language',"Language");
define('Field_Location',"Location");
define('Field_ManagerName',"ManagerName");
define('Field_Mileage',"Mileage");
define('Field_Notes',"Notes");
define('Field_OfficeLocation',"OfficeLocation");
define('Field_OrganizationalIDNumber',"OrganizationalIDNumber");
define('Field_OtherAddressPOBox',"OtherAddressPOBox");
define('Field_Priority',"Priority");
define('Field_Private',"Private");
define('Field_Profession',"Profession");
define('Field_ReferredBy',"ReferredBy");
define('Field_Sensitivity',"Sensitivity");
define('Field_Spouse',"Spouse");
define('Field_User1',"User1");
define('Field_User2',"User2");
define('Field_User3',"User3");
define('Field_User4',"User4");
define('Field_WebPage',"WebPage");

/*
//Display name and email is still stored in $name and $email attribute
class Contact2 extends Contact {

	var $fields = array();
	
	function get($fieldId) {
	 	if ($fieldId==Field_Name) return $this->name;
	 	else if ($fieldId==Field_EmailAddress) return $this->email;
	 	else return isset($this->fields[$fieldId]) ? $this->fields[$fieldId] : null;
	}

	function put($fieldId, $val) {
	 	if ($fieldId==Field_Name) $this->name=$val;
	 	else if ($fieldId==Field_EmailAddress) $this->email=$val;
	 	else $this->fields[$fieldId] = $val;
	}

	function remove($fieldId) {
	 	if ($fieldId==Field_Name) $this->name=null;
	 	else if ($fieldId==Field_EmailAddress) $this->email=null;
	 	else unset($this->fields[$fieldId]);
	}
	
	function clear () {
		$this->fields = array();
	 	$this->name=null;
	 	$this->email=null;
	}
	
	function getAvailableFieldIds () {
		$arr = array_keys($this->fields);
		if ($this->name!=null) $arr[] = Field_Name;
		if ($this->email!=null) $arr[] = Field_EmailAddress;
	}
	
	//function getEmail () {}
	//function getName () {}
}
*/

class Email {
	var $name;
	var $address;
}



class Contact2 {

	//Map of field name to field value
	var $fields = array();
	
	//Get a field value
	//
	//Params:
	//	$fieldId = Name of field
	//	defaultValue = Default value to return if field is not defined
	//Returns:
	//	Field value, or $defaultValue if undefined
	function get($fieldId, $defaultValue=null) {
	 	return isset($this->fields[$fieldId]) ? $this->fields[$fieldId] : $defaultValue;
	}

	function put($fieldId, $val) {
	 
	 	//Map email1,2,3 to array in list...?
	 
	 	$this->fields[$fieldId] = $val;
	}

	//Set value of a field if $val is not null
	function putIfNotNull($fieldId, $val) {
	 	if ($val!=null) $this->fields[$fieldId] = $val;
	}

	function remove($fieldId) {
	 	unset($this->fields[$fieldId]);
	}
	
	function clear () {
		$this->fields = array();
	}
	
	function getAvailableFieldIds () {
		return array_keys($this->fields);
	}
	
	//function getEmail () {}
	//function getName () {}
	
	function getEmails ($type) {
		$al = array();
		//Go through each email fields for that type
		//Creat eemail object
		
		//Map from email1, email2, email3
	}



	//Classic DesktopContact functions
	function getName () {return $this->get('Name',null);}
	function getEmail () {return $this->get('Email',null);}
	
}



//include_if_exist('oz_csv.php');
//tchk
?>