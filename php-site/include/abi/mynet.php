<?php
/********************************************************************************
MyNet.com contacts importer (Turkish)

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.1
********************************************************************************/
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//MyNetImporter
/////////////////////////////////////////////////////////////////////////////////////////
class MyNetImporter extends WebRequestor {

	function fetchContacts ($loginemail, $password) {

		//Having some issues with certain hosting provider. We disable GZIP for now.
		$this->supportGzip = false;
		
		//Fetch 1 to get cookies
		
		//$html = $this->httpGet("http://uyeler.mynet.com/login/login.asp");
		$html = $this->httpGet("http://uyeler.mynet.com/login/login.asp?rurl=http%3a%2f%2fadresdefteri.mynet.com%2fExim%2fEximPage.aspx&formname=addressbook");

	 	$login = $this->getEmailParts($loginemail);

		//Fetch 2 Login form
		$form = new HttpForm;
		$form->addField("nameofservice", "addressbook");
		$form->addField("pageURL", "http://uyeler.mynet.com/login/login.asp?rurl=http%3a%2f%2fadresdefteri.mynet.com%2fExim%2fEximPage.aspx&amp;formname=addressbook");
		$form->addField("faultCount", "");
		$form->addField("faultyUser", "");
		$form->addField("loginRequestingURL", "http://adresdefteri.mynet.com/Exim/EximPage.aspx");
		$form->addField("username", $login[0]);
		$form->addField("password", $password);
		$form->addField("rememberstate", "2");
		$postData = $form->buildPostData();
		$html = $this->httpPost("https://uyeler.mynet.com/index/uyegiris.html", $postData);
		if (strpos($html, 'Hatal')!=false && strpos($html, 'tfen tekrar deneyin')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		//Download CSV
		$form = new HttpForm;
		$form->addField("format", "microsoft_csv");
		$postData = $form->buildPostData();
		$html = $this->httpPost("http://adresdefteri.mynet.com/Exim/ExportFileDownload.aspx", $postData);
		$res = $this->extractContactsFromCsv($html);
	 	$this->close();
		return $res;
	}	
}

?>
