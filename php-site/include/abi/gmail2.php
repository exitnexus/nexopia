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

//include_once(dirname(__FILE__).'/abimporter.php');
if (!defined('__ABI')) die('Please include abi.php to use this importer!');

/////////////////////////////////////////////////////////////////////////////////////////
//GMailImporter2 (using Google Contacts API)
/////////////////////////////////////////////////////////////////////////////////////////
class GMailImporter2 extends WebRequestor {
 
  	var $AUTH_REGEX = "/^Auth=(.*?)\$/ims";
  	var $ERROR_REGEX = "/^Error=(.*?)\$/ims";
  	var $ENTRY_REGEX = "/<entry[^>]*>(.*?)<\/entry>/ims";
  	var $TITLE_REGEX = "/<title[^>]*>(.*?)<\/title>/ims";
  	var $EMAIL_REGEX = "/<gd:email[^>]*?.*?address='([^']*)'/ims";
  	
	var $loginEmail;
	var $extraHeaders;
	
	//private List<HttpHeader> additionalHeaders;
	//private String loginEmail;
			
	function login ($loginEmail, $password) {

		$form = new HttpForm;
		$form->addField("accountType", "HOSTED_OR_GOOGLE");
		$form->addField("Email", $loginEmail);
		$form->addField("Passwd", $password);
		$form->addField("service", "cp"); // Contacts
		$form->addField("source", "Octazen-ABI-1");
		//$form->addField("_authtrkcde", "{#TRKCDE#}");
		$postData = $form->buildPostData();
		$html = $this->httpPost("https://www.google.com/accounts/ClientLogin", $postData);
		//$extraHeaders = array('Content-Type: text/plain');
		
		$this->extraHeaders = array();
		$this->extraHeaders[] = 'Content-Type: application/atom+xml';

		$sc = $this->lastStatusCode;
		if ($sc==200) {
	        if (preg_match($this->AUTH_REGEX,$html,$matches)==0)
				return abi_set_error(_ABI_FAILED,'Cannot find auth token');
			$auth = $matches[1];
			$this->extraHeaders[] = "Authorization: GoogleLogin auth=$auth";
			return _ABI_SUCCESS;
		}
		else if ($sc==403) {
	        if (preg_match($this->ERROR_REGEX,$html,$matches)>0)
				$error = trim($matches[1]);
			if ('BadAuthentication'==$error)
				return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
			else if ('NotVerified'==$error)
				return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Email address has not been verified');
			else if ('TermsNotAgreed'==$error)
				return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Gmail terms not agreed');
			else if ('CaptchaRequired'==$error)
				return abi_set_error(_ABI_CAPTCHA_RAISED,'Captcha challenge raised');
			// TODO: HANDLE CAPTCHA TOKENS,ETC
			else if ('AccountDeleted'==$error)
				return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Account deleted');
			else if ('AccountDisabled'==$error)
				return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Account disabled');
			else if ('ServiceDisabled'==$error)
				return abi_set_error(_ABI_FAILED,'Service disabled');
			else if ('ServiceUnavailable'==$error)
				return abi_set_error(_ABI_FAILED,'Google contacts service unavailable. Try again later.');
			else // if ('Unknown'==$error)
				return abi_set_error(_ABI_FAILED,'Unknown gmail error');
		}
		else {
			return abi_set_error(_ABI_FAILED,'Unexpected error code '.$sc);
		}
	}	
	
	function fetchContacts ($loginEmail, $password) {

		//Remove ".gmail" suffix
		$loginEmail = preg_replace("/^(.*?)(\.gmail)$/ims", '${1}', $loginEmail);

		$res = $this->login($loginEmail,$password);
		if ($res!=_ABI_SUCCESS) {
		 
		 	$err=  abi_get_error();
		 	$errcode = abi_get_errorcode();
		 
			//Google sometimes raises a captcha exception, which doesn't happen with normal screen scraping
		 	if ($res==_ABI_CAPTCHA_RAISED && class_exists('GMailImporter')) {
				$imp = new GMailImporter;
				$res2 = $imp->fetchContacts($loginEmail,$password);
				if (is_array($res2)) return $res2;
			}

			return abi_set_error($errcode,$err);
		}
			
		$url = "http://www.google.com/m8/feeds/contacts/$loginEmail/base?max-results=10000";
		
		//$extraHeaders = array('Content-Type: text/plain');
		$html = $this->httpRequest($url,false,null,'UTF-8',$this->extraHeaders);

		$cl = array();
		
        preg_match_all($this->ENTRY_REGEX, $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
		 	$entryHtml = $val[1];
		 	$name = null;
	        if (preg_match($this->TITLE_REGEX,$entryHtml,$matches2)!=0)
	        	$name = htmlentities2utf8($matches2[1]);

	        preg_match_all($this->EMAIL_REGEX, $entryHtml, $matches2, PREG_SET_ORDER);
			foreach ($matches2 as $val2) {
	        	$email = trim(htmlentities2utf8($val2[1]));
	        	if (abi_valid_email($email)) {
	        	 	$name2 = $name;
					if (empty($name2)) $name2=$email;
					$cl[] = new Contact($name2,$email);
				}
			}
		}
		
		return abi_sort_contacts_by_name ($cl);
	}
}
?>
