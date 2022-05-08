<?
$simplepage = true;
$forceserver = true;
require_once("include/general.lib.php");
$template = new template('pictures/imgframe');
if (isset($_GET['imgurl']) && isset($_GET['picid']))
{
	$template->set('pictureData', true);
	$template->set('imgurl', htmlentities(getREQval('imgurl')));
	$template->set('picid', getREQval('picid', 'int'));
	// Todo: validate?
	$template->set('fullurl', (isset($_GET['fullurl'])? htmlentities($_GET['fullurl']) : ''));

}
$template->display();

