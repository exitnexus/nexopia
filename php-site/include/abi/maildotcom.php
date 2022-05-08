<?php
/********************************************************************************
Rediffmail contacts importer

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
//Mail.com Importer
/////////////////////////////////////////////////////////////////////////////////////////
class MailDotComImporter extends WebRequestor {

	function fetchContacts ($loginemail, $password) {

		$form = new HttpForm;
		$form->addField("login", $loginemail);
		$form->addField("password", $password);
		$form->addField("redirlogin", "1");
//		$form->addField("siteselected", "normal");
		$form->addField("siteselected", "betaus");
		$form->addField("_authtrkcde", "{#TRKCDE#}");
		$postData = $form->buildPostData();
		$html = $this->httpPost("http://www2.mail.com/scripts/common/proxy.main?signin=1&lang=us", $postData);
		if (strpos($html, 'err=err_invalid_login')!==false ||
			strpos($this->lastUrl, 'err_invalid_login')!==false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		$form = new HttpForm;
		$form->addField("showexport", "showexport");
		$form->addField("action", "export");
		$form->addField("format", "csv");
		$form->addField("submit", "Export");
		$postData = $form->buildPostData();
		$html = $this->httpPost("/scripts/addr/external.cgi?gab=1", $postData);
		

		//No contacts
		if (strpos($html,"export failed because there is no records in database")!==FALSE)
			return array();

        $res = $this->extractContactsFromCsv($html);
        $this->close();
		return $res;
	}
}



?>
