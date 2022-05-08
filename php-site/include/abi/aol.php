<?php
/********************************************************************************
AOL contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
Version: 1.2
********************************************************************************/
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//AolImporter
/////////////////////////////////////////////////////////////////////////////////////////
class AolImporter extends WebRequestor {

	//##
	//<form name="loginForm" method="POST" action="/_cqr/login/login.psp" onsubmit="return validateSigninForm(this);">
	var $LOGINFORM_REGEX = "/<form\s+name=\"[^\"]*oginForm\"[^>]*?action=\"([^\"]*?)\"[^>]*>(.*?)<\/form/ims";
	
	var $CONTACT_REGEX = "/<span class=\"fullName\">(.*?)<\/span>(.*?)<hr class=\"contactSeparator\">/ims";
	var $EMAILS_REGEX = "/<span>[\\d\\w ]*Email[\\d\\w ]*:<\/span>\\s*<span>(.*?)<\/span>/ims";
	var $SCREENNAME_REGEX = "/<span>Screen Name:<\/span>\\s*<span>(.*?)<\/span>/ims";
	var $patternAOLSitedomain = "/sitedomain.*?\"(.*?)\";/ims";
	var $patternAOLSiteState = "/siteState.*?\"(.*?)\";/ims";
	var $patternAOLSeamless = "/seamless.*?\"(.*?)\";/ims";
	var $patternAOLInput = "/<input.*?>/ims";
	var $patternHidden = "/type=\"hidden\"/ims";
	var $patternAOLName = "/name=\"(.*?)\"/ims";
	var $patternAOLValue = "/value=\"(.*?)\"/ims";
	var $patternAOLLoginForm = "/<form.*?name=\"loginForm\".*?>[\\s\\S]*<\\/form>/ims";
	var $patternAOLAction = "/<form.*?action=\"(.*?)\".*?>/ims";
	var $patternAOLVerify = "/<body onLoad=\".*?'(http.*?)'.*>/ims";
	var $patternAOLHost = "/Host.*?\"(.*?)\";/ims";
	var $patternAOLUserID = "/&uid:(.*?)&/ims";
	var $patternAOLPath = "/gSuccessPath.*?\"(.*?)\";/ims";
	var $INVALIDLOGIN_REGEX = "/invalid screen name or password/ims";
	var $HOST_REGEX = "/var gPreferredHost = \"?([^\";]*).*?var gTargetHost = \"?([^\";]*).*?var gSuccessPath = \"?([^\";]*)/ims";

	var $LOGINFORMURL_REGEX = "/goToLoginUrl.*snsRedir\\(\"([^\"]*)\"/ims";

	function fetchContacts ($loginemail, $password) {

	 	$parts = $this->getEmailParts($loginemail);
	 	$login = $loginemail;
	 	$dom = strtolower($parts[1]);
	 	$loginurl = 'http://webmail.aol.com';
	 	if ($dom=="aol.in") {
			$loginurl = 'http://webmail.aol.in';
		}
	 	else if ($dom=="aol.it" || $dom=="aol.es") {
			//Login is using email address
		}
		else {
		 	//For other domains, force login via us site for english
		 	$login = $parts[0];
		}
		$html = $this->httpGet($loginurl);

/*
		if (preg_match($this->LOGINFORMURL_REGEX,$html,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find url to login form');
		}
		$loginurl = $matches[1];
		$html = $this->httpGet($loginurl);
*/

		$form = abi_extract_form_by_name($html, 'AOLLoginForm');
		if ($form==null) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Missing login form');
		}
		$form->setField('loginId',$login);
		$form->setField('password',$password);
		$postData = $form->buildPostData();
		$html = $this->httpPost($form->action, $postData);

		if (preg_match($this->INVALIDLOGIN_REGEX,$html)) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}


        /////////////////////////////////////////////////////
        //HANDLE LOGIN BOUNCE PAGE
        /////////////////////////////////////////////////////
        /*
		if (preg_match($this->patternAOLVerify,$html,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Unable to find bounce page');
		}
		$location = $matches[1];
        //Maybe can optimize if we don't actually download, but just follow the forwards?
        $html = $this->httpGet($location);
        */

		//At this point, AOL may ask for a new security question to be defined.
		//We'll just skip it and go to http://webmail.aol.com
        $html = $this->httpGet($loginurl);

		//New AOL adds 2nd level redirection
		if (preg_match($this->patternAOLVerify,$html,$matches)!=0) {
			$location = $matches[1];
	        $html = $this->httpGet($location);
		}

        /////////////////////////////////////////////////////
        //GET HOST FOR THE WEBMAIL
        /////////////////////////////////////////////////////
		if (preg_match($this->HOST_REGEX,$html,$matches)!=0) {
			$preferredhost = $matches[1];
			$targethost = $matches[2];
			$successpath = $matches[3];
			if ($targethost!="null") {
				$preferredhost = $targethost;
			}
			$homeuri = "http://".$preferredhost.$successpath;
		}
		else {
			$homeuri = $this->lastUrl;
		}

        //Lookup the recently received header response of "set-cookie"
        //It should contain the user id there. 
        $cookiestring = $this->cookiejar->getCookieString($homeuri);
		if (preg_match($this->patternAOLUserID,$cookiestring,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Unable to find aol user id');
		}
        $uid = $matches[1];
		$location = $this->makeAbsolute($homeuri,'AB/addresslist-print.aspx?command=all&undefined&sort=LastFirstNick&sortDir=Ascending&nameFormat=FirstLastNick&user='.$uid);

        //FETCH ADDRESS BOOK (PRINT VERSION)
        $html = $this->httpGet($location);

		//EXTRACT CONTACTS
		$al = array();	
		
        preg_match_all($this->CONTACT_REGEX, $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
		 	$name = $val[1];
		 	//Make into plain text name
		 	$name = preg_replace('/<[^>]*>/','',$name);
		 	$name = htmlentities2utf8($name);
            $contactdetails = $val[2];
            
			if (preg_match($this->SCREENNAME_REGEX,$contactdetails,$matches2)!=0) {
				$screenname = trim($matches2[1]);
				if (!empty($screenname)) {
				 	if (empty($name)) $name=$screenname;
				 	if (strpos(strtolower($screenname),'@')===false) {
					 	//NOTE: For now, we'll take screen name as "aol.com"
					 	$screenname.='@aol.com';
					}
					$contact = new Contact($name, $screenname);
					$al[] = $contact;
				}
			}
            
	        preg_match_all($this->EMAILS_REGEX, $contactdetails, $matches2, PREG_SET_ORDER);
			foreach ($matches2 as $val2) {
				$email = trim($val2[1]);
				if (!empty($email)) {
					//if (strstr($email, '@') === FALSE) $email .= '@ aol.com';
					$contact = new Contact($name, $email);
					$al[] = $contact;
				}
		 	}
		}
		
	 	$this->close();
		return $al;
	}
	
	
}

?>
