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
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//FastMailImporter
/////////////////////////////////////////////////////////////////////////////////////////
class FastMailImporter extends WebRequestor {

 	var $UST_REGEX = "/(Ust=[^;]*)/ims";
 	var $UDM_REGEX = "/(UDm=[^;]*)/ims";

	function fetchContacts ($loginemail, $password) {

		$emailparts = $this->getEmailParts ($loginemail);
		$login = $emailparts[0];
		$domain = $emailparts[1];
		$location = 'http://www.'.$domain.'/mail';
		$form = new HttpForm;
		$form->addField("MLS", "LN-*");
		$form->addField("FLN-UserName", $login);
		$form->addField("FLN-Password", $password);
		$form->addField("MSignal_LN-Authenticate*", "Login");
		$form->addField("FLN-ScreenSize", "-1");
		$postData = $form->buildPostData();
		$html = $this->httpPost($location, $postData);
		if (strpos($html, 'The user name or password you entered was incorrect')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

        if (preg_match($this->UST_REGEX,$this->lastUrl,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Missing UST');
		}
		$ust = $matches[1];
        if (preg_match($this->UDM_REGEX,$this->lastUrl,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Missing UDM');
		}
		$udm = $matches[1];
		
		// Export address book
		$location = 'http://www.'.$domain.'/mail?'.$ust.'&'.$udm;
		$form = new HttpForm;
		$form->addField("MSignal_UA-Download*", "Download");
		$form->addField("FUA-DownloadFormat", "OL");
		$form->addField("_charset_", "utf-8"); // ??
		$form->addField("MLS", "UA-*");
		$form->addField("MSS", "!AD-*");
		$form->addField("SAD-MADC-TP", "0");
		$form->addField("SAD-MADC-DI", "20");
		$form->addField("SAD-MADC-SF", "DN3_0");
		$form->addField("SAD-MADC-SpecialSortBy", "SNM:0");
		$postData = $form->buildPostData();
		$html = $this->httpPost($location, $postData);
		
        $res = $this->extractContactsFromCsv($html);
        $this->close();
		return $res;
	}
}


?>
