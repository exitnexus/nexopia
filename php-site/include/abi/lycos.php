<?php
/********************************************************************************
Lycos (international) contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.0
********************************************************************************/
//include_once(dirname(__FILE__).'/abimporter.php');
if (!defined('__ABI')) die('Please include abi.php to use this importer!');

/////////////////////////////////////////////////////////////////////////////////////////
//LycosImporter
/////////////////////////////////////////////////////////////////////////////////////////
class LycosImporter extends WebRequestor {

	var $EXTRACT_REGEX = "/<option value=\"([\\w\\d]*@[^\"]*)\">([^<]*)<\/option>/ims";
	var $COOKIEURL_REGEX = "/src=\"(\/lsu\/signin\/cookie.jsp[^\"]*)\"/ims";

	function fetchLocalContacts ($login, $password, $postUrl) {
        $form = new HttpForm;
        /*
        $form->addField("login",$login);
        $form->addField("password",$password);
        $form->addField('hiddenlogin','Username');
        $form->addField('hiddenpassword','******');
        */
        $form->addField("membername", $login);
        $form->addField("passtxt", "Passwort");
        $form->addField("password", $password);
        $form->addField("service", "MAIL");
        $form->addField("redirect", "");	//"http://mail.lycos.de/");
        $form->addField("target_url", "");
        $form->addField("fail_url", "");
        $form->addField("format", "");
        $form->addField("redir_fail", "");	//"http://www.lycos.de/kommunizieren/index.html");
        $form->addField("product", "mail");	//Jubii
        $form->addField("username", "");
        $form->addField("countryCode", "us");
        $form->addField("x", "13");
        $form->addField("y", "4");
		$form->addField("_authtrkcde", "{#TRKCDE#}");
        $postData = $form->buildPostData();
    	$html = $this->httpPost($postUrl, $postData);
    	
    	if (strpos($this->lastUrl,'/?error=')>0) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad username or password');
		}

		/*    	
		if (strpos($html,"That%2Balias%2Bis%2Bnot%2Bregistered%2Bwith")>0) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name');
		}
		if (strpos($html,"Your%2Bpassword%2Bis%2Bincorrect")>0) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad password');
		}
		*/

		//Find cookie.jsp
        if (preg_match($this->COOKIEURL_REGEX,$html,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find cookie url');
		}
		$location = $this->makeAbsolute($this->lastUrl, $matches[1]);
        $html = $this->httpGet($location);

        //Fetch the address book page for compose
        $location = $this->makeAbsolute($this->lastUrl, "/app/abook/popup.jsp");
        $html = $this->httpGet($location);

        /////////////////////////////////////////////////////
        //EXTRACT!
        /////////////////////////////////////////////////////
		$al = array();	
        preg_match_all($this->EXTRACT_REGEX, $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
		 	$email = htmlentities2utf8(trim($val[1]));
		 	$name = htmlentities2utf8(trim($val[2]));
			$contact = new Contact(htmlentities2utf8($name), htmlentities2utf8($email));
			$al[] = $contact;
		}
	 	$this->close();
	 	
	 	//Signout
        $html = $this->httpGet("/lsu/signout/signout.jsp");
        
		return $al;
	}
	

	function fetchIntContacts ($login, $password) {
        $form = new HttpForm;
        $form->addField('m_PR','27');
        $form->addField('m_CBURL','http://mail.lycos.com/lycos/addrbook/ExportAddr.lycos?ptype=act&fileType=OUTLOOK');
        $form->addField("action","login");
        $form->addField("m_U",$login);
        $form->addField("m_P",$password);
        $form->addField('login','Sign In');
        $postData = $form->buildPostData();
    	$html = $this->httpPost('https://registration.lycos.com/login.php?m_PR=27', $postData, 'utf8');

		//or search for <div class="errMsg">
		if (strpos($html,'that entry does not match any record in our files')>0) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		/*
		if (strpos($html,'There was a problem with your')>0) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}
		*/
		/*
		if (strpos($html,'E-mail Address')==false && strpos($html,'Nickname')==false) {
		 	$this->close();
			return _ABI_FAILED;
		}
		*/

        //Next, extract outlook style CSV!
        $res = $this->extractContactsFromCsv($html);
        $this->close();
		return $res;
	}
	
	function fetchContacts ($loginemail, $password) {
	 
	 	$parts = $this->getEmailParts ($loginemail);
	 	$loginid = $parts[0];
	 	if ($parts[1]=="lycos.com") {
			return $this->fetchIntContacts($parts[0],$password);
		}
	 	else if ($parts[1]=="lycos.co.uk") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.co.uk/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.at") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.at/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.be") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.be/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.ch") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.ch/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.de") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.de/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.es") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.es/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.fr" || $parts[1]=="caramail.com" || $parts[1]=="caramail.fr") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.caramail.lycos.fr/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.it") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.it/lsu/signin/action.jsp');
		}
	 	else if ($parts[1]=="lycos.nl") {
		 	return $this->fetchLocalContacts($loginid,$password,'http://secure.mail.lycos.nl/lsu/signin/action.jsp');
		}
		else {
			return abi_set_error(_ABI_UNSUPPORTED,'Unsupported domain '.$parts[1]);
		}
	}


}

?>
