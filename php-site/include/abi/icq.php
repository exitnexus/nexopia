<?php
/********************************************************************************
IcqMail contacts importer

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
//IcqImporter
/////////////////////////////////////////////////////////////////////////////////////////
class IcqImporter extends WebRequestor {
 
	function fetchContacts ($loginemail, $password) {

	 	$parts = $this->getEmailParts ($loginemail);
	 	$login = $parts[0];

		$form = new HttpForm;
		$form->addField("faction", "login");
		$form->addField("domain", "icqmail.com");
		$form->addField("username", $login);
		$form->addField("password", $password);
		$form->addField("_authtrkcde", "{#TRKCDE#}");
		$postData = $form->buildPostData();
		$html = $this->httpPost("http://www.icqmail.com/default.asp", $postData);
		if (strpos($html, 'Please enter a valid username')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name');
		}
		if (strpos($html, 'Please enter the correct password for this account')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Wrong password');
		}

        //Perform export
		$html = $this->httpGet("http://www.icqmail.com/contacts/contacts_import_export.asp?action=export&app=Microsoft+Outlook");
		$res = $this->extractContactsFromCsv($html);
	 	$this->close();
		return $res;
	}	
}


?>
