<?php
/********************************************************************************
Mac.com contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.0
********************************************************************************/
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//MacMailImporter
/////////////////////////////////////////////////////////////////////////////////////////
class MacMailImporter extends WebRequestor {

 	var $EXTRACT_REGEX = "/\{\"email1\":\"(.*?)\",\"fullName\":\"(.*?)\"\\}[,\\]]/ims";

	function fetchContacts ($loginemail, $password) {

	 	$login = $this->getEmailParts($loginemail);
	 	$login = $login[0];

		$form = new HttpForm;
		$form->addField("username", $login);
		$form->addField("password", $password);
		$form->addField("loginHiddenField", "onLogin");
		$form->addField("lang", "en");
		$postData = $form->buildPostData();
		$html = $this->httpPost("https://www.mac.com/WebObjects/Bookmarks.woa/wa/authenticate?cty=US&lang=en&aff=consumer", $postData);
		if (strpos($html, 'Invalid member name or password')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		$html = $this->httpGet("http://www.mac.com/WebObjects/Webmail.woa/wa/DirectAction/emptyPage?action=compose");
		
		if (strpos($html, 'Renew your membership today')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Membership expired');
		}

		
		$location = $this->makeAbsolute($this->lastUrl,"/WebObjects/Webmail.woa/wa/ComposeDirectAction/composeMessage");
		$form = new HttpForm;
		$form->addField("to","");
		$form->addField("mode","");
		$form->addField("src","");
		$postData = $form->buildPostData();
		$html = $this->httpPost($location,$postData);

		$al = array();	
        preg_match_all($this->EXTRACT_REGEX, $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
		 	$email = jsDecode(trim($val[1]));
		 	$name = jsDecode(trim($val[2]));
		 	if (empty($name)) $name=$email;
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
