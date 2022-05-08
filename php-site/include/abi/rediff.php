<?php
/********************************************************************************
Rediffmail contacts importer

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
//ReddiffImporter
/////////////////////////////////////////////////////////////////////////////////////////
class RediffImporter extends WebRequestor {

 	var $HOSTURL_REGEX = "/(http:\/\/[^\/]*?)\/.*?&session_id=([^&]*)/ims";
 	var $EXTRACT_REGEX = "/<input type=checkbox name=\"mail_address\" value=\"([^\"]*)\">\\s*(?:&nbsp;)?\\s*([^<]*)<\/td>/i";

	function fetchContacts ($loginemail, $password) {

	 	$login = $this->getEmailParts($loginemail);
	 	$login = $login[0];

	 	//rediffmail sometimes fail couple of times
		$form = new HttpForm;
		$form->addField("FormName", "existing");
		$form->addField("login", $login);
		$form->addField("passwd", $password);
		$form->addField("proceed", "Sign in");
		$form->addField("_authtrkcde", "{#TRKCDE#}");
		$postData = $form->buildPostData();
		$html = $this->httpPost("http://mail.rediff.com/cgi-bin/login.cgi", $postData);
		if (strpos($html, 'Your login failed')!==false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

        if (preg_match($this->HOSTURL_REGEX,$this->lastUrl,$matches)==0) {
	        if (preg_match($this->HOSTURL_REGEX,$html,$matches)==0) {
			 	$this->close();
				return abi_set_error(_ABI_FAILED,'Cannot find email host');
			}
		}
		
		$host = $matches[1];
		$sessionId = $matches[2];
		$location = $host."/iris/Main?do=popaddr&login=".urlencode($login)."&session_id=".$sessionId."&field=to&formname=Compose";
		$html = $this->httpGet($location);

        /////////////////////////////////////////////////////
        //EXTRACT!
        /////////////////////////////////////////////////////
		$al = array();	
        preg_match_all($this->EXTRACT_REGEX, $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
		 	$email = htmlentities2utf8(trim($val[1]));
		 	$name = htmlentities2utf8(trim($val[2]));
		 	if (!empty($email)) {
				$contact = new Contact($name, $email);
				$al[] = $contact;
			}
		}
		$this->close();
		return $al;
	}
}


?>
