<?php
/********************************************************************************
Contacts Importer / Invite Sender Configuration File

Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
V: 1.0
********************************************************************************/

define('_ABI_CONFIG_FILE','');

//Enable/disable debug mode (1=enable, 0=disable). 
//	During debug mode, http traffic is dumped to the browser as well.
define('_ABI_DEBUG',0);

//Enable/Disable gzip compression in cURL. (1=enable, 0=disable))
//	If cURL is not compiled with zlib, this will fail. In this case, set the value to 0 to disable gzip.
define('_ABI_GZIP',1);

//Enable (true) HTTP 1.1 features where supported, Disable (false) otherwise
define('_ABI_HTTP1_1',false);

//GoDaddy users, please enable this line to make use of their proxy
//define('_ABI_PROXY', "http://proxy.shr.secureserver.net:3128");


//Others
//define('_ABI_PROXYPORT', 3128);
//define('_ABI_PROXYTYPE', CURLPROXY_SOCKS5);
//define('_ABI_PROXY', "193.196.39.9");

//define('_ABI_ABCAPTCHA',1);	//1=Enable the new return code _ABI_CAPTCHA_RAISED for contacts importer (otherwise, existing code returns _ABI_FAILED)

//--------------------------------------------------
// CAPTCHA 
//--------------------------------------------------

define('_ABI_CAPTCHA',true);	//1=enable captcha, 0=treat captcha as error

//
//As a safety precaution, we disable housekeeping of captcha cache folder by default.
//To turn on housekeeping of captcha cache, set this value to true. Housekeeping involves
//deleting all files in the captcha folder.
//
define('_ABI_HOUSEKEEP_CACHE',false);	//1=enable cache to be cleared by file deletion

//
//Uncomment and change these lines to define your captcha path and  uri (without terminating 
//slash) if you wish to change from the default path. If undefined, defaults to the your 
//*.php/captcha folder.
//
//define('_ABI_CAPTCHA_FILE_PATH','/var/www/html/captcha');
//define('_ABI_CAPTCHA_URI_PATH','/captcha');


?>