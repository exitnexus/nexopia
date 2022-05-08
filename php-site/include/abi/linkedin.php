<?php
/********************************************************************************
LinkedIn contacts importer

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
//LinkedInImporter
/////////////////////////////////////////////////////////////////////////////////////////
class LinkedInImporter extends WebRequestor {

 	var $CONTACT_REGEX = "/var s\\d+=\\{\\};(.*?)\\.profileLink=/ims";
 	var $EMAIL_REGEX = "/var s\\d+=\"([^\"]*)\";s\\d+.emailAddress=s\\d+/ims";
 	var $FNAME_REGEX = "/var s\\d+=\"([^\"]*)\";s\\d+.firstName=s\\d+/ims";
 	var $LNAME_REGEX = "/var s\\d+=\"([^\"]*)\";s\\d+.lastName=s\\d+/ims";


	function fetchContacts ($loginemail, $password) {

		//Remove trailing .linkedin.com. Could do better with regex.
		$loginemail = strtolower($loginemail);
		$loginemail = str_replace(".linkedin","",$loginemail);
		
		$form = new HttpForm;
		$form->addField("session_key", $loginemail);
		$form->addField("session_password", $password);
		$form->addField("session_login", "Sign In");
		$form->addField("_authtrkcde", "{#TRKCDE#}");
		$postData = $form->buildPostData();
		$html = $this->httpPost("https://www.linkedin.com/secure/login", $postData);
		if (strpos($html, 'does not match our records')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		/*
		$form = new HttpForm;
		$form->addField("outputType", "microsoft_outlook");
		$form->addField("exportNetwork", "exportNetwork");
		$postData = $form->buildPostArray();
		$html = $this->httpPost("http://www.linkedin.com/addressBookExport", $postData);
		$res = $this->extractContactsFromCsv($html);
	 	$this->close();
		return $res;
		*/

		$va = $this->cookiejar->getCookieValues('http://www.linkedin.com/','JSESSIONID');
		if (empty($va))  {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find JSESSIONID');
		}
		$jsessionid = $va[0];
		
		$v = time() & 0x0FFFFFFF;
		
		$sb="callCount=1\r\n";
		$sb.="JSESSIONID=$jsessionid\r\n";
		$sb.="c0-scriptName=ConnectionsBrowserService\r\n";
		$sb.="c0-methodName=getMyConnections\r\n";
		$sb.="c0-id=$v\r\n";
		$sb.="c0-param0=string:0\r\n";
		$sb.="c0-param1=number:-1\r\n";
		$sb.="c0-param2=string:DONT_CARE\r\n";
		$sb.="c0-param3=number:5000\r\n";
		$sb.="c0-param4=boolean:false\r\n";
		$sb.="c0-param5=boolean:true\r\n";
		$sb.="xml=true\r\n";

		$url = "/dwr/exec/ConnectionsBrowserService.getMyConnections.dwr";
		//$extraHeaders = array('Content-Type'=>'text/plain');
		$extraHeaders = array('Content-Type: text/plain');
		$html = $this->httpPost($url,$sb, 'utf-8',$extraHeaders);

		$cl = array();
		
        preg_match_all($this->CONTACT_REGEX, $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
		 	$js = $val[0];
	        if (preg_match($this->EMAIL_REGEX,$js,$matches2)) {
	         	$email = jsDecode($matches2[1]);
	         	if (abi_valid_email($email)) {
		        	$fname = preg_match($this->FNAME_REGEX,$js,$matches2) ? jsDecode($matches2[1]) : '';
		        	$lname = preg_match($this->LNAME_REGEX,$js,$matches2) ? jsDecode($matches2[1]) : '';
		        	$name = trim($fname.' '.$lname);
		        	if (empty($name)) $name=$email;
		        	$cl[] = new Contact($name,$email);
				}
	        }
		}
		
	 	$this->close();
	 	return $cl;
	}	
}


?>
