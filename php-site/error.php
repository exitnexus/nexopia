<?

	if(isset($_SERVER['REQUEST_URI']))
		$url = substr($_SERVER['REQUEST_URI'],1);
	if(isset($_SERVER['REDIRECT_URL']))
		$url = substr($_SERVER['REDIRECT_URL'],1);

	if($url != "favicon.ico"){
//		include_once("include/general.lib.php");

		if(	strlen($url) <= 12 &&
			strpos($url,".php") === false &&
			strpos($url,".jpg") === false &&
			strpos($url,".html")=== false &&
			strpos($url,".gif") === false &&
			strpos($url,".png") === false &&
			strpos($url,".swf") === false &&
			strpos($url,".js")  === false &&
			strpos($url,".css") === false &&
			strpos($url,".ico") === false){

			header("location: /profile.php?uid=$url");
			exit;
		}

		if(substr($url,0,3) == "www"){
			header("location: http://$url");
			exit;
		}
	}
/*
if(0){ //enable mirroring

	$mirroring = array('picdir','thumbdir','bannerdir','basefiledir','gallerypicdir','gallerythumbdir','imagedir','smilydir');

	if(strlen($url) < 100 && strpos($url,"..") === false){
		$extension = strtolower(substr($url,strrpos($url,".")+1));

		if($extension == "php" || $extension == "php3")
			break;

		foreach($mirroring as $item){
			if(substr($REDIRECT_URL, 0, strlen($config[$item])) == $config[$item]){ //starts with one of the dirs

				if(file_exists($masterserver . $url)){
					$dirs = explode("/", dirname($url));

					umask(0);

					$basedir = $docRoot;
					foreach($dirs as $dir){
						if(!is_dir("$basedir/$dir"))
							@mkdir("$basedir/$dir",0777);
						$basedir .= "/$dir";
					}

		$masterimageserver = "beta.www.nexopia.com";

		$remotefile = "http://" . $masterimageserver . $url;
		$localfile = 				 		$docRoot . $url;

		$remote = fopen($remotefile,'r');
		if(!$remote)    die("error: Can't open remote image: $remotefile");

		$local = fopen($localfile,'w');
		if(!$local)     die("error: Can't open local image: $localfile");

		while($buf = fread($remote,4096))
			fwrite($local,$buf);

		fclose($remote);
		fclose($local);

					header("location: $url");
					exit;
				}
				break;
			}
		}
	}
}*/

header("404 Not Found");
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<HTML><HEAD>
<TITLE>404 Not Found</TITLE>
</HEAD><BODY>
<H1>Not Found</H1>
The requested URL $url was not found on this server.
</BODY></HTML>

