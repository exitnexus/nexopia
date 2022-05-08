<?php
/********************************************************************************
Copyright 2007 Octazen Solutions
All Rights Reserved

You may not reprint or redistribute this code without permission from Octazen Solutions.

WWW: http://www.octazen.com
Email: support@octazen.com
Version: 1.0.0
Date: 21 Sep 2007
---------------------------------------------------------------------------------
Imports all necessary PHP include files
********************************************************************************/
function abi_include ($file) {
 	if (file_exists("abimporter/$file")) include_once("abimporter/$file");
 	else if (file_exists("./$file")) include_once("./$file");
}
function abi_include_either ($file1,$file2) {
 	if (file_exists("abimporter/$file1")) include_once("abimporter/$file1");
 	else if (file_exists("./$file1")) include_once("./$file1");
 	else if (file_exists("abimporter/$file2")) include_once("abimporter/$file2");
 	else if (file_exists("./$file2")) include_once("./$file2");
}
abi_include("gmail.php");
abi_include("hotmail.php");
abi_include_either("yahoo2.php","yahoo.php");
abi_include("yahoojp.php");
abi_include("lycos.php");
abi_include("aol.php");
abi_include("rediff.php");
abi_include("indiatimes.php");
abi_include("macmail.php");
abi_include("maildotcom.php");
abi_include("fastmail.php");
abi_include("gmx.php");
abi_include("linkedin.php");
abi_include("icq.php");
abi_include("webde.php");
abi_include("mynet.php");
abi_include("mailru.php");
abi_include("freenetde.php");
abi_include("libero.php");
abi_include("interia.php");
abi_include("rambler.php");
abi_include("yandex.php");
abi_include("onet.php");
abi_include("wppl.php");
abi_include("sapo.php");
abi_include("o2.php");
abi_include("tonline.php");
abi_include("oz_ldif.php");

?>