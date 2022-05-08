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
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//LinkedInImporter
/////////////////////////////////////////////////////////////////////////////////////////
class LinkedInImporter extends WebRequestor {
 
	function fetchContacts ($loginemail, $password) {

		//Remove trailing .linkedin.com. Could do better with regex.
		$loginemail = strtolower($loginemail);
		$loginemail = str_replace(".linkedin","",$loginemail);
		
		$form = new HttpForm;
		$form->addField("session_key", $loginemail);
		$form->addField("session_password", $password);
		$form->addField("session_login", "Sign In");
		$postData = $form->buildPostData();
		$html = $this->httpPost("https://www.linkedin.com/secure/login", $postData);
		if (strpos($html, 'does not match our records')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		$form = new HttpForm;
		$form->addField("outputType", "microsoft_outlook");
		$form->addField("exportNetwork", "exportNetwork");
		$postData = $form->buildPostArray();
		$html = $this->httpPost("http://www.linkedin.com/addressBookExport", $postData);
		$res = $this->extractContactsFromCsv($html);
	 	$this->close();
		return $res;
	}	
}


?>
