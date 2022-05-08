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

define('_ABI_DEBUG',0);	//1=enable debug, 0=no debug
define('_ABI_GZIP',1);	//1=enable gzip, 0=no gzip support
//GoDaddy users, please enable this line
//define('_ABI_PROXY', "http://proxy.shr.secureserver.net:3128");

//Others?
//define('_ABI_PROXYPORT', 3128);
//define('_ABI_PROXYTYPE', CURLPROXY_SOCKS5);
//define('_ABI_PROXY', "193.196.39.9");


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