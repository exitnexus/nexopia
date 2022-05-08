<?

	include("include/mirrorconfig.inc.php");

	if(!isset($id) || !is_numeric($id))
		exit;

	if(!isset($imagemasters[$REMOTE_ADDR]))
		exit;

	if(file_exists($docRoot . $imgdir   . floor($id/1000) . "/" . $id . ".jpg"))
			unlink($docRoot . $imgdir   . floor($id/1000) . "/" . $id . ".jpg");
	if(file_exists($docRoot . $thumbdir . floor($id/1000) . "/" . $id . ".jpg"))
			unlink($docRoot . $thumbdir . floor($id/1000) . "/" . $id . ".jpg");


