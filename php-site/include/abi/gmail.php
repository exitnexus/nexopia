<?php
/********************************************************************************
Gmail contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.1
********************************************************************************/
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//GMailImporter
/////////////////////////////////////////////////////////////////////////////////////////
class GMailImporter extends WebRequestor {
// 	var $REDIRECT_REGEX = "/url='?([^\"'']*)'?/i";
  	var $REDIRECT_REGEX = "/<meta[^>]*?url=[\"|']?([^\"']*)[\"|']?/i";

 	var $BASEURL_REGEX = "/<base href=\"([^\"]*)\"[^>]*>/i";
 	var $CLEAN_REGEX = "/<span[^>]*>[^<]*<\/span>/i";
 	var $EXTRACT_REGEX = "/<input type=\"?checkbox\"?[^>]*>[^<]*<\/td>[^<]*<td[^>]*>[^<]*<b>([^<]*)<\/b>[^<]*<\/td>[^<]*<td[^>]*>([^\&<]*)/i";

 	var $AT_REGEX = "/<input\\s+type=\"hidden\"\\s+name=\"at\"\\s+value=\"([^\"]+)\"/ims";

	function fetchCsv ($loginemail, $password, $fmt='csv') {

//http://www.gmail.com/
//https://www.google.com/accounts/ServiceLogin?service=mail&passive=true&rm=false&continue=https%3A%2F%2Fmail.google.com%2Fmail%2F%3Fnsr%3D1%26ui%3Dhtml%26zy%3Dl&ltmpl=default&ltmplcache=2

	 	$login = $this->getEmailParts($loginemail);

/*	 	
	 	$html = $this->httpGet('https://www.google.com/accounts/ServiceLogin?service=mail&passive=true&rm=false&continue=http%3A%2F%2Fmail.google.com%2Fmail%2F&ltmpl=default&ltmplcache=2');
		$form = abi_extract_form_by_id($html, 'gaia_loginform');
        if (is_null($form)) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find login form');
		}
		$form->setField("Email", $loginemail);
		$form->setField("Passwd", $password);
		$postData = $form->buildPostData();
		$html = $this->httpPost($form->action, $postData);
*/

		$location='';
		//Use retry as local google server seems to be failing here at times (occured in Malaysia servers))
		//Seems to be happening to PHP curl only.
		for ($tries=0; $tries<10; $tries++) {
			$form = new HttpForm;
			$form->addField("ltmpl", "default");
			$form->addField("ltmplcache", "2");
			$form->addField("continue", "http://mail.google.com/mail?");
			$form->addField("hl", "en");
			$form->addField("service", "mail");
			$form->addField("Email", $loginemail);
			$form->addField("Passwd", $password);
			$form->addField("rmShown", "1");
			$form->addField("null", "Sign in");
			$postData = $form->buildPostData();
			$html = $this->httpPost("https://www.google.com/accounts/ServiceLoginAuth", $postData);
			
			if (empty($html)) {
			 //echo "[RETRY]";
				continue;
			}
	
			if (strpos($html, 'Username and password do not match')!=false) {
			 	$this->close();
				return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
			}
	
	        if (preg_match($this->REDIRECT_REGEX,$html,$matches)==0) {
			 	$this->close();
				return abi_set_error(_ABI_FAILED,'Cannot find redirection page');
			}
			$location = html_entity_decode($matches[1]);
			
			break;
		}
		
		//Gzip seems to be causing some problems with this section when used with GoDaddy proxy.
		//We temporarily disable gzip in this case.
		$supportGzip = $this->supportGzip;
		$this->supportGzip = false;
		$html = $this->httpGet($location);
		//Reenable gzip if any
		$this->supportGzip = $supportGzip;

		$url2 = $this->makeAbsolute($this->lastUrl, '/mail');
		$at = null;
		$ats = $this->cookiejar->getCookieValues ($url2, "GMAIL_AT");
		if (!empty($ats) && count($ats)>0) {
			$at = $ats[0];
		}
		if (empty($at)) {
            $html = $this->httpGet("http://mail.google.com/mail/?view=sec");
	        if (preg_match($this->AT_REGEX,$html,$matches)) {
				$at = html_entity_decode($matches[1]);
			}
		}

		if (empty($at)) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Failed to login. Unable to obtain GMAIL_AT.');
		}


		if ($fmt=='outlook') {
			$html = $this->httpGet("http://mail.google.com/mail/contacts/data/export?exportType=ALL&groupToExport=&out=OUTLOOK_CSV");
		}
		else {
			$html = $this->httpGet("http://mail.google.com/mail/contacts/data/export?exportType=ALL&groupToExport=&out=GMAIL_CSV");
		}
		//$res = $this->extractContactsFromGmailCsv($html);
		return $html;

/*		
		$res = $this->extractContactsFromCsv($html);
		if (!is_array($res)) {
		 	//Try the older CSV
			$form = new HttpForm;
			$form->addField("at", $at);
			$form->addField("ecf", "g");	//o for outlook
			$form->addField("ac", 'Export Contacts');
			$postData = $form->buildPostArray();
			$html = $this->httpPost("http://mail.google.com/mail/?ui=1&view=fec", $postData);
			$res = $this->extractContactsFromGmailCsv($html);
		}
		
	 	$this->close();
		return $res;
*/
	}	
	
	function fetchContacts ($loginemail, $password) {

		$html = $this->fetchCsv($loginemail,$password,'outlook');
		if (!is_string($html)) {
			return $html;
		}
		//$res = $this->extractContactsFromGmailCsv($html);
		$res = $this->extractContactsFromCsv($html);
		return $res;
	}
		
	function fetchContacts2 ($loginemail, $password) {
		$html = $this->fetchCsv($loginemail,$password,'outlook');
		if (!is_string($html)) {
			return $res;
		}
		$ce = new CsvExtractor;
		return $ce->extract($html);
	}

}

?>
