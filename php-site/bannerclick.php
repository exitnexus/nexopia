<?

	$login=0;
	$forceserver = true;

	require_once("include/general.lib.php");

	$id = getREQval('id', 'int');

	if(!$id){
		header("location: /");
		exit();
	}

	$page = (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $_SERVER['PHP_SELF']);

	$page = trim($page);

//trim http://
	if(substr($page, 0, 7) == 'http://')
		$page = substr($page, 7);

//trim domain
	$pos = strpos($page, '/');
	if($pos === false) //no domain
		$pos = 0;
	$page = substr($page, $pos+1); //trim the domain and the trailing slash

//trim end
	$pos = strpos($page, '.');
	if($pos)
		$page = substr($page, 0, $pos); //trim everything after the first period

	$link = $banner->click($id, $page);

	header("Location: $link");
	exit;
