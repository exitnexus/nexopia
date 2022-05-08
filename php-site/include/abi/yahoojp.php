<?php
/********************************************************************************
Yahoo contacts importer

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
//YahooJpImporter
/////////////////////////////////////////////////////////////////////////////////////////
class YahooJpImporter extends WebRequestor {

	function fetchContacts ($loginemail, $password) {

		//Login
	 	$parts = $this->getEmailParts ($loginemail);
	 	$login = $parts[0];
		
		//$html = $this->httpGet("http://mail.yahoo.co.jp");
		$html = $this->httpGet("http://address.yahoo.co.jp");
		$form = abi_extract_form_by_name($html, 'login_form');
		if ($form==null) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find login form');
		}
		$form->setField('login',$login);
		$form->setField('passwd',$password);
		$form->addField("_authtrkcde", "{#TRKCDE#}");
        $postData = $form->buildPostData();
    	$html = $this->httpPost($form->action, $postData);
		if (strpos($html,"class=\"yregertxt\"")!==false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}
		
		//Fetch address book
		$html = $this->httpGet("http://address.yahoo.co.jp/yab/jp/Yahoo.csv?loc=jp&.rand=599806486&A=Y&Yahoo.csv",'SHIFT_JIS');
        $res = $this->extractContactsFromYahooCsv($html);
        $this->close();
		return $res;
	}
}

?>
