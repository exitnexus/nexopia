<?

	$login=0;

	require_once("include/general.lib.php");

	$id = getREQval('id', 'int');

	if(!$id){
		header("location: /");
		exit();
	}

	$page = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');

//trim http://
	if(substr($page, 0, 7) == 'http://')
		$page = substr($page, 7);

//trim domain and end
	$pos = strpos($page, '/');
	$page = substr($page, $pos+1, strpos($page, '.', $pos)-$pos-1);

	$link = $banner->click($id, $page);

	header("location: $link");
	exit;
