<?php
/********************************************************************************
GMX contacts importer

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.1
********************************************************************************/
include_once("abimporter.php");

/////////////////////////////////////////////////////////////////////////////////////////
//GmxImporter
/////////////////////////////////////////////////////////////////////////////////////////
class GmxImporter extends WebRequestor {
 
 	var $DETAIL_REGEX = "/CUSTOMERNO=(\\d+).*?&t=([^&]+)/ims";

	function logout () 
	{
		$this->httpGet("http://logout.gmx.net");
	}
	
	function fetchCsv ($loginemail, $password) {

		$form = new HttpForm;
		$form->addField("AREA", "1");
		$form->addField("EXT", "redirect");
		$form->addField("EXT2", "");
		$form->addField("id", $loginemail);
		$form->addField("p", $password);
		$postData = $form->buildPostData();
		$html = $this->httpPost("http://service.gmx.net/de/cgi/login", $postData);
		if (strpos($html, 'Diese Kennung wurde vom Systembetreiber gesperrt')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Account closed by system operator');
		}
		if (strpos($html, '<div class="error">')!=false) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

        if (preg_match($this->DETAIL_REGEX,$this->lastUrl,$matches)==0) {
		 	$this->close();
			return abi_set_error(_ABI_FAILED,'Cannot find CUSTOMERNO and t');
		}

		$customerNo = urldecode($matches[1]);
		$t = urldecode($matches[2]);
		$form = new HttpForm;
		$form->addField("CUSTOMERNO", $customerNo);
		$form->addField("t", $t);
		$form->addField("site", "importexport");
		$form->addField("language", "english");
		$form->addField("dataformat", "o2002");
		$form->addField("b_export", "Export starten");
		$postData = $form->buildPostArray();
		
		$html = $this->httpPost("http://service.gmx.net/de/cgi/addrbk.fcgi", $postData,'utf-8');

		//Convert character set to utf-8
		if (function_exists('mb_convert_encoding')) $html = mb_convert_encoding($html, "utf-8","ISO-8859-1");
		else if (function_exists('iconf')) $html = iconv('ISO-8859-1', 'utf-8', $html);
		//else, we can't perform the conversion. Return raw form.
		
		//ISO-8859-1

	 	$this->logout();
		return $html;
		/*		
		$res = $this->extractContactsFromCsv($html);
		

	 	$this->close();
	 	*/
		return $res;
	}	
	
	function fetchContacts ($loginemail, $password) {

		$html = $this->fetchCsv($loginemail,$password);
		if (!is_string($html)) {
			return $html;
		}
		$res = $this->extractContactsFromCsv($html);
		return $res;
	}
		
	function fetchContacts2 ($loginemail, $password) {
		$html = $this->fetchCsv($loginemail,$password);
		if (!is_string($html)) {
			return $html;
		}
		$ce = new CsvExtractor;
		return $ce->extract($html);
	}

}


?>
