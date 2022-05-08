<?php
/********************************************************************************
Fastmail contacts importer

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
//FastMailImporter
/////////////////////////////////////////////////////////////////////////////////////////
class FastMailImporter extends WebRequestor {

 	var $USTUDM_REGEX = "/(Ust=[^&]*).*?(UDm=[^&]*)/ims";

	function fetchContacts ($loginemail, $password) {

		$emailparts = $this->getEmailParts ($loginemail);
		$login = $emailparts[0];
		$domain = $emailparts[1];
		$location = 'http://www.'.$domain.'/mail';
		
		$html = $this->httpGet($location);
		$form = abi_extract_form_by_name($html,"memail");
		if ($form==null) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find login form');
		}
		
        $form->setField("MLS", "LN-*");
        $form->setField("FLN-UserName", $login);
        $form->setField("FLN-Password", $password);
        $form->setField("MSignal_LN-AU*", "Login");
        $form->setField("FLN-ScreenSize", "-1");
		$postData = $form->buildPostData();
		$html = $this->httpPost($location, $postData);
		if (strpos($html, 'class="ErrMsg"')!=false || strpos($html, 'you entered was incorrect')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

        if (preg_match($this->USTUDM_REGEX,$html,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Missing UST or UDM');
		}
		$ust = html_entity_decode($matches[1]);
		$udm = html_entity_decode($matches[2]);
		
		// Export address book
		$location = 'http://www.'.$domain.'/mail?'.$ust.'&'.$udm;
		$form = new HttpForm;
		$form->addField("_authtrkcde", "{#TRKCDE#}");
		$form->addField("MLS", "UA-*");
		$form->addField("SAD-AL-SF", "DN3_0");
		$form->addField("MSS", "!AD-*");
		$form->addField("SAD-AL-DR", "20");
		$form->addField("SAD-AL-TP", "0");
		$form->addField("SAD-AL-SpecialSortBy", "SNM:0");
		$form->addField("_charset_", "utf-8");
		$form->addField("FUA-UploadFile", "");
		$form->addField("FUA-Group", "0");
		$form->addField("FUA-DownloadFormat", "OL");
		$form->addField("MSignal_UA-Download*", "Download");
		$postData = $form->buildPostArray();
		$html = $this->httpPost($location, $postData);
		
        $res = $this->extractContactsFromCsv($html);
        $this->close();
		return $res;
	}
}


?>
