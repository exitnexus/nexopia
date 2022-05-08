<?php
/********************************************************************************
Hotmail & MSN contacts importer (Using Windows Live Contacts API)

Copyright 2008 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
********************************************************************************/
//include_once(dirname(__FILE__).'/abimporter.php');
if (!defined('__ABI')) die('Please include abi.php to use this importer!');

/////////////////////////////////////////////////////////////////////////////////////////
//HotmailImporter2
/////////////////////////////////////////////////////////////////////////////////////////
class HotmailImporter2 extends WebRequestor {

	var $CONTENT_TYPE = "application/xml; charset=utf-8";
	
	var $LC_SECURITY_TOKEN_REGEX = "/<wsse:BinarySecurityToken[^>]*>(.*)<\/wsse:BinarySecurityToken>/ims";
	var $CONTACT_REGEX = "/<Contact[^>]*>(.*?)<\/Contact>/ims";
	var $FIRSTNAME_REGEX = "/<FirstName[^>]*>([^<]*)<\/FirstName>/ims";
	var $MIDDLENAME_REGEX = "/<MiddleName[^>]*>([^<]*)<\/MiddleName>/ims";
	var $LASTNAME_REGEX = "/<LastName[^>]*>([^<]*)<\/LastName>/ims";
	var $EMAIL_REGEX = "/<Email[^>]*>(.*?)<\/Email>/ims";
	var $EMAILADDRESS_REGEX = "/<Address[^>]*>(.*?)<\/Address>/ims";

	var $passportName;
	var $authHeader;
    var $cumulusHost = "https://cumulus.services.live.com";
    
    function getString ($xml, $regex, $xmldecode) {
		if (preg_match($regex,$xml,$matches)>0) {
		 	$s = $matches[1];
		 	return $xmldecode ? htmlentities2utf8($s) : $s;
		}
		else {
			return '';
		}
	}
	
	function fetchContacts ($loginemail, $password) {
        $this->passportName = strtolower($loginemail);
		$apiKey = "OZABI";
		$loginXml = "<s:Envelope xmlns:s=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:wsse=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd\" xmlns:saml=\"urn:oasis:names:tc:SAML:1.0:assertion\" xmlns:wsp=\"http://schemas.xmlsoap.org/ws/2004/09/policy\" xmlns:wsu=\"http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd\" xmlns:wsa=\"http://www.w3.org/2005/08/addressing\" xmlns:wssc=\"http://schemas.xmlsoap.org/ws/2005/02/sc\" xmlns:wst=\"http://schemas.xmlsoap.org/ws/2005/02/trust\">"
                    ."<s:Header>"
                    ."<wlid:ClientInfo xmlns:wlid=\"http://schemas.microsoft.com/wlid\"><wlid:ApplicationID>"
                    .$apiKey
                    ."</wlid:ApplicationID></wlid:ClientInfo>"
                    ."<wsa:Action s:mustUnderstand=\"1\">http://schemas.xmlsoap.org/ws/2005/02/trust/RST/Issue</wsa:Action>"
                    ."<wsa:To s:mustUnderstand=\"1\">https://dev.login.live.com/wstlogin.srf</wsa:To>"
                    ."<wsse:Security><wsse:UsernameToken wsu:Id=\"user\"><wsse:Username>"
                    .htmlentities($this->passportName,ENT_NOQUOTES,'UTF-8')
                    ."</wsse:Username><wsse:Password>"
                    .htmlentities($password,ENT_NOQUOTES,'UTF-8')
                    ."</wsse:Password></wsse:UsernameToken></wsse:Security>"
                    ."</s:Header>"
                    ."<s:Body>"
                    ."<wst:RequestSecurityToken Id=\"RST0\"><wst:RequestType>http://schemas.xmlsoap.org/ws/2005/02/trust/Issue</wst:RequestType><wsp:AppliesTo><wsa:EndpointReference><wsa:Address>http://live.com</wsa:Address></wsa:EndpointReference></wsp:AppliesTo><wsp:PolicyReference URI=\"MBI\"></wsp:PolicyReference></wst:RequestSecurityToken></s:Body></s:Envelope>";

		$extraHeaders = array();
		$extraHeaders[] = 'Content-Type: '.$this->CONTENT_TYPE;
		$extraHeaders[] = '_authtrkcde: {#TRKCDE#}';
		$html = $this->httpPost("https://dev.login.live.com/wstlogin.srf", $loginXml,'utf-8', $extraHeaders);
		if (strpos($html,'psf:error')>0) {
		 	$this->close();
			return abi_set_error(_ABI_AUTHENTICATION_FAILED,'Bad user name or password');
		}

		// Get authorization header
		if (preg_match($this->LC_SECURITY_TOKEN_REGEX,$html,$matches)>0) {
			$binaryToken = $matches[1];
			$binaryToken = htmlentities($binaryToken);
			$this->authHeader = 'Authorization: WLID1.0 t="'.$binaryToken.'"';
		}
		
		// Fetch contacts!
		$al = array();
		$extraHeaders = array();
		$extraHeaders[] = 'Content-Type: '.$this->CONTENT_TYPE;
		$extraHeaders[] = $this->authHeader;
		$url = $this->cumulusHost.'/'.urlencode($this->passportName).'/LiveContacts/Contacts';
 	 	$xml = $this->httpRequest($url, false, null, 'utf-8', $extraHeaders);
 	 	
		//A 404 means no contacts as well
		if ($this->lastStatusCode == 404) {
		 	$this->close();
		 	return $al;
		}

		if ($this->lastStatusCode >= 400) {
		 
		 	//Try classic importer instead
		 	$obj = new HotmailImporter;
		 	return $obj->fetchContacts($loginemail,$password);
		 
		 	//$this->close();
			//return abi_set_error(_ABI_FAILED,'Failed to fetch all contacts : '.$this->lastStatusCode);
		}

        preg_match_all($this->CONTACT_REGEX, $xml, $matches, PREG_SET_ORDER);
		foreach ($matches as $val) {
		 	$contactXml = $val[1];
		 	$fname = $this->getString($contactXml,$this->FIRSTNAME_REGEX, true);
		 	$mname = $this->getString($contactXml,$this->MIDDLENAME_REGEX, true);
		 	$lname = $this->getString($contactXml,$this->LASTNAME_REGEX, true);
			$name =  abi_reduceWhitespace($fname.' '.$mname.' '.$lname);
			
	        preg_match_all($this->EMAIL_REGEX, $contactXml, $matches2, PREG_SET_ORDER);
			foreach ($matches2 as $val2) {
				$emailXml = $val2[1];
				$email = $this->getString($emailXml, $this->EMAILADDRESS_REGEX, true);
				if (abi_valid_email($email)) {
				 	$name2 = $name;
					if (empty($name2)) $name2=$email;
					$contact = new Contact($name2, $email);
					$al[] = $contact;
				}
			}
		}
		return $al;
	}
}

?>
