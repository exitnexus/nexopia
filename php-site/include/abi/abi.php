<?php
/********************************************************************************
Copyright 2008 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
Date: 8 Jul 2009
---------------------------------------------------------------------------------
Main include file that includes all other necessary PHP files for the 
contacts importer and invite sender libraries

Please change the necessary configuration settings in abiconfig.php
********************************************************************************/

define('__ABI',1);

//Return results of importer. Do not rely on the hardcoded values as it may change in the future.
define('_ABI_SUCCESS',0);				//Successful retrieval
define('_ABI_AUTHENTICATION_FAILED',1);	//Authentication failed (bad user name or password)
define('_ABI_FAILED',2);				//Connection to server failed
define('_ABI_UNSUPPORTED',3);			//Unsupported mail provider
define('_ABI_CAPTCHA_RAISED',4);		//Captcha challenge was raised
define('_ABI_USER_INPUT_REQUIRED',5);	//A general user input is required. Cannot proceed
define('_ABI_BLOCKED',6);				//Message/Action was blocked by server (due to some censorship, etc)



function abi_include ($file) {
 	$path = dirname(__FILE__).'/'.$file;
 	if (file_exists($path)) include($path);
}
function abi_include_either ($file1,$file2) {
 	$path1 = dirname(__FILE__).'/'.$file1;
 	$path2 = dirname(__FILE__).'/'.$file2;
 	if (file_exists($path1)) include($path1);
 	else if (file_exists($path2)) include($path2);
	
}

//Include config file (required))
include(dirname(__FILE__)."/abiconfig.php");

//Include common codes
abi_include("oz_abi_domains.php");
abi_include("oz_ldif.php");
abi_include("oz_vcard.php");
include(dirname(__FILE__)."/abimporter.php");
abi_include("oz_csv.php");
abi_include("oz_json.php");

//Experimental
abi_include("oz_abcontact.php");

//Contacts Importer Bundle Great Essentials & Bundle 1
abi_include("hotmail.php");
abi_include("hotmail2.php");
abi_include("gmail.php");
abi_include("gmail2.php");
abi_include_either("yahoo2.php","yahoo.php");
abi_include("yahoojp.php");
abi_include_either("aol2.php","aol.php");
abi_include_either("lycos2.php","lycos.php");
abi_include("maildotcom.php");
abi_include("rediff.php");
abi_include("indiatimes.php");
abi_include("icq.php");

//Contacts Importer Bundle 1
abi_include("macmail.php");
abi_include("fastmail.php");
abi_include("gmx.php");
abi_include("webde.php");
abi_include("linkedin.php");
abi_include("mynet.php");

//Contacts Importer Bundle 2
abi_include("mailru.php");
abi_include_either("freenetde2.php","freenetde.php");
abi_include("libero.php");
abi_include("interia.php");
abi_include("rambler.php");
abi_include("yandex.php");
abi_include("onet.php");
abi_include("wppl.php");
abi_include("sapo.php");
abi_include("o2.php");
abi_include("tonline.php");

//Contacts Importer Bundle 3
abi_include("terra.php");
abi_include("emailit.php");
abi_include("orangees.php");
abi_include("aliceit.php");
abi_include("plaxo.php");

//Invite Sender Bundle 1
abi_include("is_friendster.php");
//Facebook importer has been removed on request by Facebook
//abi_include("is_facebook.php");
abi_include("is_orkut.php");
abi_include("is_myspace.php");
abi_include("is_hi5.php");

//Invite Sender Bundle 2
abi_include("is_bebo.php");
abi_include("is_blackplanet.php");
abi_include("is_xing.php");



?>
