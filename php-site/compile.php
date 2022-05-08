<?php
/*
 * NOTE : Run this file from public html 
 * complies all the template files into their php equivilance
 */
$forceserver = true;
require_once("include/general.lib.php");


$template = new template("index/index",false);
$template->write("index/index");
echo "Compiled index/index \n";

//$template = new template("admin/admin", false);
//$template->write("admin/admin");
//echo "Compiled admin/admin \n";

//$template = new template("articles/article/article",false);
//$template->write("articles/article/article");
//echo "Compiled articles/article/article \n";

//$template = new template("articles/articlelist/articlelist", false);
//$template->write("articles/articlelist/articlelist");
//echo "Compiled articles/articlelist/articlelist \n";

//$template = new template("usercomments/listcomments", false);
//$template->write("usercomments/listcomments");
//echo "Compiled usercomments/listcomments \n";

//$template = new template("usercomments/addusercomment", false);
//$template->write("usercomments/addusercomment");
//echo "Compiled usercomments/addusercomments \n";

//$template = new template("messages/listfolders", false);
//$template->write("messages/listfolders");
//echo "Compiled messages/listfolders \n";

//$template = new template("messages/writemsg", false);
//$template->write("messages/writemsg");
//echo "Compiled messages/writemsg \n";

//$template = new template("messages/viewmsg1", false);
//$template->write("messages/viewmsg1");
//echo "Compiled messages/viewmsg1 \n";

//$template = new template("messages/listmessages", false);
//$template->write("messages/listmessages");
//echo "Compiled messages/listmessages \n";

$template = new template("messages/ignorelist", false);
$template->write("messages/ignorelist");
echo "Compiled messages/ignorelist\n";

$template = new template("weblog/weblogmain", false);
$template->write("weblog/weblogmain");
echo "Compiled weblog/weblogmain\n";
?>
