<?

	$filename = $_SERVER['REQUEST_URI']; //check this
	$imgroot = "/home/imageserver";
	$masterroot = "/home/nexopia/public_html";

	$classes = array(
					"users"        => '/^\/+(users)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
					"userthumb"    => '/^\/+(users\/thumbs)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
					"gallery"      => '/^\/+(gallery)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
					"gallerythumb" => '/^\/+(gallery\/thumbs)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
					"galleryfull"  => '/^\/+(gallery\/full)\/([0-9]{1,6})\/([\.a-zA-Z0-9]{0,13})$/',
					"banner"       => '/^\/+(banners)\/([\.\-_ a-zA-Z0-9]{0,48})$/',
					"uploads"      => '/^\/+(uploads)\/([0-9]{1,5})\/([0-9]{1,8})\/([\.\-_ a-zA-Z0-9]{0,32})$/',
					"smilies"      => '/^\/+(images\/smilies)\/([\.a-zA-Z0-9]{0,20})$/',
					"images"       => '/^\/+(images)\/([\._a-zA-Z0-9]{0,20})$/',
					"skin"         => '/^\/+(skins)\/([a-zA-Z0-9]{1,16})\/([\._a-zA-Z0-9]{0,20})$/',
					"skinjs"       => '/^\/+(skins)\/([\.a-zA-Z0-9]{0,16})$/',
					);


	$fileclass = false;
	foreach($classes as $class => $dir){
		if(preg_match($dir, $filename)){
			$fileclass = $class;
			break;
		}
	}

//invalid file
//	if(!$fileclass)
//	 	failure();

//already exists?
	if(file_exists($imgroot . $filename))
		success($imgroot . $filename);

//exists on the master?
	if(file_exists($masterroot . $filename)){
		if (!file_exists(dirname($imgroot . $filename))) {
			@mkdir($imgroot . dirname($filename), 0777, true);
		}

	 	if(copy($masterroot . $filename, $imgroot . $filename))
			success($imgroot . $filename);
	}

//doesn't exist, or can't copy
	failure();

/////////////////////

function success($file){
	header("Content-Type: image/jpeg");
#	header("X-LIGHTTPD-send-file: $file");
	readfile($file);
	exit;
}

function failure(){
	header("Status: 404");
	echo "The file cannot be found";
	exit;
}
