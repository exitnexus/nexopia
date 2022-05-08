<?php
/********************************************************************************
Hotmail & MSN contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.3
********************************************************************************/
//include_once(dirname(__FILE__).'/abimporter.php');
//include_once(dirname(__FILE__).'/hotmail2.php');
if (!defined('__ABI')) die('Please include abi.php to use this importer!');

/////////////////////////////////////////////////////////////////////////////////////////
//HotmailImporter
/////////////////////////////////////////////////////////////////////////////////////////
class HotmailImporter extends WebRequestor {
 
    var $LIVECONTACT_REGEX = "/<tr>.*?<td class=\"dContactPickerBodyNameCol\">.*?&#x200.;\\s*(.*?)\\s*&#x200.;.*?<\/td>\\s*<td class=\"dContactPickerBodyEmailCol\">\\s*([^<]*?)\\s*<\/td>.*?<\/tr>/ims";
    
	function fetchContacts ($login, $password) {

		// Post login form
		$form = new HttpForm;
		$form->addField("__EVENTTARGET", ".");
		$form->addField("__EVENTARGUMENT", "");
		$form->addField("LoginTextBox", $login);
		$form->addField("DomainField", "passport.com");
		$form->addField("PasswordTextBox", $password);
		$form->addField("PasswordSubmit", "Sign in");
        $postData = $form->buildPostData();
    	$html = $this->httpPost('https://mid.live.com/si/post.aspx?lc=1033&id=71570&ru=http%3a%2f%2fmobile.live.com%2fwml%2fmigrate.aspx%3freturl%3dhttp%253a%252f%252fmobile.live.com%252fhm%252fDefault.aspx%26fti%3dy&mlc=en-US&mspsty=302&mspto=1&tw=14400&kv=2', $postData);
		if (strpos($this->lastUrl,"error.asp")!==false || strpos($html,"AllAnnotations_Error")!==false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		$html = $this->httpGet('http://mail.live.com/mail/ContactPickerLight.aspx?n='.rand(0,20000));
		$al = array();	
        preg_match_all($this->LIVECONTACT_REGEX, $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
            $name = htmlentities2utf8(trim($val[1]));
            $email = htmlentities2utf8(trim($val[2]));

            //if (abi_valid_email($email)) {
			if (abi_valid_email($email)) {
				$contact = new Contact($name,$email);
				$al[] = $contact;
			}
		}
		return $al;
	}
}

?>
