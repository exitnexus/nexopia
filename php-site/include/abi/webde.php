<?php
/********************************************************************************
Web.de contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.1
********************************************************************************/
//include_once(dirname(__FILE__).'/abimporter.php');
if (!defined('__ABI')) die('Please include abi.php to use this importer!');

/////////////////////////////////////////////////////////////////////////////////////////
//WebDeImporter
/////////////////////////////////////////////////////////////////////////////////////////
class WebDeImporter extends WebRequestor {

 	var $SI_REGEX = "/[\\?&]si=([^&]+)/ims";
 	var $SID_REGEX = "/[\\?&]sid=([^&]+)/ims";

	function fetchCsv ($loginemail, $password) {

	 	$parts = $this->getEmailParts ($loginemail);
	 	$login = $parts[0];

		$form = new HttpForm;
		$form->addField("service", "freemail");
		$form->addField("server", "https://freemail.web.de");
		$form->addField("onerror", "https://freemail.web.de/msg/temporaer.htm");
		$form->addField("onfail", "https://freemail.web.de/msg/logonfailed.htm");
		$form->addField("username", $login);
		$form->addField("password", $password);
		$form->addField("rv_dologon", "Login");
		$form->addField("_authtrkcde", "{#TRKCDE#}");
		$postData = $form->buildPostData();
		$html = $this->httpPost("https://login.web.de/intern/login/", $postData);
		if (strpos($html, 'Passwort vergessen')!=false ||
			strpos($html, 'vielleicht vertippt')!=false ||
			strpos($html, 'nicht erfolgreich')!=false ||
			strpos($this->lastUrl, 'failed')>0)			
			{
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

        if (preg_match($this->SI_REGEX,$this->lastUrl,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find si');
		}
		$si = $matches[1];
		
		//Get logout link
		$currentUrl = $this->lastUrl;
		
		$location =  "/online/adressbuch/?si=$si";
		$html = $this->httpGet($location);

        if (preg_match($this->SID_REGEX,$this->lastUrl,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find sid');
		}
		$sid = $matches[1];
		
		$form = new HttpForm;
		$form->addField("action", "export");
		$form->addField("sid", $sid);
		$form->addField("category", "0");
		$form->addField("language", "2");
		$form->addField("expType", "3");
		$form->addField("export", "Exportieren");
		$postData = $form->buildPostData();
		$html = $this->httpPost("/adr_action/", $postData);
		
		//Convert character set to utf-8
		//if (function_exists('mb_convert_encoding')) $html = mb_convert_encoding($html, "utf-8","ISO-8859-1");
		//else if (function_exists('iconf')) $html = iconv('ISO-8859-1', 'utf-8', $html);
		//else, we can't perform the conversion. Return raw form.
		
//		$res = $this->extractContactsFromCsv($html);
//	 	$this->close();
	 	

		//Logout	 	
		$location = $this->makeAbsolute($currentUrl,"/?si=$si&rv_logoff=true");
	 	$this->httpGet($location);
	 	
		return $html;
	}	
	
	
		
	function fetchContacts ($loginemail, $password) {

		$html = $this->fetchCsv($loginemail,$password);
		if (!is_string($html)) {
			return $html;
		}
		$res = $this->extractContactsFromCsv($html);
		return $res;
	}
		
	function fetchContacts2 ($loginemail, $password) {
		$html = $this->fetchCsv($loginemail,$password);
		if (!is_string($html)) {
			return $res;
		}
		$ce = new CsvExtractor;
		return $ce->extract($html);
	}

}


?>
